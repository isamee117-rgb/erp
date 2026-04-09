<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'companyId'     => $this->company_id,
            'partyId'       => $this->party_id,
            'date'          => is_numeric($this->date) ? $this->date : strtotime($this->date) * 1000,
            'amount'        => (float) $this->amount,
            'paymentMethod' => $this->payment_method,
            'type'          => $this->type,
            'referenceNo'   => $this->reference_no ?? '',
            'notes'         => $this->notes ?? '',
            'glAccountId'   => $this->gl_account_id,
        ];
    }
}
