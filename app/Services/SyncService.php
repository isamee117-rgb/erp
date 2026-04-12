<?php

namespace App\Services;

use App\Http\Resources\BusinessCategoryResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ChartOfAccountResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\CustomRoleResource;
use App\Http\Resources\DocumentSequenceResource;
use App\Http\Resources\EntityTypeResource;
use App\Http\Resources\InventoryCostLayerResource;
use App\Http\Resources\InventoryLedgerResource;
use App\Http\Resources\PartyResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\PurchaseOrderResource;
use App\Http\Resources\PurchaseReturnResource;
use App\Http\Resources\SaleOrderResource;
use App\Http\Resources\SaleReturnResource;
use App\Http\Resources\UnitOfMeasureResource;
use App\Http\Resources\UserResource;
use App\Models\AccountMapping;
use App\Models\BusinessCategory;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\Company;
use App\Models\CustomRole;
use App\Models\DocumentSequence;
use App\Models\EntityType;
use App\Models\InventoryCostLayer;
use App\Models\InventoryLedger;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\SaleOrder;
use App\Models\SaleReturn;
use App\Models\Setting;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SyncService
{
    public function __construct(
        protected DocumentSequenceService $sequenceService,
    ) {}

    // ── Core: user, companies, roles, settings ────────────────────────────────
    // ~200ms — page render ke liye minimum required data
    public function getCoreData(User $user): array
    {
        $isSuper = $user->system_role === 'Super Admin';
        $coId    = $user->company_id;

        [$companies, $users, $customRoles] = $this->fetchTenantData($isSuper, $coId);

        $currencySetting      = Setting::where('key', 'currency')->first();
        $invoiceFormatSetting = Setting::where('key', 'invoice_format')->first();

        $costingMethod     = 'moving_average';
        $documentSequences = collect();

        $chartOfAccounts = collect();
        $accountMappings = collect();

        if (!$isSuper && $coId) {
            $company       = Company::find($coId);
            $costingMethod = $company->costing_method ?? 'moving_average';

            $this->sequenceService->ensureSequencesExist($coId);
            $documentSequences = DocumentSequence::where('company_id', $coId)->get();
            $chartOfAccounts   = ChartOfAccount::where('company_id', $coId)
                ->selectRaw('chart_of_accounts.*, (
                    SELECT COALESCE(SUM(jel.debit), 0) - COALESCE(SUM(jel.credit), 0)
                    FROM journal_entry_lines jel
                    JOIN journal_entries je ON je.id = jel.journal_entry_id
                    WHERE je.is_posted = 1
                    AND jel.account_id = chart_of_accounts.id
                ) as balance')
                ->orderBy('code')
                ->get();
            $accountMappings   = AccountMapping::where('company_id', $coId)->with('account')->get();
        }

        return [
            'companies'          => CompanyResource::collection($companies),
            'users'              => UserResource::collection($users),
            'customRoles'        => CustomRoleResource::collection($customRoles),
            'documentSequences'  => DocumentSequenceResource::collection($documentSequences),
            'currency'           => $currencySetting?->value  ?? 'Rs.',
            'invoiceFormat'      => $invoiceFormatSetting?->value ?? 'A4',
            'costingMethod'      => $costingMethod,
            'chartOfAccounts'    => ChartOfAccountResource::collection($chartOfAccounts),
            'accountMappings'    => $accountMappings->keyBy('mapping_key')->map(fn($m) => [
                'accountId' => $m->account_id,
                'accountCode' => $m->account?->code,
                'accountName' => $m->account?->name,
            ]),
        ];
    }

    // ── Master: products, parties, categories, UOMs ───────────────────────────
    // ~500ms — product listing, POS, party forms
    public function getMasterData(User $user): array
    {
        $isSuper = $user->system_role === 'Super Admin';
        $coId    = $user->company_id;

        $products   = $this->scopedQuery(Product::with(['uomConversions.uom', 'priceTiers']), $isSuper, $coId);
        $parties    = $this->scopedQuery(Party::query(), $isSuper, $coId);
        $categories = $this->scopedQuery(Category::query(), $isSuper, $coId);
        $uoms       = $this->scopedQuery(UnitOfMeasure::query(), $isSuper, $coId);

        $entityTypes        = EntityType::all();
        $businessCategories = BusinessCategory::all();

        return [
            'products'           => ProductResource::collection($products),
            'parties'            => PartyResource::collection($parties),
            'categories'         => CategoryResource::collection($categories),
            'uoms'               => UnitOfMeasureResource::collection($uoms),
            'entityTypes'        => EntityTypeResource::collection($entityTypes),
            'businessCategories' => BusinessCategoryResource::collection($businessCategories),
        ];
    }

    // ── Transactions: sales, purchases, payments, ledger ─────────────────────
    // ~2-3s — reports, history, reconciliation
    public function getTransactionData(User $user): array
    {
        $isSuper = $user->system_role === 'Super Admin';
        $coId    = $user->company_id;

        $sales           = $this->scopedQuery(SaleOrder::with('items'), $isSuper, $coId);
        $purchaseOrders  = $this->scopedQuery(PurchaseOrder::with(['items', 'receives.items']), $isSuper, $coId);
        $payments        = $this->scopedQuery(Payment::query(), $isSuper, $coId);
        $ledger          = $this->scopedQuery(InventoryLedger::query(), $isSuper, $coId);
        $salesReturns    = $this->scopedQuery(SaleReturn::with('items'), $isSuper, $coId);
        $purchaseReturns = $this->scopedQuery(PurchaseReturn::with('items'), $isSuper, $coId);
        $costLayers      = $this->scopedQuery(InventoryCostLayer::query(), $isSuper, $coId);

        return [
            'sales'          => SaleOrderResource::collection($sales),
            'purchaseOrders' => PurchaseOrderResource::collection($purchaseOrders),
            'payments'       => PaymentResource::collection($payments),
            'ledger'         => InventoryLedgerResource::collection($ledger),
            'salesReturns'   => SaleReturnResource::collection($salesReturns),
            'purchaseReturns' => PurchaseReturnResource::collection($purchaseReturns),
            'costLayers'     => InventoryCostLayerResource::collection($costLayers),
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function fetchTenantData(bool $isSuper, ?string $coId): array
    {
        if ($isSuper) {
            return [Company::all(), User::all(), CustomRole::all()];
        }

        return [
            Company::where('id', $coId)->get(),
            User::where('company_id', $coId)->get(),
            CustomRole::where('company_id', $coId)->get(),
        ];
    }

    private function scopedQuery(\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $query, bool $isSuper, ?string $coId)
    {
        return $isSuper ? $query->get() : $query->where('company_id', $coId)->get();
    }
}
