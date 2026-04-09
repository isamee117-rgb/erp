<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCostLayer extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'company_id', 'product_id', 'quantity', 'remaining_quantity',
        'unit_cost', 'reference_id', 'reference_type',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'remaining_quantity' => 'integer',
        'unit_cost' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
