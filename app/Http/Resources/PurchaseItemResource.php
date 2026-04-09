<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'productId'        => $this->product_id,
            'quantity'         => $this->quantity,
            'unitCost'         => (float) $this->unit_cost,
            'totalLineCost'    => (float) $this->total_line_cost,
            'receivedQuantity' => (int)   ($this->received_quantity ?? 0),
            'returnedQuantity' => (int)   ($this->returned_quantity ?? 0),
        ];
    }
}
