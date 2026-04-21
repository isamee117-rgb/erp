<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'status',
        'max_user_limit',
        'registration_payment',
        'saas_plan',
        'info_name',
        'info_tagline',
        'info_address',
        'info_phone',
        'info_email',
        'info_website',
        'info_tax_id',
        'info_logo_url',
        'costing_method',
        'industry',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function customRoles()
    {
        return $this->hasMany(CustomRole::class);
    }

    public function parties()
    {
        return $this->hasMany(Party::class);
    }

    public function saleOrders()
    {
        return $this->hasMany(SaleOrder::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function entityTypes()
    {
        return $this->hasMany(EntityType::class);
    }

    public function businessCategories()
    {
        return $this->hasMany(BusinessCategory::class);
    }
}
