<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChartOfAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'code'      => $this->code,
            'name'      => $this->name,
            'type'      => $this->type,
            'subType'   => $this->sub_type,
            'isSystem'  => $this->is_system,
            'isActive'  => $this->is_active,
            'companyId' => $this->company_id,
            'openingBalance' => (float) ($this->opening_balance ?? 0),
            'balance'        => (float) ($this->balance ?? 0),
        ];
    }
}