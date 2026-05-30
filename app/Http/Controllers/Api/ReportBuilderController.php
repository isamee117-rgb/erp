<?php

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
