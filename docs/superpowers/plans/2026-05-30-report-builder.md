# Report Builder Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a Report Builder Settings page where users map Chart of Accounts to fixed P&L and Balance Sheet lines, and ensure the P&L date filter includes a "Today" preset.

**Architecture:** New `report_line_mappings` table stores per-company account-to-line mappings. `ReportBuilderController` exposes GET/PUT endpoints for the config page. `ReportController` loads mappings at report-run time and uses them when present, falling back to the current sub_type logic otherwise.

**Tech Stack:** Laravel 12, PHP 8.2, MySQL, Vanilla JS, Tom Select (local `/dist/libs/tom-select/`), PHPUnit 11

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `database/migrations/2026_05_30_000001_create_report_line_mappings_table.php` | Create | Schema for report_line_mappings |
| `app/Models/ReportLineMapping.php` | Create | Eloquent model for report_line_mappings |
| `app/Http/Controllers/Api/ReportBuilderController.php` | Create | GET/PUT /api/report-builder/{type} |
| `tests/Feature/ReportBuilderTest.php` | Create | Feature tests for ReportBuilderController |
| `app/Http/Controllers/Api/ReportController.php` | Modify | Add mapped mode to profitLoss() and balanceSheet() |
| `routes/api.php` | Modify | 2 new API routes |
| `routes/web.php` | Modify | 1 new page route |
| `public/js/api.js` | Modify | getReportBuilder(), updateReportBuilder() |
| `resources/views/layouts/app.blade.php` | Modify | Sidebar link for Report Builder |
| `resources/views/pages/report-builder.blade.php` | Create | Settings page HTML shell |
| `public/js/pages/report-builder.js` | Create | Full mapping UI logic |
| `resources/views/pages/reports.blade.php` | Modify | Add "Today" button to P&L filter bar |
| `public/js/pages/reports.js` | Modify | Today preset + mapped render for P&L and BS |

---

## Task 1: Database Migration

**Files:**
- Create: `database/migrations/2026_05_30_000001_create_report_line_mappings_table.php`

- [ ] **Step 1: Create the migration file**

```php
<?php
// database/migrations/2026_05_30_000001_create_report_line_mappings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_line_mappings', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->enum('report_type', ['profit_loss', 'balance_sheet']);
            $table->string('line_key', 50);
            $table->string('account_id');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('chart_of_accounts')->onDelete('cascade');
            $table->unique(['company_id', 'report_type', 'account_id']);
            $table->index(['company_id', 'report_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_line_mappings');
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
/c/xampp/php/php artisan migrate
```

Expected output includes: `2026_05_30_000001_create_report_line_mappings_table ........ 34ms DONE`

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_05_30_000001_create_report_line_mappings_table.php
git commit -m "feat: add report_line_mappings migration"
```

---

## Task 2: ReportLineMapping Model

**Files:**
- Create: `app/Models/ReportLineMapping.php`

- [ ] **Step 1: Create the model**

```php
<?php
// app/Models/ReportLineMapping.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportLineMapping extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'report_type',
        'line_key',
        'account_id',
    ];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/ReportLineMapping.php
git commit -m "feat: add ReportLineMapping model"
```

---

## Task 3: ReportBuilderController + Feature Tests

**Files:**
- Create: `app/Http/Controllers/Api/ReportBuilderController.php`
- Create: `tests/Feature/ReportBuilderTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/ReportBuilderTest.php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\ReportLineMapping;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

class ReportBuilderTest extends ApiTestCase
{
    private function makeAccount(string $type, string $code = '4100'): ChartOfAccount
    {
        return ChartOfAccount::create([
            'id'         => 'COA-' . Str::random(9),
            'company_id' => $this->company->id,
            'code'       => $code,
            'name'       => $code . ' Account',
            'type'       => $type,
            'sub_type'   => null,
            'is_system'  => false,
            'is_active'  => true,
        ]);
    }

    #[Test]
    public function get_profit_loss_config_returns_three_lines_with_empty_accounts(): void
    {
        $response = $this->getJson('/api/report-builder/profit_loss', $this->auth());

        $response->assertOk()
                 ->assertJsonPath('reportType', 'profit_loss')
                 ->assertJsonCount(3, 'lines');

        $keys = collect($response->json('lines'))->pluck('lineKey')->toArray();
        $this->assertEqualsCanonicalizing(
            ['sales_revenue', 'cogs', 'operating_expenses'],
            $keys
        );
        // Each line starts with empty accounts
        foreach ($response->json('lines') as $line) {
            $this->assertIsArray($line['accounts']);
            $this->assertEmpty($line['accounts']);
        }
    }

    #[Test]
    public function get_balance_sheet_config_returns_six_lines(): void
    {
        $response = $this->getJson('/api/report-builder/balance_sheet', $this->auth());

        $response->assertOk()
                 ->assertJsonCount(6, 'lines');

        $keys = collect($response->json('lines'))->pluck('lineKey')->toArray();
        $this->assertEqualsCanonicalizing(
            ['current_assets', 'fixed_assets', 'other_assets',
             'current_liabilities', 'long_term_liabilities', 'owners_equity'],
            $keys
        );
    }

    #[Test]
    public function invalid_report_type_returns_422(): void
    {
        $this->getJson('/api/report-builder/invalid_type', $this->auth())
             ->assertStatus(422);
    }

    #[Test]
    public function super_admin_cannot_access_report_builder(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $token      = $this->loginAndGetToken($superAdmin);

        $this->getJson('/api/report-builder/profit_loss', $this->auth($token))
             ->assertStatus(403);
    }

    #[Test]
    public function save_and_retrieve_profit_loss_mappings(): void
    {
        $revenue = $this->makeAccount('Revenue', '4100');
        $cogs    = $this->makeAccount('Expense', '5000');

        $response = $this->putJson('/api/report-builder/profit_loss', [
            'mappings' => [
                'sales_revenue'      => [$revenue->id],
                'cogs'               => [$cogs->id],
                'operating_expenses' => [],
            ],
        ], $this->auth());

        $response->assertOk();

        $salesLine = collect($response->json('lines'))->firstWhere('lineKey', 'sales_revenue');
        $this->assertCount(1, $salesLine['accounts']);
        $this->assertEquals($revenue->id, $salesLine['accounts'][0]['id']);
        $this->assertEquals('4100', $salesLine['accounts'][0]['code']);

        $this->assertDatabaseHas('report_line_mappings', [
            'company_id'  => $this->company->id,
            'report_type' => 'profit_loss',
            'line_key'    => 'sales_revenue',
            'account_id'  => $revenue->id,
        ]);
    }

    #[Test]
    public function saving_replaces_previous_mappings(): void
    {
        $acc1 = $this->makeAccount('Revenue', '4100');
        $acc2 = $this->makeAccount('Revenue', '4200');

        $this->putJson('/api/report-builder/profit_loss', [
            'mappings' => [
                'sales_revenue'      => [$acc1->id],
                'cogs'               => [],
                'operating_expenses' => [],
            ],
        ], $this->auth());

        // Replace acc1 with acc2
        $this->putJson('/api/report-builder/profit_loss', [
            'mappings' => [
                'sales_revenue'      => [$acc2->id],
                'cogs'               => [],
                'operating_expenses' => [],
            ],
        ], $this->auth());

        $this->assertDatabaseMissing('report_line_mappings', ['account_id' => $acc1->id]);
        $this->assertDatabaseHas('report_line_mappings', ['account_id' => $acc2->id]);
    }

