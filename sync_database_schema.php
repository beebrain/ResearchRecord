<?php

/**
 * Database Schema Sync Script
 * 
 * Generates SQL statements to sync production database schema with localhost
 * 
 * Usage:
 *   1. Update the production database credentials below
 *   2. Run: php sync_database_schema.php
 *   3. Review the generated SQL statements
 *   4. Optionally execute them on production (use with caution!)
 */

// ============================================
// CONFIGURATION
// ============================================

// Localhost Database Configuration
$localhostConfig = [
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'researchrecord',
    'port' => 3306
];

// Production Database Configuration
$productionConfig = [
    'hostname' => getenv('DB_PROD_HOST') ?: '202.29.52.124',
    'username' => getenv('DB_PROD_USER') ?: 'rac',
    'password' => getenv('DB_PROD_PASS') ?: 'rac@URU@2025',
    'database' => getenv('DB_PROD_NAME') ?: 'rac',
    'port' => (int)(getenv('DB_PROD_PORT') ?: 3306)
];

// Set to true to actually execute SQL on production (USE WITH EXTREME CAUTION!)
$executeOnProduction = false;

// ============================================
// SCRIPT EXECUTION
// ============================================

class DatabaseSchemaSyncer
{
    private $localhostConn;
    private $productionConn;
    private $localhostConfig;
    private $productionConfig;
    private $executeOnProduction;
    private $sqlStatements = [];
    private $warnings = [];

    public function __construct($localhostConfig, $productionConfig, $executeOnProduction = false)
    {
        $this->localhostConfig = $localhostConfig;
        $this->productionConfig = $productionConfig;
        $this->executeOnProduction = $executeOnProduction;
    }

    /**
     * Connect to both databases
     */
    public function connect()
    {
        echo "Connecting to databases...\n";
        echo str_repeat("=", 80) . "\n";

        // Connect to localhost
        try {
            $this->localhostConn = new mysqli(
                $this->localhostConfig['hostname'],
                $this->localhostConfig['username'],
                $this->localhostConfig['password'],
                $this->localhostConfig['database'],
                $this->localhostConfig['port']
            );

            if ($this->localhostConn->connect_error) {
                throw new Exception("Localhost connection failed: " . $this->localhostConn->connect_error);
            }
            $this->localhostConn->set_charset("utf8mb4");
            echo "✓ Connected to LOCALHOST: {$this->localhostConfig['database']}\n";
        } catch (Exception $e) {
            die("ERROR: " . $e->getMessage() . "\n");
        }

        // Connect to production
        try {
            $this->productionConn = new mysqli(
                $this->productionConfig['hostname'],
                $this->productionConfig['username'],
                $this->productionConfig['password'],
                $this->productionConfig['database'],
                $this->productionConfig['port']
            );

            if ($this->productionConn->connect_error) {
                throw new Exception("Production connection failed: " . $this->productionConn->connect_error);
            }
            $this->productionConn->set_charset("utf8mb4");
            echo "✓ Connected to PRODUCTION: {$this->productionConfig['database']}\n";
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
        $default = $columnDef['Default'] !== null ? "DEFAULT '{$columnDef['Default']}'" : '';
        $extra = $columnDef['Extra'] ? strtoupper($columnDef['Extra']) : '';
        
        $sql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$columnDef['Type']} {$null}";
        if ($default) $sql .= " {$default}";
        if ($extra) $sql .= " {$extra}";
        if ($columnDef['Comment']) $sql .= " COMMENT '{$columnDef['Comment']}'";
        
        return $sql . ";";
    }

