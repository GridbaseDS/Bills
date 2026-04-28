<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RecurringInvoiceItem extends Model {
    protected $fillable = ['recurring_id', 'description', 'quantity', 'unit_price', 'amount', 'sort_order'];
    public  = false;
}
