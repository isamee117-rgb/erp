<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $incrementing = true;

    protected $fillable = [
        'key',
        'value',
    ];
}
