<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductUomConversionResource;
use App\Http\Resources\ProductPriceTierResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'companyId'      => $this->company_id,
            'sku'            => $this->sku          ?? '',
            'barcode'        => $this->barcode      ?? '',
            'itemNumber'     => $this->item_number  ?? '',
            'name'           => $this->name,
            'type'           => $this->type,
            'uom'            => $this->uom          ?? '',
            'baseUomId'      => $this->base_uom_id  ?? null,
            'categoryId'     => $this->category_id,
            'currentStock'   => (int)   $this->current_stock,
            'reorderLevel'   => (int)   $this->reorder_level,
            'unitCost'       => (float) $this->unit_cost,
            'unitPrice'      => (float) $this->unit_price,
            'uomConversions' => ProductUomConversionResource::collection(
                $this->whenLoaded('uomConversions')
            ),
            'priceTiers' => ProductPriceTierResource::collection(
                $this->whenLoaded('priceTiers')
            ),
        ];
    }
}
