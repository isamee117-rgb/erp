<?php

namespace App\Services;

use App\Models\AccountMapping;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Payment;
use App\Models\PurchaseReceive;
use App\Models\PurchaseReturn;
use App\Models\SaleOrder;
use App\Models\SaleReturn;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JournalPostingService
{
    public function __construct(
        protected DocumentSequenceService $sequenceService,
    ) {}

    public function postSaleInvoice(SaleOrder $sale): void
    {
        $company = Company::find($sale->company_id);
        if (!$this->isAccountingActive($company)) return;

        $sale->loadMissing('items');

        $revenueAccount   = $this->getMapping($sale->company_id, 'sales_revenue');
        $cogsAccount      = $this->getMapping($sale->company_id, 'cost_of_goods_sold');
        $inventoryAccount = $this->getMapping($sale->company_id, 'inventory_asset');

        // Cash sale → DR Cash/Bank | Credit sale → DR Accounts Receivable
        $isCash = strtolower($sale->payment_method ?? '') === 'cash';
        if ($isCash) {
            $debitAccount     = $this->getMapping($sale->company_id, 'cash_account');
            $debitDescription = 'Cash received';
        } else {
            $debitAccount     = $this->getMapping($sale->company_id, 'accounts_receivable');
            $debitDescription = 'Accounts receivable';
        }

        $totalAmount = (float) $sale->total_amount;
        $totalCogs   = $sale->items->sum(fn($i) => (float) $i->cogs);

        $customerName = $sale->customer?->name ?? 'Walk-in';

        $lines = [
            ['account_id' => $debitAccount->id,   'debit' => $totalAmount, 'credit' => 0,           'description' => $debitDescription],
            ['account_id' => $revenueAccount->id, 'debit' => 0,            'credit' => $totalAmount, 'description' => 'Sales revenue'],
        ];

        if ($totalCogs > 0) {
            $lines[] = ['account_id' => $cogsAccount->id,      'debit' => $totalCogs, 'credit' => 0,         'description' => 'Cost of goods sold'];
            $lines[] = ['account_id' => $inventoryAccount->id, 'debit' => 0,          'credit' => $totalCogs, 'description' => 'Inventory reduced'];
        }

        $this->createEntry([
            'company_id'     => $sale->company_id,
            'date'           => $sale->created_at->toDateString(),
            'description'    => "Sales Invoice #{$sale->invoice_no} - {$customerName}",
            'reference_type' => 'sale_order',
            'reference_id'   => $sale->id,
            'created_by'     => $sale->company_id, // fallback — caller injects user if needed
        ], $lines);
    }

    public function postSaleReturn(SaleReturn $return, string $userId): void
    {
        $company = Company::find($return->company_id);
        if (!$this->isAccountingActive($company)) return;

        $return->loadMissing('items.product');

        $paymentAccount   = $this->getMappingOrFallback($return->company_id, 'cash_account');
        $revenueAccount   = $this->getMapping($return->company_id, 'sales_revenue');
        $inventoryAccount = $this->getMapping($return->company_id, 'inventory_asset');
        $cogsAccount      = $this->getMapping($return->company_id, 'cost_of_goods_sold');

        $totalAmount = (float) $return->total_amount;
        $returnedCogs = $return->items->sum(function ($item) {
            return (float) ($item->unit_price * $item->quantity * 0.6); // fallback ratio if cogs not on return item
        });

        $this->createEntry([
            'company_id'     => $return->company_id,
            'date'           => $return->created_at->toDateString(),
            'description'    => "Sale Return #{$return->return_no} against Invoice #{$return->original_sale_id}",
            'reference_type' => 'sale_return',
            'reference_id'   => $return->id,
            'created_by'     => $userId,
        ], [
            ['account_id' => $revenueAccount->id,   'debit' => $totalAmount,  'credit' => 0,            'description' => 'Revenue reversed'],
            ['account_id' => $paymentAccount->id,   'debit' => 0,             'credit' => $totalAmount,  'description' => 'Cash refunded'],
            ['account_id' => $inventoryAccount->id, 'debit' => $returnedCogs, 'credit' => 0,             'description' => 'Stock returned'],
            ['account_id' => $cogsAccount->id,      'debit' => 0,             'credit' => $returnedCogs, 'description' => 'COGS reversed'],
        ]);
    }

    public function postPurchaseReceive(PurchaseReceive $receive, string $userId): void
    {
        $company = Company::find($receive->company_id);
        if (!$this->isAccountingActive($company)) return;

        $receive->loadMissing(['items', 'purchaseOrder.vendor']);

        $inventoryAccount = $this->getMapping($receive->company_id, 'inventory_asset');
        $payableAccount   = $this->getMapping($receive->company_id, 'accounts_payable');

        $totalValue = $receive->items->sum(fn($i) => (float) $i->quantity * (float) $i->unit_cost);
        $poNo       = $receive->purchaseOrder?->po_no ?? $receive->purchase_order_id;
        $vendor     = $receive->purchaseOrder?->vendor?->name ?? 'Vendor';

        $this->createEntry([
            'company_id'     => $receive->company_id,
            'date'           => $receive->created_at->toDateString(),
            'description'    => "Goods Received - PO# {$poNo} - {$vendor}",
            'reference_type' => 'purchase_receive',
            'reference_id'   => $receive->id,
            'created_by'     => $userId,
        ], [
            ['account_id' => $inventoryAccount->id, 'debit' => $totalValue, 'credit' => 0,          'description' => 'Inventory received'],
            ['account_id' => $payableAccount->id,   'debit' => 0,           'credit' => $totalValue, 'description' => 'Accounts payable'],
        ]);
    }

    public function postPurchaseReturn(PurchaseReturn $return, string $userId): void
    {
        $company = Company::find($return->company_id);
        if (!$this->isAccountingActive($company)) return;

        $payableAccount   = $this->getMapping($return->company_id, 'accounts_payable');
        $inventoryAccount = $this->getMapping($return->company_id, 'inventory_asset');

        $totalAmount = (float) $return->total_amount;

        $this->createEntry([
            'company_id'     => $return->company_id,
            'date'           => $return->created_at->toDateString(),
            'description'    => "Purchase Return #{$return->return_no} against PO #{$return->original_purchase_id}",
            'reference_type' => 'purchase_return',
            'reference_id'   => $return->id,
            'created_by'     => $userId,
        ], [
            ['account_id' => $payableAccount->id,   'debit' => $totalAmount, 'credit' => 0,           'description' => 'Payable reduced'],
            ['account_id' => $inventoryAccount->id, 'debit' => 0,            'credit' => $totalAmount, 'description' => 'Inventory returned'],
        ]);
    }

    public function postPayment(Payment $payment, string $userId): void
    {
        $company = Company::find($payment->company_id);
        if (!$this->isAccountingActive($company)) return;

        $receivableAccount = $this->getMapping($payment->company_id, 'accounts_receivable');
        $payableAccount    = $this->getMapping($payment->company_id, 'accounts_payable');

        // Use GL account selected on payment form; fall back to cash_account mapping
        $glAccount = $payment->gl_account_id
            ? ChartOfAccount::find($payment->gl_account_id)
            : null;
        if (!$glAccount) {
            $glAccount = $this->getMapping($payment->company_id, 'cash_account');
        }

        $amount    = (float) $payment->amount;
        $partyName = $payment->party?->name ?? 'Party';

        if ($payment->type === 'Receipt') {
            // Customer pays us: DR GL Account (Cash/Bank), CR Accounts Receivable
            $lines = [
                ['account_id' => $glAccount->id,         'debit' => $amount, 'credit' => 0,      'description' => 'Cash/Bank received'],
                ['account_id' => $receivableAccount->id, 'debit' => 0,       'credit' => $amount, 'description' => 'Receivable cleared'],
            ];
            $description = "Customer Receipt - {$partyName} - Ref: {$payment->reference_no}";
        } else {
            // We pay vendor: DR Accounts Payable, CR GL Account (Cash/Bank)
            $lines = [
                ['account_id' => $payableAccount->id, 'debit' => $amount, 'credit' => 0,      'description' => 'Payable cleared'],
                ['account_id' => $glAccount->id,      'debit' => 0,       'credit' => $amount, 'description' => 'Cash/Bank paid'],
            ];
            $description = "Vendor Payment - {$partyName} - Ref: {$payment->reference_no}";
        }

        $this->createEntry([
            'company_id'     => $payment->company_id,
            'date'           => now()->toDateString(),
            'description'    => $description,
            'reference_type' => 'payment',
            'reference_id'   => $payment->id,
            'created_by'     => $userId,
        ], $lines);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function isAccountingActive(?Company $company): bool
    {
        if (!$company || !$company->id) return false;
        return $this->hasMappings($company->id);
    }

    private function hasMappings(string $companyId): bool
    {
        return AccountMapping::where('company_id', $companyId)->exists();
    }

    private function getMapping(string $companyId, string $key): ChartOfAccount
    {
        $mapping = AccountMapping::where('company_id', $companyId)
            ->where('mapping_key', $key)
            ->with('account')
            ->first();

        if (!$mapping || !$mapping->account) {
            throw new \RuntimeException("Account mapping missing: {$key} for company {$companyId}");
        }

        return $mapping->account;
    }

    // Returns the mapped account, or falls back to $fallbackKey if primary is not mapped
    private function getMappingOrFallback(string $companyId, string $key, string $fallbackKey = 'cash_account'): ChartOfAccount
    {
        $mapping = AccountMapping::where('company_id', $companyId)
            ->where('mapping_key', $key)
            ->with('account')
            ->first();

        if ($mapping && $mapping->account) {
            return $mapping->account;
        }

        return $this->getMapping($companyId, $fallbackKey);
    }

    private function generateEntryNo(string $companyId): string
    {
        return $this->sequenceService->getNextNumber($companyId, 'journal_entry');
    }

    private function createEntry(array $data, array $lines): JournalEntry
    {
        $entryId = 'JE-' . Str::random(9);
        $entryNo = $this->generateEntryNo($data['company_id']);

        $entry = JournalEntry::create([
            'id'             => $entryId,
            'company_id'     => $data['company_id'],
            'entry_no'       => $entryNo,
            'date'           => $data['date'],
            'description'    => $data['description'],
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id'   => $data['reference_id']   ?? null,
            'is_posted'      => true,
            'created_by'     => $data['created_by'],
        ]);

        foreach ($lines as $line) {
            JournalEntryLine::create([
                'id'               => 'JEL-' . Str::random(9),
                'journal_entry_id' => $entryId,
                'account_id'       => $line['account_id'],
                'description'      => $line['description'] ?? null,
                'debit'            => $line['debit']  ?? 0,
                'credit'           => $line['credit'] ?? 0,
            ]);
        }

        return $entry;
    }
}