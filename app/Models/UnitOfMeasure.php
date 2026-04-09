<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitOfMeasure extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'units_of_measure';

    protected $fillable = [
        'id',
        'company_id',
        'name',
    ];
}
