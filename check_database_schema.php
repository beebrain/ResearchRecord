<?php

/**
 * Database Schema Comparison Script
 * 
 * Compares database schema between localhost and production environments
 * 
 * Usage:
 *   1. Update the production database credentials below
 *   2. Run: php check_database_schema.php
 *   3. Review the output report
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
// TODO: Update these with your production credentials
// You can also set these via environment variables:
//   DB_PROD_HOST, DB_PROD_USER, DB_PROD_PASS, DB_PROD_NAME, DB_PROD_PORT
$productionConfig = [
    'hostname' => getenv('DB_PROD_HOST') ?: '202.29.52.124',  // Update this
    'username' => getenv('DB_PROD_USER') ?: 'rac',        // Update this
    'password' => getenv('DB_PROD_PASS') ?: 'rac@URU@2025',   // Update this
    'database' => getenv('DB_PROD_NAME') ?: 'rac',             // Update if different
    'port' => (int)(getenv('DB_PROD_PORT') ?: 3306)                       // Update if different
];

// ============================================
// SCRIPT EXECUTION
// ============================================

class DatabaseSchemaComparator
{
    private $localhostConn;
    private $productionConn;
    private $localhostConfig;
    private $productionConfig;
    private $differences = [];
    private $report = [];

    public function __construct($localhostConfig, $productionConfig)
    {
        $this->localhostConfig = $localhostConfig;
        $this->productionConfig = $productionConfig;
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
     * Normalize CREATE TABLE statement for comparison
     */
    private function normalizeCreateTable($createTable)
    {
        // Remove AUTO_INCREMENT values
        $createTable = preg_replace('/AUTO_INCREMENT=\d+/', 'AUTO_INCREMENT=0', $createTable);

        // Remove default charset/collate if present (they might differ but be equivalent)
        $createTable = preg_replace('/DEFAULT CHARSET=\w+/', '', $createTable);
        $createTable = preg_replace('/COLLATE=\w+/', '', $createTable);

        // Normalize whitespace
        $createTable = preg_replace('/\s+/', ' ', $createTable);
        $createTable = trim($createTable);

        return $createTable;
    }

    /**
     * Compare two arrays of columns
     */
    private function compareColumns($localColumns, $prodColumns, $tableName)
    {
        $differences = [];

        // Check for columns in localhost but not in production
        foreach ($localColumns as $columnName => $localCol) {
            if (!isset($prodColumns[$columnName])) {
                $differences[] = [
                    'type' => 'missing_column_production',
                    'table' => $tableName,
                    'column' => $columnName,
                    'details' => "Column exists in LOCALHOST but not in PRODUCTION"
                ];
            } else {
                // Compare column properties
                $prodCol = $prodColumns[$columnName];
                $props = ['Type', 'Null', 'Key', 'Default', 'Extra'];

                foreach ($props as $prop) {
                    if ($localCol[$prop] != $prodCol[$prop]) {
                        $differences[] = [
                            'type' => 'column_difference',
                            'table' => $tableName,
                            'column' => $columnName,
                            'property' => $prop,
                            'localhost' => $localCol[$prop],
                            'production' => $prodCol[$prop]
                        ];
                    }
                }
            }
        }

        // Check for columns in production but not in localhost
        foreach ($prodColumns as $columnName => $prodCol) {
            if (!isset($localColumns[$columnName])) {
                $differences[] = [
                    'type' => 'missing_column_localhost',
                    'table' => $tableName,
                    'column' => $columnName,
                    'details' => "Column exists in PRODUCTION but not in LOCALHOST"
                ];
            }
        }

        return $differences;
    }

    /**
     * Compare indexes
     */
    private function compareIndexes($localIndexes, $prodIndexes, $tableName)
    {
        $differences = [];

        foreach ($localIndexes as $indexName => $localIndex) {
            if (!isset($prodIndexes[$indexName])) {
                $differences[] = [
                    'type' => 'missing_index_production',
                    'table' => $tableName,
                    'index' => $indexName,
                    'details' => "Index exists in LOCALHOST but not in PRODUCTION"
                ];
            } else {
                $prodIndex = $prodIndexes[$indexName];
                if (json_encode($localIndex) !== json_encode($prodIndex)) {
                    $differences[] = [
                        'type' => 'index_difference',
                        'table' => $tableName,
                        'index' => $indexName,
                        'localhost' => $localIndex,
                        'production' => $prodIndex
                    ];
                }
            }
        }

        foreach ($prodIndexes as $indexName => $prodIndex) {
            if (!isset($localIndexes[$indexName])) {
                $differences[] = [
                    'type' => 'missing_index_localhost',
                    'table' => $tableName,
                    'index' => $indexName,
                    'details' => "Index exists in PRODUCTION but not in LOCALHOST"
                ];
            }
        }

        return $differences;
    }

    /**
     * Compare foreign keys
     */
    private function compareForeignKeys($localFKs, $prodFKs, $tableName)
    {
        $differences = [];

        foreach ($localFKs as $fkName => $localFK) {
            if (!isset($prodFKs[$fkName])) {
                $differences[] = [
                    'type' => 'missing_foreign_key_production',
                    'table' => $tableName,
                    'foreign_key' => $fkName,
                    'details' => "Foreign key exists in LOCALHOST but not in PRODUCTION"
                ];
            } else {
                $prodFK = $prodFKs[$fkName];
                if (json_encode($localFK) !== json_encode($prodFK)) {
                    $differences[] = [
                        'type' => 'foreign_key_difference',
                        'table' => $tableName,
                        'foreign_key' => $fkName,
                        'localhost' => $localFK,
                        'production' => $prodFK
                    ];
                }
            }
        }

        foreach ($prodFKs as $fkName => $prodFK) {
            if (!isset($localFKs[$fkName])) {
                $differences[] = [
                    'type' => 'missing_foreign_key_localhost',
                    'table' => $tableName,
                    'foreign_key' => $fkName,
                    'details' => "Foreign key exists in PRODUCTION but not in LOCALHOST"
                ];
            }
        }

        return $differences;
    }

    /**
     * Compare tables
     */
    public function compareTables()
    {
        echo "Comparing tables...\n";
        echo str_repeat("=", 80) . "\n";

        $localTables = $this->getTables($this->localhostConn);
        $prodTables = $this->getTables($this->productionConn);

        $allTables = array_unique(array_merge($localTables, $prodTables));
        sort($allTables);

        $tableDifferences = [];

        foreach ($allTables as $tableName) {
            $inLocal = in_array($tableName, $localTables);
            $inProd = in_array($tableName, $prodTables);

            if (!$inLocal) {
                $tableDifferences[] = [
                    'type' => 'missing_table_localhost',
                    'table' => $tableName,
                    'details' => "Table exists in PRODUCTION but not in LOCALHOST"
                ];
                continue;
            }

            if (!$inProd) {
                $tableDifferences[] = [
                    'type' => 'missing_table_production',
                    'table' => $tableName,
                    'details' => "Table exists in LOCALHOST but not in PRODUCTION"
                ];
                continue;
            }

            // Compare columns
            $localColumns = $this->getTableColumns($this->localhostConn, $tableName);
            $prodColumns = $this->getTableColumns($this->productionConn, $tableName);
            $columnDiffs = $this->compareColumns($localColumns, $prodColumns, $tableName);
            $tableDifferences = array_merge($tableDifferences, $columnDiffs);

            // Compare indexes
            $localIndexes = $this->getTableIndexes($this->localhostConn, $tableName);
            $prodIndexes = $this->getTableIndexes($this->productionConn, $tableName);
            $indexDiffs = $this->compareIndexes($localIndexes, $prodIndexes, $tableName);
            $tableDifferences = array_merge($tableDifferences, $indexDiffs);

            // Compare foreign keys
            $localFKs = $this->getForeignKeys($this->localhostConn, $tableName);
            $prodFKs = $this->getForeignKeys($this->productionConn, $tableName);
            $fkDiffs = $this->compareForeignKeys($localFKs, $prodFKs, $tableName);
            $tableDifferences = array_merge($tableDifferences, $fkDiffs);

            // Compare CREATE TABLE statements (for overall structure)
            $localCreate = $this->getTableStructure($this->localhostConn, $tableName);
            $prodCreate = $this->getTableStructure($this->productionConn, $tableName);

            if ($localCreate && $prodCreate) {
                $localNormalized = $this->normalizeCreateTable($localCreate);
                $prodNormalized = $this->normalizeCreateTable($prodCreate);

                if ($localNormalized !== $prodNormalized) {
                    // Only add if we haven't already found column/index differences
                    if (empty($columnDiffs) && empty($indexDiffs) && empty($fkDiffs)) {
                        $tableDifferences[] = [
                            'type' => 'table_structure_difference',
                            'table' => $tableName,
                            'details' => "Table structure differs (check normalized CREATE statements)"
                        ];
                    }
                }
            }

            if (empty($columnDiffs) && empty($indexDiffs) && empty($fkDiffs)) {
                echo "✓ Table '{$tableName}' - MATCH\n";
            } else {
                echo "✗ Table '{$tableName}' - DIFFERENCES FOUND\n";
            }
        }

        $this->differences['tables'] = $tableDifferences;
        echo "\n";
    }

    /**
     * Compare views
     */
    public function compareViews()
    {
        echo "Comparing views...\n";
        echo str_repeat("=", 80) . "\n";

        $localViews = $this->getViews($this->localhostConn);
        $prodViews = $this->getViews($this->productionConn);

        $allViews = array_unique(array_merge(array_keys($localViews), array_keys($prodViews)));
        sort($allViews);

        $viewDifferences = [];

        foreach ($allViews as $viewName) {
            $inLocal = isset($localViews[$viewName]);
            $inProd = isset($prodViews[$viewName]);

            if (!$inLocal) {
                $viewDifferences[] = [
                    'type' => 'missing_view_localhost',
                    'view' => $viewName,
                    'details' => "View exists in PRODUCTION but not in LOCALHOST"
                ];
                echo "✗ View '{$viewName}' - MISSING IN LOCALHOST\n";
                continue;
            }

            if (!$inProd) {
                $viewDifferences[] = [
                    'type' => 'missing_view_production',
                    'view' => $viewName,
                    'details' => "View exists in LOCALHOST but not in PRODUCTION"
                ];
                echo "✗ View '{$viewName}' - MISSING IN PRODUCTION\n";
                continue;
            }

            // Normalize view definitions for comparison
            $localDef = preg_replace('/\s+/', ' ', trim($localViews[$viewName]));
            $prodDef = preg_replace('/\s+/', ' ', trim($prodViews[$viewName]));

            if ($localDef !== $prodDef) {
                $viewDifferences[] = [
                    'type' => 'view_definition_difference',
                    'view' => $viewName,
                    'localhost' => $localViews[$viewName],
                    'production' => $prodViews[$viewName]
                ];
                echo "✗ View '{$viewName}' - DEFINITION DIFFERS\n";
            } else {
                echo "✓ View '{$viewName}' - MATCH\n";
            }
        }

        $this->differences['views'] = $viewDifferences;
        echo "\n";
    }

    /**
     * Generate detailed report
     */
    public function generateReport()
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "DETAILED DIFFERENCE REPORT\n";
        echo str_repeat("=", 80) . "\n\n";

        $totalIssues = 0;

        // Table differences
        if (!empty($this->differences['tables'])) {
            echo "TABLE DIFFERENCES:\n";
            echo str_repeat("-", 80) . "\n";
            foreach ($this->differences['tables'] as $diff) {
                $totalIssues++;
                echo "\n[{$diff['type']}] Table: {$diff['table']}\n";

                if (isset($diff['column'])) {
                    echo "  Column: {$diff['column']}\n";
                }
                if (isset($diff['index'])) {
                    echo "  Index: {$diff['index']}\n";
                }
                if (isset($diff['foreign_key'])) {
                    echo "  Foreign Key: {$diff['foreign_key']}\n";
                }
                if (isset($diff['property'])) {
                    echo "  Property: {$diff['property']}\n";
                    echo "    Localhost:   {$diff['localhost']}\n";
                    echo "    Production: {$diff['production']}\n";
                }
                if (isset($diff['details'])) {
                    echo "  Details: {$diff['details']}\n";
                }
            }
            echo "\n";
        }

        // View differences
        if (!empty($this->differences['views'])) {
            echo "VIEW DIFFERENCES:\n";
            echo str_repeat("-", 80) . "\n";
            foreach ($this->differences['views'] as $diff) {
                $totalIssues++;
                echo "\n[{$diff['type']}] View: {$diff['view']}\n";
                if (isset($diff['details'])) {
                    echo "  Details: {$diff['details']}\n";
                }
                if (isset($diff['localhost']) && isset($diff['production'])) {
                    echo "  Localhost Definition:\n";
                    echo "    " . wordwrap($diff['localhost'], 76, "\n    ") . "\n";
                    echo "  Production Definition:\n";
                    echo "    " . wordwrap($diff['production'], 76, "\n    ") . "\n";
                }
            }
            echo "\n";
        }

        // Summary
        echo str_repeat("=", 80) . "\n";
        echo "SUMMARY\n";
        echo str_repeat("=", 80) . "\n";
        echo "Total Issues Found: {$totalIssues}\n";

        if ($totalIssues === 0) {
            echo "\n✓ SUCCESS: Database schemas MATCH!\n";
        } else {
            echo "\n✗ WARNING: Database schemas DO NOT MATCH!\n";
            echo "Please review the differences above and sync the schemas.\n";
        }

        echo str_repeat("=", 80) . "\n";
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
echo "DATABASE SCHEMA COMPARISON TOOL\n";
echo "Localhost vs Production\n";
echo str_repeat("=", 80) . "\n\n";

// Check if production config is set
if ($productionConfig['hostname'] === 'your-production-host.com') {
    echo "ERROR: Please update the production database credentials in the script!\n";
    echo "Edit the \$productionConfig array at the top of this file.\n";
    exit(1);
}

$comparator = new DatabaseSchemaComparator($localhostConfig, $productionConfig);

try {
    $comparator->connect();
    $comparator->compareTables();
    $comparator->compareViews();
    $comparator->generateReport();
    $comparator->close();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    $comparator->close();
    exit(1);
}

echo "\nDone!\n";
