<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RecurringInvoice extends Model {
    protected $fillable = ['client_id', 'frequency', 'status', 'start_date', 'end_date', 'next_issue_date', 'occurrences_limit', 'occurrences_count', 'subtotal', 'tax_rate', 'currency', 'auto_send', 'send_via', 'notes', 'terms', 'created_by'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'next_issue_date' => 'date', 'auto_send' => 'boolean'];
    public function items() { return $this->hasMany(RecurringInvoiceItem::class, 'recurring_id'); }
    public function client() { return $this->belongsTo(Client::class); }
    public function invoices() { return $this->hasMany(Invoice::class, 'recurring_id'); }
}
