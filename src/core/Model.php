<?php
namespace Core;

use PDO;
use PDOException;
use PDOStatement;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';

    /**
     * Get Database Connection
     */
    protected function db(): PDO
    {
        return Database::getConnection();
    }

    /**
     * Jalankan query (Public wrapper)
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        return Database::query($sql, $params);
    }

    /**
     * Cari by id
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->query($sql, [$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Ambil semua
     */
    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        return $this->query($sql)->fetchAll();
    }

    /**
     * Insert data
     */
    public function insert(array $data): int
    {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        
        return (int) $this->db()->lastInsertId();
    }

    /**
     * Update data
     */
    public function update(int $id, array $data): bool
    {
        $fields = '';
        $values = [];
        foreach ($data as $key => $value) {
            $fields .= "{$key} = ?, ";
            $values[] = $value;
        }
        $fields = rtrim($fields, ', ');
        $values[] = $id;

        $sql = "UPDATE {$this->table} SET {$fields} WHERE {$this->primaryKey} = ?";
        $stmt = $this->query($sql, $values);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete data
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }
}
