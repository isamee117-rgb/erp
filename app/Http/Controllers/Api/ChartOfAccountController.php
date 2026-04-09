<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChartOfAccountResource;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChartOfAccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->get('auth_user');

        $accounts = ChartOfAccount::where('company_id', $user->company_id)
            ->selectRaw('chart_of_accounts.*, (
                SELECT COALESCE(SUM(jel.debit), 0) - COALESCE(SUM(jel.credit), 0)
                FROM journal_entry_lines jel
                JOIN journal_entries je ON je.id = jel.journal_entry_id
                WHERE je.is_posted = 1
                AND jel.account_id = chart_of_accounts.id
            ) as balance')
            ->orderBy('code')
            ->get();

        return ChartOfAccountResource::collection($accounts);
    }

    public function store(Request $request)
    {
        $user = $request->get('auth_user');
        if (!$user->company_id) {
            return response()->json(['error' => 'Not available for Super Admin.'], 403);
        }

        $data = $request->validate([
            'code'    => 'required|string|max:20',
            'name'    => 'required|string|max:255',
            'type'    => 'required|in:Asset,Liability,Equity,Revenue,Expense',
            'subType' => 'nullable|string|max:100',
        ]);

        $exists = ChartOfAccount::where('company_id', $user->company_id)
            ->where('code', $data['code'])
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'Account code already exists'], 422);
        }

        $account = ChartOfAccount::create([
            'id'         => 'COA-' . Str::random(9),
            'company_id' => $user->company_id,
            'code'       => $data['code'],
            'name'       => $data['name'],
            'type'       => $data['type'],
            'sub_type'   => $data['subType'] ?? null,
            'is_system'  => false,
            'is_active'  => true,
        ]);

        return new ChartOfAccountResource($account);
    }

    public function update(Request $request, $id)
    {
        $account = ChartOfAccount::findOrFail($id);
        $data    = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'type'     => 'sometimes|in:Asset,Liability,Equity,Revenue,Expense',
            'subType'  => 'nullable|string|max:100',
            'isActive' => 'sometimes|boolean',
        ]);

        $account->update([
            'name'      => $data['name']     ?? $account->name,
            'type'      => $data['type']     ?? $account->type,
            'sub_type'  => $data['subType']  ?? $account->sub_type,
            'is_active' => $data['isActive'] ?? $account->is_active,
        ]);

        return new ChartOfAccountResource($account);
    }

    public function destroy($id)
    {
        $account = ChartOfAccount::findOrFail($id);

        if ($account->is_system) {
            return response()->json(['error' => 'System accounts cannot be deleted'], 422);
        }

        if ($account->hasTransactions()) {
            return response()->json(['error' => 'Account has transactions and cannot be deleted'], 422);
        }

        $account->delete();
        return response()->json(['success' => true]);
    }
}