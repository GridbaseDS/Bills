<?php
namespace App\Models;

class Client
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(c.contact_name LIKE ? OR c.company_name LIKE ? OR c.email LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search]);
        }

        if (isset($filters['is_active'])) {
            $where[] = "c.is_active = ?";
            $params[] = (int) $filters['is_active'];
        }

        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) as total FROM clients c WHERE $whereClause";
        $total = $this->db->fetchOne($countSql, $params)['total'];

        $sql = "SELECT c.*, 
                    (SELECT COUNT(*) FROM invoices WHERE client_id = c.id) as invoice_count,
                    (SELECT COALESCE(SUM(total), 0) FROM invoices WHERE client_id = c.id AND status = 'paid') as total_paid
                FROM clients c 
                WHERE $whereClause 
                ORDER BY c.created_at DESC 
                LIMIT $perPage OFFSET $offset";

        $data = $this->db->fetchAll($sql, $params);

        return [
            'data'       => $data,
            'total'      => (int) $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'total_pages'=> (int) ceil($total / $perPage),
        ];
    }

    public function getById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM clients WHERE id = ?",
            [$id]
        );
    }

    public function create(array $data): int
    {
        $allowed = [
            'company_name', 'contact_name', 'email', 'phone', 'whatsapp',
            'tax_id', 'address_line1', 'address_line2', 'city', 'state',
            'postal_code', 'country', 'notes'
        ];
        $filtered = array_intersect_key($data, array_flip($allowed));
        return $this->db->insert('clients', $filtered);
    }

    public function update(int $id, array $data): int
    {
        $allowed = [
            'company_name', 'contact_name', 'email', 'phone', 'whatsapp',
            'tax_id', 'address_line1', 'address_line2', 'city', 'state',
            'postal_code', 'country', 'notes', 'is_active'
        ];
        $filtered = array_intersect_key($data, array_flip($allowed));
        return $this->db->update('clients', $filtered, ['id' => $id]);
    }

    public function delete(int $id): int
    {
        // Soft delete - just deactivate
        return $this->db->update('clients', ['is_active' => 0], ['id' => $id]);
    }

    public function getInvoiceHistory(int $clientId): array
    {
        return $this->db->fetchAll(
            "SELECT id, invoice_number, status, issue_date, due_date, total, currency
             FROM invoices WHERE client_id = ? ORDER BY issue_date DESC",
            [$clientId]
        );
    }

    public function getQuoteHistory(int $clientId): array
    {
        return $this->db->fetchAll(
            "SELECT id, quote_number, status, issue_date, expiry_date, total, currency
             FROM quotes WHERE client_id = ? ORDER BY issue_date DESC",
            [$clientId]
        );
    }

    public function getSelectList(): array
    {
        return $this->db->fetchAll(
            "SELECT id, contact_name, company_name, email, whatsapp 
             FROM clients WHERE is_active = 1 ORDER BY contact_name ASC"
        );
    }
}
