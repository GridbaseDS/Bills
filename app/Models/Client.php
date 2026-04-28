<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Client extends Model {
    protected $fillable = ['company_name', 'contact_name', 'email', 'phone', 'whatsapp', 'tax_id', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country', 'notes', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function quotes() { return $this->hasMany(Quote::class); }
    public function recurringInvoices() { return $this->hasMany(RecurringInvoice::class); }
}
