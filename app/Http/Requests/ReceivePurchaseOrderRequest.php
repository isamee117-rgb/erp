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
            'notes'                       => 'sometimes|string|max:500',
            'receiveDate'                 => 'sometimes|date|before_or_equal:today',
        ];
    }
}
