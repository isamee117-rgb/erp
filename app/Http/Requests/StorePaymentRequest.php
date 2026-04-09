<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'partyId'       => 'required|string|exists:parties,id',
            'amount'        => 'required|numeric|min:0.01',
            'paymentMethod' => 'required|string|in:Cash,Bank,Cheque,Other',
            'type'          => 'required|string|in:Receipt,Payment',
            'date'          => 'sometimes|numeric',
            'referenceNo'   => 'sometimes|string|max:100',
            'notes'         => 'sometimes|nullable|string|max:500',
            'glAccountId'   => 'sometimes|nullable|string|exists:chart_of_accounts,id',
        ];
    }
}
