<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Quote extends Model {
    protected $fillable = ['quote_number', 'client_id', 'status', 'issue_date', 'expiry_date', 'subtotal', 'tax_rate', 'tax_amount', 'discount_type', 'discount_value', 'discount_amount', 'total', 'currency', 'notes', 'terms', 'converted_invoice_id', 'pdf_path', 'sent_at', 'sent_via', 'viewed_at', 'created_by'];
    protected $casts = ['issue_date' => 'date', 'expiry_date' => 'date', 'sent_at' => 'datetime', 'viewed_at' => 'datetime'];
    public function items() { return $this->hasMany(QuoteItem::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function invoice() { return $this->belongsTo(Invoice::class, 'converted_invoice_id'); }
}
