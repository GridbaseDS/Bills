<?php
namespace App\Controllers;

use App\Models\Database;

class DashboardController
{
    private Database $db;

    public function __construct() { $this->db = Database::getInstance(); }

    public function stats(): void
    {
        $totalRevenue = $this->db->fetchOne("SELECT COALESCE(SUM(total), 0) as val FROM invoices WHERE status = 'paid'")['val'];
        $pendingAmount = $this->db->fetchOne("SELECT COALESCE(SUM(total - amount_paid), 0) as val FROM invoices WHERE status IN ('sent','viewed','partial')")['val'];
        $overdueAmount = $this->db->fetchOne("SELECT COALESCE(SUM(total - amount_paid), 0) as val FROM invoices WHERE status IN ('sent','viewed','partial') AND due_date < CURDATE()")['val'];
        $totalClients = $this->db->fetchOne("SELECT COUNT(*) as val FROM clients WHERE is_active = 1")['val'];
        $invoiceCount = $this->db->fetchOne("SELECT COUNT(*) as val FROM invoices WHERE status != 'cancelled'")['val'];
        $paidThisMonth = $this->db->fetchOne("SELECT COALESCE(SUM(total), 0) as val FROM invoices WHERE status = 'paid' AND MONTH(paid_at) = MONTH(CURDATE()) AND YEAR(paid_at) = YEAR(CURDATE())")['val'];

        $recentInvoices = $this->db->fetchAll("SELECT i.id, i.invoice_number, i.status, i.total, i.due_date, i.currency, c.contact_name, c.company_name FROM invoices i JOIN clients c ON i.client_id = c.id WHERE i.status != 'cancelled' ORDER BY i.created_at DESC LIMIT 5");
        $overdueInvoices = $this->db->fetchAll("SELECT i.id, i.invoice_number, i.total, i.due_date, i.currency, c.contact_name, c.company_name FROM invoices i JOIN clients c ON i.client_id = c.id WHERE i.status IN ('sent','viewed','partial') AND i.due_date < CURDATE() ORDER BY i.due_date ASC LIMIT 5");

        jsonResponse([
            'stats' => [
                'total_revenue'  => (float)$totalRevenue,
                'pending_amount' => (float)$pendingAmount,
                'overdue_amount' => (float)$overdueAmount,
                'total_clients'  => (int)$totalClients,
                'invoice_count'  => (int)$invoiceCount,
                'paid_this_month'=> (float)$paidThisMonth,
            ],
            'recent_invoices'  => $recentInvoices,
            'overdue_invoices' => $overdueInvoices,
        ]);
    }

    public function chart(string $type): void
    {
        if ($type === 'revenue') {
            $data = $this->db->fetchAll("SELECT DATE_FORMAT(paid_at, '%Y-%m') as month, COALESCE(SUM(total), 0) as amount FROM invoices WHERE status = 'paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month ASC");
            jsonResponse(['type' => 'revenue', 'data' => $data]);
        } elseif ($type === 'status') {
            $data = $this->db->fetchAll("SELECT status, COUNT(*) as count FROM invoices WHERE status != 'cancelled' GROUP BY status");
            jsonResponse(['type' => 'status', 'data' => $data]);
        } else {
            jsonResponse(['error' => 'Invalid chart type'], 400);
        }
    }

    public function activity(): void
    {
        $activities = $this->db->fetchAll("SELECT a.*, u.name as user_name FROM activity_log a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 20");
        jsonResponse($activities);
    }
}
