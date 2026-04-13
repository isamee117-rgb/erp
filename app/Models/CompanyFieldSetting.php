<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyFieldSetting extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'entity_type',
        'field_key',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
