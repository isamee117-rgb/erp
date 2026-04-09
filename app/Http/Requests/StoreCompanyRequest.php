<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'              => 'required|string|max:255',
            'adminUsername'     => 'required|string|max:100',
            'adminPassword'     => 'required|string|min:6',
            'maxUserLimit'      => 'sometimes|integer|min:1',
            'registrationPayment' => 'sometimes|numeric|min:0',
            'saasPlan'          => 'sometimes|string|in:Monthly,Yearly,Lifetime',
        ];
    }
}
