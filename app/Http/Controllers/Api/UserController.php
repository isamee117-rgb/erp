<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{

    public function store(StoreUserRequest $request)
    {
        $authUser = $request->get('auth_user');
        $data     = $request->validated();

        User::create([
            'id'          => 'user-' . Str::random(9),
            'username'    => $data['username'],
            'name'        => $data['name'] ?? '',
            'password'    => $data['password'],
            'system_role' => 'Standard User',
            'role_id'     => $data['roleId'] ?? null,
            'company_id'  => $authUser->company_id,
            'is_active'   => $data['isActive'] ?? true,
        ]);

        return response()->json(['success' => true]);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $authUser = $request->get('auth_user');
        $user     = User::where('id', $id)
            ->where('company_id', $authUser->company_id)
            ->firstOrFail();

        $data    = $request->validated();
        $updates = [];

        if (isset($data['name']))     $updates['name']     = $data['name'];
        if (isset($data['username'])) $updates['username'] = $data['username'];
        if (array_key_exists('roleId', $data)) {
            $updates['role_id'] = $data['roleId'] ?: null;
        }

        if (!empty($updates)) $user->update($updates);

        return response()->json(['success' => true]);
    }

    public function setStatus(Request $request, $id)
    {
        $authUser = $request->get('auth_user');
        $user     = User::where('id', $id)
            ->where('company_id', $authUser->company_id)
            ->firstOrFail();

        $user->update(['is_active' => $request->input('isActive') ?? $request->input('is_active')]);
        return response()->json(['success' => true]);
    }

    public function updatePassword(UpdatePasswordRequest $request, $id)
    {
        $authUser = $request->get('auth_user');
        $user     = User::where('id', $id)
            ->where('company_id', $authUser->company_id)
            ->firstOrFail();

        $user->update(['password' => $request->validated()['password']]);
        return response()->json(['success' => true]);
    }
}
