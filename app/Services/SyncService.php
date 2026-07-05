<?php

namespace App\Services;

use App\Http\Resources\BusinessCategoryResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ChartOfAccountResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\CustomRoleResource;
use App\Http\Resources\DocumentSequenceResource;
use App\Http\Resources\EntityTypeResource;
use App\Http\Resources\InventoryLedgerResource;
use App\Http\Resources\PartyResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\PurchaseOrderResource;
use App\Http\Resources\PurchaseReturnResource;
use App\Http\Resources\SaleOrderResource;
use App\Http\Resources\JobCardResource;
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
use App\Models\InventoryLedger;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\JobCard;
use App\Models\PurchaseReturn;
use App\Models\SaleOrder;
use App\Models\SaleReturn;
use App\Models\Setting;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Config\DynamicFields;
use App\Models\CompanyFieldSetting;
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

        // 1 query instead of 3 separate Setting lookups
        $settings         = Setting::where('company_id', $coId)
            ->whereIn('key', ['currency', 'invoice_format', 'job_card_mode', 'expiry_date_enabled', 'mfg_date_enabled', 'expiry_alert_days'])
            ->get()->keyBy('key');

        $costingMethod     = 'moving_average';
        $documentSequences = collect();

        $chartOfAccounts = collect();
        $accountMappings = collect();

        if (!$isSuper && $coId) {
            $company       = Company::find($coId);
            $costingMethod = $company->costing_method ?? 'moving_average';

            // Only run ensureSequencesExist when sequences are missing — 1 COUNT query
            // instead of 10 firstOrCreate calls on every sync
            $expectedCount = count(DocumentSequenceService::TYPES);
            if (DocumentSequence::where('company_id', $coId)->count() < $expectedCount) {
                $this->sequenceService->ensureSequencesExist($coId);
            }
            $documentSequences = DocumentSequence::where('company_id', $coId)->get();

            // Subquery join instead of correlated subquery — single query, ONLY_FULL_GROUP_BY safe.
            // Company scope is on the outer query; the balance sub only needs account_id grouping.
            $balanceSub = \DB::table('journal_entry_lines as jel')
                ->join('journal_entries as je', function ($join) {
                    $join->on('je.id', '=', 'jel.journal_entry_id')->where('je.is_posted', 1);
                })
                ->select('jel.account_id')
                ->selectRaw('COALESCE(SUM(jel.debit), 0) - COALESCE(SUM(jel.credit), 0) as journal_balance')
                ->groupBy('jel.account_id');

            $chartOfAccounts = ChartOfAccount::where('chart_of_accounts.company_id', $coId)
                ->leftJoinSub($balanceSub, 'bal', 'bal.account_id', '=', 'chart_of_accounts.id')
                ->select('chart_of_accounts.*')
                ->selectRaw('COALESCE(chart_of_accounts.opening_balance, 0) + COALESCE(bal.journal_balance, 0) as balance')
                ->orderBy('chart_of_accounts.code')
                ->get();

            $accountMappings = AccountMapping::where('company_id', $coId)->with('account')->get();
        }

        return [
            'companies'          => CompanyResource::collection($companies),
            'users'              => UserResource::collection($users),
            'customRoles'        => CustomRoleResource::collection($customRoles),
            'documentSequences'  => DocumentSequenceResource::collection($documentSequences),
            'currency'           => $settings->get('currency')?->value ?? 'Rs.',
            'invoiceFormat'      => $settings->get('invoice_format')?->value ?? 'A4',
            'costingMethod'      => $costingMethod,
            'jobCardMode'        => (bool) ($settings->get('job_card_mode')?->value ?? false),
            'expiryDateEnabled'  => (bool) ($settings->get('expiry_date_enabled')?->value ?? false),
            'mfgDateEnabled'     => (bool) ($settings->get('mfg_date_enabled')?->value ?? false),
            'expiryAlertDays'    => (int) ($settings->get('expiry_alert_days')?->value ?? 30),
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

        $entityTypes        = $this->scopedQuery(EntityType::query(), $isSuper, $coId);
        $businessCategories = $this->scopedQuery(BusinessCategory::query(), $isSuper, $coId);

        // Field settings payload
        $fieldSettingsPayload = [
            'enabledKeys' => ['product' => [], 'customer' => []],
            'definitions' => DynamicFields::all(),
        ];

        if (!$isSuper && $coId) {
            CompanyFieldSetting::where('company_id', $coId)
                ->where('is_enabled', true)
                ->get()
                ->each(function ($row) use (&$fieldSettingsPayload) {
                    $entityType = $row->entity_type; // 'product' or 'customer'
                    if (isset($fieldSettingsPayload['enabledKeys'][$entityType])) {
                        $fieldSettingsPayload['enabledKeys'][$entityType][] = $row->field_key;
                    }
                });
        }

        return [
            'products'           => ProductResource::collection($products),
            'parties'            => PartyResource::collection($parties),
            'categories'         => CategoryResource::collection($categories),
            'uoms'               => UnitOfMeasureResource::collection($uoms),
            'entityTypes'        => EntityTypeResource::collection($entityTypes),
            'businessCategories' => BusinessCategoryResource::collection($businessCategories),
            'fieldSettings'      => $fieldSettingsPayload,
        ];
    }

    // ── Transactions: sales, purchases, payments, ledger ─────────────────────
    // Default: last 3 months. Pass $from/$to to override.
    public function getTransactionData(User $user, ?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        $from ??= now()->subMonths(3)->startOfDay();

        $isSuper = $user->system_role === 'Super Admin';
        $coId    = $user->company_id;

        $sales           = $this->scopedQueryWithDates(SaleOrder::with('items'),                         $isSuper, $coId, $from, $to);
        $purchaseOrders  = $this->scopedQueryWithDates(PurchaseOrder::with(['items', 'receives.items']), $isSuper, $coId, $from, $to);
        $payments        = $this->scopedQueryWithDates(Payment::query(),                                 $isSuper, $coId, $from, $to);
        $ledger          = $this->scopedQueryWithDates(InventoryLedger::query(),                         $isSuper, $coId, $from, $to);
        $salesReturns    = $this->scopedQueryWithDates(SaleReturn::with('items'),                        $isSuper, $coId, $from, $to);
        $purchaseReturns = $this->scopedQueryWithDates(PurchaseReturn::with('items'),                    $isSuper, $coId, $from, $to);

        // Only open cost layers needed — consumed layers (remaining_quantity=0) are historical
        // and already reflected in stored cogs on sale_items; frontend never reads costLayers
        $costLayers = collect();

        $openJobCards = $isSuper ? collect() : JobCard::with('items')
            ->where('company_id', $coId)
            ->where('status', 'open')
            ->get();

        $recentJobCards = $isSuper ? collect() : JobCard::where('company_id', $coId)
            ->where('status', 'closed')
            ->where('created_at', '>=', $from)
            ->when($to, fn($q) => $q->where('created_at', '<=', $to))
            ->orderByDesc('closed_at')
            ->limit(100)
            ->get();

        return [
            'loadedFrom'      => $from->toDateString(),
            'sales'           => SaleOrderResource::collection($sales),
            'purchaseOrders'  => PurchaseOrderResource::collection($purchaseOrders),
            'payments'        => PaymentResource::collection($payments),
            'ledger'          => InventoryLedgerResource::collection($ledger),
            'salesReturns'    => SaleReturnResource::collection($salesReturns),
            'purchaseReturns' => PurchaseReturnResource::collection($purchaseReturns),
            'costLayers'      => [],
            'jobCards'        => JobCardResource::collection($openJobCards),
            'jobCardHistory'  => JobCardResource::collection($recentJobCards),
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

    private function scopedQueryWithDates(
        \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $query,
        bool $isSuper,
        ?string $coId,
        \Carbon\Carbon $from,
        ?\Carbon\Carbon $to
    ) {
        $query->where('created_at', '>=', $from);
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        return $isSuper ? $query->get() : $query->where('company_id', $coId)->get();
    }
}