    #[Test]
    public function cannot_map_same_account_to_two_lines(): void
    {
        $acc = $this->makeAccount('Revenue', '4100');

        $this->putJson('/api/report-builder/profit_loss', [
            'mappings' => [
                'sales_revenue'      => [$acc->id],
                'operating_expenses' => [$acc->id],
                'cogs'               => [],
            ],
        ], $this->auth())->assertStatus(422);
    }

    #[Test]
    public function cannot_map_account_from_other_company(): void
    {
        $otherCompany  = $this->createCompany(['name' => 'Other Co']);
        $otherAccount  = ChartOfAccount::create([
            'id'         => 'COA-' . Str::random(9),
            'company_id' => $otherCompany->id,
            'code'       => '9999',
            'name'       => 'Foreign Account',
            'type'       => 'Revenue',
            'sub_type'   => null,
            'is_system'  => false,
            'is_active'  => true,
        ]);

        $this->putJson('/api/report-builder/profit_loss', [
            'mappings' => [
                'sales_revenue'      => [$otherAccount->id],
                'cogs'               => [],
                'operating_expenses' => [],
            ],
        ], $this->auth())->assertStatus(422);
    }
}
```

- [ ] **Step 2: Run tests — confirm they all fail**

```bash
/c/xampp/php/php artisan test tests/Feature/ReportBuilderTest.php
```

Expected: All 8 tests FAIL (ReportBuilderController does not exist yet).

- [ ] **Step 3: Create ReportBuilderController**

```php
<?php
// app/Http/Controllers/Api/ReportBuilderController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\ReportLineMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportBuilderController extends Controller
{
    private const LINES = [
        'profit_loss' => [
            'sales_revenue'      => 'Sales Revenue',
            'cogs'               => 'Cost of Goods Sold',
            'operating_expenses' => 'Operating Expenses',
        ],
        'balance_sheet' => [
            'current_assets'        => 'Current Assets',
            'fixed_assets'          => 'Fixed Assets',
            'other_assets'          => 'Other Assets',
            'current_liabilities'   => 'Current Liabilities',
            'long_term_liabilities' => 'Long-term Liabilities',
            'owners_equity'         => "Owner's Equity",
        ],
    ];

    public function index(Request $request, string $type)
    {
        if (!array_key_exists($type, self::LINES)) {
            return response()->json(['error' => 'Invalid report type.'], 422);
        }

        $user = $request->get('auth_user');
        if (!$user->company_id) {
            return response()->json(['error' => 'Not available for Super Admin.'], 403);
        }

        $mappings = ReportLineMapping::where('company_id', $user->company_id)
            ->where('report_type', $type)
            ->with('account:id,code,name')
            ->get()
            ->groupBy('line_key');

        $lines = collect(self::LINES[$type])->map(function ($label, $lineKey) use ($mappings) {
            $accounts = ($mappings[$lineKey] ?? collect())->map(fn($m) => [
                'id'   => $m->account->id,
                'code' => $m->account->code,
                'name' => $m->account->name,
            ])->values();

            return ['lineKey' => $lineKey, 'label' => $label, 'accounts' => $accounts];
        })->values();

        return response()->json(['reportType' => $type, 'lines' => $lines]);
    }

    public function update(Request $request, string $type)
    {
        if (!array_key_exists($type, self::LINES)) {
            return response()->json(['error' => 'Invalid report type.'], 422);
        }

        $user = $request->get('auth_user');
        if (!$user->company_id) {
            return response()->json(['error' => 'Not available for Super Admin.'], 403);
        }

        $data = $request->validate([
            'mappings'     => 'required|array',
            'mappings.*'   => 'array',
            'mappings.*.*' => 'string|exists:chart_of_accounts,id',
        ]);

        $validKeys = array_keys(self::LINES[$type]);
        foreach (array_keys($data['mappings']) as $key) {
            if (!in_array($key, $validKeys)) {
                return response()->json(['error' => "Invalid line key: {$key}"], 422);
            }
        }

        $allAccountIds = collect($data['mappings'])->flatten()->filter()->toArray();

        if (count($allAccountIds) !== count(array_unique($allAccountIds))) {
            return response()->json(['error' => 'An account cannot be mapped to more than one line.'], 422);
        }

        if (!empty($allAccountIds)) {
            $count = ChartOfAccount::where('company_id', $user->company_id)
                ->whereIn('id', $allAccountIds)
                ->count();
            if ($count !== count($allAccountIds)) {
                return response()->json(['error' => 'One or more accounts do not belong to this company.'], 422);
            }
        }

        DB::transaction(function () use ($user, $type, $data) {
            ReportLineMapping::where('company_id', $user->company_id)
                ->where('report_type', $type)
                ->delete();

            foreach ($data['mappings'] as $lineKey => $accountIds) {
                foreach (array_filter($accountIds) as $accountId) {
                    ReportLineMapping::create([
                        'id'          => 'RLM-' . Str::random(9),
                        'company_id'  => $user->company_id,
                        'report_type' => $type,
                        'line_key'    => $lineKey,
                        'account_id'  => $accountId,
                    ]);
                }
            }
        });

        return $this->index($request, $type);
    }
}
```

- [ ] **Step 4: Add routes to `routes/api.php`**

Find the authenticated route group that contains existing report routes (search for `reports/profit-loss`). Add these two lines immediately after the existing report routes:

```php
Route::get('/report-builder/{type}',  [ReportBuilderController::class, 'index']);
Route::put('/report-builder/{type}',  [ReportBuilderController::class, 'update']);
```

Also add the use statement at the top of the file:

```php
use App\Http\Controllers\Api\ReportBuilderController;
```

- [ ] **Step 5: Run the tests — all should pass**

```bash
/c/xampp/php/php artisan test tests/Feature/ReportBuilderTest.php
```

Expected: 8 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/ReportBuilderController.php tests/Feature/ReportBuilderTest.php routes/api.php
git commit -m "feat: add ReportBuilderController with GET/PUT endpoints and tests"
```

---

## Task 4: ReportController — P&L Mapped Mode

**Files:**
- Modify: `app/Http/Controllers/Api/ReportController.php`

- [ ] **Step 1: Replace the entire `profitLoss()` method and add two private helpers**

Open `app/Http/Controllers/Api/ReportController.php`. Add this import at the top with the other use statements:

```php
use App\Models\ChartOfAccount;
use App\Models\ReportLineMapping;
```

Replace the existing `profitLoss()` method (lines 13–83) with:

