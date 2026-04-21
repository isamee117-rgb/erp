<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePartyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function messages(): array
    {
        return [
            'name.required'          => 'Name is required.',
            'name.max'               => 'Name must be 255 characters or less.',
            'type.required'          => 'Type (Customer or Vendor) is required.',
            'type.in'                => 'Type must be either "Customer" or "Vendor".',
            'email.email'            => 'Email address is not valid.',
            'email.max'              => 'Email must be 255 characters or less.',
            'phone.max'              => 'Phone number must be 50 characters or less.',
            'address.max'            => 'Address must be 500 characters or less.',
            'creditLimit.numeric'    => 'Credit Limit must be a number.',
            'creditLimit.min'        => 'Credit Limit cannot be negative.',
            'openingBalance.numeric' => 'Opening Balance must be a number.',
            'last_odometer_reading.numeric' => 'Last Odometer Reading must be a number.',
            'last_odometer_reading.min'     => 'Last Odometer Reading cannot be negative.',
        ];
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'type'          => 'required|string|in:Customer,Vendor',
            'phone'         => 'sometimes|nullable|string|max:50',
            'email'         => 'sometimes|nullable|email|max:255',
            'address'       => 'sometimes|nullable|string|max:500',
            'creditLimit'   => 'sometimes|numeric|min:0',
            'openingBalance' => 'sometimes|numeric',
            // Dynamic customer fields
            'make_model_year'       => 'nullable|string|max:100',
            'vehicle_reg_number'    => 'nullable|string|max:100',
            'vin_chassis_number'    => 'nullable|string|max:100',
            'engine_number'         => 'nullable|string|max:100',
            'last_odometer_reading' => 'nullable|numeric|min:0',
        ];
    }
}
