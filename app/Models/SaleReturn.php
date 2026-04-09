<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'return_no',
        'company_id',
        'original_sale_id',
        'customer_id',
        'total_amount',
        'reason',
    ];

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function originalSale()
    {
        return $this->belongsTo(SaleOrder::class, 'original_sale_id');
    }

    public function customer()
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }
}