```php
public function profitLoss(Request $request)
{
    $user = $request->get('auth_user');
    if (!$user->company_id) {
        return response()->json(['error' => 'Reports are not available for Super Admin. Please select a company.'], 403);
    }

    $from = $request->input('from');
    $to   = $request->input('to');

    $request->validate([
        'from' => 'required|date',
        'to'   => 'required|date|after_or_equal:from',
    ]);

    $salesReturns = (float) SaleReturn::where('sale_returns.company_id', $user->company_id)
        ->join('sale_orders', 'sale_returns.original_sale_id', '=', 'sale_orders.invoice_no')
        ->whereDate('sale_orders.created_at', '>=', $from)
        ->whereDate('sale_orders.created_at', '<=', $to)
        ->sum('sale_returns.total_amount');

    $mappings = ReportLineMapping::where('company_id', $user->company_id)
        ->where('report_type', 'profit_loss')
        ->get()
        ->groupBy('line_key');

    if ($mappings->isNotEmpty()) {
        return $this->profitLossMapped($user->company_id, $from, $to, $mappings, $salesReturns);
    }

    return $this->profitLossFallback($user->company_id, $from, $to, $salesReturns);
}

private function profitLossFallback(string $companyId, string $from, string $to, float $salesReturns): \Illuminate\Http\JsonResponse
{
    $lines = JournalEntryLine::query()
        ->join('chart_of_accounts as coa', 'journal_entry_lines.account_id', '=', 'coa.id')
        ->join('journal_entries as je', 'journal_entry_lines.journal_entry_id', '=', 'je.id')
        ->where('je.company_id', $companyId)
        ->where('je.is_posted', true)
        ->whereDate('je.date', '>=', $from)
        ->whereDate('je.date', '<=', $to)
        ->whereIn('coa.type', ['Revenue', 'Expense'])
        ->select(
            'coa.id', 'coa.code', 'coa.name', 'coa.type', 'coa.sub_type',
            DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
            DB::raw('SUM(journal_entry_lines.credit) as total_credit')
        )
        ->groupBy('coa.id', 'coa.code', 'coa.name', 'coa.type', 'coa.sub_type')
        ->orderBy('coa.code')
        ->get();

    $revenue      = $lines->where('type', 'Revenue');
    $expenses     = $lines->where('type', 'Expense');
    $cogsAccounts = $expenses->where('sub_type', 'cost_of_goods_sold');
    $opexAccounts = $expenses->whereNotIn('sub_type', ['cost_of_goods_sold']);

    $totalRevenue  = $revenue->sum(fn($a) => $a->total_credit - $a->total_debit);
    $totalCogs     = $cogsAccounts->sum(fn($a) => $a->total_debit - $a->total_credit);
    $totalExpenses = $opexAccounts->sum(fn($a) => $a->total_debit - $a->total_credit);
    $netRevenue    = $totalRevenue - $salesReturns;
    $grossProfit   = $netRevenue - $totalCogs;
    $netProfit     = $grossProfit - $totalExpenses;

    return response()->json([
        'period'        => ['from' => $from, 'to' => $to],
        'useMappings'   => false,
        'revenue'       => $this->formatAccountGroup($revenue->groupBy('sub_type'), 'credit'),
        'totalRevenue'  => round($totalRevenue, 2),
        'salesReturns'  => round($salesReturns, 2),
        'netRevenue'    => round($netRevenue, 2),
        'cogs'          => $this->formatAccountGroup($cogsAccounts->groupBy('sub_type'), 'debit'),
        'totalCogs'     => round($totalCogs, 2),
        'grossProfit'   => round($grossProfit, 2),
        'expenses'      => $this->formatAccountGroup($opexAccounts->groupBy('sub_type'), 'debit'),
        'totalExpenses' => round($totalExpenses, 2),
        'netProfit'     => round($netProfit, 2),
    ]);
}

private function profitLossMapped(string $companyId, string $from, string $to, $mappings, float $salesReturns): \Illuminate\Http\JsonResponse
{
    $lineConfig = [
        'sales_revenue'      => ['label' => 'Sales Revenue',      'normalBalance' => 'credit'],
        'cogs'               => ['label' => 'Cost of Goods Sold',  'normalBalance' => 'debit'],
        'operating_expenses' => ['label' => 'Operating Expenses',  'normalBalance' => 'debit'],
    ];

    $allMappedIds = $mappings->flatten()->pluck('account_id')->filter()->toArray();

    $journalTotals = [];
    if (!empty($allMappedIds)) {
        JournalEntryLine::query()
            ->join('journal_entries as je', 'journal_entry_lines.journal_entry_id', '=', 'je.id')
            ->where('je.company_id', $companyId)
            ->where('je.is_posted', true)
            ->whereDate('je.date', '>=', $from)
            ->whereDate('je.date', '<=', $to)
            ->whereIn('journal_entry_lines.account_id', $allMappedIds)
            ->select(
                'journal_entry_lines.account_id',
                DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit) as total_credit')
            )
            ->groupBy('journal_entry_lines.account_id')
            ->get()
            ->each(function ($row) use (&$journalTotals) {
                $journalTotals[$row->account_id] = $row;
            });
    }

    $accountDetails = ChartOfAccount::whereIn('id', $allMappedIds)
        ->select('id', 'code', 'name')
        ->get()
        ->keyBy('id');

    $lines = [];
    foreach ($lineConfig as $lineKey => $config) {
        $lineMappings = $mappings[$lineKey] ?? collect();
        $lineAccounts = [];
        $lineTotal    = 0;

        foreach ($lineMappings as $mapping) {
            $acc    = $accountDetails[$mapping->account_id] ?? null;
            if (!$acc) continue;
            $jt     = $journalTotals[$acc->id] ?? null;
            $debit  = $jt ? (float) $jt->total_debit  : 0;
            $credit = $jt ? (float) $jt->total_credit : 0;
            $balance = $config['normalBalance'] === 'credit'
                ? $credit - $debit
                : $debit  - $credit;

            $lineAccounts[] = ['id' => $acc->id, 'code' => $acc->code, 'name' => $acc->name, 'balance' => round($balance, 2)];
            $lineTotal += $balance;
        }

        $lines[$lineKey] = ['label' => $config['label'], 'accounts' => $lineAccounts, 'total' => round($lineTotal, 2)];
    }

    $allRevExpIds = ChartOfAccount::where('company_id', $companyId)
        ->whereIn('type', ['Revenue', 'Expense'])
        ->pluck('id')->toArray();

    $unmappedAccounts = ChartOfAccount::whereIn('id', array_diff($allRevExpIds, $allMappedIds))
        ->select('id', 'code', 'name', 'type')
        ->get()
        ->map(fn($a) => ['id' => $a->id, 'code' => $a->code, 'name' => $a->name, 'type' => $a->type])
        ->values()->toArray();

    $totalRevenue  = $lines['sales_revenue']['total'];
    $totalCogs     = $lines['cogs']['total'];
    $totalExpenses = $lines['operating_expenses']['total'];
    $netRevenue    = $totalRevenue - $salesReturns;
    $grossProfit   = $netRevenue - $totalCogs;
    $netProfit     = $grossProfit - $totalExpenses;

    return response()->json([
        'period'           => ['from' => $from, 'to' => $to],
        'useMappings'      => true,
        'lines'            => $lines,
        'salesReturns'     => round($salesReturns, 2),
        'netRevenue'       => round($netRevenue, 2),
        'grossProfit'      => round($grossProfit, 2),
        'netProfit'        => round($netProfit, 2),
        'unmappedAccounts' => $unmappedAccounts,
    ]);
}
```

- [ ] **Step 2: Run existing tests to confirm no regressions**

```bash
/c/xampp/php/php artisan test tests/Feature/
```

