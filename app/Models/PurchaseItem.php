<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'purchase_order_id',
        'product_id',
        'quantity',
        'unit_cost',
        'total_line_cost',
        'received_quantity',
    ];

    protected $casts = [
        'received_quantity' => 'integer',
    ];
}
