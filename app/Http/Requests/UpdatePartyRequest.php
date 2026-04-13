<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePartyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'          => 'sometimes|string|max:255',
            'type'          => 'sometimes|string|in:Customer,Vendor',
            'phone'         => 'sometimes|string|max:50',
            'email'         => 'sometimes|nullable|email|max:255',
            'address'       => 'sometimes|string|max:500',
            'creditLimit'   => 'sometimes|numeric|min:0',
            'openingBalance' => 'sometimes|numeric',
            'currentBalance' => 'sometimes|numeric',
            // Dynamic customer fields
            'vehicle_reg_number'    => 'nullable|string|max:100',
            'vin_chassis_number'    => 'nullable|string|max:100',
            'engine_number'         => 'nullable|string|max:100',
            'last_odometer_reading' => 'nullable|numeric|min:0',
        ];
    }
}