Expected: All existing tests pass (AuthTest, UserTest, PartyTest, SaleTest, PurchaseTest, PaymentTest, ReportBuilderTest).

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Api/ReportController.php
git commit -m "feat: add P&L mapped mode to ReportController with fallback"
```

---

## Task 5: ReportController — Balance Sheet Mapped Mode

**Files:**
- Modify: `app/Http/Controllers/Api/ReportController.php`

- [ ] **Step 1: Replace `balanceSheet()` and add `balanceSheetMapped()` helper**

Replace the existing `balanceSheet()` method (lines starting at `public function balanceSheet`) with:

```php
public function balanceSheet(Request $request)
{
    $user = $request->get('auth_user');
    if (!$user->company_id) {
        return response()->json(['error' => 'Reports are not available for Super Admin. Please select a company.'], 403);
    }

    $asOf = $request->input('as_of');
    $request->validate(['as_of' => 'required|date']);

    $mappings = ReportLineMapping::where('company_id', $user->company_id)
        ->where('report_type', 'balance_sheet')
        ->get()
        ->groupBy('line_key');

    if ($mappings->isNotEmpty()) {
        return $this->balanceSheetMapped($user->company_id, $asOf, $mappings);
    }

    return $this->balanceSheetFallback($user->company_id, $asOf);
}

private function balanceSheetFallback(string $companyId, string $asOf): \Illuminate\Http\JsonResponse
{
    $lines = DB::table('chart_of_accounts as coa')
        ->where('coa.company_id', $companyId)
        ->whereIn('coa.type', ['Asset', 'Liability', 'Equity'])
        ->leftJoin('journal_entry_lines as jel', function ($join) use ($asOf, $companyId) {
            $join->on('jel.account_id', '=', 'coa.id')
                ->whereExists(function ($q) use ($asOf, $companyId) {
                    $q->from('journal_entries as je')
                        ->whereColumn('je.id', 'jel.journal_entry_id')
                        ->where('je.company_id', $companyId)
                        ->where('je.is_posted', true)
                        ->whereDate('je.date', '<=', $asOf);
                });
        })
        ->select(
            'coa.id', 'coa.code', 'coa.name', 'coa.type', 'coa.sub_type',
            DB::raw('COALESCE(coa.opening_balance, 0) as opening_balance'),
            DB::raw('COALESCE(SUM(jel.debit), 0) as total_debit'),
            DB::raw('COALESCE(SUM(jel.credit), 0) as total_credit')
        )
        ->groupBy('coa.id', 'coa.code', 'coa.name', 'coa.type', 'coa.sub_type', 'coa.opening_balance')
        ->orderBy('coa.code')
        ->get();

    $retainedEarnings     = $this->calculateRetainedEarnings($companyId, $asOf);
    $openingBalanceEquity = $this->calculateOpeningBalanceEquity($companyId);

    $assets      = $lines->where('type', 'Asset');
    $liabilities = $lines->where('type', 'Liability');
    $equity      = $lines->where('type', 'Equity');

    $totalAssets      = $assets->sum(fn($a) => $a->opening_balance + $a->total_debit - $a->total_credit);
    $totalLiabilities = $liabilities->sum(fn($a) => $a->opening_balance + $a->total_credit - $a->total_debit);
    $totalEquityAccts = $equity->sum(fn($a) => $a->opening_balance + $a->total_credit - $a->total_debit);
    $totalEquity      = $totalEquityAccts + $retainedEarnings + $openingBalanceEquity;

    return response()->json([
        'asOf'                 => $asOf,
        'useMappings'          => false,
        'assets'               => $this->formatAccountGroupWithOpening($assets->groupBy('sub_type'), 'debit'),
        'totalAssets'          => round($totalAssets, 2),
        'liabilities'          => $this->formatAccountGroupWithOpening($liabilities->groupBy('sub_type'), 'credit'),
        'totalLiabilities'     => round($totalLiabilities, 2),
        'equity'               => $this->formatAccountGroupWithOpening($equity->groupBy('sub_type'), 'credit'),
        'openingBalanceEquity' => round($openingBalanceEquity, 2),
        'retainedEarnings'     => round($retainedEarnings, 2),
        'totalEquity'          => round($totalEquity, 2),
        'totalLiabEquity'      => round($totalLiabilities + $totalEquity, 2),
    ]);
}

private function balanceSheetMapped(string $companyId, string $asOf, $mappings): \Illuminate\Http\JsonResponse
{
    $lineConfig = [
        'current_assets'        => ['label' => 'Current Assets',        'isAsset' => true],
        'fixed_assets'          => ['label' => 'Fixed Assets',           'isAsset' => true],
        'other_assets'          => ['label' => 'Other Assets',           'isAsset' => true],
        'current_liabilities'   => ['label' => 'Current Liabilities',    'isAsset' => false],
        'long_term_liabilities' => ['label' => 'Long-term Liabilities',  'isAsset' => false],
        'owners_equity'         => ['label' => "Owner's Equity",          'isAsset' => false],
    ];

    $allMappedIds = $mappings->flatten()->pluck('account_id')->filter()->toArray();

    // Journal totals up to asOf
    $journalTotals = [];
    if (!empty($allMappedIds)) {
        DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->where('je.company_id', $companyId)
            ->where('je.is_posted', true)
            ->whereDate('je.date', '<=', $asOf)
            ->whereIn('jel.account_id', $allMappedIds)
            ->select('jel.account_id', DB::raw('SUM(jel.debit) as total_debit'), DB::raw('SUM(jel.credit) as total_credit'))
            ->groupBy('jel.account_id')
            ->get()
            ->each(function ($row) use (&$journalTotals) {
                $journalTotals[$row->account_id] = $row;
            });
    }

    $accountDetails = ChartOfAccount::whereIn('id', $allMappedIds)
        ->select('id', 'code', 'name', 'opening_balance')
        ->get()
        ->keyBy('id');

    $lines = [];
    foreach ($lineConfig as $lineKey => $config) {
        $lineMappings = $mappings[$lineKey] ?? collect();
        $lineAccounts = [];
        $lineTotal    = 0;

        foreach ($lineMappings as $mapping) {
            $acc     = $accountDetails[$mapping->account_id] ?? null;
            if (!$acc) continue;
            $jt      = $journalTotals[$acc->id] ?? null;
            $debit   = $jt ? (float) $jt->total_debit  : 0;
            $credit  = $jt ? (float) $jt->total_credit : 0;
            $opening = (float) ($acc->opening_balance ?? 0);
            $balance = $config['isAsset']
                ? $opening + $debit - $credit
                : $opening + $credit - $debit;

            if (abs($balance) < 0.005) continue;

            $lineAccounts[] = ['id' => $acc->id, 'code' => $acc->code, 'name' => $acc->name, 'balance' => round($balance, 2)];
            $lineTotal += $balance;
        }

        $lines[$lineKey] = ['label' => $config['label'], 'accounts' => $lineAccounts, 'total' => round($lineTotal, 2)];
    }

    $retainedEarnings     = $this->calculateRetainedEarnings($companyId, $asOf);
    $openingBalanceEquity = $this->calculateOpeningBalanceEquity($companyId);

    $assetKeys     = ['current_assets', 'fixed_assets', 'other_assets'];
    $liabilityKeys = ['current_liabilities', 'long_term_liabilities'];

    $totalAssets      = array_sum(array_map(fn($k) => $lines[$k]['total'], $assetKeys));
    $totalLiabilities = array_sum(array_map(fn($k) => $lines[$k]['total'], $liabilityKeys));
    $totalEquity      = $lines['owners_equity']['total'] + $retainedEarnings + $openingBalanceEquity;

    $allBsIds = ChartOfAccount::where('company_id', $companyId)
        ->whereIn('type', ['Asset', 'Liability', 'Equity'])
        ->pluck('id')->toArray();

    $unmappedAccounts = ChartOfAccount::whereIn('id', array_diff($allBsIds, $allMappedIds))
        ->select('id', 'code', 'name', 'type')
        ->get()
        ->map(fn($a) => ['id' => $a->id, 'code' => $a->code, 'name' => $a->name, 'type' => $a->type])
        ->values()->toArray();

    return response()->json([
        'asOf'                 => $asOf,
        'useMappings'          => true,
        'lines'                => $lines,
        'retainedEarnings'     => round($retainedEarnings, 2),
        'openingBalanceEquity' => round($openingBalanceEquity, 2),
        'totalAssets'          => round($totalAssets, 2),
        'totalLiabilities'     => round($totalLiabilities, 2),
        'totalEquity'          => round($totalEquity, 2),
        'totalLiabEquity'      => round($totalLiabilities + $totalEquity, 2),
        'unmappedAccounts'     => $unmappedAccounts,
    ]);
}
```

- [ ] **Step 2: Run full test suite**

```bash
/c/xampp/php/php artisan test tests/Feature/
```

Expected: All tests pass.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Api/ReportController.php
git commit -m "feat: add Balance Sheet mapped mode to ReportController"
```

