# รันบน win-kc (Administrator PowerShell) หลังได้ไฟล์ deploy
#   cd C:\inetpub
#   tar -xzf C:\Users\Administrator\Downloads\win-kc-rr.tgz -C ResearchRecord
#   powershell -ExecutionPolicy Bypass -File C:\inetpub\ResearchRecord\scripts\win-kc-on-server-install.ps1
param(
    [string]$ProjectRoot = "C:\inetpub\ResearchRecord",
    [string]$IisAppName = "recordresearch"
)

$ErrorActionPreference = "Stop"
$PublicRoot = Join-Path $ProjectRoot "public"

Import-Module WebAdministration

# หา site ที่ชี้ newscience\public
$site = Get-Website | Where-Object { $_.physicalPath -match 'newscience\\public' } | Select-Object -First 1
if (-not $site) {
    $site = Get-Website | Where-Object { $_.Name -match 'sci' } | Select-Object -First 1
}
if (-not $site) { throw "Cannot find IIS site for sci.uru.ac.th" }
$siteName = $site.Name
Write-Host "IIS site: $siteName ($($site.physicalPath))"

if (-not (Test-Path "$PublicRoot\index.php")) { throw "Missing $PublicRoot\index.php" }

if (-not (Test-Path "$ProjectRoot\.env")) {
    if (Test-Path "$ProjectRoot\.env.win-kc") { Copy-Item "$ProjectRoot\.env.win-kc" "$ProjectRoot\.env" }
    elseif (Test-Path "$ProjectRoot\.env.production.example") { Copy-Item "$ProjectRoot\.env.production.example" "$ProjectRoot\.env" }
}

foreach ($sub in @('cache','logs','session','uploads','debugbar')) {
    $dir = Join-Path "$ProjectRoot\writable" $sub
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
    Copy-Item "$ProjectRoot\writable\index.html" "$dir\index.html" -Force -ErrorAction SilentlyContinue
}
icacls "$ProjectRoot\writable" /grant "IIS AppPool\$($site.applicationPool):(OI)(CI)M" /T | Out-Null

$app = Get-WebApplication -Site $siteName -Name $IisAppName -ErrorAction SilentlyContinue
if (-not $app) {
    New-WebApplication -Site $siteName -Name $IisAppName -PhysicalPath $PublicRoot -ApplicationPool $site.applicationPool
    Write-Host "Created IIS app /$IisAppName -> $PublicRoot"
} else {
    Write-Host "IIS app /$IisAppName exists -> $($app.physicalPath)"
}

$stub = Join-Path $site.physicalPath $IisAppName
if ((Test-Path $stub) -and ($stub -ne $PublicRoot)) {
    Rename-Item $stub "$stub.bak.$(Get-Date -Format 'yyyyMMdd_HHmmss')"
}

Set-Location $ProjectRoot
if (Get-Command composer -ErrorAction SilentlyContinue) { composer install --no-dev --no-interaction }
php spark cache:clear 2>$null
php spark migrate --all 2>$null

Write-Host "Test: https://sci.uru.ac.th/$IisAppName/index.php/auth/login"
