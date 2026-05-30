<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportLineMapping extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'report_type',
        'line_key',
        'account_id',
    ];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
