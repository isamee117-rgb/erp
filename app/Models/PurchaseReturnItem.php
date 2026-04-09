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
        'quantity',
        'unit_cost',
        'total_line_cost',
    ];
}
