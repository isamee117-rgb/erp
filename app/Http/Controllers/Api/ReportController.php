<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntryLine;
use App\Models\ReportLineMapping;
use App\Models\SaleReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
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

    // ── Private helpers ───────────────────────────────────────────────────────

    // Net of ALL opening balances: Asset openings - Liability openings - Equity openings.
    // This ensures the Balance Sheet equation balances when accounts have opening balances
    // that were not entered via double-entry journal entries.
    private function calculateOpeningBalanceEquity(string $companyId): float
    {
        $result = DB::table('chart_of_accounts')
            ->where('company_id', $companyId)
            ->selectRaw("
                SUM(CASE WHEN type = 'Asset'     THEN COALESCE(opening_balance, 0) ELSE 0 END) -
                SUM(CASE WHEN type = 'Liability' THEN COALESCE(opening_balance, 0) ELSE 0 END) -
                SUM(CASE WHEN type = 'Equity'    THEN COALESCE(opening_balance, 0) ELSE 0 END) as net
            ")
            ->value('net');

        return (float) $result;
    }

    private function calculateRetainedEarnings(string $companyId, string $asOf): float
    {
        // Revenue/Expense opening balances ka net (Revenue opening - Expense opening)
        $openingNet = DB::table('chart_of_accounts')
            ->where('company_id', $companyId)
            ->whereIn('type', ['Revenue', 'Expense'])
            ->selectRaw("
                SUM(CASE WHEN type = 'Revenue' THEN COALESCE(opening_balance, 0) ELSE 0 END) -
                SUM(CASE WHEN type = 'Expense' THEN COALESCE(opening_balance, 0) ELSE 0 END) as net
            ")
            ->value('net');

        // Journal entries se net (Revenue credits - Expense debits)
        $journalNet = JournalEntryLine::query()
            ->join('chart_of_accounts as coa', 'journal_entry_lines.account_id', '=', 'coa.id')
            ->join('journal_entries as je', 'journal_entry_lines.journal_entry_id', '=', 'je.id')
            ->where('je.company_id', $companyId)
            ->where('je.is_posted', true)
            ->whereDate('je.date', '<=', $asOf)
            ->whereIn('coa.type', ['Revenue', 'Expense'])
            ->selectRaw('SUM(journal_entry_lines.credit) - SUM(journal_entry_lines.debit) as net')
            ->value('net');

        return (float) $openingNet + (float) $journalNet;
    }

    private function formatAccountGroup($grouped, string $normalBalance): array
    {
        $result = [];
        foreach ($grouped as $subType => $accounts) {
            $result[$subType ?? 'General'] = $this->formatAccounts($accounts, $normalBalance);
        }
        return $result;
    }

    private function formatAccountGroupWithOpening($grouped, string $normalBalance): array
    {
        $result = [];
        foreach ($grouped as $subType => $accounts) {
            $result[$subType ?? 'General'] = $this->formatAccountsWithOpening($accounts, $normalBalance);
        }
        return $result;
    }

    private function formatAccounts($accounts, string $normalBalance): array
    {
        return $accounts->map(function ($a) use ($normalBalance) {
            $balance = $normalBalance === 'debit'
                ? $a->total_debit - $a->total_credit
                : $a->total_credit - $a->total_debit;

            return [
                'id'      => $a->id,
                'code'    => $a->code,
                'name'    => $a->name,
                'balance' => round($balance, 2),
            ];
        })->values()->toArray();
    }

    private function formatAccountsWithOpening($accounts, string $normalBalance): array
    {
        return $accounts->map(function ($a) use ($normalBalance) {
            $opening = (float) ($a->opening_balance ?? 0);
            $journalNet = $normalBalance === 'debit'
                ? $a->total_debit - $a->total_credit
                : $a->total_credit - $a->total_debit;

            return [
                'id'      => $a->id,
                'code'    => $a->code,
                'name'    => $a->name,
                'balance' => round($opening + $journalNet, 2),
            ];
        })->filter(fn($a) => $a['balance'] != 0)->values()->toArray();
    }
}