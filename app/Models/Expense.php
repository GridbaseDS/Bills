<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'provider_name',
        'provider_tax_id',
        'ncf',
        'expense_date',
        'subtotal',
        'tax_amount',
        'total',
        'expense_type',
        'payment_method',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Relationship: Creator (User)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
