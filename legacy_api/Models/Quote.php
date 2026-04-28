<?php
namespace App\Models;

class Quote
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
            $where[] = "(q.quote_number LIKE ? OR c.contact_name LIKE ? OR c.company_name LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            array_push($params, $s, $s, $s);
        }
        if (!empty($filters['status'])) { $where[] = "q.status = ?"; $params[] = $filters['status']; }
        if (!empty($filters['client_id'])) { $where[] = "q.client_id = ?"; $params[] = (int)$filters['client_id']; }

        $wc = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        $total = $this->db->fetchOne("SELECT COUNT(*) as total FROM quotes q JOIN clients c ON q.client_id=c.id WHERE $wc", $params)['total'];

        $data = $this->db->fetchAll(
            "SELECT q.*, c.contact_name, c.company_name, c.email as client_email FROM quotes q JOIN clients c ON q.client_id=c.id WHERE $wc ORDER BY q.created_at DESC LIMIT $perPage OFFSET $offset",
            $params
        );

        return ['data'=>$data,'total'=>(int)$total,'page'=>$page,'per_page'=>$perPage,'total_pages'=>(int)ceil($total/$perPage)];
    }

    public function getById(int $id): ?array
    {
        $q = $this->db->fetchOne(
            "SELECT q.*, c.contact_name, c.company_name, c.email as client_email, c.phone as client_phone, c.whatsapp as client_whatsapp, c.address_line1, c.address_line2, c.city, c.state, c.postal_code, c.country, c.tax_id as client_tax_id FROM quotes q JOIN clients c ON q.client_id=c.id WHERE q.id=?",
            [$id]
        );
        if ($q) $q['items'] = $this->db->fetchAll("SELECT * FROM quote_items WHERE quote_id=? ORDER BY sort_order", [$id]);
        return $q;
    }

    public function create(array $data, array $items): int
    {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $setting = new Setting();
            $prefix = $setting->get('quote_prefix', 'QUO-');
            $nextNum = (int)$setting->get('quote_next_number', '1001');
            $num = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            $sub = 0;
            foreach ($items as $i) $sub += $i['quantity'] * $i['unit_price'];
            $tax = $sub * (($data['tax_rate'] ?? 0) / 100);
            $disc = 0;
            if (!empty($data['discount_value'])) {
                $disc = ($data['discount_type'] ?? 'fixed') === 'percentage' ? $sub * ($data['discount_value'] / 100) : (float)$data['discount_value'];
            }
            $validity = (int)$setting->get('default_quote_validity', '15');

            $qid = $db->insert('quotes', [
                'quote_number'=>$num, 'client_id'=>(int)$data['client_id'], 'status'=>'draft',
                'issue_date'=>$data['issue_date'] ?? date('Y-m-d'),
                'expiry_date'=>$data['expiry_date'] ?? date('Y-m-d', strtotime("+{$validity} days")),
                'subtotal'=>$sub, 'tax_rate'=>$data['tax_rate']??0, 'tax_amount'=>$tax,
                'discount_type'=>$data['discount_type']??null, 'discount_value'=>$data['discount_value']??0,
                'discount_amount'=>$disc, 'total'=>$sub+$tax-$disc, 'currency'=>$data['currency']??'USD',
                'notes'=>$data['notes']??null, 'terms'=>$data['terms']??null, 'created_by'=>$data['created_by']??null,
            ]);

            foreach ($items as $idx => $i) {
                $db->insert('quote_items', ['quote_id'=>$qid,'description'=>$i['description'],'quantity'=>$i['quantity'],'unit_price'=>$i['unit_price'],'amount'=>$i['quantity']*$i['unit_price'],'sort_order'=>$idx]);
            }
            $setting->set('quote_next_number', (string)($nextNum + 1));
            $db->insert('activity_log', ['entity_type'=>'quote','entity_id'=>$qid,'action'=>'created','description'=>"Quote $num created",'user_id'=>$data['created_by']??null]);
            $db->commit();
            return $qid;
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
                $tax = $sub * (($data['tax_rate'] ?? 0) / 100);
                $disc = 0;
                if (!empty($data['discount_value'])) {
                    $disc = ($data['discount_type'] ?? 'fixed') === 'percentage' ? $sub * ($data['discount_value'] / 100) : (float)$data['discount_value'];
                }
                $data['subtotal'] = $sub; $data['tax_amount'] = $tax; $data['discount_amount'] = $disc; $data['total'] = $sub + $tax - $disc;
                $db->delete('quote_items', ['quote_id'=>$id]);
                foreach ($items as $idx => $i) {
                    $db->insert('quote_items', ['quote_id'=>$id,'description'=>$i['description'],'quantity'=>$i['quantity'],'unit_price'=>$i['unit_price'],'amount'=>$i['quantity']*$i['unit_price'],'sort_order'=>$idx]);
                }
            }
            $allowed = ['client_id','status','issue_date','expiry_date','subtotal','tax_rate','tax_amount','discount_type','discount_value','discount_amount','total','currency','notes','terms'];
            $result = $db->update('quotes', array_intersect_key($data, array_flip($allowed)), ['id'=>$id]);
            $db->commit();
            return $result;
        } catch (\Exception $e) { $db->rollback(); throw $e; }
    }

    public function convertToInvoice(int $quoteId): int
    {
        $quote = $this->getById($quoteId);
        if (!$quote) throw new \Exception('Quote not found');

        $inv = new Invoice();
        $invoiceId = $inv->create([
            'client_id'=>$quote['client_id'], 'tax_rate'=>$quote['tax_rate'],
            'discount_type'=>$quote['discount_type'], 'discount_value'=>$quote['discount_value'],
            'currency'=>$quote['currency'], 'notes'=>$quote['notes'], 'terms'=>$quote['terms'], 'created_by'=>$quote['created_by'],
        ], array_map(fn($i) => ['description'=>$i['description'],'quantity'=>$i['quantity'],'unit_price'=>$i['unit_price']], $quote['items']));

        $this->db->update('quotes', ['status'=>'converted','converted_invoice_id'=>$invoiceId], ['id'=>$quoteId]);
        $this->db->insert('activity_log', ['entity_type'=>'quote','entity_id'=>$quoteId,'action'=>'converted','description'=>"Quote {$quote['quote_number']} converted to invoice"]);
        return $invoiceId;
    }

    public function delete(int $id): int { return $this->db->delete('quotes', ['id'=>$id]); }
}
