<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCard extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'job_card_no',
        'status',
        'customer_id',
        'customer_name',
        'phone',
        'vehicle_reg_number',
        'vin_chassis_number',
        'engine_number',
        'make_model_year',
        'lift_number',
        'current_odometer',
        'payment_method',
        'parts_subtotal',
        'services_subtotal',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount',
        'grand_total',
        'created_by',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'closed_at'         => 'datetime',
            'parts_subtotal'    => 'float',
            'services_subtotal' => 'float',
            'subtotal'          => 'float',
            'discount'          => 'float',
            'discount_value'    => 'float',
            'grand_total'       => 'float',
            'current_odometer'  => 'float',
        ];
    }

    public function items()
    {
        return $this->hasMany(JobCardItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCompany(\Illuminate\Database\Eloquent\Builder $query, string $companyId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('company_id', $companyId);
    }
}
