<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentSequenceService
{
    const TYPES = [
        'po_number' => ['prefix' => 'PO-', 'label' => 'Purchase Order'],
        'sale_invoice' => ['prefix' => 'INV-', 'label' => 'Sales Invoice'],
        'sale_return' => ['prefix' => 'CM-', 'label' => 'Sales Return (Credit Memo)'],
        'purchase_return' => ['prefix' => 'DM-', 'label' => 'Purchase Return (Debit Memo)'],
        'customer_no' => ['prefix' => 'CUST-', 'label' => 'Customer'],
        'vendor_no' => ['prefix' => 'VEND-', 'label' => 'Vendor'],
        'item_no' => ['prefix' => 'ITM-', 'label' => 'Item'],
        'sku'           => ['prefix' => 'SKU-',  'label' => 'SKU'],
        'journal_entry' => ['prefix' => 'JE-',   'label' => 'Journal Entry'],
        'job_card'      => ['prefix' => 'JC-',   'label' => 'Job Card'],
    ];

    public function ensureSequencesExist(string $companyId): void
    {
        foreach (self::TYPES as $type => $config) {
            try {
                DocumentSequence::firstOrCreate(
                    ['company_id' => $companyId, 'type' => $type],
                    [
                        'id' => 'SEQ-' . Str::random(9),
                        'prefix' => $config['prefix'],
                        'next_number' => 1,
                        'is_locked' => false,
                    ]
                );
            } catch (\Illuminate\Database\QueryException $e) {
                if (str_contains($e->getMessage(), 'duplicate') || str_contains($e->getMessage(), 'unique')) {
                    continue;
                }
                throw $e;
            }
        }
    }

    private function validateType(string $type): void
    {
        if (!array_key_exists($type, self::TYPES)) {
            throw new \InvalidArgumentException("Invalid sequence type: {$type}");
        }
    }

    public function getNextNumber(string $companyId, string $type): string
    {
        $this->validateType($type);

        return DB::transaction(function () use ($companyId, $type) {
            $seq = DocumentSequence::where('company_id', $companyId)
                ->where('type', $type)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                $this->ensureSequencesExist($companyId);
                $seq = DocumentSequence::where('company_id', $companyId)
                    ->where('type', $type)
                    ->lockForUpdate()
                    ->first();
            }

            if (!$seq) {
                throw new \RuntimeException("Failed to create sequence for type: {$type}");
            }

            $number = $seq->prefix . str_pad($seq->next_number, 5, '0', STR_PAD_LEFT);

            $seq->update([
                'next_number' => $seq->next_number + 1,
                'is_locked' => true,
            ]);

            return $number;
        });
    }

    public function getSequences(string $companyId): array
    {
        $this->ensureSequencesExist($companyId);
        return DocumentSequence::where('company_id', $companyId)->get()->toArray();
    }

    public function updateSequence(string $companyId, string $type, string $prefix, int $nextNumber): DocumentSequence
    {
        $this->validateType($type);

        $seq = DocumentSequence::where('company_id', $companyId)
            ->where('type', $type)
            ->firstOrFail();

        if ($seq->is_locked && $nextNumber < $seq->next_number) {
            throw new \Exception("Cannot set next number below {$seq->next_number} — this sequence has already been used and going backwards would create duplicate codes.");
        }

        $seq->update([
            'prefix' => $prefix,
            'next_number' => max(1, $nextNumber),
        ]);

        return $seq->fresh();
    }
}
