# User Import Script Documentation

## Overview
This standalone PHP script imports users from a CSV file into the user table without requiring CodeIgniter CLI.

## Files
- **import_users_standalone.php** - Main import script

## Usage

### Method 1: Run from Command Line
```bash
# Import using default CSV file (user.csv in the same directory)
php import_users_standalone.php

# Import using a specific CSV file
php import_users_standalone.php /path/to/your/file.csv
```

### Method 2: Run from Web Browser
Simply access the script via your web browser:
```
http://yourserver/import_users_standalone.php
```

The script will automatically use the `user.csv` file in the same directory.

## CSV File Format

The CSV file should have the following columns:

| Column | Description | Required |
|--------|-------------|----------|
| EMAIL | User email address | Yes |
| NAME | Thai first name | Yes |
| SURNAME | Thai last name | No |
| POSITION_NAME | Position (ignored) | No |
| DEPARTMENT_BRANCH | Department (ignored) | No |
| FACULTY | Faculty (ignored) | No |

**Note:** Only EMAIL, NAME, and SURNAME are imported. Other columns are ignored.

### Example CSV
```csv
EMAIL,NAME,SURNAME,POSITION_NAME,DEPARTMENT_BRANCH,FACULTY
john@example.com,จอห์น,โด,อาจารย์,วิทยาการคอมพิวเตอร์,วิศวกรรมศาสตร์
jane@example.com,เจน,สมิธ,ผู้ช่วยศาสตราจารย์,คณิตศาสตร์,วิทยาศาสตร์
```

## Encoding Support

The script automatically handles:
- **UTF-8** (with or without BOM)
- **Windows-874** (Thai encoding) - automatically converts to UTF-8
- **Windows-1252** (fallback)

This ensures Thai characters display correctly.

## Database Configuration

**IMPORTANT:** Edit these settings at the top of `import_users_standalone.php` to match your database:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'rac');              // Change to your database user
define('DB_PASS', 'rac@URU@2025');     // Change to your database password
define('DB_NAME', 'rac');               // Change to your database name
define('DB_CHARSET', 'utf8mb4');
```

**For local testing**, use:
```php
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'researchrecord');
```

## Features

✓ Automatic encoding detection and conversion (Windows-874 → UTF-8)
✓ Duplicate email detection (skips existing users)
✓ Email validation
✓ Real-time progress display
✓ Error handling and logging
✓ Works from both command line and web browser
✓ Supports both CSV (comma) and TSV (tab) formats

## Import Behavior

1. **Skips duplicates** - If an email already exists, the user is skipped
2. **Validates emails** - Invalid emails are skipped
3. **Requires name** - Records without a name are skipped
4. **Default role** - All imported users get `user` role (valid roles: `user`, `faculty_admin`, `super_admin`)
5. **Active by default** - All imported users are marked as active

## Output Example

```
Starting import from: C:\xampp\htdocs\researchRecord/user.csv
Database connection established
Converting file encoding from Windows-874 to UTF-8...
Encoding conversion successful

Processing records...

✓ Imported: john@example.com - จอห์น โด
✓ Imported: jane@example.com - เจน สมิธ

═══════════════════════════════════════
Import Complete!
═══════════════════════════════════════
Total Inserted: 2
Total Skipped:  0
═══════════════════════════════════════
```

## Troubleshooting

### Error: "CSV file not found"
- Make sure the CSV file exists in the specified location
- Check the file path is correct
- Use absolute paths when running from command line

### Error: "Database connection failed"
- Verify database credentials in the script
- Make sure MySQL/MariaDB is running
- Check database name is correct

### Thai characters appear as ????
- Make sure your database uses `utf8mb4` charset
- Verify the table columns use `utf8mb4_general_ci` collation
- The script handles encoding conversion automatically

### All records skipped
- This means all emails already exist in the database
- Check for duplicate imports
- Verify email addresses in the CSV file

## Security Notes

⚠️ **Important:** This script should be:
- Removed from the server after use, OR
- Protected with authentication, OR
- Placed outside the web root

For production use, consider adding authentication or running only from command line.

## Data Imported

Each user record includes:
- `email` - Lowercased email address
- `thai_name` - Thai first name
- `thai_lastname` - Thai last name
- `gf_name` - Same as thai_name
- `gl_name` - Same as thai_lastname
- `role` - Set to 'user' (can be changed to 'faculty_admin' or 'super_admin' in the script if needed)
- `active` - Set to 1 (active)
- `created_at` - Current timestamp
