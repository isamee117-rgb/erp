<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->invoice_no ?? $this->id,
            'companyId'     => $this->company_id,
            'customerId'    => $this->customer_id,
            'createdAt'     => strtotime($this->created_at) * 1000,
            'paymentMethod' => $this->payment_method,
            'totalAmount'   => (float) $this->total_amount,
            'items'         => SaleItemResource::collection($this->whenLoaded('items')),
            'isReturned'    => $this->is_returned   ?? false,
            'returnStatus'  => $this->return_status ?? 'none',
        ];
    }
}
