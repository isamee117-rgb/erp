<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'companyId'   => $this->company_id,
            'name'        => $this->name,
            'description' => $this->description ?? '',
            'permissions' => $this->permissions  ?? [],
        ];
    }
}
