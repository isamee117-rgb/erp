<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'companyId'         => $this->company_id,
            'jobCardNo'         => $this->job_card_no,
            'status'            => $this->status,
            'saleId'            => $this->sale_id,
            'customerId'        => $this->customer_id,
            'customerName'      => $this->customer_name,
            'phone'             => $this->phone,
            'vehicleRegNumber'  => $this->vehicle_reg_number,
            'vinChassisNumber'  => $this->vin_chassis_number,
            'engineNumber'      => $this->engine_number,
            'makeModelYear'     => $this->make_model_year,
            'liftNumber'        => $this->lift_number,
            'currentOdometer'   => $this->current_odometer ? (float) $this->current_odometer : null,
            'paymentMethod'     => $this->payment_method,
            'partsSubtotal'     => (float) $this->parts_subtotal,
            'servicesSubtotal'  => (float) $this->services_subtotal,
            'subtotal'          => (float) $this->subtotal,
            'discountType'      => $this->discount_type,
            'discountValue'     => (float) $this->discount_value,
            'discount'          => (float) $this->discount,
            'grandTotal'        => (float) $this->grand_total,
            'createdBy'         => $this->created_by,
            'closedAt'          => $this->closed_at ? strtotime($this->closed_at) * 1000 : null,
            'createdAt'         => strtotime($this->created_at) * 1000,
            'items'             => JobCardItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
