<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLedger extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'inventory_ledger';

    protected $fillable = [
        'id',
        'company_id',
        'product_id',
        'transaction_type',
        'quantity_change',
        'reference_id',
    ];
}
