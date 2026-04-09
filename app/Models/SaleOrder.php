<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleOrder extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'invoice_no',
        'company_id',
        'customer_id',
        'payment_method',
        'total_amount',
        'is_returned',
        'return_status',
    ];

    protected function casts(): array
    {
        return [
            'is_returned' => 'boolean',
        ];
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }

    public function scopePending($query)
    {
        return $query->where('is_returned', false)
                     ->where('return_status', 'none');
    }

    public function scopeReturned($query)
    {
        return $query->where('is_returned', true);
    }

    public function scopePartiallyReturned($query)
    {
        return $query->where('return_status', 'partial');
    }

    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForCustomer($query, string $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}
