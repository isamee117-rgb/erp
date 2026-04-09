<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReceiveItem extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'purchase_receive_id', 'purchase_item_id', 'product_id',
        'quantity', 'unit_cost',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'float',
    ];

    public function purchaseReceive()
    {
        return $this->belongsTo(PurchaseReceive::class);
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
