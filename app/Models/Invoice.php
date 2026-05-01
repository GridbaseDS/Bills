<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Invoice extends Model {
    protected $fillable = ['invoice_number', 'client_id', 'status', 'issue_date', 'due_date', 'subtotal', 'tax_rate', 'tax_amount', 'discount_type', 'discount_value', 'discount_amount', 'total', 'amount_paid', 'currency', 'notes', 'terms', 'pdf_path', 'sent_at', 'sent_via', 'viewed_at', 'paid_at', 'recurring_id', 'created_by', 'payment_token', 'payment_token_expires_at'];
    protected $casts = ['issue_date' => 'date', 'due_date' => 'date', 'sent_at' => 'datetime', 'viewed_at' => 'datetime', 'paid_at' => 'datetime', 'payment_token_expires_at' => 'datetime'];
    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    
    /**
     * Generate a unique payment token for this invoice
     */
    public function generatePaymentToken($expiresInDays = 30) {
        $this->payment_token = Str::random(64);
        $this->payment_token_expires_at = now()->addDays($expiresInDays);
        $this->save();
        return $this->payment_token;
    }
    
    /**
     * Get the payment URL for this invoice
     */
    public function getPaymentUrl() {
        if (!$this->payment_token) {
            $this->generatePaymentToken();
        }
        return url("/pay/{$this->payment_token}");
    }
    
    /**
     * Check if the payment token is valid
     */
    public function isPaymentTokenValid() {
        return $this->payment_token && 
               $this->payment_token_expires_at && 
               $this->payment_token_expires_at->isFuture() &&
               !in_array($this->status, ['paid', 'cancelled']);
    }
    
    /**
     * Get the remaining balance
     */
    public function getRemainingBalance() {
        return $this->total - $this->amount_paid;
    }
}
