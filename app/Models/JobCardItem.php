<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCardItem extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'job_card_id',
        'item_type',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'discount',
        'total_line_price',
    ];

    protected $casts = [
        'quantity'         => 'float',
        'unit_price'       => 'float',
        'discount'         => 'float',
        'total_line_price' => 'float',
    ];

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
