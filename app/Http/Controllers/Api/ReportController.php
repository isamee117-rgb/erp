<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JournalEntryLine;
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
        $grossProfit   = $totalRevenue - $totalCogs;
        $netProfit     = $grossProfit - $totalExpenses;

        return response()->json([
            'period'         => ['from' => $from, 'to' => $to],
            'revenue'        => $this->formatAccountGroup($revenue->groupBy('sub_type'), 'credit'),
            'totalRevenue'   => round($totalRevenue, 2),
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

        $lines = JournalEntryLine::query()
            ->join('chart_of_accounts as coa', 'journal_entry_lines.account_id', '=', 'coa.id')
            ->join('journal_entries as je', 'journal_entry_lines.journal_entry_id', '=', 'je.id')
            ->where('je.company_id', $user->company_id)
            ->where('je.is_posted', true)
            ->whereDate('je.date', '<=', $asOf)
            ->whereIn('coa.type', ['Asset', 'Liability', 'Equity'])
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

        // Retained earnings = net P&L from inception to as_of
        $retainedEarnings = $this->calculateRetainedEarnings($user->company_id, $asOf);

        $assets      = $lines->where('type', 'Asset');
        $liabilities = $lines->where('type', 'Liability');
        $equity      = $lines->where('type', 'Equity');

        $totalAssets      = $assets->sum(fn($a) => $a->total_debit - $a->total_credit);
        $totalLiabilities = $liabilities->sum(fn($a) => $a->total_credit - $a->total_debit);
        $totalEquity      = $equity->sum(fn($a) => $a->total_credit - $a->total_debit) + $retainedEarnings;

        return response()->json([
            'asOf'              => $asOf,
            'assets'            => $this->formatAccountGroup($assets->groupBy('sub_type'), 'debit'),
            'totalAssets'       => round($totalAssets, 2),
            'liabilities'       => $this->formatAccountGroup($liabilities->groupBy('sub_type'), 'credit'),
            'totalLiabilities'  => round($totalLiabilities, 2),
            'equity'            => $this->formatAccountGroup($equity->groupBy('sub_type'), 'credit'),
            'retainedEarnings'  => round($retainedEarnings, 2),
            'totalEquity'       => round($totalEquity, 2),
            'totalLiabEquity'   => round($totalLiabilities + $totalEquity, 2),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function calculateRetainedEarnings(string $companyId, string $asOf): float
    {
        $result = JournalEntryLine::query()
            ->join('chart_of_accounts as coa', 'journal_entry_lines.account_id', '=', 'coa.id')
            ->join('journal_entries as je', 'journal_entry_lines.journal_entry_id', '=', 'je.id')
            ->where('je.company_id', $companyId)
            ->where('je.is_posted', true)
            ->whereDate('je.date', '<=', $asOf)
            ->whereIn('coa.type', ['Revenue', 'Expense'])
            ->selectRaw('SUM(journal_entry_lines.credit) - SUM(journal_entry_lines.debit) as net')
            ->value('net');

        return (float) $result;
    }

    private function formatAccountGroup($grouped, string $normalBalance): array
    {
        $result = [];
        foreach ($grouped as $subType => $accounts) {
            $result[$subType ?? 'General'] = $this->formatAccounts($accounts, $normalBalance);
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
}