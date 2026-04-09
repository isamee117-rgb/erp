<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'items'                  => 'required|array|min:1',
            'items.*.productId'      => 'required|string|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.discount'       => 'sometimes|numeric|min:0',
            'customerId'             => 'sometimes|nullable|string|exists:parties,id',
            'paymentMethod'          => 'sometimes|string|in:Cash,Credit,Bank,Cheque,Other',
        ];
    }
}
