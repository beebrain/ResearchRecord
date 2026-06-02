# แก้ .env newScience — แยกบรรทัด researchrecordsso.sharedSecret
$envFile = 'C:\inetpub\newscience\.env'
$content = Get-Content $envFile -Raw
$content = $content -replace 'RESEARCH_CV_AUTO_PULL_MAX_AGE_DAYS = 30researchrecordsso\.sharedSecret[^\r\n]*', "RESEARCH_CV_AUTO_PULL_MAX_AGE_DAYS = 30`r`nresearchrecordsso.sharedSecret = `"pisit_secret`""
if ($content -notmatch '(?m)^researchrecordsso\.sharedSecret\s*=') {
    $content = $content.TrimEnd() + "`r`nresearchrecordsso.sharedSecret = `"pisit_secret`"`r`n"
}
Set-Content -Path $envFile -Value $content -NoNewline
Select-String -Path $envFile -Pattern 'RESEARCH_CV_AUTO_PULL|researchrecordsso.sharedSecret'
