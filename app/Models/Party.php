<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'parties';

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'type',
        'name',
        'phone',
        'email',
        'address',
        'sub_type',
        'payment_terms',
        'credit_limit',
        'bank_details',
        'category',
        'opening_balance',
        'current_balance',
        // dynamic fields
        'make_model_year',
        'vehicle_reg_number',
        'vin_chassis_number',
        'engine_number',
        'last_odometer_reading',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function saleOrders()
    {
        return $this->hasMany(SaleOrder::class, 'customer_id');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'vendor_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function saleReturns()
    {
        return $this->hasMany(SaleReturn::class, 'customer_id');
    }

    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class, 'vendor_id');
    }
}
