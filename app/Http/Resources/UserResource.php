<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'username'   => $this->username,
            'name'       => $this->name ?? '',
            'systemRole' => $this->system_role,
            'roleId'     => $this->role_id,
            'companyId'  => $this->company_id,
            'isActive'   => $this->is_active,
        ];
    }
}
