<?php
namespace App\Models;

class Setting
{
    private Database $db;

    public function __construct() { $this->db = Database::getInstance(); }

    public function get(string $key, string $default = ''): string
    {
        $row = $this->db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        return $row ? ($row['setting_value'] ?? $default) : $default;
    }

    public function set(string $key, string $value, string $group = 'general'): void
    {
        $exists = $this->db->fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
        if ($exists) {
            $this->db->update('settings', ['setting_value' => $value], ['setting_key' => $key]);
        } else {
            $this->db->insert('settings', ['setting_key' => $key, 'setting_value' => $value, 'setting_group' => $group]);
        }
    }

    public function getGroup(string $group): array
    {
        $rows = $this->db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_group = ?", [$group]);
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
        return $result;
    }

    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT setting_key, setting_value, setting_group FROM settings ORDER BY setting_group, setting_key");
    }

    public function updateBulk(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function getCompanyInfo(): array
    {
        return $this->getGroup('company');
    }
}