---

## Task 6: Web Route + api.js Methods

**Files:**
- Modify: `routes/web.php`
- Modify: `public/js/api.js`

- [ ] **Step 1: Add web route in `routes/web.php`**

Find the block of existing `Route::get(...)` page routes and add:

```php
Route::get('/report-builder', fn() => view('pages.report-builder'));
```

- [ ] **Step 2: Add two API methods in `public/js/api.js`**

Find the `getBalanceSheet` method (around line 307) and add immediately after it:

```js
        getReportBuilder: function(type) {
            return request('GET', '/report-builder/' + type);
        },
        updateReportBuilder: function(type, mappings) {
            return request('PUT', '/report-builder/' + type, { mappings: mappings });
        },
```

- [ ] **Step 3: Commit**

```bash
git add routes/web.php public/js/api.js
git commit -m "feat: add report-builder web route and api.js methods"
```

---

## Task 7: Sidebar Link

**Files:**
- Modify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: Add Report Builder link in the Accounting sidebar section**

Find this block in `app.blade.php` (around line 141–146):

```html
<li class="nav-item" data-module="Accounting">
    <a class="nav-link" href="{{ $base }}/accounting/coa" data-nav-path="/accounting/coa">
        <span class="nav-link-icon"><i class="ti ti-list-numbers"></i></span>
        <span class="nav-link-title">Chart of Accounts</span>
    </a>
</li>
```

Add the following block **immediately after** that `</li>`:

```html
<li class="nav-item" data-module="Accounting">
    <a class="nav-link" href="{{ $base }}/report-builder" data-nav-path="/report-builder">
        <span class="nav-link-icon"><i class="ti ti-layout-list"></i></span>
        <span class="nav-link-title">Report Builder</span>
    </a>
</li>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/layouts/app.blade.php
git commit -m "feat: add Report Builder sidebar link"
```

---

## Task 8: Report Builder Blade Page

**Files:**
- Create: `resources/views/pages/report-builder.blade.php`

- [ ] **Step 1: Create the Blade file**

```blade
{{-- resources/views/pages/report-builder.blade.php --}}
@extends('layouts.app')

@section('title', 'Report Builder')

@section('content')
<div class="container-xl">
  <div class="page-header mb-3">
    <div class="row align-items-center">
      <div class="col">
        <h2 class="page-title">Report Builder</h2>
        <div class="text-muted mt-1">Map your Chart of Accounts to P&amp;L and Balance Sheet lines</div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header p-0 border-bottom">
      <ul class="nav nav-tabs card-header-tabs ms-0" id="rbTabs">
        <li class="nav-item">
          <button class="nav-link active px-4" id="rb-tab-pl" onclick="rbSwitchTab('profit_loss')">
            <i class="ti ti-chart-bar me-1"></i> Profit &amp; Loss
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link px-4" id="rb-tab-bs" onclick="rbSwitchTab('balance_sheet')">
            <i class="ti ti-scale me-1"></i> Balance Sheet
          </button>
        </li>
      </ul>
    </div>

    <div class="card-body">
      <div id="rb-info-banner" class="alert alert-info alert-dismissible mb-3" role="alert">
        <i class="ti ti-info-circle me-1"></i>
        Map your Chart of Accounts to each report line. One account can only be assigned to one line per report.
        Save when done — reports will use these mappings immediately.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>

      <div id="rb-loading" class="text-center py-5 d-none">
        <div class="spinner-border text-primary"></div>
        <div class="mt-2 text-muted">Loading configuration…</div>
      </div>

      <div id="rb-content" class="d-none">
        <table class="table table-bordered align-middle mb-3" id="rb-table">
          <thead class="table-light">
            <tr>
              <th style="width:30%">Report Line</th>
              <th>Mapped Accounts <span class="text-muted fw-normal">(search by code or name)</span></th>
            </tr>
          </thead>
          <tbody id="rb-tbody"></tbody>
        </table>

        <div id="rb-unmapped-warning" class="alert alert-warning d-none mb-3">
          <i class="ti ti-alert-triangle me-1"></i>
          <strong>Unmapped accounts:</strong>
          <span id="rb-unmapped-list"></span>
          <span class="text-muted ms-2">— these will appear as a warning in the report</span>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <button class="btn btn-outline-secondary" onclick="rbReset()">
            <i class="ti ti-refresh me-1"></i> Reset
          </button>
          <button class="btn btn-primary" id="rb-save-btn" onclick="rbSave()" disabled>
            <i class="ti ti-device-floppy me-1"></i> Save Mapping
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="/dist/libs/tom-select/css/tom-select.bootstrap5.min.css">
<script src="/dist/libs/tom-select/js/tom-select.complete.min.js"></script>
<script src="/js/pages/report-builder.js"></script>
@endpush
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/pages/report-builder.blade.php
git commit -m "feat: add report-builder blade page"
```

---

## Task 9: Report Builder JS

**Files:**
- Create: `public/js/pages/report-builder.js`

- [ ] **Step 1: Create the JS file**

