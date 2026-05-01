<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model {
    protected $fillable = ['invoice_id', 'amount', 'payment_method', 'payment_date', 'reference', 'notes'];
    public $timestamps = false;
    const UPDATED_AT = null;
    protected $casts = [
        'payment_date' => 'date', 
        'created_at' => 'datetime',
        'amount' => 'decimal:2'
    ];
    
    public function invoice() { 
        return $this->belongsTo(Invoice::class); 
    }
}
