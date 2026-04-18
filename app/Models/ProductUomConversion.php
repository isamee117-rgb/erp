<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUomConversion extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'product_uom_conversions';

    protected $fillable = [
        'id',
        'product_id',
        'uom_id',
        'multiplier',
        'is_default_purchase_unit',
        'is_default_sales_unit',
    ];

    protected $casts = [
        'multiplier'               => 'float',
        'is_default_purchase_unit' => 'boolean',
        'is_default_sales_unit'    => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function uom()
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }
}
