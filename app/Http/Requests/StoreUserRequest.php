<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'username' => 'required|string|max:100|unique:users,username',
            'name'     => 'sometimes|string|max:255',
            'password' => 'required|string|min:6',
            'roleId'   => 'sometimes|nullable|string|exists:custom_roles,id',
            'isActive' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'Username already exists.',
        ];
    }
}
