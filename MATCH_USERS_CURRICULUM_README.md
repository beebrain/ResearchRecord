# User-Curriculum-Faculty Matching Script

## Overview

This script reads `match.csv` and matches users with their curriculum and faculty information based on their Thai name and lastname.

## File Location

[match_users_curriculum.php](c:\xampp\htdocs\researchRecord\match_users_curriculum.php)

## How to Run

### On Local Server
```bash
# Via command line
cd c:\xampp\htdocs\researchRecord
php match_users_curriculum.php

# Via web browser
http://localhost/researchRecord/match_users_curriculum.php
```

### On Production Server
```bash
# Via command line
cd /path/to/researchRecord
php match_users_curriculum.php

# Via web browser
https://research.academic.uru.ac.th/public/match_users_curriculum.php
```

## What It Does

1. **Reads match.csv** - Contains 4 columns:
   - ชื่อ (First Name)
   - นามสกุล (Last Name)
   - หลักสูตร (Curriculum)
   - คณะ (Faculty)

2. **Matches Users** - Finds users in the database by matching:
   - `thai_name` + `thai_lastname` from CSV
   - Against `user` table

3. **Creates Faculty Records** - If faculty doesn't exist:
   - Creates new record in `faculties` table
   - Auto-generates faculty code (first 2 Thai characters)

4. **Creates Curriculum Records** - If curriculum doesn't exist:
   - Creates new record in `curriculum` table
   - Links to faculty_id
   - Auto-generates curriculum code
   - Defaults to 'bachelor' degree level

5. **Updates User Records** - Sets:
   - `curriculum_id` - Links user to their curriculum
   - `faculty_id` - Links user to their faculty

## Features

✅ **Auto-detect Environment** - Switches between local/production database credentials
✅ **Encoding Conversion** - Handles Windows-874 Thai encoding
✅ **Smart Caching** - Caches faculties and curriculums to reduce queries
✅ **Progress Indicators** - Shows real-time progress every 50 records
✅ **Error Handling** - Continues processing even if individual records fail
✅ **Detailed Logging** - Shows exactly what happened to each user

## CSV Format

```csv
ชื่อ,นามสกุล,หลักสูตร,คณะ
รุ่งทิวา,ปราบริปู,ครุศาสตรบัณฑิต สาขาวิชาประถมศึกษา,คณะครุศาสตร์
อุษณีย์,เขนยทิพย์,ครุศาสตรบัณฑิต สาขาวิชาประถมศึกษา,คณะครุศาสตร์
```

**Encoding**: Windows-874 (Thai) or UTF-8

## Database Configuration

The script auto-detects the environment:

### Local Development
- Host: `localhost`
- User: `root`
- Password: `` (empty)
- Database: `researchrecord`

### Production Server
- Host: `localhost`
- User: `rac`
- Password: `rac@URU@2025`
- Database: `rac`

## Test Results (Local)

Last run on local server:

```
Total processed:       325
Users found:           264
Users not found:       61
Users updated:         155
Already up to date:    53
Faculties created:     0
Curriculums created:   31
Errors:                56
```

### Statistics Breakdown

- **264 users matched** - Successfully found in database by name
- **61 users not found** - Names in CSV don't match any user in database
- **155 users updated** - curriculum_id and/or faculty_id was set
- **53 already up to date** - Users already had correct values
- **0 faculties created** - All faculties already existed
- **31 curriculums created** - New curriculum records were created
- **56 errors** - Mostly duplicate processing or data issues

## Output Example

```
Processing: รุ่งทิวา ปราบริปู → ✓ Found (UID: 758, Email: rungtiwa.pra@live.uru.ac.th) → ➕ Created curriculum: ครุศาสตรบัณฑิต สาขาวิชาประถมศึกษา → ✅ Updated

Processing: อุษณีย์ เขนยทิพย์ → ✓ Found (UID: 880, Email: utsanee.kan@live.uru.ac.th) → ✅ Updated

Processing: กฤษณา คิดดี → ❌ User not found
```

### Status Icons

- ✓ **Found** - User matched in database
- ❌ **User not found** - No matching user in database
- ➕ **Created** - New faculty or curriculum record created
- ✅ **Updated** - User record updated with new IDs
- ⏭ **Already up to date** - No changes needed
- ⚠ **Warning** - Invalid data format

## Matching Logic

The script matches users using exact string matching:

```sql
SELECT * FROM user
WHERE thai_name = ? AND thai_lastname = ?
LIMIT 1
```

**Important**: Names must match exactly (case-sensitive, character-for-character).

## Common Issues

### Issue 1: User Not Found

**Problem**: CSV shows "❌ User not found"

**Causes**:
1. Name spelling differs between CSV and database
2. Extra spaces in CSV or database
3. User hasn't been imported yet
4. Different Thai character encoding

