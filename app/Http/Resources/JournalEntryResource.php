<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'entryNo'       => $this->entry_no,
            'companyId'     => $this->company_id,
            'date'          => $this->date?->toDateString(),
            'description'   => $this->description,
            'referenceType' => $this->reference_type,
            'referenceId'   => $this->reference_id,
            'documentNo'    => $this->document_no  ?? null,
            'partyName'     => $this->party_name   ?? null,
            'isPosted'      => $this->is_posted,
            'createdBy'     => $this->created_by,
            'totalDebit'    => $this->whenLoaded('lines', fn() => round($this->lines->sum('debit'), 2)),
            'totalCredit'   => $this->whenLoaded('lines', fn() => round($this->lines->sum('credit'), 2)),
            'lines'         => JournalEntryLineResource::collection($this->whenLoaded('lines')),
            'createdAt'     => $this->created_at?->toDateTimeString(),
        ];
    }
}