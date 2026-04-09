<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $table = 'chart_of_accounts';

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'name',
        'type',
        'sub_type',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function journalEntryLines()
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    public function accountMappings()
    {
        return $this->hasMany(AccountMapping::class, 'account_id');
    }

    public function hasTransactions(): bool
    {
        return $this->journalEntryLines()->exists();
    }
}