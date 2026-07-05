<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryDatesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'expiryDateEnabled' => 'required|boolean',
            'mfgDateEnabled'    => 'required|boolean',
            'expiryAlertDays'   => 'required|integer|min:1|max:365',
        ];
    }

    public function messages(): array
    {
        return [
            'expiryAlertDays.min' => 'Expiry alert days must be between 1 and 365.',
            'expiryAlertDays.max' => 'Expiry alert days must be between 1 and 365.',
        ];
    }
}
