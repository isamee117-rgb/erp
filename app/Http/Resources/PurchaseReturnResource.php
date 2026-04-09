<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->return_no ?? $this->id,
            'companyId'          => $this->company_id,
            'originalPurchaseId' => $this->original_purchase_id,
            'vendorId'           => $this->vendor_id,
            'createdAt'          => strtotime($this->created_at) * 1000,
            'totalAmount'        => (float) $this->total_amount,
            'items'              => PurchaseReturnItemResource::collection($this->whenLoaded('items')),
            'reason'             => $this->reason ?? '',
        ];
    }
}
