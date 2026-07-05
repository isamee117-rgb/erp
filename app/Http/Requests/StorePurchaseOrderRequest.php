<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vendorId'             => 'required|string|exists:parties,id',
            'items'                => 'required|array|min:1',
            'items.*.productId'    => 'required|string|exists:products,id',
            'items.*.uomId'        => 'sometimes|nullable|string|exists:units_of_measure,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unitCost'     => 'sometimes|numeric|min:0',
            'items.*.batchNo'      => 'nullable|string|max:255',
            'items.*.mfgDate'      => 'nullable|date',
            'items.*.expiryDate'   => 'nullable|date',
            'orderDate'            => 'sometimes|nullable|date',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('items', []) as $i => $item) {
                if (!empty($item['mfgDate']) && !empty($item['expiryDate'])
                    && strtotime($item['expiryDate']) <= strtotime($item['mfgDate'])) {
                    $validator->errors()->add(
                        "items.$i.expiryDate",
                        'Expiry date must be after the manufacturing date.'
                    );
                }
            }
        });
    }
}
