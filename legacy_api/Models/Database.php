<?php
namespace App\Models;

/**
 * Database connection singleton using PDO.
 */
class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo;

    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        $this->pdo = new \PDO($dsn, $config['username'], $config['password'], $config['options']);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a query and return all results.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a query and return a single row.
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Execute a query and return the number of affected rows.
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Insert a row and return the last insert ID.
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_map(fn($col) => "`$col`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
        $this->execute($sql, array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update rows matching conditions.
     */
    public function update(string $table, array $data, array $where): int
    {
        $setClauses = implode(', ', array_map(fn($col) => "`$col` = ?", array_keys($data)));
        $whereClauses = implode(' AND ', array_map(fn($col) => "`$col` = ?", array_keys($where)));

        $sql = "UPDATE `$table` SET $setClauses WHERE $whereClauses";
        return $this->execute($sql, array_merge(array_values($data), array_values($where)));
    }

    /**
     * Delete rows matching conditions.
     */
    public function delete(string $table, array $where): int
    {
        $whereClauses = implode(' AND ', array_map(fn($col) => "`$col` = ?", array_keys($where)));
        $sql = "DELETE FROM `$table` WHERE $whereClauses";
        return $this->execute($sql, array_values($where));
    }

    /**
     * Get the count of rows matching conditions.
     */
    public function count(string $table, array $where = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM `$table`";
        $params = [];

        if (!empty($where)) {
            $whereClauses = implode(' AND ', array_map(fn($col) => "`$col` = ?", array_keys($where)));
            $sql .= " WHERE $whereClauses";
            $params = array_values($where);
        }

        $result = $this->fetchOne($sql, $params);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Begin a transaction.
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction.
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback a transaction.
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }
}