```js
// public/js/pages/report-builder.js

var _rbCurrentType = 'profit_loss';
var _rbDirty = false;
var _rbTomSelects = {};
var _rbAllAccounts = [];  // all COA accounts for this company

// Type → which COA types are relevant in the dropdown
var _rbAccountTypes = {
    profit_loss:   ['Revenue', 'Expense'],
    balance_sheet: ['Asset', 'Liability', 'Equity'],
};

window.ERP.onReady = function() {
    _rbAllAccounts = (window.ERP.state.chartOfAccounts || []);
    loadReportBuilder('profit_loss');
};

function rbSwitchTab(type) {
    if (type === _rbCurrentType) return;
    _rbCurrentType = type;

    document.getElementById('rb-tab-pl').classList.toggle('active', type === 'profit_loss');
    document.getElementById('rb-tab-bs').classList.toggle('active', type === 'balance_sheet');

    _rbDirty = false;
    document.getElementById('rb-save-btn').disabled = true;
    loadReportBuilder(type);
}

function loadReportBuilder(type) {
    document.getElementById('rb-loading').classList.remove('d-none');
    document.getElementById('rb-content').classList.add('d-none');

    ERP.api.getReportBuilder(type)
        .then(function(data) {
            document.getElementById('rb-loading').classList.add('d-none');
            rbRender(data, type);
            document.getElementById('rb-content').classList.remove('d-none');
        })
        .catch(function(e) {
            document.getElementById('rb-loading').classList.add('d-none');
            alert('Error loading report config: ' + e.message);
        });
}

function rbRender(data, type) {
    // Destroy existing Tom Select instances
    Object.values(_rbTomSelects).forEach(function(ts) { ts.destroy(); });
    _rbTomSelects = {};

    var relevantTypes = _rbAccountTypes[type] || [];
    var filteredAccounts = _rbAllAccounts.filter(function(a) {
        return relevantTypes.indexOf(a.type) !== -1;
    });

    var sectionColors = {
        profit_loss: {
            sales_revenue:      '#eff6ff',
            cogs:               '#fef3c7',
            operating_expenses: '#fef2f2',
        },
        balance_sheet: {
            current_assets:        '#eff6ff',
            fixed_assets:          '#eff6ff',
            other_assets:          '#eff6ff',
            current_liabilities:   '#fef3c7',
            long_term_liabilities: '#fef3c7',
            owners_equity:         '#f5f3ff',
        },
    };

    var html = '';
    (data.lines || []).forEach(function(line) {
        var bg = (sectionColors[type] || {})[line.lineKey] || '#fff';
        html += '<tr style="background:' + bg + '">';
        html += '<td class="fw-semibold">' + line.label + '</td>';
        html += '<td><select id="rb-select-' + line.lineKey + '" multiple placeholder="Select accounts…"></select></td>';
        html += '</tr>';
    });
    document.getElementById('rb-tbody').innerHTML = html;

    // Initialize Tom Select for each row
    (data.lines || []).forEach(function(line) {
        var el = document.getElementById('rb-select-' + line.lineKey);
        if (!el) return;

        var options = filteredAccounts.map(function(a) {
            return { value: a.id, text: a.code + ' - ' + a.name };
        });
        var items = (line.accounts || []).map(function(a) { return a.id; });

        var ts = new TomSelect(el, {
            options:     options,
            items:       items,
            maxItems:    null,
            valueField:  'value',
            labelField:  'text',
            searchField: ['text'],
            plugins:     ['remove_button'],
            onChange: function() {
                _rbDirty = true;
                document.getElementById('rb-save-btn').disabled = false;
            },
        });
        _rbTomSelects[line.lineKey] = ts;
    });
}

function rbSave() {
    var mappings = {};
    Object.keys(_rbTomSelects).forEach(function(lineKey) {
        mappings[lineKey] = _rbTomSelects[lineKey].getValue();
    });

    document.getElementById('rb-save-btn').disabled = true;

    ERP.api.updateReportBuilder(_rbCurrentType, mappings)
        .then(function(data) {
            _rbDirty = false;
            rbRenderUnmappedWarning([]);  // server returns updated config — re-render
            rbRender(data, _rbCurrentType);

            var toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 m-3 alert alert-success shadow';
            toast.style.zIndex = '9999';
            toast.innerHTML = '<i class="ti ti-check me-1"></i> Mapping saved successfully.';
            document.body.appendChild(toast);
            setTimeout(function() { toast.remove(); }, 3000);
        })
        .catch(function(e) {
            document.getElementById('rb-save-btn').disabled = false;
            alert('Error saving mapping: ' + e.message);
        });
}

function rbReset() {
    if (!confirm('Clear all mappings for this report type? This cannot be undone.')) return;

    var emptyMappings = {};
    Object.keys(_rbTomSelects).forEach(function(lineKey) {
        emptyMappings[lineKey] = [];
    });

    ERP.api.updateReportBuilder(_rbCurrentType, emptyMappings)
        .then(function(data) {
            _rbDirty = false;
            document.getElementById('rb-save-btn').disabled = true;
            rbRender(data, _rbCurrentType);
            rbRenderUnmappedWarning([]);
        })
        .catch(function(e) { alert('Error: ' + e.message); });
}

function rbRenderUnmappedWarning(unmapped) {
    var el = document.getElementById('rb-unmapped-warning');
    if (!unmapped || !unmapped.length) {
        el.classList.add('d-none');
        return;
    }
    document.getElementById('rb-unmapped-list').textContent =
        unmapped.map(function(a) { return a.code + ' - ' + a.name; }).join(', ');
    el.classList.remove('d-none');
}
```

- [ ] **Step 2: Commit**

```bash
git add public/js/pages/report-builder.js
git commit -m "feat: add report-builder.js with Tom Select mapping UI"
```

---

## Task 10: Date Filter Fix + Mapped Render in Reports

**Files:**
- Modify: `resources/views/pages/reports.blade.php`
- Modify: `public/js/pages/reports.js`

- [ ] **Step 1: Add "Today" button in `reports.blade.php`**

Find this line (around line 805):

```html
<button class="btn btn-sm btn-outline-secondary" onclick="rptPlSetPeriod('month')">This Month</button>
```

Add a "Today" button **immediately before** it:

```html
<button class="btn btn-sm btn-outline-secondary" onclick="rptPlSetPeriod('today')">Today</button>
```

The block should now read:

```html
<button class="btn btn-sm btn-outline-secondary" onclick="rptPlSetPeriod('today')">Today</button>
<button class="btn btn-sm btn-outline-secondary" onclick="rptPlSetPeriod('month')">This Month</button>
<button class="btn btn-sm btn-outline-secondary" onclick="rptPlSetPeriod('quarter')">This Quarter</button>
<button class="btn btn-sm btn-outline-secondary" onclick="rptPlSetPeriod('year')">This Year</button>
```

- [ ] **Step 2: Add 'today' case to `rptPlSetPeriod()` in `reports.js`**

Find `rptPlSetPeriod` (line 2008). The function currently has `if (period === 'month') ... else if (period === 'quarter') ... else { year }`. Add a `today` case:

Replace:

```js
function rptPlSetPeriod(period) {
  var now = new Date(), y = now.getFullYear(), m = now.getMonth();
  var from, to;
  if (period === 'month') {
    from = new Date(y, m, 1); to = new Date(y, m+1, 0);
  } else if (period === 'quarter') {
    var q = Math.floor(m/3);
    from = new Date(y, q*3, 1); to = new Date(y, q*3+3, 0);
  } else {
    from = new Date(y, 0, 1); to = new Date(y, 11, 31);
  }
  document.getElementById('rptPlFrom').value = rptPlFmtDate(from);
  document.getElementById('rptPlTo').value   = rptPlFmtDate(to);
}
```

With:

```js
function rptPlSetPeriod(period) {
  var now = new Date(), y = now.getFullYear(), m = now.getMonth();
  var from, to;
  if (period === 'today') {
    from = now; to = now;
  } else if (period === 'month') {
    from = new Date(y, m, 1); to = new Date(y, m+1, 0);
  } else if (period === 'quarter') {
    var q = Math.floor(m/3);
    from = new Date(y, q*3, 1); to = new Date(y, q*3+3, 0);
  } else {
    from = new Date(y, 0, 1); to = new Date(y, 11, 31);
  }
  document.getElementById('rptPlFrom').value = rptPlFmtDate(from);
  document.getElementById('rptPlTo').value   = rptPlFmtDate(to);
  runProfitLoss();
}
```

Note: `runProfitLoss()` is added at the end so every preset immediately fires the report, matching Balance Sheet preset behavior.

