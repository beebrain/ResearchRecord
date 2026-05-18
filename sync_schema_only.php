<?php

/**
 * Database Schema-Only Sync Script
 * 
 * Syncs database SCHEMA (structure only) from local to server
 * DOES NOT touch any data - only structure changes
 * 
 * Features:
 * - Creates missing tables
 * - Adds missing columns
 * - Modifies existing columns
 * - Syncs indexes and foreign keys
 * - Syncs views
 * - Safe: Never drops columns/tables (data protection)
 * - Dry-run mode by default
 * 
 * Usage:
 *   1. Update server database credentials below
 *   2. Run: php sync_schema_only.php
 *   3. Review the generated SQL
 *   4. Set $executeOnServer = true to apply changes (or run SQL manually)
 */

// ============================================
// CONFIGURATION
// ============================================

// Local Database Configuration (Source)
$localConfig = [
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'researchrecord',
    'port' => 3306
];

// Server Database Configuration (Target)
// You can also set these via environment variables:
//   DB_SERVER_HOST, DB_SERVER_USER, DB_SERVER_PASS, DB_SERVER_NAME, DB_SERVER_PORT
$serverConfig = [
    'hostname' => getenv('DB_SERVER_HOST') ?: '202.29.52.124',
    'username' => getenv('DB_SERVER_USER') ?: 'rac',
    'password' => getenv('DB_SERVER_PASS') ?: 'rac@URU@2025',
    'database' => getenv('DB_SERVER_NAME') ?: 'rac',
    'port' => (int)(getenv('DB_SERVER_PORT') ?: 3306)
];

// Safety Settings
$executeOnServer = false;  // Set to true to actually execute SQL on server (USE WITH CAUTION!)
$saveSQLToFile = true;     // Save generated SQL to file
$sqlOutputFile = 'schema_sync_' . date('Y-m-d_His') . '.sql';  // Output SQL file name

// ============================================
// SCRIPT EXECUTION
// ============================================

class SchemaOnlySyncer
{
    private $localConn;
    private $serverConn;
    private $localConfig;
    private $serverConfig;
    private $executeOnServer;
    private $sqlStatements = [];
    private $warnings = [];
    private $errors = [];

    public function __construct($localConfig, $serverConfig, $executeOnServer = false)
    {
        $this->localConfig = $localConfig;
        $this->serverConfig = $serverConfig;
        $this->executeOnServer = $executeOnServer;
    }

    /**
     * Connect to both databases
     */
    public function connect()
    {
        echo "Connecting to databases...\n";
        echo str_repeat("=", 80) . "\n";

        // Connect to local database
        try {
            $this->localConn = new mysqli(
                $this->localConfig['hostname'],
                $this->localConfig['username'],
                $this->localConfig['password'],
                $this->localConfig['database'],
                $this->localConfig['port']
            );

            if ($this->localConn->connect_error) {
                throw new Exception("Local connection failed: " . $this->localConn->connect_error);
            }
            $this->localConn->set_charset("utf8mb4");
            echo "✓ Connected to LOCAL: {$this->localConfig['database']}\n";
        } catch (Exception $e) {
            die("ERROR: " . $e->getMessage() . "\n");
        }

        // Connect to server database
        try {
            $this->serverConn = new mysqli(
                $this->serverConfig['hostname'],
                $this->serverConfig['username'],
                $this->serverConfig['password'],
                $this->serverConfig['database'],
                $this->serverConfig['port']
            );

            if ($this->serverConn->connect_error) {
                throw new Exception("Server connection failed: " . $this->serverConn->connect_error);
            }
            $this->serverConn->set_charset("utf8mb4");
            echo "✓ Connected to SERVER: {$this->serverConfig['database']}\n";
        } catch (Exception $e) {
            die("ERROR: " . $e->getMessage() . "\n");
        }

        echo str_repeat("=", 80) . "\n\n";
    }

    /**
     * Get all tables from a database
     */
    private function getTables($connection)
    {
        $tables = [];
        $result = $connection->query("SHOW TABLES");

        if ($result) {
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
        }

        return $tables;
    }

