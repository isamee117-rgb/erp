<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ApiTokenAuth middleware already authenticated the request.
    }

    public function rules(): array
    {
        return [
            'from'          => 'required|date',
            'to'            => 'required|date|after_or_equal:from',
            'page'          => 'integer|min:1',
            'perPage'       => 'integer|min:1|max:200',
            'export'        => 'boolean',
            'customerId'    => 'nullable|string',
            'vendorId'      => 'nullable|string',
            'paymentMethod' => 'nullable|string',
            'status'        => 'nullable|string',
            'search'        => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'from.required'     => 'A start date is required.',
            'to.required'       => 'An end date is required.',
            'to.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }
}
