<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'company_id', 'type', 'prefix', 'next_number', 'is_locked',
    ];

    protected $casts = [
        'next_number' => 'integer',
        'is_locked' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
