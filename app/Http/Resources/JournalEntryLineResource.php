<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'journalEntryId' => $this->journal_entry_id,
            'accountId'      => $this->account_id,
            'accountCode'    => $this->account?->code,
            'accountName'    => $this->account?->name,
            'description'    => $this->description,
            'debit'          => (float) $this->debit,
            'credit'         => (float) $this->credit,
        ];
    }
}