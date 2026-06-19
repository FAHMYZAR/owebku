<?php
namespace Modules\Auth;

use Core\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id_user';

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE username = ?";
        $stmt = $this->query($sql, [$username]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = $this->query($sql, [$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Create new user with hashed password
     */
    public function createUser(array $data): int
    {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        return $this->insert($data);
    }
}
