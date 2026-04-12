<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPriceTier extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'product_id',
        'company_id',
        'category',
        'price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
