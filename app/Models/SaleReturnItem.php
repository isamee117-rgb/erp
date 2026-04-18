<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturnItem extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'sale_return_id',
        'product_id',
        'uom_id',
        'uom_multiplier',
        'quantity',
        'unit_price',
        'discount',
        'total_line_price',
    ];
}
