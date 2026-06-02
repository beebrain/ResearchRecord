# Import all data from remote rac into localhost rac (win-kc)
param(
    [string]$DbName = 'rac',
    [string]$RootPass = 'admin@SCI@2026',
    [string]$RemoteHost = '202.29.52.124',
    [string]$RemoteUser = 'rac',
    [string]$RemotePass = 'rac@URU@2025'
)

$ErrorActionPreference = 'Stop'
$mysql = '"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"'
$mysqldump = '"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe"'
$errLog = 'C:\inetpub\ResearchRecord\deploy\import_err.txt'

$truncate = @"
SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE admission_form_teachers;
TRUNCATE TABLE authors;
TRUNCATE TABLE curriculum;
TRUNCATE TABLE cv_entries;
TRUNCATE TABLE cv_sections;
TRUNCATE TABLE faculties;
TRUNCATE TABLE publication_authors;
TRUNCATE TABLE publications;
TRUNCATE TABLE roles;
TRUNCATE TABLE student_admission_forms;
TRUNCATE TABLE teacher_curriculum;
TRUNCATE TABLE user;
TRUNCATE TABLE user_profile;
TRUNCATE TABLE user_roles;
SET FOREIGN_KEY_CHECKS=1;
"@

Write-Host "=== Truncate local data tables ==="
cmd /c "$mysql -u root -p$RootPass --default-character-set=utf8mb4 $DbName -e `"$($truncate -replace "`n",' ')`" 2> $errLog"
if ($LASTEXITCODE -ne 0) { Get-Content $errLog; throw 'truncate failed' }

Write-Host "=== Import from $RemoteHost via pipe ==="
$pipe = "$mysqldump -h $RemoteHost -u $RemoteUser -p$RemotePass --no-create-info --no-tablespaces --single-transaction --set-gtid-purged=OFF --default-character-set=utf8mb4 --skip-triggers --ignore-table=$DbName.migrations $DbName | $mysql -u root -p$RootPass --default-character-set=utf8mb4 $DbName 2> $errLog"
cmd /c $pipe
if ($LASTEXITCODE -ne 0) { Get-Content $errLog; throw 'import failed' }

Write-Host "=== Import complete ==="