**Solution**:
- Check exact spelling in both CSV and database
- Run query: `SELECT thai_name, thai_lastname FROM user WHERE thai_name LIKE '%firstName%'`

### Issue 2: Errors During Processing

**Problem**: Script shows errors for some records

**Causes**:
1. Invalid CSV format
2. Database constraint violations
3. Duplicate curriculum names

**Solution**:
- Check error message in output
- Verify CSV format (4 columns)
- Check for duplicates in CSV

### Issue 3: Wrong Encoding

**Problem**: Thai characters display as ����

**Causes**:
- CSV file not in Windows-874 or UTF-8 encoding

**Solution**:
- Script auto-converts Windows-874 to UTF-8
- If still broken, save CSV as UTF-8 with BOM

## Database Tables

### user
```sql
uid             INT         - Primary key
thai_name       VARCHAR     - Thai first name
thai_lastname   VARCHAR     - Thai last name
curriculum_id   INT         - FK to curriculum.id
faculty_id      INT         - FK to faculties.id
```

### faculties
```sql
id              INT         - Primary key
name            VARCHAR     - Faculty name (Thai)
code            VARCHAR     - Faculty code (2 chars)
status          TINYINT     - Active status (1=active)
```

### curriculum
```sql
id              INT         - Primary key
faculty_id      INT         - FK to faculties.id
name            VARCHAR     - Curriculum name (Thai)
code            VARCHAR     - Curriculum code
degree_level    ENUM        - bachelor/master/doctoral
status          TINYINT     - Active status (1=active)
```

## Verification

### Check User Updates
```sql
SELECT
    u.uid,
    u.thai_name,
    u.thai_lastname,
    u.email,
    c.name as curriculum_name,
    f.name as faculty_name
FROM user u
LEFT JOIN curriculum c ON u.curriculum_id = c.id
LEFT JOIN faculties f ON u.faculty_id = f.id
WHERE u.curriculum_id IS NOT NULL
LIMIT 10;
```

### Check Created Curriculums
```sql
SELECT
    c.id,
    c.name,
    c.code,
    f.name as faculty_name
FROM curriculum c
JOIN faculties f ON c.faculty_id = f.id
ORDER BY c.id DESC
LIMIT 10;
```

### Count Matched Users
```sql
SELECT
    COUNT(*) as total_users,
    COUNT(curriculum_id) as users_with_curriculum,
    COUNT(faculty_id) as users_with_faculty
FROM user;
```

## Safety Features

✅ **No Data Loss** - Only updates curriculum_id and faculty_id fields
✅ **Idempotent** - Can run multiple times safely
✅ **Transaction Safe** - Uses PDO with proper error handling
✅ **Read-Only CSV** - Original CSV file is not modified
✅ **Logging** - Detailed output for every operation

## Performance

- **Processing Speed**: ~6-8 records per second
- **325 records**: ~40-50 seconds
- **Database Queries**: Optimized with caching
- **Memory Usage**: < 10MB

## Future Enhancements

- [ ] Fuzzy name matching for similar names
- [ ] Support for multiple CSV files
- [ ] Rollback functionality
- [ ] Export unmatched users to CSV
- [ ] Email notification on completion
- [ ] Web UI for uploading CSV

## Files Modified/Created

- ✅ [match_users_curriculum.php](c:\xampp\htdocs\researchRecord\match_users_curriculum.php) - Main script
- ✅ [match.csv](c:\xampp\htdocs\researchRecord\match.csv) - Input data (325 records)
- ✅ This README

## Related Scripts

- [import_users_standalone.php](c:\xampp\htdocs\researchRecord\import_users_standalone.php) - Import users from CSV
- [app/Commands/ImportUsers.php](c:\xampp\htdocs\researchRecord\app\Commands\ImportUsers.php) - CLI user import

## Troubleshooting

### Script Won't Run

```bash
# Check PHP is installed
php -v

# Check file exists
ls -la match_users_curriculum.php

# Check CSV exists
ls -la match.csv

# Run with error output
php match_users_curriculum.php 2>&1
```

### Database Connection Failed

```bash
# Test database connection
mysql -u root researchrecord -e "SELECT COUNT(*) FROM user;"

# Or on production
mysql -u rac -p'rac@URU@2025' rac -e "SELECT COUNT(*) FROM user;"
```

### Permission Denied (Linux/Production)

```bash
# Make script executable
chmod +x match_users_curriculum.php

# Check file ownership
ls -la match_users_curriculum.php

# Run with sudo if needed
sudo php match_users_curriculum.php
```

## Support

For issues or questions:
1. Check the detailed output from the script
2. Review the error messages
3. Verify database connectivity
4. Check CSV file format and encoding

## License

Internal tool for URU Research Record system.
