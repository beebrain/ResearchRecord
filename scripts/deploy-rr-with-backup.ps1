<#
.SYNOPSIS
    Deploy Research Record files through FTP with local and remote backups.
.DESCRIPTION
    This script downloads the current remote file, saves it under deploy-backups,
    uploads that copy back to the server as <file>.bak.<timestamp>, then uploads
    the new file and verifies SHA-256 by downloading it again.
#>

param(
    [string]$FtpHost = "202.29.52.124",
    [int]$FtpPort = 21,
    [string]$FtpUser = "rac",
    [string]$FtpPass = "",
    [string]$RemoteRoot = "research_academic",
    [string[]]$Files = @("app/Controllers/CvSyncApiController.php"),
    [string]$Restore = "",
    [string]$BackupSuffix = "",
    [string]$LocalBackupRoot = "deploy-backups/RR"
)

$ErrorActionPreference = "Stop"
$RepoRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$EnvFile = Join-Path $PSScriptRoot "ftp_rr.env"
if (Test-Path $EnvFile) {
    Get-Content $EnvFile | ForEach-Object {
        if ($_ -match '^\s*([^#][^=]+)=(.*)$') {
            $key = $matches[1].Trim()
            $val = $matches[2].Trim()
            switch ($key) {
                'FTP_HOST' { $FtpHost = $val }
                'FTP_PORT' { $FtpPort = [int]$val }
                'FTP_USER' { $FtpUser = $val }
                'FTP_PASS' { $FtpPass = $val }
                'FTP_REMOTE_ROOT' { $RemoteRoot = $val }
                'LOCAL_BACKUP_ROOT' { $LocalBackupRoot = $val }
            }
        }
    }
}

if (-not $FtpPass) {
    $sec = Read-Host "FTP password for $FtpUser@$FtpHost" -AsSecureString
    $bstr = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec)
    $FtpPass = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr)
    [System.Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
}

if (-not $FtpPass) {
    throw "FTP password is required."
}

$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$BaseUri = "ftp://${FtpHost}:$FtpPort/$($RemoteRoot.Trim('/'))"
$Credential = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)

function New-FtpRequest {
    param([string]$Uri, [string]$Method)
    $req = [System.Net.FtpWebRequest]::Create($Uri)
    $req.Credentials = $Credential
    $req.Method = $Method
    $req.UseBinary = $true
    $req.UsePassive = $true
    $req.KeepAlive = $false
    return $req
}

function Get-RemoteUri {
    param([string]$RelativePath)
    return "$BaseUri/$($RelativePath.TrimStart('/'))"
}

function Download-RemoteFile {
    param([string]$RelativePath, [string]$Destination)
    $dir = Split-Path $Destination -Parent
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
    $req = New-FtpRequest -Uri (Get-RemoteUri $RelativePath) -Method ([System.Net.WebRequestMethods+Ftp]::DownloadFile)
    $resp = $req.GetResponse()
    try {
        $stream = $resp.GetResponseStream()
        $fileStream = [System.IO.File]::Create($Destination)
        try {
            $stream.CopyTo($fileStream)
        } finally {
            $fileStream.Close()
            $stream.Close()
        }
    } finally {
        $resp.Close()
    }
}

function Upload-File {
    param([string]$Source, [string]$RelativePath)
    $req = New-FtpRequest -Uri (Get-RemoteUri $RelativePath) -Method ([System.Net.WebRequestMethods+Ftp]::UploadFile)
    $req.ContentLength = (Get-Item $Source).Length
    $stream = $req.GetRequestStream()
    $fileStream = [System.IO.File]::OpenRead($Source)
    try {
        $fileStream.CopyTo($stream)
    } finally {
        $fileStream.Close()
        $stream.Close()
    }
    $resp = $req.GetResponse()
    $resp.Close()
}

function Get-Sha256 {
    param([string]$Path)
    return (Get-FileHash -Algorithm SHA256 -Path $Path).Hash.ToLowerInvariant()
}

function Verify-Upload {
    param([string]$LocalFile, [string]$RelativePath)
    $tmp = Join-Path ([System.IO.Path]::GetTempPath()) ("rr-verify-" + [System.Guid]::NewGuid().ToString("N"))
    Download-RemoteFile -RelativePath $RelativePath -Destination $tmp
    try {
        $localHash = Get-Sha256 $LocalFile
        $remoteHash = Get-Sha256 $tmp
        if ($localHash -ne $remoteHash) {
            throw "SHA-256 mismatch for $RelativePath. local=$localHash remote=$remoteHash"
        }
        Write-Host "  [OK] SHA-256 $remoteHash" -ForegroundColor Green
    } finally {
        Remove-Item $tmp -Force -ErrorAction SilentlyContinue
    }
}

function Deploy-One {
    param([string]$RelativePath)
    $rel = $RelativePath.TrimStart('/')
    $local = Join-Path $RepoRoot $rel
    if (-not (Test-Path $local)) {
        throw "Local file not found: $local"
    }
    $backup = Join-Path $RepoRoot (Join-Path $LocalBackupRoot (Join-Path $Timestamp $rel))
    $remoteBackup = "$rel.bak.$Timestamp"

    Write-Host "Deploy $rel" -ForegroundColor Cyan
    Write-Host "  Downloading current remote file..."
    Download-RemoteFile -RelativePath $rel -Destination $backup
    Write-Host "  [OK] Local backup: $backup" -ForegroundColor Green

    Write-Host "  Creating remote backup: $remoteBackup"
    Upload-File -Source $backup -RelativePath $remoteBackup
    Verify-Upload -LocalFile $backup -RelativePath $remoteBackup

    Write-Host "  Uploading new file..."
    Upload-File -Source $local -RelativePath $rel
    Verify-Upload -LocalFile $local -RelativePath $rel
}

function Restore-One {
    param([string]$RelativePath)
    if (-not $BackupSuffix) {
        throw "Restore requires -BackupSuffix .bak.YYYYMMDD_HHMMSS to avoid choosing the wrong backup."
    }
    $rel = $RelativePath.TrimStart('/')
    $backupRel = "$rel$BackupSuffix"
    $tmp = Join-Path ([System.IO.Path]::GetTempPath()) ("rr-restore-" + [System.Guid]::NewGuid().ToString("N"))

    Write-Host "Restore $rel from $backupRel" -ForegroundColor Cyan
    Download-RemoteFile -RelativePath $backupRel -Destination $tmp
    try {
        Upload-File -Source $tmp -RelativePath $rel
        Verify-Upload -LocalFile $tmp -RelativePath $rel
    } finally {
        Remove-Item $tmp -Force -ErrorAction SilentlyContinue
    }
}

if ($Restore) {
    Restore-One -RelativePath $Restore
} else {
    foreach ($file in $Files) {
        Deploy-One -RelativePath $file
    }
}

Write-Host "Done." -ForegroundColor Green
