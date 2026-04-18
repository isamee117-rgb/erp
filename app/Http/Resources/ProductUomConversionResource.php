<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductUomConversionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'productId'             => $this->product_id,
            'uomId'                 => $this->uom_id,
            'uomName'               => $this->uom?->name ?? '',
            'multiplier'            => (float) $this->multiplier,
            'isDefaultPurchaseUnit' => (bool)  $this->is_default_purchase_unit,
            'isDefaultSalesUnit'    => (bool)  $this->is_default_sales_unit,
        ];
    }
}
