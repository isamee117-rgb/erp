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
            // Dynamic fields
            'brand_name'            => $this->brand_name           ?? null,
            'size'                  => $this->size                 ?? null,
            'color'                 => $this->color                ?? null,
            'style'                 => $this->style                ?? null,
            'bin_shelf_location'    => $this->bin_shelf_location   ?? null,
            'expiry_date'           => $this->expiry_date          ?? null,
            'batch_lot_number'      => $this->batch_lot_number     ?? null,
            'storage_condition'     => $this->storage_condition    ?? null,
            'drug_composition'      => $this->drug_composition     ?? null,
            'schedule_category'     => $this->schedule_category    ?? null,
            'manufacturer_name'     => $this->manufacturer_name    ?? null,
            'dosage_form'           => $this->dosage_form          ?? null,
            'storage_temp_req'      => $this->storage_temp_req     ?? null,
            'part_number'           => $this->part_number          ?? null,
            'vehicle_compatibility' => $this->vehicle_compatibility ?? null,
            'core_charge_flag'      => $this->core_charge_flag !== null ? (bool) $this->core_charge_flag : null,
            'warranty_period'       => $this->warranty_period      ?? null,
            'technical_specs'       => $this->technical_specs      ?? null,
        ];
    }
}
