<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'companyId'      => $this->company_id,
            'code'           => $this->code          ?? '',
            'type'           => $this->type,
            'name'           => $this->name,
            'phone'          => $this->phone          ?? '',
            'email'          => $this->email          ?? '',
            'address'        => $this->address        ?? '',
            'subType'        => $this->sub_type       ?? '',
            'paymentTerms'   => $this->payment_terms  ?? '',
            'creditLimit'    => (float) $this->credit_limit,
            'bankDetails'    => $this->bank_details   ?? '',
            'category'       => $this->category       ?? '',
            'openingBalance' => (float) $this->opening_balance,
            'currentBalance' => (float) $this->current_balance,
        ];
    }
}
