<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function messages(): array
    {
        return [
            'name.required'      => 'Product name is required.',
            'name.max'           => 'Product name must be 32 characters or less.',
            'name.not_regex'     => 'Product name must not contain HTML tags.',
            'unitPrice.numeric'  => 'Unit Price (Sale) must be a number.',
            'unitPrice.min'      => 'Unit Price (Sale) cannot be negative.',
            'unitCost.numeric'   => 'Unit Cost must be a number.',
            'unitCost.min'       => 'Unit Cost cannot be negative.',
            'initialStock.numeric' => 'Opening Stock must be a number.',
            'initialStock.min'   => 'Opening Stock cannot be negative.',
            'reorderLevel.integer' => 'Reorder Level must be a whole number.',
            'reorderLevel.min'   => 'Reorder Level cannot be negative.',
            'expiry_date.date'   => 'Expiry Date must be a valid date.',
            'storage_condition.in' => 'Storage Condition must be Ambient, Chilled, or Frozen.',
            'schedule_category.in' => 'Schedule Category must be H, H1, X, or OTC.',
            'dosage_form.in'     => 'Dosage Form must be Tablet, Syrup, Injection, or Capsule.',
        ];
    }

    public function rules(): array
    {
        return [
            // Standard fields
            'name'          => ['required', 'string', 'max:32', 'not_regex:/<[^>]*>/'],
            'sku'           => 'sometimes|nullable|string|max:100',
            'barcode'       => 'sometimes|nullable|string|max:100',
            'type'          => 'sometimes|string|max:50',
            'uom'           => 'sometimes|nullable|string|max:50',
            'baseUomId'     => 'sometimes|nullable|string|max:20',
            'categoryId'    => 'sometimes|nullable|string|max:20',
            'unitCost'      => 'sometimes|numeric|min:0',
            'unitPrice'     => 'sometimes|numeric|min:0',
            'reorderLevel'  => 'sometimes|integer|min:0',
            'initialStock'  => 'sometimes|numeric|min:0',
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