- [ ] **Step 3: Update `rptRenderPL()` to handle mapped format**

Find `rptRenderPL` (line 2038). Replace the entire function with:

```js
function rptRenderPL(data, from, to) {
  document.getElementById('rptPlPeriodLabel').textContent = 'Period: ' + from + ' to ' + to;
  var html = '';

  if (data.useMappings && data.lines) {
    // Mapped mode — fixed lines from Report Builder
    var lines = data.lines;
    html += '<tr class="pl-section-row"><td colspan="2">Revenue</td></tr>';
    (lines.sales_revenue && lines.sales_revenue.accounts || []).forEach(function(acc) {
      html += '<tr><td style="padding-left:28px!important;"><code style="font-size:0.78rem;color:#3B4FE4;">' + (acc.code||'') + '</code> ' + (acc.name||'') + '</td><td class="text-end">' + ERP.formatCurrency(acc.balance||0) + '</td></tr>';
    });
    if (!lines.sales_revenue || !lines.sales_revenue.accounts.length) {
      html += '<tr><td colspan="2" class="text-center text-muted py-2" style="font-size:0.8rem;">No accounts mapped to Sales Revenue.</td></tr>';
    }
    html += '<tr class="pl-subtotal-row"><td>Sales Revenue</td><td class="text-end">' + ERP.formatCurrency((lines.sales_revenue||{}).total||0) + '</td></tr>';
    if (data.salesReturns > 0) {
      html += '<tr><td style="padding-left:28px!important;color:#dc2626;">Less: Sales Returns</td><td class="text-end" style="color:#dc2626;">(' + ERP.formatCurrency(data.salesReturns) + ')</td></tr>';
    }
    html += '<tr class="pl-total-row profit"><td>Net Revenue</td><td class="text-end">' + ERP.formatCurrency(data.netRevenue||0) + '</td></tr>';

    html += '<tr class="pl-section-row"><td colspan="2">Cost of Goods Sold</td></tr>';
    (lines.cogs && lines.cogs.accounts || []).forEach(function(acc) {
      html += '<tr><td style="padding-left:28px!important;"><code style="font-size:0.78rem;color:#3B4FE4;">' + (acc.code||'') + '</code> ' + (acc.name||'') + '</td><td class="text-end">' + ERP.formatCurrency(acc.balance||0) + '</td></tr>';
    });
    html += '<tr class="pl-subtotal-row"><td>Total COGS</td><td class="text-end">' + ERP.formatCurrency((lines.cogs||{}).total||0) + '</td></tr>';
    var grossProfit = data.grossProfit || 0;
    html += '<tr class="pl-total-row ' + (grossProfit>=0?'profit':'loss') + '"><td>Gross Profit</td><td class="text-end">' + ERP.formatCurrency(grossProfit) + '</td></tr>';

    html += '<tr class="pl-section-row"><td colspan="2">Operating Expenses</td></tr>';
    (lines.operating_expenses && lines.operating_expenses.accounts || []).forEach(function(acc) {
      html += '<tr><td style="padding-left:28px!important;"><code style="font-size:0.78rem;color:#3B4FE4;">' + (acc.code||'') + '</code> ' + (acc.name||'') + '</td><td class="text-end">' + ERP.formatCurrency(acc.balance||0) + '</td></tr>';
    });
    html += '<tr class="pl-subtotal-row"><td>Total Expenses</td><td class="text-end">' + ERP.formatCurrency((lines.operating_expenses||{}).total||0) + '</td></tr>';
    var netProfit = data.netProfit || 0;
    html += '<tr class="pl-total-row ' + (netProfit>=0?'profit':'loss') + '"><td>' + (netProfit>=0?'Net Profit':'Net Loss') + '</td><td class="text-end">' + ERP.formatCurrency(Math.abs(netProfit)) + '</td></tr>';

    if (data.unmappedAccounts && data.unmappedAccounts.length) {
      var names = data.unmappedAccounts.map(function(a){ return a.code + ' - ' + a.name; }).join(', ');
      html += '<tr style="background:#fff7ed;"><td colspan="2" style="font-size:0.8rem;color:#c2410c;padding:8px 12px;"><i class="ti ti-alert-triangle me-1"></i><strong>Unmapped accounts (excluded from above):</strong> ' + names + '</td></tr>';
    }
  } else {
    // Fallback mode — sub_type grouping (existing logic)
    html += '<tr class="pl-section-row"><td colspan="2">Revenue</td></tr>';
    html += rptRenderSubTypeRows(data.revenue||{});
    html += '<tr class="pl-subtotal-row"><td>Total Revenue</td><td class="text-end">' + ERP.formatCurrency(data.totalRevenue||0) + '</td></tr>';
    html += '<tr class="pl-section-row"><td colspan="2">Cost of Goods Sold</td></tr>';
    html += rptRenderSubTypeRows(data.cogs||{});
    html += '<tr class="pl-subtotal-row"><td>Total COGS</td><td class="text-end">' + ERP.formatCurrency(data.totalCogs||0) + '</td></tr>';
    var grossProfit = (data.totalRevenue||0) - (data.totalCogs||0);
    html += '<tr class="pl-total-row ' + (grossProfit>=0?'profit':'loss') + '"><td>Gross Profit</td><td class="text-end">' + ERP.formatCurrency(grossProfit) + '</td></tr>';
    html += '<tr class="pl-section-row"><td colspan="2">Operating Expenses</td></tr>';
    html += rptRenderSubTypeRows(data.expenses||{});
    html += '<tr class="pl-subtotal-row"><td>Total Expenses</td><td class="text-end">' + ERP.formatCurrency(data.totalExpenses||0) + '</td></tr>';
    var netProfit = grossProfit - (data.totalExpenses||0);
    html += '<tr class="pl-total-row ' + (netProfit>=0?'profit':'loss') + '"><td>' + (netProfit>=0?'Net Profit':'Net Loss') + '</td><td class="text-end">' + ERP.formatCurrency(Math.abs(netProfit)) + '</td></tr>';
  }

  document.getElementById('rptPlBody').innerHTML = html;
}
```

- [ ] **Step 4: Update `rptRenderBS()` to handle mapped format**

Find `rptRenderBS` (line 2098). Replace the entire function with:

