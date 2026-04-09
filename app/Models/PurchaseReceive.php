<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReceive extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'company_id', 'purchase_order_id', 'notes',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseReceiveItem::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