    /**
     * Get table structure (CREATE TABLE statement)
     */
    private function getTableStructure($connection, $tableName)
    {
        $result = $connection->query("SHOW CREATE TABLE `{$tableName}`");
        if ($result && $row = $result->fetch_assoc()) {
            return $row['Create Table'];
        }
        return null;
    }

    /**
     * Get table columns information
     */
    private function getTableColumns($connection, $tableName)
    {
        $columns = [];
        $result = $connection->query("SHOW FULL COLUMNS FROM `{$tableName}`");

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $columns[$row['Field']] = [
                    'Type' => $row['Type'],
                    'Null' => $row['Null'],
                    'Key' => $row['Key'],
                    'Default' => $row['Default'],
                    'Extra' => $row['Extra'],
                    'Collation' => $row['Collation'] ?? null,
                    'Comment' => $row['Comment'] ?? ''
                ];
            }
        }

        return $columns;
    }

    /**
     * Get indexes for a table
     */
    private function getTableIndexes($connection, $tableName)
    {
        $indexes = [];
        $result = $connection->query("SHOW INDEXES FROM `{$tableName}`");

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $keyName = $row['Key_name'];
                if (!isset($indexes[$keyName])) {
                    $indexes[$keyName] = [
                        'Non_unique' => $row['Non_unique'],
                        'Column_name' => [],
                        'Index_type' => $row['Index_type']
                    ];
                }
                $indexes[$keyName]['Column_name'][] = $row['Column_name'];
            }
        }

        return $indexes;
    }

    /**
     * Get foreign keys for a table
     */
    private function getForeignKeys($connection, $tableName)
    {
        $foreignKeys = [];
        $dbName = $connection->query("SELECT DATABASE()")->fetch_array()[0];

        $sql = "SELECT 
                    kcu.CONSTRAINT_NAME,
                    kcu.COLUMN_NAME,
                    kcu.REFERENCED_TABLE_NAME,
                    kcu.REFERENCED_COLUMN_NAME,
                    rc.UPDATE_RULE,
                    rc.DELETE_RULE
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                INNER JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
                    ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                    AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
                WHERE kcu.TABLE_SCHEMA = '{$dbName}'
                AND kcu.TABLE_NAME = '{$tableName}'
                AND kcu.REFERENCED_TABLE_NAME IS NOT NULL";

        $result = $connection->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $constraintName = $row['CONSTRAINT_NAME'];
                if (!isset($foreignKeys[$constraintName])) {
                    $foreignKeys[$constraintName] = [
                        'COLUMN_NAME' => $row['COLUMN_NAME'],
                        'REFERENCED_TABLE_NAME' => $row['REFERENCED_TABLE_NAME'],
                        'REFERENCED_COLUMN_NAME' => $row['REFERENCED_COLUMN_NAME'],
                        'UPDATE_RULE' => $row['UPDATE_RULE'],
                        'DELETE_RULE' => $row['DELETE_RULE']
                    ];
                }
            }
        }

        return $foreignKeys;
    }

    /**
     * Get all views
     */
    private function getViews($connection)
    {
        $views = [];
        $dbName = $connection->query("SELECT DATABASE()")->fetch_array()[0];

        $sql = "SELECT TABLE_NAME, VIEW_DEFINITION 
                FROM INFORMATION_SCHEMA.VIEWS 
                WHERE TABLE_SCHEMA = '{$dbName}'";

        $result = $connection->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $views[$row['TABLE_NAME']] = $row['VIEW_DEFINITION'];
            }
        }

        return $views;
    }

    /**
     * Generate ALTER TABLE statement to add a column
     */
    private function generateAddColumnSQL($tableName, $columnName, $columnDef)
    {
        $null = $columnDef['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = '';
        if ($columnDef['Default'] !== null) {
            $defaultValue = $columnDef['Default'];
            // Handle CURRENT_TIMESTAMP variations (case-insensitive, with or without parentheses)
            if (preg_match('/^current_timestamp(\(\))?$/i', $defaultValue)) {
                $default = "DEFAULT CURRENT_TIMESTAMP";
            } else {
                $default = "DEFAULT '{$defaultValue}'";
            }
        }
        $extra = $columnDef['Extra'] ? strtoupper($columnDef['Extra']) : '';

        $sql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$columnDef['Type']} {$null}";
        if ($default) $sql .= " {$default}";
        if ($extra) $sql .= " {$extra}";
        if ($columnDef['Comment']) {
            $comment = $this->localConn->real_escape_string($columnDef['Comment']);
            $sql .= " COMMENT '{$comment}'";
        }

        return $sql . ";";
    }

    /**
     * Generate ALTER TABLE statement to modify a column
     */
    private function generateModifyColumnSQL($tableName, $columnName, $columnDef)
    {
        $null = $columnDef['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = '';
        if ($columnDef['Default'] !== null) {
            $defaultValue = $columnDef['Default'];
            // Handle CURRENT_TIMESTAMP variations (case-insensitive, with or without parentheses)
            if (preg_match('/^current_timestamp(\(\))?$/i', $defaultValue)) {
                $default = "DEFAULT CURRENT_TIMESTAMP";
            } else {
                $default = "DEFAULT '{$defaultValue}'";
            }
        }
        $extra = $columnDef['Extra'] ? strtoupper($columnDef['Extra']) : '';

        $sql = "ALTER TABLE `{$tableName}` MODIFY COLUMN `{$columnName}` {$columnDef['Type']} {$null}";
        if ($default) $sql .= " {$default}";
        if ($extra) $sql .= " {$extra}";
        if ($columnDef['Comment']) {
            $comment = $this->localConn->real_escape_string($columnDef['Comment']);
            $sql .= " COMMENT '{$comment}'";
        }

        return $sql . ";";
    }

    /**
     * Generate CREATE TABLE statement
     */
    private function generateCreateTableSQL($tableName, $createTable)
    {
        // Remove AUTO_INCREMENT values to make it more generic
        $createTable = preg_replace('/AUTO_INCREMENT=\d+/', 'AUTO_INCREMENT=1', $createTable);
        return $createTable . ";";
    }

    /**
     * Generate CREATE INDEX statement
     */
    private function generateCreateIndexSQL($tableName, $indexName, $indexDef)
    {
        $columns = implode('`, `', $indexDef['Column_name']);
        $unique = $indexDef['Non_unique'] == 0 ? 'UNIQUE ' : '';

        if ($indexName === 'PRIMARY') {
            return "ALTER TABLE `{$tableName}` ADD PRIMARY KEY (`{$columns}`);";
        } else {
            return "CREATE {$unique}INDEX `{$indexName}` ON `{$tableName}` (`{$columns}`);";
        }
    }

    /**
     * Generate DROP INDEX statement (only for non-primary indexes)
     */
    private function generateDropIndexSQL($tableName, $indexName)
    {
        if ($indexName === 'PRIMARY') {
            // Never drop primary key - too dangerous
            return null;
        } else {
            return "DROP INDEX `{$indexName}` ON `{$tableName}`;";
        }
    }

    /**
     * Generate ADD FOREIGN KEY statement
     */
    private function generateAddForeignKeySQL($tableName, $fkName, $fkDef)
    {
        return "ALTER TABLE `{$tableName}` ADD CONSTRAINT `{$fkName}` 
                FOREIGN KEY (`{$fkDef['COLUMN_NAME']}`) 
                REFERENCES `{$fkDef['REFERENCED_TABLE_NAME']}` (`{$fkDef['REFERENCED_COLUMN_NAME']}`) 
                ON DELETE {$fkDef['DELETE_RULE']} 
                ON UPDATE {$fkDef['UPDATE_RULE']};";
    }

    /**
     * Generate DROP FOREIGN KEY statement
     */
    private function generateDropForeignKeySQL($tableName, $fkName)
    {
        return "ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$fkName}`;";
    }

    /**
     * Generate CREATE VIEW statement
     */
    private function generateCreateViewSQL($viewName, $viewDefinition)
    {
        return "CREATE OR REPLACE VIEW `{$viewName}` AS {$viewDefinition};";
    }

    /**
     * Check if a table name is actually a view
     */
    private function isView($connection, $name)
    {
        $dbName = $connection->query("SELECT DATABASE()")->fetch_array()[0];
        $sql = "SELECT COUNT(*) as count 
                FROM INFORMATION_SCHEMA.VIEWS 
                WHERE TABLE_SCHEMA = '{$dbName}' AND TABLE_NAME = '{$name}'";
        $result = $connection->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            return $row['count'] > 0;
        }
        return false;
    }

    /**
     * Sync tables
     */
    public function syncTables()
    {
        echo "Analyzing tables (SCHEMA ONLY - no data will be touched)...\n";
        echo str_repeat("=", 80) . "\n";

        $localTables = $this->getTables($this->localConn);
        $serverTables = $this->getTables($this->serverConn);

        // Get views to exclude them from table operations
        $localViews = $this->getViews($this->localConn);
        $serverViews = $this->getViews($this->serverConn);
        $allViews = array_unique(array_merge(array_keys($localViews), array_keys($serverViews)));

        $allTables = array_unique(array_merge($localTables, $serverTables));
        sort($allTables);

        foreach ($allTables as $tableName) {
            // Skip views - they are handled separately in syncViews()
            if (in_array($tableName, $allViews)) {
                continue;
            }
            $inLocal = in_array($tableName, $localTables);
            $inServer = in_array($tableName, $serverTables);

            if (!$inLocal) {
                $this->warnings[] = "Table '{$tableName}' exists in SERVER but not in LOCAL - skipping (data safety)";
                echo "⚠ Table '{$tableName}' - EXISTS IN SERVER ONLY (skipping)\n";
                continue;
            }

            if (!$inServer) {
                // Table doesn't exist in server - create it
                $createTable = $this->getTableStructure($this->localConn, $tableName);
                if ($createTable) {
                    $sql = $this->generateCreateTableSQL($tableName, $createTable);
                    $this->sqlStatements[] = [
                        'type' => 'create_table',
                        'table' => $tableName,
                        'sql' => $sql,
                        'description' => "Create table '{$tableName}'"
                    ];
                    echo "✓ Table '{$tableName}' - WILL BE CREATED\n";
                }
                continue;
            }

            // Table exists in both - compare and sync columns
            $localColumns = $this->getTableColumns($this->localConn, $tableName);
            $serverColumns = $this->getTableColumns($this->serverConn, $tableName);

            // Check for new columns (add only - never drop)
            foreach ($localColumns as $columnName => $localCol) {
                if (!isset($serverColumns[$columnName])) {
                    $sql = $this->generateAddColumnSQL($tableName, $columnName, $localCol);
                    $this->sqlStatements[] = [
                        'type' => 'add_column',
                        'table' => $tableName,
                        'column' => $columnName,
                        'sql' => $sql,
                        'description' => "Add column '{$columnName}' to table '{$tableName}'"
                    ];
                    echo "✓ Table '{$tableName}' - Column '{$columnName}' WILL BE ADDED\n";
                } else {
                    // Compare column properties
                    $serverCol = $serverColumns[$columnName];
                    $props = ['Type', 'Null', 'Default', 'Extra'];
                    $needsUpdate = false;

                    foreach ($props as $prop) {
                        if ($localCol[$prop] != $serverCol[$prop]) {
                            $needsUpdate = true;
                            break;
                        }
                    }

                    if ($needsUpdate) {
                        $sql = $this->generateModifyColumnSQL($tableName, $columnName, $localCol);
                        $this->sqlStatements[] = [
                            'type' => 'modify_column',
                            'table' => $tableName,
                            'column' => $columnName,
                            'sql' => $sql,
                            'description' => "Modify column '{$columnName}' in table '{$tableName}'"
                        ];
                        echo "✓ Table '{$tableName}' - Column '{$columnName}' WILL BE MODIFIED\n";
                    }
                }
            }

            // Check for columns in server but not in local (warn but don't remove - data safety)
            foreach ($serverColumns as $columnName => $serverCol) {
                if (!isset($localColumns[$columnName])) {
                    $this->warnings[] = "Column '{$columnName}' in table '{$tableName}' exists in SERVER but not in LOCAL - NOT removing (data safety)";
                    echo "⚠ Table '{$tableName}' - Column '{$columnName}' EXISTS IN SERVER ONLY (not removing)\n";
                }
            }

            // Compare indexes
            $localIndexes = $this->getTableIndexes($this->localConn, $tableName);
            $serverIndexes = $this->getTableIndexes($this->serverConn, $tableName);

            // Add missing indexes
            foreach ($localIndexes as $indexName => $localIndex) {
                if (!isset($serverIndexes[$indexName])) {
                    $sql = $this->generateCreateIndexSQL($tableName, $indexName, $localIndex);
                    $this->sqlStatements[] = [
                        'type' => 'add_index',
                        'table' => $tableName,
                        'index' => $indexName,
                        'sql' => $sql,
                        'description' => "Add index '{$indexName}' to table '{$tableName}'"
                    ];
                    echo "✓ Table '{$tableName}' - Index '{$indexName}' WILL BE ADDED\n";
                }
            }

            // Compare foreign keys
            $localFKs = $this->getForeignKeys($this->localConn, $tableName);
            $serverFKs = $this->getForeignKeys($this->serverConn, $tableName);

            // Add missing foreign keys
            foreach ($localFKs as $fkName => $localFK) {
                if (!isset($serverFKs[$fkName])) {
                    $sql = $this->generateAddForeignKeySQL($tableName, $fkName, $localFK);
                    $this->sqlStatements[] = [
                        'type' => 'add_foreign_key',
                        'table' => $tableName,
                        'foreign_key' => $fkName,
                        'sql' => $sql,
                        'description' => "Add foreign key '{$fkName}' to table '{$tableName}'"
                    ];
                    echo "✓ Table '{$tableName}' - Foreign key '{$fkName}' WILL BE ADDED\n";
                }
            }
        }

        echo "\n";
    }

    /**
     * Sync views
     */
    public function syncViews()
    {
        echo "Analyzing views...\n";
        echo str_repeat("=", 80) . "\n";

        $localViews = $this->getViews($this->localConn);
        $serverViews = $this->getViews($this->serverConn);

        $allViews = array_unique(array_merge(array_keys($localViews), array_keys($serverViews)));
        sort($allViews);

        foreach ($allViews as $viewName) {
            $inLocal = isset($localViews[$viewName]);
            $inServer = isset($serverViews[$viewName]);

            if (!$inLocal) {
                $this->warnings[] = "View '{$viewName}' exists in SERVER but not in LOCAL - skipping";
                echo "⚠ View '{$viewName}' - EXISTS IN SERVER ONLY (skipping)\n";
                continue;
            }

            if (!$inServer) {
                // View doesn't exist in server - create it
                $sql = $this->generateCreateViewSQL($viewName, $localViews[$viewName]);
                $this->sqlStatements[] = [
                    'type' => 'create_view',
                    'view' => $viewName,
                    'sql' => $sql,
                    'description' => "Create view '{$viewName}'"
                ];
                echo "✓ View '{$viewName}' - WILL BE CREATED\n";
            } else {
                // View exists - check if definition matches
                $localDef = preg_replace('/\s+/', ' ', trim($localViews[$viewName]));
                $serverDef = preg_replace('/\s+/', ' ', trim($serverViews[$viewName]));

                if ($localDef !== $serverDef) {
                    // Replace view
                    $sql = $this->generateCreateViewSQL($viewName, $localViews[$viewName]);
                    $this->sqlStatements[] = [
                        'type' => 'replace_view',
                        'view' => $viewName,
                        'sql' => $sql,
                        'description' => "Replace view '{$viewName}'"
                    ];
                    echo "✓ View '{$viewName}' - WILL BE REPLACED\n";
                } else {
                    echo "✓ View '{$viewName}' - MATCHES\n";
                }
            }
        }

        echo "\n";
    }

    /**
     * Generate and display SQL statements
     */
    public function generateSQL()
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "GENERATED SQL STATEMENTS (SCHEMA ONLY - NO DATA)\n";
        echo str_repeat("=", 80) . "\n\n";

        if (empty($this->sqlStatements)) {
            echo "✓ No SQL statements needed - schemas are already in sync!\n\n";
            return '';
        }

        $sql = "-- ============================================\n";
        $sql .= "-- Database Schema Sync SQL (SCHEMA ONLY - NO DATA)\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Source: LOCAL ({$this->localConfig['database']})\n";
        $sql .= "-- Target: SERVER ({$this->serverConfig['database']})\n";
        $sql .= "-- ============================================\n\n";
        $sql .= "-- WARNING: Review all statements before executing!\n";
        $sql .= "-- This script only modifies SCHEMA (structure), not DATA\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($this->sqlStatements as $stmt) {
            $sql .= "-- {$stmt['description']}\n";
            $sql .= $stmt['sql'] . "\n\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        echo $sql;

        return $sql;
    }

    /**
     * Save SQL to file
     */
    public function saveSQLToFile($filename)
    {
        if (empty($this->sqlStatements)) {
            return false;
        }

        $sql = $this->generateSQL();
        if (file_put_contents($filename, $sql)) {
            echo "\n✓ SQL saved to: {$filename}\n";
            return true;
        } else {
            echo "\n✗ Failed to save SQL to file: {$filename}\n";
            return false;
        }
    }

    /**
     * Execute SQL statements on server
     */
    public function executeSQL()
    {
        if (!$this->executeOnServer) {
            return;
        }

        if (empty($this->sqlStatements)) {
            return;
        }

        echo "\n" . str_repeat("=", 80) . "\n";
        echo "EXECUTING SQL ON SERVER\n";
        echo str_repeat("=", 80) . "\n\n";

        $this->serverConn->query("SET FOREIGN_KEY_CHECKS = 0");

        foreach ($this->sqlStatements as $stmt) {
            echo "Executing: {$stmt['description']}... ";
            if ($this->serverConn->query($stmt['sql'])) {
                echo "✓ SUCCESS\n";
            } else {
                $error = $this->serverConn->error;
                echo "✗ FAILED: {$error}\n";
                $this->errors[] = [
                    'statement' => $stmt['description'],
                    'error' => $error
                ];
            }
        }

        $this->serverConn->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * Display warnings
     */
    public function displayWarnings()
    {
        if (!empty($this->warnings)) {
            echo "\n" . str_repeat("=", 80) . "\n";
            echo "WARNINGS\n";
            echo str_repeat("=", 80) . "\n\n";

            foreach ($this->warnings as $warning) {
                echo "⚠ {$warning}\n";
            }

            echo "\n";
        }
    }

    /**
     * Display errors
     */
    public function displayErrors()
    {
        if (!empty($this->errors)) {
            echo "\n" . str_repeat("=", 80) . "\n";
            echo "ERRORS\n";
            echo str_repeat("=", 80) . "\n\n";

            foreach ($this->errors as $error) {
                echo "✗ {$error['statement']}: {$error['error']}\n";
            }

            echo "\n";
        }
    }

    /**
     * Close database connections
     */
    public function close()
    {
        if ($this->localConn) {
            $this->localConn->close();
        }
        if ($this->serverConn) {
            $this->serverConn->close();
        }
    }
}

