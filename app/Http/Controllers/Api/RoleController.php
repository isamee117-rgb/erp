<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomRoleResource;
use App\Models\CustomRole;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->get('auth_user');
        $data = $request->all();

        $role = CustomRole::create([
            'id'          => 'ROLE-' . Str::random(9),
            'company_id'  => $user->company_id,
            'name'        => $data['name']        ?? '',
            'description' => $data['description'] ?? '',
            'permissions' => $data['permissions'] ?? [],
        ]);

        return new CustomRoleResource($role);
    }

    public function update(Request $request, $id)
    {
        $role = CustomRole::findOrFail($id);
        $data = $request->all();

        $role->update([
            'name'        => $data['name']        ?? $role->name,
            'description' => $data['description'] ?? $role->description,
            'permissions' => $data['permissions'] ?? $role->permissions,
        ]);

        return new CustomRoleResource($role);
    }

    public function destroy($id)
    {
        CustomRole::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
