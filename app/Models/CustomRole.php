<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomRole extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'name',
        'description',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
