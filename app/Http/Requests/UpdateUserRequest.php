<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'username' => 'sometimes|string|max:100|unique:users,username,' . $userId,
            'name'     => 'sometimes|string|max:255',
            'roleId'   => 'sometimes|nullable|string|exists:custom_roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'Username already taken.',
        ];
    }
}
