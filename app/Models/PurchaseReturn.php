<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'return_no',
        'company_id',
        'original_purchase_id',
        'vendor_id',
        'total_amount',
        'reason',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function originalPurchase()
    {
        return $this->belongsTo(PurchaseOrder::class, 'original_purchase_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Party::class, 'vendor_id');
    }
}
