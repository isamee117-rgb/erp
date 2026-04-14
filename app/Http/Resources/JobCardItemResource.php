<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobCardItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'jobCardId'      => $this->job_card_id,
            'itemType'       => $this->item_type,
            'productId'      => $this->product_id,
            'productName'    => $this->product_name,
            'quantity'       => (float) $this->quantity,
            'unitPrice'      => (float) $this->unit_price,
            'discount'       => (float) $this->discount,
            'totalLinePrice' => (float) $this->total_line_price,
        ];
    }
}
