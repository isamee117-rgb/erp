<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $incrementing = true;

    protected $fillable = [
        'company_id',
        'key',
        'value',
    ];

    public function scopeForCompany($query, ?string $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
