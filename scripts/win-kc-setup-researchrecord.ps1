# ครั้งแรกบน win-kc — ตั้ง C:\inetpub\ResearchRecord + IIS Application
# รัน PowerShell as Administrator บน server:
#   Set-ExecutionPolicy Bypass -Scope Process -Force
#   .\win-kc-setup-researchrecord.ps1
#
param(
    [string]$RepoUrl = "https://github.com/beebrain/ResearchRecord.git",
    [string]$Branch = "master",
    [string]$SiteName = "sci.uru.ac.th",
    [string]$AppAlias = "ResearchRecord",
    [string]$ProjectRoot = "C:\inetpub\ResearchRecord",
    [string]$AppPool = "DefaultAppPool"
)

$ErrorActionPreference = "Stop"
$PublicRoot = Join-Path $ProjectRoot "public"

Write-Host "=== Research Record setup ===" -ForegroundColor Cyan
Write-Host "Project: $ProjectRoot"
Write-Host "IIS app: /$AppAlias -> $PublicRoot"

if (-not (Test-Path $ProjectRoot)) {
    Write-Host "Cloning $RepoUrl ..."
    git clone --branch $Branch $RepoUrl $ProjectRoot
} else {
    Write-Host "Project folder exists — skip clone"
}

if (-not (Test-Path $PublicRoot\index.php)) {
    throw "Missing $PublicRoot\index.php — check clone path"
}

Set-Location $ProjectRoot

if (-not (Test-Path ".env")) {
    if (Test-Path ".env.production.example") {
        Copy-Item ".env.production.example" ".env"
        Write-Host "Created .env from .env.production.example — EDIT secrets before go-live" -ForegroundColor Yellow
    } else {
        Write-Host "WARNING: no .env — create manually" -ForegroundColor Yellow
    }
}

if (Get-Command composer -ErrorAction SilentlyContinue) {
    composer install --no-dev --no-interaction
} else {
    Write-Host "composer not in PATH — run manually" -ForegroundColor Yellow
}

Import-Module WebAdministration -ErrorAction Stop

$existing = Get-WebApplication -Site $SiteName -Name $AppAlias -ErrorAction SilentlyContinue
if ($existing) {
    Write-Host "IIS Application '$AppAlias' already exists -> $($existing.physicalPath)"
} else {
    Write-Host "Creating IIS Application $AppAlias ..."
    New-WebApplication -Site $SiteName -Name $AppAlias -PhysicalPath $PublicRoot -ApplicationPool $AppPool
}

$writable = Join-Path $ProjectRoot "writable"
if (Test-Path $writable) {
    icacls $writable /grant "IIS AppPool\${AppPool}:(OI)(CI)M" /T | Out-Null
    Write-Host "Granted write on writable/ for AppPool $AppPool"
}

# Stub ใต้ newscience public (ถ้ามี) — เปลี่ยนชื่อ ไม่ลบ config
$stub = "C:\inetpub\newscience\public\ResearchRecord"
if (Test-Path $stub) {
    $bak = "${stub}.bak.$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    Rename-Item $stub $bak
    Write-Host "Renamed stub folder -> $bak" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Next steps:" -ForegroundColor Green
Write-Host "  1. Edit $ProjectRoot\.env (DB, encryption.key, newscience_sso.sharedSecret)"
Write-Host "  2. php spark key:generate   (if encryption.key empty)"
Write-Host "  3. php spark migrate --all"
Write-Host "  4. curl -I https://sci.uru.ac.th/ResearchRecord/index.php/auth/login"
Write-Host "=== done ===" -ForegroundColor Cyan
