<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Invoice extends Model {
    protected $fillable = ['invoice_number', 'client_id', 'status', 'issue_date', 'due_date', 'subtotal', 'tax_rate', 'tax_amount', 'discount_type', 'discount_value', 'discount_amount', 'total', 'amount_paid', 'currency', 'notes', 'terms', 'pdf_path', 'sent_at', 'sent_via', 'viewed_at', 'paid_at', 'recurring_id', 'created_by'];
    protected $casts = ['issue_date' => 'date', 'due_date' => 'date', 'sent_at' => 'datetime', 'viewed_at' => 'datetime', 'paid_at' => 'datetime'];
    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function payments() { return $this->hasMany(Payment::class); }
}
