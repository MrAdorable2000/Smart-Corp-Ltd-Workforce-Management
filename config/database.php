<?php
/**
 * Database Configuration & PDO Connection
 * Singleton pattern - returns single PDO instance
 */

if (!defined('APP_NAME')) require_once __DIR__ . '/../config/config.php';

class Database
{
    private static $instance = null;
    private $pdo;
    private $inTransaction = false;

    private function __construct()
    {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (APP_ENV === 'development') {
                die('Database Connection Failed: ' . $e->getMessage());
            }
            die('Database connection error. Please contact administrator.');
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Execute a SELECT query and return all rows
     */
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a SELECT query and return single row
     */
    public function fetch($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Execute a SELECT query and return single column
     */
    public function fetchColumn($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Execute INSERT/UPDATE/DELETE
     */
    public function execute($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Insert row and return last insert ID
     * AUTO-LOGS to audit_logs table
     */
    public function insert($table, $data)
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);
        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->pdo->prepare($sql);
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $stmt->bindValue(':' . $key, null, PDO::PARAM_NULL);
            } elseif (is_int($value)) {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } elseif (is_bool($value)) {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_BOOL);
            } else {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
            }
        }
        $stmt->execute();
        $newId = $this->pdo->lastInsertId();

        // AUTO-LOG this insert (skip logging table itself to prevent infinite loop)
        if ($table !== 'audit_logs' && class_exists('DatabaseAudit')) {
            try { DatabaseAudit::logInsert($table, $data, $newId); } catch (Exception $e) {}
        }

        return $newId;
    }

    /**
     * Update rows by condition
     * AUTO-LOGS to audit_logs (captures old values before update)
     */
    public function update($table, $data, $where, $whereParams = [])
    {
        // Capture old values BEFORE update (for audit trail)
        $oldData = null;
        if ($table !== 'audit_logs' && class_exists('DatabaseAudit')) {
            try { $oldData = DatabaseAudit::captureOldValues($table, $where, $whereParams); } catch (Exception $e) {}
        }

        $setClause = implode(', ', array_map(fn($c) => "`{$c}` = :{$c}", array_keys($data)));
        $sql = "UPDATE `{$table}` SET {$setClause} WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $stmt->bindValue(':' . $key, null, PDO::PARAM_NULL);
            } elseif (is_int($value)) {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } elseif (is_bool($value)) {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_BOOL);
            } else {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
            }
        }
        foreach ($whereParams as $key => $value) {
            if (is_null($value)) {
                $stmt->bindValue(':' . ltrim($key, ':'), null, PDO::PARAM_NULL);
            } elseif (is_int($value)) {
                $stmt->bindValue(':' . ltrim($key, ':'), $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . ltrim($key, ':'), $value, PDO::PARAM_STR);
            }
        }
        $stmt->execute();
        $rowCount = $stmt->rowCount();

        // AUTO-LOG this update
        if ($table !== 'audit_logs' && class_exists('DatabaseAudit') && $oldData) {
            $recordId = $oldData['id'] ?? ($whereParams['id'] ?? null);
            try { DatabaseAudit::logUpdate($table, $recordId, $oldData, $data); } catch (Exception $e) {}
        }

        return $rowCount;
    }

    /**
     * Delete rows by condition
     * AUTO-LOGS to audit_logs (captures old values before delete)
     */
    public function delete($table, $where, $params = [])
    {
        // Capture old values BEFORE delete (for audit trail)
        $oldData = null;
        if ($table !== 'audit_logs' && class_exists('DatabaseAudit')) {
            try { $oldData = DatabaseAudit::captureOldValuesAll($table, $where, $params); } catch (Exception $e) {}
        }

        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rowCount = $stmt->rowCount();

        // AUTO-LOG this delete
        if ($table !== 'audit_logs' && class_exists('DatabaseAudit') && !empty($oldData)) {
            foreach ($oldData as $row) {
                $recordId = $row['id'] ?? null;
                try { DatabaseAudit::logDelete($table, $recordId, $row); } catch (Exception $e) {}
            }
        }

        return $rowCount;
    }

    /**
     * Count rows
     */
    public function count($table, $where = '1=1', $params = [])
    {
        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE {$where}";
        return (int) $this->fetchColumn($sql, $params);
    }

    public function beginTransaction()
    {
        if ($this->pdo->beginTransaction()) {
            $this->inTransaction = true;
            return true;
        }
        return false;
    }

    public function commit()
    {
        if ($this->inTransaction) {
            $this->pdo->commit();
            $this->inTransaction = false;
        }
    }

    public function rollBack()
    {
        if ($this->inTransaction) {
            $this->pdo->rollBack();
            $this->inTransaction = false;
        }
    }

    public function quote($value)
    {
        return $this->pdo->quote($value);
    }

    // Prevent cloning
    private function __clone() {}
    public function __wakeup() { throw new Exception("Cannot unserialize singleton"); }
}

/**
 * Helper function to get DB instance
 */
function db() {
    return Database::getInstance();
}
