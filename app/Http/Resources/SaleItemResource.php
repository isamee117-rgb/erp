<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'productId'        => $this->product_id,
            'uomId'            => $this->uom_id        ?? null,
            'uomMultiplier'    => (float) ($this->uom_multiplier ?? 1),
            'quantity'         => $this->quantity,
            'unitPrice'        => (float) $this->unit_price,
            'discount'         => (float) ($this->discount ?? 0),
            'totalLinePrice'   => (float) $this->total_line_price,
            'cogs'             => (float) ($this->cogs ?? 0),
            'returnedQuantity' => (int)   ($this->returned_quantity ?? 0),
        ];
    }
}
