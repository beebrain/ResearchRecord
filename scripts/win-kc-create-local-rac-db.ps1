# สร้างฐาน rac ใหม่บน MySQL localhost (win-kc) — schema ว่าง ไม่ copy ข้อมูลจาก 202.29.52.124
# Usage (Administrator PowerShell):
#   powershell -ExecutionPolicy Bypass -File C:\inetpub\ResearchRecord\scripts\win-kc-create-local-rac-db.ps1
param(
    [string]$DbName = 'rac',
    [string]$DbUser = 'rac',
    [string]$DbPass = 'rac@URU@2026',
    [string]$RootPass = 'admin@SCI@2026',
    [string]$RemoteHost = '202.29.52.124',
    [string]$RemoteUser = 'rac',
    [string]$RemotePass = 'rac@URU@2025',
    [string]$RrRoot = 'C:\inetpub\ResearchRecord'
)

$ErrorActionPreference = 'Stop'
$mysql = 'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe'
$mysqldump = 'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe'
$schemaFile = Join-Path $RrRoot 'deploy\rac_local_schema.sql'
$migrationsFile = Join-Path $RrRoot 'deploy\rac_local_migrations.sql'

New-Item -ItemType Directory -Force -Path (Split-Path $schemaFile) | Out-Null

function Invoke-MySql {
    param([string]$Sql, [string]$Database = '')
    $args = @("-u", "root", "-p$RootPass", "--batch", "--skip-column-names")
    if ($Database -ne '') { $args += $Database }
    $args += @("-e", $Sql)
    $prev = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    & $mysql @args 2>$null | Out-Null
    $ErrorActionPreference = $prev
    if ($LASTEXITCODE -ne 0) { throw "mysql failed: $Sql" }
}

Write-Host "=== 1. Create database and user ==="
Invoke-MySql "DROP DATABASE IF EXISTS ``$DbName``;"
Invoke-MySql "CREATE DATABASE ``$DbName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
Invoke-MySql "CREATE USER IF NOT EXISTS '$DbUser'@'localhost' IDENTIFIED BY '$DbPass';"
Invoke-MySql "CREATE USER IF NOT EXISTS '$DbUser'@'127.0.0.1' IDENTIFIED BY '$DbPass';"
Invoke-MySql "GRANT ALL PRIVILEGES ON ``$DbName``.* TO '$DbUser'@'localhost';"
Invoke-MySql "GRANT ALL PRIVILEGES ON ``$DbName``.* TO '$DbUser'@'127.0.0.1';"
Invoke-MySql "FLUSH PRIVILEGES;"

Write-Host "=== 2. Dump schema (no data) from remote $RemoteHost ==="
& $mysqldump -h $RemoteHost -u $RemoteUser "-p$RemotePass" `
    --no-data --no-tablespaces --routines --triggers --single-transaction `
    --set-gtid-purged=OFF $DbName | Set-Content -Path $schemaFile -Encoding utf8

Write-Host "=== 3. Import schema to localhost ==="
Get-Content $schemaFile | & $mysql -u root "-p$RootPass" $DbName

Write-Host "=== 4. Copy migration history ==="
& $mysqldump -h $RemoteHost -u $RemoteUser "-p$RemotePass" `
    --no-create-info --no-tablespaces --skip-triggers --compact $DbName migrations | Set-Content -Path $migrationsFile -Encoding utf8
Get-Content $migrationsFile | & $mysql -u root "-p$RootPass" $DbName

Write-Host "=== 5. Update ResearchRecord .env ==="
$envFile = Join-Path $RrRoot '.env'
$content = Get-Content $envFile -Raw
$content = $content -replace 'database\.default\.hostname\s*=\s*\S+', "database.default.hostname = localhost"
$content = $content -replace 'database\.default\.password\s*=\s*\S+', "database.default.password = $DbPass"
Set-Content -Path $envFile -Value $content -NoNewline

Write-Host "=== 6. Verify ==="
Set-Location $RrRoot
php spark cache:clear 2>$null
php spark migrate:status 2>&1 | Select-Object -Last 5
& $mysql -u $DbUser "-p$DbPass" $DbName -e "SELECT COUNT(*) AS users FROM user; SELECT COUNT(*) AS migrations FROM migrations;"

Write-Host ""
Write-Host "Done. RR now uses localhost/$DbName (empty data, current schema)."
Write-Host "Login via newScience SSO will auto-create users on first access."
