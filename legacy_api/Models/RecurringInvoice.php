<?php
namespace App\Models;

class RecurringInvoice
{
    private Database $db;

    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = ['1=1']; $params = [];
        if (!empty($filters['status'])) { $where[] = "r.status = ?"; $params[] = $filters['status']; }
        if (!empty($filters['client_id'])) { $where[] = "r.client_id = ?"; $params[] = (int)$filters['client_id']; }
        $wc = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        $total = $this->db->fetchOne("SELECT COUNT(*) as total FROM recurring_invoices r JOIN clients c ON r.client_id=c.id WHERE $wc", $params)['total'];
        $data = $this->db->fetchAll("SELECT r.*, c.contact_name, c.company_name, c.email as client_email, (SELECT COUNT(*) FROM invoices WHERE recurring_id=r.id) as generated_count FROM recurring_invoices r JOIN clients c ON r.client_id=c.id WHERE $wc ORDER BY r.created_at DESC LIMIT $perPage OFFSET $offset", $params);
        return ['data'=>$data,'total'=>(int)$total,'page'=>$page,'per_page'=>$perPage,'total_pages'=>(int)ceil($total/$perPage)];
    }

    public function getById(int $id): ?array
    {
        $r = $this->db->fetchOne("SELECT r.*, c.contact_name, c.company_name, c.email as client_email FROM recurring_invoices r JOIN clients c ON r.client_id=c.id WHERE r.id=?", [$id]);
        if ($r) {
            $r['items'] = $this->db->fetchAll("SELECT * FROM recurring_invoice_items WHERE recurring_id=? ORDER BY sort_order", [$id]);
            $r['invoices'] = $this->db->fetchAll("SELECT id, invoice_number, status, issue_date, total FROM invoices WHERE recurring_id=? ORDER BY issue_date DESC", [$id]);
        }
        return $r;
    }

    public function create(array $data, array $items): int
    {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $sub = 0;
            foreach ($items as $i) $sub += $i['quantity'] * $i['unit_price'];

            $rid = $db->insert('recurring_invoices', [
                'client_id'=>(int)$data['client_id'], 'frequency'=>$data['frequency']??'monthly', 'status'=>'active',
                'start_date'=>$data['start_date']??date('Y-m-d'), 'end_date'=>$data['end_date']??null,
                'next_issue_date'=>$data['next_issue_date']??$data['start_date']??date('Y-m-d'),
                'occurrences_limit'=>$data['occurrences_limit']??null, 'subtotal'=>$sub,
                'tax_rate'=>$data['tax_rate']??0, 'currency'=>$data['currency']??'USD',
                'auto_send'=>$data['auto_send']??0, 'send_via'=>$data['send_via']??'email',
                'notes'=>$data['notes']??null, 'terms'=>$data['terms']??null, 'created_by'=>$data['created_by']??null,
            ]);

            foreach ($items as $idx => $i) {
                $db->insert('recurring_invoice_items', ['recurring_id'=>$rid,'description'=>$i['description'],'quantity'=>$i['quantity'],'unit_price'=>$i['unit_price'],'amount'=>$i['quantity']*$i['unit_price'],'sort_order'=>$idx]);
            }
            $db->insert('activity_log', ['entity_type'=>'recurring','entity_id'=>$rid,'action'=>'created','description'=>"Recurring invoice created ({$data['frequency']})",'user_id'=>$data['created_by']??null]);
            $db->commit();
            return $rid;
        } catch (\Exception $e) { $db->rollback(); throw $e; }
    }

    public function update(int $id, array $data, array $items = []): int
    {
        $db = $this->db;
        $db->beginTransaction();
        try {
            if (!empty($items)) {
                $sub = 0;
                foreach ($items as $i) $sub += $i['quantity'] * $i['unit_price'];
                $data['subtotal'] = $sub;
                $db->delete('recurring_invoice_items', ['recurring_id'=>$id]);
                foreach ($items as $idx => $i) {
                    $db->insert('recurring_invoice_items', ['recurring_id'=>$id,'description'=>$i['description'],'quantity'=>$i['quantity'],'unit_price'=>$i['unit_price'],'amount'=>$i['quantity']*$i['unit_price'],'sort_order'=>$idx]);
                }
            }
            $allowed = ['client_id','frequency','status','start_date','end_date','next_issue_date','occurrences_limit','subtotal','tax_rate','currency','auto_send','send_via','notes','terms'];
            $result = $db->update('recurring_invoices', array_intersect_key($data, array_flip($allowed)), ['id'=>$id]);
            $db->commit();
            return $result;
        } catch (\Exception $e) { $db->rollback(); throw $e; }
    }

    public function toggleStatus(int $id, string $status): int
    {
        return $this->db->update('recurring_invoices', ['status'=>$status], ['id'=>$id]);
    }

    public function getDueForGeneration(): array
    {
        return $this->db->fetchAll("SELECT r.*, c.contact_name, c.email as client_email, c.whatsapp as client_whatsapp FROM recurring_invoices r JOIN clients c ON r.client_id=c.id WHERE r.status='active' AND r.next_issue_date <= CURDATE() AND (r.end_date IS NULL OR r.end_date >= CURDATE()) AND (r.occurrences_limit IS NULL OR r.occurrences_count < r.occurrences_limit)");
    }

    public function calculateNextDate(string $frequency, string $fromDate): string
    {
        $intervals = ['weekly'=>'+1 week','biweekly'=>'+2 weeks','monthly'=>'+1 month','quarterly'=>'+3 months','semiannual'=>'+6 months','annual'=>'+1 year'];
        return date('Y-m-d', strtotime($intervals[$frequency] ?? '+1 month', strtotime($fromDate)));
    }

    public function incrementCount(int $id, string $nextDate): int
    {
        return $this->db->execute("UPDATE recurring_invoices SET occurrences_count = occurrences_count + 1, next_issue_date = ? WHERE id = ?", [$nextDate, $id]);
    }

    public function delete(int $id): int { return $this->db->update('recurring_invoices', ['status'=>'cancelled'], ['id'=>$id]); }
}
