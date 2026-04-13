<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Standard fields (all sometimes for partial update)
            'name'          => 'sometimes|string|max:255',
            'sku'           => 'sometimes|nullable|string|max:100',
            'barcode'       => 'sometimes|nullable|string|max:100',
            'type'          => 'sometimes|string|max:50',
            'uom'           => 'sometimes|nullable|string|max:50',
            'baseUomId'     => 'sometimes|nullable|string|max:20',
            'categoryId'    => 'sometimes|nullable|string|max:20',
            'unitCost'      => 'sometimes|numeric|min:0',
            'unitPrice'     => 'sometimes|numeric|min:0',
            'reorderLevel'  => 'sometimes|integer|min:0',
            'currentStock'  => 'sometimes|numeric|min:0',
            // Dynamic product fields
            'brand_name'            => 'nullable|string|max:255',
            'size'                  => 'nullable|string|max:100',
            'color'                 => 'nullable|string|max:100',
            'style'                 => 'nullable|string|max:100',
            'bin_shelf_location'    => 'nullable|string|max:255',
            'expiry_date'           => 'nullable|date',
            'batch_lot_number'      => 'nullable|string|max:255',
            'storage_condition'     => 'nullable|string|in:Ambient,Chilled,Frozen',
            'drug_composition'      => 'nullable|string|max:255',
            'schedule_category'     => 'nullable|string|in:H,H1,X,OTC',
            'manufacturer_name'     => 'nullable|string|max:255',
            'dosage_form'           => 'nullable|string|in:Tablet,Syrup,Injection,Capsule',
            'storage_temp_req'      => 'nullable|string|max:255',
            'part_number'           => 'nullable|string|max:255',
            'vehicle_compatibility' => 'nullable|string|max:255',
            'core_charge_flag'      => 'nullable|boolean',
            'warranty_period'       => 'nullable|string|max:255',
            'technical_specs'       => 'nullable|string|max:2000',
        ];
    }
}
