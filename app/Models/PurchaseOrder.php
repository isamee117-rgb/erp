<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'po_no',
        'company_id',
        'vendor_id',
        'status',
        'total_amount',
        'received_amount',
        'return_status',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function receives()
    {
        return $this->hasMany(PurchaseReceive::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Party::class, 'vendor_id');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'Draft');
    }

    public function scopePartiallyReceived($query)
    {
        return $query->where('status', 'Partially Received');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'Received');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['Draft', 'Partially Received']);
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'Returned');
    }

    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForVendor($query, string $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }
}
