<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'purchase_return_id',
        'product_id',
        'uom_id',
        'uom_multiplier',
        'quantity',
        'unit_cost',
        'total_line_cost',
    ];
}
