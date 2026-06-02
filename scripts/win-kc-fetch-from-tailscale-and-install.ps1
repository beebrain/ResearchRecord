# รันบน win-kc (Administrator PowerShell) — รับไฟล์จาก Tailscale แล้วติดตั้ง RR
# จาก Mac ส่งก่อน: tailscale file cp deploy/win-kc-rr.tgz win-kc49a7sh1gd:
#
# One-liner (ครั้งแรก — ยังไม่มีโฟลเดอร์ RR):
#   cd $env:USERPROFILE; tailscale file get; New-Item -ItemType Directory -Force C:\inetpub\ResearchRecord | Out-Null; tar -xzf "$env:USERPROFILE\Downloads\win-kc-rr.tgz" -C C:\inetpub\ResearchRecord; powershell -ExecutionPolicy Bypass -File C:\inetpub\ResearchRecord\scripts\win-kc-on-server-install.ps1
#
param(
    [string]$ProjectRoot = "C:\inetpub\ResearchRecord",
    [string]$IisAppName = "Research",
    [string]$ArchiveName = "win-kc-rr.tgz"
)

$ErrorActionPreference = "Stop"

function Find-TailscaleArchive {
    param([string]$Name)
    $candidates = @(
        (Join-Path $env:USERPROFILE "Downloads\$Name"),
        (Join-Path $env:USERPROFILE "Downloads\Files\$Name"),
        "C:\Users\Administrator\Downloads\$Name",
        "C:\Users\Administrator\Downloads\Files\$Name"
    )
    foreach ($p in $candidates) {
        if (Test-Path $p) { return $p }
    }
    return $null
}

Write-Host "=== Tailscale file get ==="
if (Get-Command tailscale -ErrorAction SilentlyContinue) {
    Push-Location $env:USERPROFILE
    try {
        tailscale file get 2>&1 | Out-Host
    } finally {
        Pop-Location
    }
} else {
    Write-Host "tailscale CLI not found — skip file get (place $ArchiveName in Downloads manually)"
}

$archive = Find-TailscaleArchive -Name $ArchiveName
if (-not $archive) {
    throw "Archive not found: $ArchiveName (run: tailscale file get)"
}
Write-Host "Using archive: $archive"

New-Item -ItemType Directory -Force -Path $ProjectRoot | Out-Null
Write-Host "Extracting to $ProjectRoot ..."
tar -xzf $archive -C $ProjectRoot

$install = Join-Path $ProjectRoot "scripts\win-kc-on-server-install.ps1"
if (-not (Test-Path $install)) {
    throw "Missing $install after extract"
}

& powershell -ExecutionPolicy Bypass -File $install -ProjectRoot $ProjectRoot -IisAppName $IisAppName

Write-Host ""
Write-Host "Done. Open: https://sci.uru.ac.th/$IisAppName/index.php/auth/login"
