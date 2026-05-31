<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->return_no ?? $this->id,
            'companyId'       => $this->company_id,
            'originalSaleId'  => $this->original_sale_id,
            'originalSaleNo'  => $this->whenLoaded('originalSale', fn() => $this->originalSale?->invoice_no ?? $this->original_sale_id),
            'customerId'      => $this->customer_id,
            'customerName'    => $this->whenLoaded('customer', fn() => $this->customer?->name),
            'createdAt'       => strtotime($this->created_at) * 1000,
            'totalAmount'     => (float) $this->total_amount,
            'items'           => SaleReturnItemResource::collection($this->whenLoaded('items')),
            'reason'          => $this->reason ?? '',
        ];
    }
}
