<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyDetailsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                => 'sometimes|string|max:255',
            'saasPlan'            => 'sometimes|string|in:Monthly,Yearly,Lifetime',
            'registrationPayment' => 'sometimes|numeric|min:0',
            'maxUserLimit'        => 'sometimes|integer|min:1',
            'infoName'            => 'sometimes|nullable|string|max:255',
            'infoTagline'         => 'sometimes|nullable|string|max:255',
            'infoAddress'         => 'sometimes|nullable|string|max:500',
            'infoPhone'           => 'sometimes|nullable|string|max:50',
            'infoEmail'           => 'sometimes|nullable|email|max:255',
            'infoWebsite'         => 'sometimes|nullable|url|max:255',
            'infoTaxId'           => 'sometimes|nullable|string|max:100',
        ];
    }
}
