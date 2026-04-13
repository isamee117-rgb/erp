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
        'base_uom_id',
        'category_id',
        'current_stock',
        'reorder_level',
        'unit_cost',
        'unit_price',
        // dynamic fields
        'brand_name',
        'size',
        'color',
        'style',
        'bin_shelf_location',
        'expiry_date',
        'batch_lot_number',
        'storage_condition',
        'drug_composition',
        'schedule_category',
        'manufacturer_name',
        'dosage_form',
        'storage_temp_req',
        'part_number',
        'vehicle_compatibility',
        'core_charge_flag',
        'warranty_period',
        'technical_specs',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function uomConversions()
    {
        return $this->hasMany(ProductUomConversion::class);
    }

    public function priceTiers()
    {
        return $this->hasMany(ProductPriceTier::class);
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
