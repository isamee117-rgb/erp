<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReceiveItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'purchaseItemId'  => $this->purchase_item_id,
            'productId'       => $this->product_id,
            'quantity'        => (int)   $this->quantity,
            'unitCost'        => (float) $this->unit_cost,
        ];
    }
}
