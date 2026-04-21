<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReceiveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'purchaseOrderId' => $this->purchase_order_id,
            'receiveDate'     => $this->receive_date ?? $this->created_at?->toDateString(),
            'createdAt'       => strtotime($this->created_at) * 1000,
            'notes'           => $this->notes ?? '',
            'items'           => PurchaseReceiveItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
