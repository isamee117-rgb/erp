<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'productId'      => $this->product_id,
            'quantity'       => $this->quantity,
            'unitPrice'      => (float) $this->unit_price,
            'discount'       => (float) ($this->discount ?? 0),
            'totalLinePrice' => (float) $this->total_line_price,
        ];
    }
}