    /**
     * Generate ALTER TABLE statement to modify a column
     */
    private function generateModifyColumnSQL($tableName, $columnName, $columnDef)
    {
        $null = $columnDef['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $columnDef['Default'] !== null ? "DEFAULT '{$columnDef['Default']}'" : '';
        $extra = $columnDef['Extra'] ? strtoupper($columnDef['Extra']) : '';
        
        $sql = "ALTER TABLE `{$tableName}` MODIFY COLUMN `{$columnName}` {$columnDef['Type']} {$null}";
        if ($default) $sql .= " {$default}";
        if ($extra) $sql .= " {$extra}";
        if ($columnDef['Comment']) $sql .= " COMMENT '{$columnDef['Comment']}'";
        
        return $sql . ";";
    }

    /**
     * Generate ALTER TABLE statement to drop a column
     */
    private function generateDropColumnSQL($tableName, $columnName)
    {
        return "ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`;";
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
     * Generate DROP INDEX statement
     */
    private function generateDropIndexSQL($tableName, $indexName)
    {
        if ($indexName === 'PRIMARY') {
            return "ALTER TABLE `{$tableName}` DROP PRIMARY KEY;";
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
     * Generate DROP VIEW statement
     */
    private function generateDropViewSQL($viewName)
    {
        return "DROP VIEW IF EXISTS `{$viewName}`;";
    }

    /**
     * Sync tables
     */
    public function syncTables()
    {
        echo "Analyzing tables...\n";
        echo str_repeat("=", 80) . "\n";

        $localTables = $this->getTables($this->localhostConn);
        $prodTables = $this->getTables($this->productionConn);

        $allTables = array_unique(array_merge($localTables, $prodTables));
        sort($allTables);

        foreach ($allTables as $tableName) {
            $inLocal = in_array($tableName, $localTables);
            $inProd = in_array($tableName, $prodTables);

            if (!$inLocal) {
                $this->warnings[] = "Table '{$tableName}' exists in PRODUCTION but not in LOCALHOST - skipping";
                echo "⚠ Table '{$tableName}' - EXISTS IN PRODUCTION ONLY (skipping)\n";
                continue;
            }

            if (!$inProd) {
                // Table doesn't exist in production - create it
                $createTable = $this->getTableStructure($this->localhostConn, $tableName);
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

            // Table exists in both - compare and sync
            $localColumns = $this->getTableColumns($this->localhostConn, $tableName);
            $prodColumns = $this->getTableColumns($this->productionConn, $tableName);

            // Check for new columns
            foreach ($localColumns as $columnName => $localCol) {
                if (!isset($prodColumns[$columnName])) {
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
                    $prodCol = $prodColumns[$columnName];
                    $props = ['Type', 'Null', 'Default', 'Extra'];
                    $needsUpdate = false;

                    foreach ($props as $prop) {
                        if ($localCol[$prop] != $prodCol[$prop]) {
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

            // Check for columns to remove (in production but not in localhost)
            foreach ($prodColumns as $columnName => $prodCol) {
                if (!isset($localColumns[$columnName])) {
                    $this->warnings[] = "Column '{$columnName}' in table '{$tableName}' exists in PRODUCTION but not in LOCALHOST - NOT removing (data safety)";
                    echo "⚠ Table '{$tableName}' - Column '{$columnName}' EXISTS IN PRODUCTION ONLY (not removing)\n";
                }
            }

            // Compare indexes
            $localIndexes = $this->getTableIndexes($this->localhostConn, $tableName);
            $prodIndexes = $this->getTableIndexes($this->productionConn, $tableName);

            // Add missing indexes
            foreach ($localIndexes as $indexName => $localIndex) {
                if (!isset($prodIndexes[$indexName])) {
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
            $localFKs = $this->getForeignKeys($this->localhostConn, $tableName);
            $prodFKs = $this->getForeignKeys($this->productionConn, $tableName);

            // Drop existing foreign keys that don't match
            foreach ($prodFKs as $fkName => $prodFK) {
                if (!isset($localFKs[$fkName])) {
                    $sql = $this->generateDropForeignKeySQL($tableName, $fkName);
                    $this->sqlStatements[] = [
                        'type' => 'drop_foreign_key',
                        'table' => $tableName,
                        'foreign_key' => $fkName,
                        'sql' => $sql,
                        'description' => "Drop foreign key '{$fkName}' from table '{$tableName}'"
                    ];
                    echo "✓ Table '{$tableName}' - Foreign key '{$fkName}' WILL BE DROPPED\n";
                }
            }

            // Add missing foreign keys
            foreach ($localFKs as $fkName => $localFK) {
                if (!isset($prodFKs[$fkName])) {
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

        $localViews = $this->getViews($this->localhostConn);
        $prodViews = $this->getViews($this->productionConn);

        $allViews = array_unique(array_merge(array_keys($localViews), array_keys($prodViews)));
        sort($allViews);

        foreach ($allViews as $viewName) {
            $inLocal = isset($localViews[$viewName]);
            $inProd = isset($prodViews[$viewName]);

            if (!$inLocal) {
                $this->warnings[] = "View '{$viewName}' exists in PRODUCTION but not in LOCALHOST - skipping";
                echo "⚠ View '{$viewName}' - EXISTS IN PRODUCTION ONLY (skipping)\n";
                continue;
            }

            if (!$inProd) {
                // View doesn't exist in production - create it
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
                $prodDef = preg_replace('/\s+/', ' ', trim($prodViews[$viewName]));

                if ($localDef !== $prodDef) {
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
        echo "GENERATED SQL STATEMENTS\n";
        echo str_repeat("=", 80) . "\n\n";

        if (empty($this->sqlStatements)) {
            echo "No SQL statements needed - schemas are already in sync!\n\n";
            return;
        }

        echo "-- ============================================\n";
        echo "-- Database Schema Sync SQL\n";
        echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        echo "-- Source: LOCALHOST ({$this->localhostConfig['database']})\n";
        echo "-- Target: PRODUCTION ({$this->productionConfig['database']})\n";
        echo "-- ============================================\n\n";

        echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($this->sqlStatements as $stmt) {
            echo "-- {$stmt['description']}\n";
            echo $stmt['sql'] . "\n\n";
        }

        echo "SET FOREIGN_KEY_CHECKS = 1;\n";
    }

    /**
     * Execute SQL statements on production
     */
    public function executeSQL()
    {
        if (!$this->executeOnProduction) {
            return;
        }

        if (empty($this->sqlStatements)) {
            return;
        }

        echo "\n" . str_repeat("=", 80) . "\n";
        echo "EXECUTING SQL ON PRODUCTION\n";
        echo str_repeat("=", 80) . "\n\n";

        $this->productionConn->query("SET FOREIGN_KEY_CHECKS = 0");

        foreach ($this->sqlStatements as $stmt) {
            echo "Executing: {$stmt['description']}... ";
            if ($this->productionConn->query($stmt['sql'])) {
                echo "✓ SUCCESS\n";
            } else {
                echo "✗ FAILED: " . $this->productionConn->error . "\n";
            }
        }

        $this->productionConn->query("SET FOREIGN_KEY_CHECKS = 1");
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
     * Close database connections
     */
    public function close()
    {
        if ($this->localhostConn) {
            $this->localhostConn->close();
        }
        if ($this->productionConn) {
            $this->productionConn->close();
        }
    }
}

// ============================================
// MAIN EXECUTION
// ============================================

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "DATABASE SCHEMA SYNC TOOL\n";
echo "Sync LOCALHOST schema to PRODUCTION\n";
echo str_repeat("=", 80) . "\n\n";

if ($executeOnProduction) {
    echo "⚠⚠⚠ WARNING: EXECUTE MODE IS ENABLED ⚠⚠⚠\n";
    echo "SQL statements will be executed on production database!\n";
    echo "Press Ctrl+C within 5 seconds to cancel...\n\n";
    sleep(5);
    echo "Proceeding...\n\n";
}

$syncer = new DatabaseSchemaSyncer($localhostConfig, $productionConfig, $executeOnProduction);

try {
    $syncer->connect();
    $syncer->syncTables();
    $syncer->syncViews();
    $syncer->generateSQL();
    $syncer->displayWarnings();
    $syncer->executeSQL();
    $syncer->close();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    $syncer->close();
    exit(1);
}

echo "\nDone!\n";

