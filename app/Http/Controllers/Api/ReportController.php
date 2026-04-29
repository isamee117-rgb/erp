<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JournalEntryLine;
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

        $lines = JournalEntryLine::query()
            ->join('chart_of_accounts as coa', 'journal_entry_lines.account_id', '=', 'coa.id')
            ->join('journal_entries as je', 'journal_entry_lines.journal_entry_id', '=', 'je.id')
            ->where('je.company_id', $user->company_id)
            ->where('je.is_posted', true)
            ->whereDate('je.date', '>=', $from)
            ->whereDate('je.date', '<=', $to)
            ->whereIn('coa.type', ['Revenue', 'Expense'])
            ->select(
                'coa.id',
                'coa.code',
                'coa.name',
                'coa.type',
                'coa.sub_type',
                DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit) as total_credit')
            )
            ->groupBy('coa.id', 'coa.code', 'coa.name', 'coa.type', 'coa.sub_type')
            ->orderBy('coa.code')
            ->get();

        $revenue  = $lines->where('type', 'Revenue');
        $expenses = $lines->where('type', 'Expense');

        // cost_of_goods_sold sub_type separates COGS from operating expenses
        $cogsAccounts = $expenses->where('sub_type', 'cost_of_goods_sold');
        $opexAccounts = $expenses->whereNotIn('sub_type', ['cost_of_goods_sold']);

        $totalRevenue  = $revenue->sum(fn($a) => $a->total_credit - $a->total_debit);
        $totalCogs     = $cogsAccounts->sum(fn($a) => $a->total_debit - $a->total_credit);
        $totalExpenses = $opexAccounts->sum(fn($a) => $a->total_debit - $a->total_credit);

        $salesReturns = (float) SaleReturn::where('sale_returns.company_id', $user->company_id)
            ->join('sale_orders', 'sale_returns.original_sale_id', '=', 'sale_orders.invoice_no')
            ->whereDate('sale_orders.created_at', '>=', $from)
            ->whereDate('sale_orders.created_at', '<=', $to)
            ->sum('sale_returns.total_amount');

        $netRevenue  = $totalRevenue - $salesReturns;
        $grossProfit = $netRevenue - $totalCogs;
        $netProfit   = $grossProfit - $totalExpenses;

        return response()->json([
            'period'         => ['from' => $from, 'to' => $to],
            'revenue'        => $this->formatAccountGroup($revenue->groupBy('sub_type'), 'credit'),
            'totalRevenue'   => round($totalRevenue, 2),
            'salesReturns'   => round($salesReturns, 2),
            'netRevenue'     => round($netRevenue, 2),
            'cogs'           => $this->formatAccountGroup($cogsAccounts->groupBy('sub_type'), 'debit'),
            'totalCogs'      => round($totalCogs, 2),
            'grossProfit'    => round($grossProfit, 2),
            'expenses'       => $this->formatAccountGroup($opexAccounts->groupBy('sub_type'), 'debit'),
            'totalExpenses'  => round($totalExpenses, 2),
            'netProfit'      => round($netProfit, 2),
        ]);
    }

    public function balanceSheet(Request $request)
    {
        $user  = $request->get('auth_user');
        if (!$user->company_id) {
            return response()->json(['error' => 'Reports are not available for Super Admin. Please select a company.'], 403);
        }

        $asOf  = $request->input('as_of');

        $request->validate(['as_of' => 'required|date']);

        // Fetch all Balance Sheet accounts with their opening balances
        // and journal entry movements up to as_of date
        $lines = DB::table('chart_of_accounts as coa')
            ->where('coa.company_id', $user->company_id)
            ->whereIn('coa.type', ['Asset', 'Liability', 'Equity'])
            ->leftJoin('journal_entry_lines as jel', function ($join) use ($asOf, $user) {
                $join->on('jel.account_id', '=', 'coa.id')
                    ->whereExists(function ($q) use ($asOf, $user) {
                        $q->from('journal_entries as je')
                            ->whereColumn('je.id', 'jel.journal_entry_id')
                            ->where('je.company_id', $user->company_id)
                            ->where('je.is_posted', true)
                            ->whereDate('je.date', '<=', $asOf);
                    });
            })
            ->select(
                'coa.id',
                'coa.code',
                'coa.name',
                'coa.type',
                'coa.sub_type',
                DB::raw('COALESCE(coa.opening_balance, 0) as opening_balance'),
                DB::raw('COALESCE(SUM(jel.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(jel.credit), 0) as total_credit')
            )
            ->groupBy('coa.id', 'coa.code', 'coa.name', 'coa.type', 'coa.sub_type', 'coa.opening_balance')
            ->orderBy('coa.code')
            ->get();

        // Retained earnings = net P&L from inception to as_of
        $retainedEarnings = $this->calculateRetainedEarnings($user->company_id, $asOf);

        $assets      = $lines->where('type', 'Asset');
        $liabilities = $lines->where('type', 'Liability');
        $equity      = $lines->where('type', 'Equity');

        // opening_balance + journal movements = true account balance
        $totalAssets      = $assets->sum(fn($a) => $a->opening_balance + $a->total_debit - $a->total_credit);
        $totalLiabilities = $liabilities->sum(fn($a) => $a->opening_balance + $a->total_credit - $a->total_debit);
        $totalEquityAccts = $equity->sum(fn($a) => $a->opening_balance + $a->total_credit - $a->total_debit);

        // Opening Balance Equity: net of all opening balances across Asset/Liability/Equity accounts.
        // Asset opening balances that have no corresponding journal entry must be offset here
        // so the Balance Sheet equation (Assets = Liabilities + Equity) holds.
        $openingBalanceEquity = $this->calculateOpeningBalanceEquity($user->company_id);

        $totalEquity     = $totalEquityAccts + $retainedEarnings + $openingBalanceEquity;
        $totalLiabEquity = $totalLiabilities + $totalEquity;

        return response()->json([
            'asOf'                  => $asOf,
            'assets'                => $this->formatAccountGroupWithOpening($assets->groupBy('sub_type'), 'debit'),
            'totalAssets'           => round($totalAssets, 2),
            'liabilities'           => $this->formatAccountGroupWithOpening($liabilities->groupBy('sub_type'), 'credit'),
            'totalLiabilities'      => round($totalLiabilities, 2),
            'equity'                => $this->formatAccountGroupWithOpening($equity->groupBy('sub_type'), 'credit'),
            'openingBalanceEquity'  => round($openingBalanceEquity, 2),
            'retainedEarnings'      => round($retainedEarnings, 2),
            'totalEquity'           => round($totalEquity, 2),
            'totalLiabEquity'       => round($totalLiabEquity, 2),
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