// ============================================
// MAIN EXECUTION
// ============================================

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "DATABASE SCHEMA-ONLY SYNC TOOL\n";
echo "Sync LOCAL schema to SERVER (NO DATA WILL BE TOUCHED)\n";
echo str_repeat("=", 80) . "\n\n";

if ($executeOnServer) {
    echo "⚠⚠⚠ WARNING: EXECUTE MODE IS ENABLED ⚠⚠⚠\n";
    echo "SQL statements will be executed on server database!\n";
    echo "Press Ctrl+C within 5 seconds to cancel...\n\n";
    sleep(5);
    echo "Proceeding...\n\n";
} else {
    echo "ℹ DRY-RUN MODE: SQL will be generated but NOT executed\n";
    echo "Set \$executeOnServer = true to apply changes\n\n";
}

$syncer = new SchemaOnlySyncer($localConfig, $serverConfig, $executeOnServer);

try {
    $syncer->connect();
    $syncer->syncTables();
    $syncer->syncViews();
    $syncer->generateSQL();
    $syncer->displayWarnings();

    if ($saveSQLToFile) {
        $syncer->saveSQLToFile($sqlOutputFile);
    }

    $syncer->executeSQL();
    $syncer->displayErrors();
    $syncer->close();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    $syncer->close();
    exit(1);
}

echo "\n✓ Done!\n";
if (!$executeOnServer && !empty($syncer->sqlStatements)) {
    echo "\nTo apply changes:\n";
    echo "  1. Review the generated SQL above or in the file\n";
    echo "  2. Set \$executeOnServer = true and run again, OR\n";
    echo "  3. Execute the SQL file manually on the server\n";
}
echo "\n";
