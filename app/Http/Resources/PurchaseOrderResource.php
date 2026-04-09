<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id'             => $this->po_no ?? $this->id,
            'companyId'      => $this->company_id,
            'vendorId'       => $this->vendor_id,
            'createdAt'      => strtotime($this->created_at) * 1000,
            'status'         => $this->status,
            'totalAmount'    => (float) $this->total_amount,
            'receivedAmount' => (float) ($this->received_amount ?? 0),
            'returnStatus'   => $this->return_status ?? 'none',
            'items'          => PurchaseItemResource::collection($this->whenLoaded('items')),
        ];

        if ($this->relationLoaded('receives') && $this->receives->isNotEmpty()) {
            $data['receives'] = PurchaseReceiveResource::collection($this->receives);
        }

        return $data;
    }
}