```js
function rptRenderBS(data, asOf) {
  if (data.useMappings && data.lines) {
    // Mapped mode
    var lines = data.lines;
    var assetKeys = ['current_assets', 'fixed_assets', 'other_assets'];
    var assetsHtml = '';
    assetKeys.forEach(function(key) {
      var line = lines[key];
      if (!line) return;
      assetsHtml += '<tr class="bs-section-row"><td colspan="2">' + line.label + '</td></tr>';
      (line.accounts || []).forEach(function(acc) {
        assetsHtml += '<tr><td style="padding-left:20px!important;"><code style="font-size:0.78rem;color:#3B4FE4;">' + (acc.code||'') + '</code> ' + (acc.name||'') + '</td><td class="text-end">' + ERP.formatCurrency(acc.balance||0) + '</td></tr>';
      });
    });
    document.getElementById('rptBsAssetsBody').innerHTML = assetsHtml || '<tr><td colspan="2" class="text-center text-muted py-3">No accounts mapped to Assets.</td></tr>';
    document.getElementById('rptBsTotalAssets').textContent = ERP.formatCurrency(data.totalAssets||0);

    var liabKeys = ['current_liabilities', 'long_term_liabilities'];
    var liabHtml = '';
    liabKeys.forEach(function(key) {
      var line = lines[key];
      if (!line) return;
      liabHtml += '<tr class="bs-section-row"><td colspan="2">' + line.label + '</td></tr>';
      (line.accounts || []).forEach(function(acc) {
        liabHtml += '<tr><td style="padding-left:20px!important;"><code style="font-size:0.78rem;color:#3B4FE4;">' + (acc.code||'') + '</code> ' + (acc.name||'') + '</td><td class="text-end">' + ERP.formatCurrency(acc.balance||0) + '</td></tr>';
      });
    });
    // Equity section
    var oeq = lines.owners_equity || {};
    liabHtml += '<tr class="bs-section-row"><td colspan="2">' + (oeq.label || "Owner's Equity") + '</td></tr>';
    (oeq.accounts || []).forEach(function(acc) {
      liabHtml += '<tr><td style="padding-left:20px!important;"><code style="font-size:0.78rem;color:#3B4FE4;">' + (acc.code||'') + '</code> ' + (acc.name||'') + '</td><td class="text-end">' + ERP.formatCurrency(acc.balance||0) + '</td></tr>';
    });
    if (data.openingBalanceEquity && Math.abs(data.openingBalanceEquity) > 0.009) {
      liabHtml += '<tr><td style="padding-left:20px!important;font-size:0.85rem;">Opening Balance Equity</td><td class="text-end">' + ERP.formatCurrency(data.openingBalanceEquity||0) + '</td></tr>';
    }
    liabHtml += '<tr><td style="padding-left:20px!important;font-size:0.85rem;font-style:italic;">Retained Earnings</td><td class="text-end">' + ERP.formatCurrency(data.retainedEarnings||0) + '</td></tr>';
    if (data.unmappedAccounts && data.unmappedAccounts.length) {
      var names = data.unmappedAccounts.map(function(a){ return a.code + ' - ' + a.name; }).join(', ');
      liabHtml += '<tr style="background:#fff7ed;"><td colspan="2" style="font-size:0.8rem;color:#c2410c;padding:8px 12px;"><i class="ti ti-alert-triangle me-1"></i><strong>Unmapped:</strong> ' + names + '</td></tr>';
    }
    document.getElementById('rptBsLiabEquityBody').innerHTML = liabHtml;
    document.getElementById('rptBsTotalLiabEquity').textContent = ERP.formatCurrency(data.totalLiabEquity||0);
  } else {
    // Fallback mode (existing logic)
    var assetsHtml = rptRenderBsGrouped(data.assets||{});
    document.getElementById('rptBsAssetsBody').innerHTML = assetsHtml || '<tr><td colspan="2" class="text-center text-muted py-3">No data.</td></tr>';
    document.getElementById('rptBsTotalAssets').textContent = ERP.formatCurrency(data.totalAssets||0);

    var liabHtml = rptRenderBsGrouped(data.liabilities||{});
    liabHtml += rptRenderBsGrouped(data.equity||{});
    if (data.openingBalanceEquity && Math.abs(data.openingBalanceEquity) > 0.009) {
      liabHtml += '<tr class="bs-section-row"><td colspan="2">Opening Balances</td></tr>';
      liabHtml += '<tr><td style="padding-left:20px!important;font-size:0.85rem;">Opening Balance Equity</td><td class="text-end">' + ERP.formatCurrency(data.openingBalanceEquity||0) + '</td></tr>';
    }
    if (data.retainedEarnings !== undefined) {
      liabHtml += '<tr><td style="padding-left:20px!important;font-size:0.85rem;font-style:italic;">Retained Earnings</td><td class="text-end">' + ERP.formatCurrency(data.retainedEarnings||0) + '</td></tr>';
    }
    document.getElementById('rptBsLiabEquityBody').innerHTML = liabHtml || '<tr><td colspan="2" class="text-center text-muted py-3">No data.</td></tr>';
    document.getElementById('rptBsTotalLiabEquity').textContent = ERP.formatCurrency(data.totalLiabEquity||0);
  }

  var diff = Math.abs((data.totalAssets||0) - (data.totalLiabEquity||0));
  var checkEl = document.getElementById('rptBsBalanceCheck');
  if (diff < 0.01) {
    checkEl.innerHTML = '<span class="bs-balanced"><i class="ti ti-circle-check me-1"></i>Balance Sheet is balanced as of ' + asOf + '</span>';
  } else {
    checkEl.innerHTML = '<span class="bs-unbalanced"><i class="ti ti-alert-triangle me-1"></i>Out of balance by ' + ERP.formatCurrency(diff) + '</span>';
  }
}
```

- [ ] **Step 5: Run full test suite one final time**

```bash
/c/xampp/php/php artisan test tests/Feature/
```

Expected: All tests pass.

- [ ] **Step 6: Commit**

```bash
git add resources/views/pages/reports.blade.php public/js/pages/reports.js
git commit -m "feat: add Today preset to P&L and update render functions for mapped mode"
```

---

## Task 11: Sync chartOfAccounts into ERP State

The Report Builder JS reads `window.ERP.state.chartOfAccounts` to populate dropdowns. Verify this is synced.

- [ ] **Step 1: Check `public/js/app.js` for `chartOfAccounts` in state**

```bash
grep -n "chartOfAccounts" public/js/app.js public/js/api.js
```

- If `chartOfAccounts` is already synced (appears in `mergeState` or sync response), no change needed — skip to Step 3.
- If it is NOT in the state, continue to Step 2.

- [ ] **Step 2: If not synced — add it to the core/master sync response**

In `app/Services/SyncService.php`, find `getCoreData()` or `getMasterData()`. Add chart of accounts to whichever is appropriate:

```php
'chartOfAccounts' => ChartOfAccount::where('company_id', $companyId)
    ->where('is_active', true)
    ->orderBy('code')
    ->select('id', 'code', 'name', 'type', 'sub_type')
    ->get()
    ->map(fn($a) => [
        'id'      => $a->id,
        'code'    => $a->code,
        'name'    => $a->name,
        'type'    => $a->type,
        'subType' => $a->sub_type,
    ]),
```

- [ ] **Step 3: Commit if Step 2 was needed**

```bash
git add app/Services/SyncService.php
git commit -m "feat: include chartOfAccounts in sync state for report builder"
```

---

## Self-Review Checklist

- [x] Migration covers all columns + unique constraint + indexes
- [x] Controller validates report type, super admin, company ownership, duplicate accounts
- [x] DB transaction wraps delete + insert so it's atomic
- [x] Fallback (`useMappings: false`) preserves existing report behavior exactly
- [x] Both P&L and Balance Sheet have mapped + fallback paths
- [x] `rptPlSetPeriod('today')` added; all presets now call `runProfitLoss()` immediately
- [x] `rptRenderPL` detects `data.useMappings` and branches accordingly
- [x] `rptRenderBS` detects `data.useMappings` and branches accordingly
- [x] Unmapped accounts warning rendered in both builder UI and report output
- [x] Tom Select restricted to relevant account types per report (Revenue/Expense vs Asset/Liability/Equity)
- [x] Task 11 guards against `chartOfAccounts` not being in ERP state
- [x] All method names consistent across tasks (e.g. `profitLossMapped`, `balanceSheetMapped`, `rbSave`, `rbReset`)
