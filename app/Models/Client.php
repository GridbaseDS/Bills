<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Client extends Model {
    protected $fillable = ['company_name', 'contact_name', 'email', 'phone', 'whatsapp', 'tax_id', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country', 'notes', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    
    protected $appends = ['name'];
    
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function quotes() { return $this->hasMany(Quote::class); }
    public function recurringInvoices() { return $this->hasMany(RecurringInvoice::class); }
    
    /**
     * Get the client's display name
     */
    public function getNameAttribute()
    {
        if (!empty($this->company_name)) {
            return $this->company_name;
        }
        if (!empty($this->contact_name)) {
            return $this->contact_name;
        }
        return 'Cliente #' . $this->id;
    }
}
