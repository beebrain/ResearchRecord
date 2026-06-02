$envFile = 'C:\inetpub\newscience\.env'
$rrEnv = 'C:\inetpub\ResearchRecord\.env'
$base = 'https://sci.uru.ac.th/recordresearch'

foreach ($f in @($envFile, $rrEnv)) {
    if (-not (Test-Path $f)) { continue }
    $content = Get-Content $f -Raw
    $content = $content -replace "app\.baseURL\s*=\s*'[^']*'", "app.baseURL = '$base/'"
    $content = $content -replace 'researchrecordsso\.baseUrl\s*=\s*.*', "researchrecordsso.baseUrl = `"$base`""
    $content = $content -replace 'researchrecordsso\.logoutUrl\s*=\s*.*', "researchrecordsso.logoutUrl = `"$base/index.php/logout`""
    $content = $content -replace 'RESEARCH_API_BASE_URL\s*=\s*.*', "RESEARCH_API_BASE_URL = `"$base/index.php`""
    Set-Content -Path $f -Value $content -NoNewline
    Write-Host "Updated $f"
}

Import-Module WebAdministration
$site = 'sci'
$pub = 'C:\inetpub\ResearchRecord\public'
if (-not (Get-WebApplication -Site $site -Name 'recordresearch' -ErrorAction SilentlyContinue)) {
    New-WebApplication -Site $site -Name 'recordresearch' -PhysicalPath $pub -ApplicationPool 'sci'
}
Get-WebApplication -Site $site | Format-Table path, physicalPath

Set-Location C:\inetpub\ResearchRecord
php spark cache:clear 2>$null
Set-Location C:\inetpub\newscience
php spark cache:clear 2>$null

Write-Host "Test: $base/index.php/auth/login"
