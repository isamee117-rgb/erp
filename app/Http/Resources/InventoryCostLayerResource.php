<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryCostLayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'companyId'         => $this->company_id,
            'productId'         => $this->product_id,
            'quantity'          => $this->quantity,
            'remainingQuantity' => $this->remaining_quantity,
            'unitCost'          => (float) $this->unit_cost,
            'referenceId'       => $this->reference_id,
            'referenceType'     => $this->reference_type,
            'createdAt'         => strtotime($this->created_at) * 1000,
        ];
    }
}
