<?php
namespace App\Models;

class Invoice
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
            $where[] = "(i.invoice_number LIKE ? OR c.contact_name LIKE ? OR c.company_name LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search]);
        }

        if (!empty($filters['status'])) {
            $where[] = "i.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['client_id'])) {
            $where[] = "i.client_id = ?";
            $params[] = (int) $filters['client_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "i.issue_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "i.issue_date <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) as total FROM invoices i 
                     JOIN clients c ON i.client_id = c.id 
                     WHERE $whereClause";
        $total = $this->db->fetchOne($countSql, $params)['total'];

        $sql = "SELECT i.*, c.contact_name, c.company_name, c.email as client_email
                FROM invoices i
                JOIN clients c ON i.client_id = c.id
                WHERE $whereClause
                ORDER BY i.created_at DESC
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
        $invoice = $this->db->fetchOne(
            "SELECT i.*, c.contact_name, c.company_name, c.email as client_email,
                    c.phone as client_phone, c.whatsapp as client_whatsapp,
                    c.address_line1, c.address_line2, c.city, c.state,
                    c.postal_code, c.country, c.tax_id as client_tax_id
             FROM invoices i
             JOIN clients c ON i.client_id = c.id
             WHERE i.id = ?",
            [$id]
        );

        if ($invoice) {
            $invoice['items'] = $this->getItems($id);
            $invoice['payments'] = $this->getPayments($id);
        }

        return $invoice;
    }

    public function getItems(int $invoiceId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order ASC",
            [$invoiceId]
        );
    }

    public function getPayments(int $invoiceId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC",
            [$invoiceId]
        );
    }

    public function create(array $data, array $items): int
    {
        $db = $this->db;
        $db->beginTransaction();

        try {
            // Generate invoice number
            $setting = new Setting();
            $prefix = $setting->get('invoice_prefix', 'GBS-');
            $nextNum = (int) $setting->get('invoice_next_number', '1001');
            $invoiceNumber = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            // Calculate totals
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $taxRate = (float) ($data['tax_rate'] ?? 0);
            $taxAmount = $subtotal * ($taxRate / 100);

            $discountAmount = 0;
            if (!empty($data['discount_value'])) {
                if (($data['discount_type'] ?? 'fixed') === 'percentage') {
                    $discountAmount = $subtotal * ($data['discount_value'] / 100);
                } else {
                    $discountAmount = (float) $data['discount_value'];
                }
            }

            $total = $subtotal + $taxAmount - $discountAmount;

            $invoiceData = [
                'invoice_number'  => $invoiceNumber,
                'client_id'       => (int) $data['client_id'],
                'status'          => 'draft',
                'issue_date'      => $data['issue_date'] ?? date('Y-m-d'),
                'due_date'        => $data['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
                'subtotal'        => $subtotal,
                'tax_rate'        => $taxRate,
                'tax_amount'      => $taxAmount,
                'discount_type'   => $data['discount_type'] ?? null,
                'discount_value'  => $data['discount_value'] ?? 0,
                'discount_amount' => $discountAmount,
                'total'           => $total,
                'amount_paid'     => 0,
                'currency'        => $data['currency'] ?? 'USD',
                'notes'           => $data['notes'] ?? null,
                'terms'           => $data['terms'] ?? null,
                'created_by'      => $data['created_by'] ?? null,
            ];

            $invoiceId = $db->insert('invoices', $invoiceData);

            // Insert items
            foreach ($items as $index => $item) {
                $amount = $item['quantity'] * $item['unit_price'];
                $db->insert('invoice_items', [
                    'invoice_id'  => $invoiceId,
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'amount'      => $amount,
                    'sort_order'  => $index,
                ]);
            }

            // Increment invoice number
            $setting->set('invoice_next_number', (string) ($nextNum + 1));

            // Log activity
            $db->insert('activity_log', [
                'entity_type' => 'invoice',
                'entity_id'   => $invoiceId,
                'action'      => 'created',
                'description' => "Invoice $invoiceNumber created",
                'user_id'     => $data['created_by'] ?? null,
            ]);

            $db->commit();
            return $invoiceId;

        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function update(int $id, array $data, array $items = []): int
    {
        $db = $this->db;
        $db->beginTransaction();

        try {
            // Recalculate totals if items provided
            if (!empty($items)) {
                $subtotal = 0;
                foreach ($items as $item) {
                    $subtotal += $item['quantity'] * $item['unit_price'];
                }

                $taxRate = (float) ($data['tax_rate'] ?? 0);
                $taxAmount = $subtotal * ($taxRate / 100);

                $discountAmount = 0;
                if (!empty($data['discount_value'])) {
                    if (($data['discount_type'] ?? 'fixed') === 'percentage') {
                        $discountAmount = $subtotal * ($data['discount_value'] / 100);
                    } else {
                        $discountAmount = (float) $data['discount_value'];
                    }
                }

                $data['subtotal'] = $subtotal;
                $data['tax_amount'] = $taxAmount;
                $data['discount_amount'] = $discountAmount;
                $data['total'] = $subtotal + $taxAmount - $discountAmount;

                // Replace items
                $db->delete('invoice_items', ['invoice_id' => $id]);
                foreach ($items as $index => $item) {
                    $amount = $item['quantity'] * $item['unit_price'];
                    $db->insert('invoice_items', [
                        'invoice_id'  => $id,
                        'description' => $item['description'],
                        'quantity'    => $item['quantity'],
                        'unit_price'  => $item['unit_price'],
                        'amount'      => $amount,
                        'sort_order'  => $index,
                    ]);
                }
            }

            $allowed = [
                'client_id', 'status', 'issue_date', 'due_date', 'subtotal',
                'tax_rate', 'tax_amount', 'discount_type', 'discount_value',
                'discount_amount', 'total', 'currency', 'notes', 'terms'
            ];
            $filtered = array_intersect_key($data, array_flip($allowed));

            $result = $db->update('invoices', $filtered, ['id' => $id]);

            $db->commit();
            return $result;

        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function updateStatus(int $id, string $status): int
    {
        $extra = [];
        if ($status === 'sent') {
            $extra['sent_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'paid') {
            $extra['paid_at'] = date('Y-m-d H:i:s');
        }

        $data = array_merge(['status' => $status], $extra);
        return $this->db->update('invoices', $data, ['id' => $id]);
    }

    public function addPayment(int $invoiceId, array $paymentData): int
    {
        $db = $this->db;
        $db->beginTransaction();

        try {
            $paymentId = $db->insert('payments', [
                'invoice_id'     => $invoiceId,
                'amount'         => $paymentData['amount'],
                'payment_method' => $paymentData['payment_method'] ?? 'bank_transfer',
                'payment_date'   => $paymentData['payment_date'] ?? date('Y-m-d'),
                'reference'      => $paymentData['reference'] ?? null,
                'notes'          => $paymentData['notes'] ?? null,
            ]);

            // Update amount paid
            $totalPaid = $this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE invoice_id = ?",
                [$invoiceId]
            )['total'];

            $invoice = $this->db->fetchOne("SELECT total FROM invoices WHERE id = ?", [$invoiceId]);
            $status = ($totalPaid >= $invoice['total']) ? 'paid' : 'partial';

            $updateData = ['amount_paid' => $totalPaid, 'status' => $status];
            if ($status === 'paid') {
                $updateData['paid_at'] = date('Y-m-d H:i:s');
            }

            $db->update('invoices', $updateData, ['id' => $invoiceId]);

            $db->insert('activity_log', [
                'entity_type' => 'payment',
                'entity_id'   => $paymentId,
                'action'      => 'payment_received',
                'description' => "Payment of {$paymentData['amount']} received for invoice #$invoiceId",
            ]);

            $db->commit();
            return $paymentId;

        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function delete(int $id): int
    {
        return $this->db->update('invoices', ['status' => 'cancelled'], ['id' => $id]);
    }

    public function markSent(int $id, string $via = 'email'): int
    {
        return $this->db->update('invoices', [
            'status'  => 'sent',
            'sent_at' => date('Y-m-d H:i:s'),
            'sent_via'=> $via,
        ], ['id' => $id]);
    }

    public function getOverdue(): array
    {
        return $this->db->fetchAll(
            "SELECT i.*, c.contact_name, c.company_name, c.email as client_email, c.whatsapp as client_whatsapp
             FROM invoices i
             JOIN clients c ON i.client_id = c.id
             WHERE i.status IN ('sent','viewed','partial') AND i.due_date < CURDATE()
             ORDER BY i.due_date ASC"
        );
    }

    public function getDueSoon(int $days = 7): array
    {
        return $this->db->fetchAll(
            "SELECT i.*, c.contact_name, c.company_name, c.email as client_email
             FROM invoices i
             JOIN clients c ON i.client_id = c.id
             WHERE i.status IN ('sent','viewed','partial') 
               AND i.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY i.due_date ASC",
            [$days]
        );
    }
}
