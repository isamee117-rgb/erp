<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'entry_no',
        'date',
        'description',
        'reference_type',
        'reference_id',
        'is_posted',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_posted' => 'boolean',
            'date'      => 'date',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}