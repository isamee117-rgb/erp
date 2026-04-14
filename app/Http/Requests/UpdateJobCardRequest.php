<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobCardRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customerId'       => 'sometimes|nullable|string|exists:parties,id',
            'customerName'     => 'sometimes|nullable|string|max:255',
            'phone'            => 'sometimes|nullable|string|max:50',
            'vehicleRegNumber' => 'sometimes|nullable|string|max:50',
            'vinChassisNumber' => 'sometimes|nullable|string|max:100',
            'engineNumber'     => 'sometimes|nullable|string|max:100',
            'makeModelYear'    => 'sometimes|nullable|string|max:100',
            'liftNumber'       => 'sometimes|nullable|string|max:50',
            'currentOdometer'  => 'sometimes|nullable|numeric|min:0',
            'paymentMethod'    => 'sometimes|string|in:Cash,Credit',
            'discountType'     => 'sometimes|string|in:fixed,percent',
            'discountValue'    => 'sometimes|numeric|min:0',
        ];
    }
}
