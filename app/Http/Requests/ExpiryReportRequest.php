<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpiryReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'page'    => 'integer|min:1',
            'perPage' => 'integer|min:1|max:200',
            'status'  => 'nullable|in:expired,expiring_soon,ok',
        ];
    }
}
