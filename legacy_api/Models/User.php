<?php
namespace App\Models;

class User
{
    private Database $db;

    public function __construct() { $this->db = Database::getInstance(); }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);
    }

    public function findById(int $id): ?array
    {
        $user = $this->db->fetchOne("SELECT id, name, email, role, avatar, last_login, created_at FROM users WHERE id = ? AND is_active = 1", [$id]);
        return $user;
    }

    public function updateLastLogin(int $id): void
    {
        $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], ['id' => $id]);
    }

    public function updatePassword(int $id, string $hashedPassword): int
    {
        return $this->db->update('users', ['password' => $hashedPassword], ['id' => $id]);
    }

    public function update(int $id, array $data): int
    {
        $allowed = ['name', 'email', 'avatar'];
        $filtered = array_intersect_key($data, array_flip($allowed));
        return $this->db->update('users', $filtered, ['id' => $id]);
    }
}
