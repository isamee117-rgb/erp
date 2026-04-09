<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'sale_order_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount',
        'total_line_price',
        'cogs',
    ];

    protected $casts = [
        'cogs' => 'float',
    ];

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
