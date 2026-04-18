<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vendorId'           => 'required|string|exists:parties,id',
            'items'              => 'required|array|min:1',
            'items.*.productId'  => 'required|string|exists:products,id',
            'items.*.uomId'      => 'sometimes|nullable|string|exists:units_of_measure,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unitCost'   => 'sometimes|numeric|min:0',
            'orderDate'          => 'sometimes|nullable|date',
        ];
    }
}
