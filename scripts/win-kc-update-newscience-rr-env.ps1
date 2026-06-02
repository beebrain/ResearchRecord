$envFile = 'C:\inetpub\newscience\.env'
$content = Get-Content $envFile -Raw
$content = $content -replace 'researchrecordsso\.baseUrl\s*=\s*.*', 'researchrecordsso.baseUrl = "https://sci.uru.ac.th/recordresearch"'
if ($content -notmatch 'researchrecordsso\.sharedSecret') {
    $content = $content.TrimEnd() + "`nresearchrecordsso.sharedSecret = `"pisit_secret`"`n"
}
$content = $content -replace 'researchrecordsso\.sharedSecret\s*=\s*.*', 'researchrecordsso.sharedSecret = "pisit_secret"'
$content = $content -replace 'researchrecordsso\.logoutUrl\s*=\s*.*', 'researchrecordsso.logoutUrl = "https://sci.uru.ac.th/recordresearch/index.php/logout"'
$content = $content -replace 'RESEARCH_API_BASE_URL\s*=\s*.*', 'RESEARCH_API_BASE_URL = "https://sci.uru.ac.th/recordresearch/index.php"'
Set-Content -Path $envFile -Value $content -NoNewline
Select-String -Path $envFile -Pattern 'researchrecordsso|RESEARCH_API'
