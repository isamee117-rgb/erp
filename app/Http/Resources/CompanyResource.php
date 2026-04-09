<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'status'              => $this->status,
            'maxUserLimit'        => $this->max_user_limit,
            'registrationPayment' => (float) $this->registration_payment,
            'saasPlan'            => $this->saas_plan,
            'info'                => [
                'name'    => $this->info_name    ?? $this->name ?? '',
                'tagline' => $this->info_tagline ?? '',
                'address' => $this->info_address ?? '',
                'phone'   => $this->info_phone   ?? '',
                'email'   => $this->info_email   ?? '',
                'website' => $this->info_website ?? '',
                'taxId'   => $this->info_tax_id  ?? '',
                'logoUrl' => $this->info_logo_url ?? '',
            ],
        ];
    }
}
