<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountMapping;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccountMappingController extends Controller
{
    public function index(Request $request)
    {
        $user     = $request->get('auth_user');
        $mappings = AccountMapping::where('company_id', $user->company_id)
            ->with('account')
            ->get()
            ->keyBy('mapping_key');

        return response()->json($mappings);
    }

    public function update(Request $request)
    {
        $user = $request->get('auth_user');
        if (!$user->company_id) {
            return response()->json(['error' => 'Account mappings are not available for Super Admin.'], 403);
        }

        $data = $request->validate([
            'mappings'                => 'required|array',
            'mappings.*.mappingKey'   => 'required|string|max:100',
            'mappings.*.accountId'    => 'required|string|exists:chart_of_accounts,id',
        ]);

        foreach ($data['mappings'] as $item) {
            $existing = AccountMapping::where('company_id', $user->company_id)
                ->where('mapping_key', $item['mappingKey'])
                ->first();

            if ($existing) {
                $existing->update(['account_id' => $item['accountId']]);
            } else {
                AccountMapping::create([
                    'id'          => 'MAP-' . Str::random(9),
                    'company_id'  => $user->company_id,
                    'mapping_key' => $item['mappingKey'],
                    'account_id'  => $item['accountId'],
                ]);
            }
        }

        return response()->json(['success' => true]);
    }
}