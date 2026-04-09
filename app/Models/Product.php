<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'sku',
        'barcode',
        'item_number',
        'name',
        'type',
        'uom',
        'category_id',
        'current_stock',
        'reorder_level',
        'unit_cost',
        'unit_price',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('current_stock', '<=', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->where('current_stock', '>', 0)
                     ->whereColumn('current_stock', '<=', 'reorder_level');
    }

    public function scopeInStock($query)
    {
        return $query->whereColumn('current_stock', '>', 'reorder_level');
    }

    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
