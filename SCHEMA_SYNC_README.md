# Database Schema-Only Sync Script

## Overview

The `sync_schema_only.php` script syncs database **SCHEMA (structure only)** from your local database to the server database. It **DOES NOT touch any data** - only structure changes.

## Features

✅ **Safe Schema Sync**

- Creates missing tables
- Adds missing columns
- Modifies existing columns (type, null, default, etc.)
- Syncs indexes and foreign keys
- Syncs views
- **Never drops columns or tables** (data protection)

✅ **Safety First**

- Dry-run mode by default
- Generates SQL for review before execution
- Saves SQL to file for manual execution
- Clear warnings for any differences

## Configuration

Edit the configuration section at the top of `sync_schema_only.php`:

```php
// Local Database Configuration (Source)
$localConfig = [
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'researchrecord',
    'port' => 3306
];

// Server Database Configuration (Target)
$serverConfig = [
    'hostname' => '202.29.52.124',
    'username' => 'rac',
    'password' => 'rac@URU@2025',
    'database' => 'rac',
    'port' => 3306
];
```

### Using Environment Variables

You can also set server credentials via environment variables:

```bash
export DB_SERVER_HOST=202.29.52.124
export DB_SERVER_USER=rac
export DB_SERVER_PASS=rac@URU@2025
export DB_SERVER_NAME=rac
export DB_SERVER_PORT=3306
```

## Usage

### Step 1: Review Configuration

Make sure your local and server database credentials are correct in the script.

### Step 2: Run in Dry-Run Mode (Recommended First)

```bash
php sync_schema_only.php
```

This will:

- Connect to both databases
- Compare schemas
- Generate SQL statements
- Display what will be changed
- **NOT execute anything** (safe)

### Step 3: Review Generated SQL

The script will:

- Display SQL statements in the console
- Save SQL to a file: `schema_sync_YYYY-MM-DD_HHMMSS.sql`

Review the SQL file to ensure it's correct.

### Step 4: Apply Changes

You have two options:

#### Option A: Execute via Script (Automatic)

Edit the script and set:

```php
$executeOnServer = true;
```

Then run:

```bash
php sync_schema_only.php
```

⚠️ **Warning**: This will execute SQL directly on the server!

#### Option B: Execute SQL Manually (Recommended)

1. Review the generated SQL file
2. Connect to your server database
3. Execute the SQL file manually

```bash
mysql -h 202.29.52.124 -u rac -p rac < schema_sync_2025-01-01_120000.sql
```

## What Gets Synced

### ✅ Will Be Synced

- **New tables** - Created on server
- **New columns** - Added to existing tables
- **Column modifications** - Type, null, default, extra attributes
- **New indexes** - Added to tables
- **New foreign keys** - Added to tables
- **Views** - Created or replaced

### ❌ Will NOT Be Synced (Data Safety)

- **Existing data** - Never touched
- **Columns in server but not in local** - Not removed (data safety)
- **Tables in server but not in local** - Not removed (data safety)
- **Indexes in server but not in local** - Not removed (data safety)

## Example Output

```
================================================================================
DATABASE SCHEMA-ONLY SYNC TOOL
Sync LOCAL schema to SERVER (NO DATA WILL BE TOUCHED)
================================================================================

ℹ DRY-RUN MODE: SQL will be generated but NOT executed
Set $executeOnServer = true to apply changes

Connecting to databases...
================================================================================
✓ Connected to LOCAL: researchrecord
✓ Connected to SERVER: rac
================================================================================

Analyzing tables (SCHEMA ONLY - no data will be touched)...
================================================================================
✓ Table 'users' - Column 'new_field' WILL BE ADDED
✓ Table 'publications' - Index 'idx_title' WILL BE ADDED
✓ Table 'authors' - WILL BE CREATED

Analyzing views...
================================================================================
✓ View 'publication_view' - WILL BE REPLACED

================================================================================
GENERATED SQL STATEMENTS (SCHEMA ONLY - NO DATA)
================================================================================

-- Add column 'new_field' to table 'users'
ALTER TABLE `users` ADD COLUMN `new_field` VARCHAR(255) NULL;

-- Add index 'idx_title' to table 'publications'
CREATE INDEX `idx_title` ON `publications` (`title`);

-- Create table 'authors'
CREATE TABLE `authors` (...);

✓ SQL saved to: schema_sync_2025-01-01_120000.sql

✓ Done!
```

## Safety Features

1. **Dry-run by default** - Never executes without explicit permission
2. **No data deletion** - Never drops columns or tables
3. **SQL file generation** - Always generates SQL for review
4. **Clear warnings** - Shows what won't be synced
5. **Error handling** - Catches and reports errors

## Troubleshooting

### Connection Errors

- Check database credentials
- Ensure server is accessible from your network
- Check firewall settings

### SQL Execution Errors

- Review the error message
- Check if the SQL is compatible with your MySQL version
- Some operations may require manual intervention

### Missing Columns/Tables

If a column or table exists in server but not in local:

- The script will warn you but won't remove it (data safety)
- Manually review and decide if removal is needed

## Best Practices

1. **Always run in dry-run mode first** - Review the generated SQL
2. **Backup your server database** - Before applying any changes
3. **Test on staging first** - If you have a staging environment
4. **Review SQL file** - Check the generated SQL before execution
5. **Execute during low-traffic periods** - To minimize impact

## Related Scripts

- `check_database_schema.php` - Compare schemas without syncing
- `sync_database_schema.php` - Original sync script (more features)
