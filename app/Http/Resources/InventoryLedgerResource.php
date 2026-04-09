<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryLedgerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'companyId'       => $this->company_id,
            'productId'       => $this->product_id,
            'createdAt'       => strtotime($this->created_at) * 1000,
            'transactionType' => $this->transaction_type,
            'quantityChange'  => $this->quantity_change,
            'referenceId'     => $this->reference_id,
        ];
    }
}
