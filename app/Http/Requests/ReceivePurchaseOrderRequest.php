<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'items'                       => 'sometimes|array',
            'items.*.productId'           => 'required_with:items|string|exists:products,id',
            'items.*.quantity'            => 'required_with:items|integer|min:1',
            'items.*.unitCost'            => 'sometimes|numeric|min:0',
            'items.*.purchaseItemId'      => 'sometimes|string',
            'items.*.batchNo'             => 'nullable|string|max:255',
            'items.*.mfgDate'             => 'nullable|date',
            'items.*.expiryDate'          => 'nullable|date',
            'notes'                       => 'sometimes|string|max:500',
            'receiveDate'                 => 'sometimes|date|before_or_equal:today',
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
