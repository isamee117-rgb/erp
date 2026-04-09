<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentSequenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'companyId'  => $this->company_id,
            'type'       => $this->type,
            'prefix'     => $this->prefix,
            'nextNumber' => $this->next_number,
            'isLocked'   => (bool) $this->is_locked,
        ];
    }
}
