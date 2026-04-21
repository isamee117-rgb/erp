<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Company;
use App\Models\User;
use App\Services\SyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(protected SyncService $syncService) {}

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['error' => 'Your account has been deactivated. Please contact your administrator.'], 401);
        }

        if ($user->company_id) {
            $company = Company::find($user->company_id);
            if (!$company || $company->status !== 'Active') {
                return response()->json(['error' => 'Your company account has been suspended. Please contact support.'], 401);
            }
        }

        $token           = Str::random(60);
        $user->api_token = $token;
        $user->save();

        return response()->json([
            'token' => $token,
            'user'  => new UserResource($user),
        ]);
    }

    // Core: user, companies, roles, settings — fast, page render ke liye
    public function syncCore(Request $request)
    {
        return response()->json(
            $this->syncService->getCoreData($request->get('auth_user'))
        );
    }

    // Master: products, parties, categories — medium speed
    public function syncMaster(Request $request)
    {
        return response()->json(
            $this->syncService->getMasterData($request->get('auth_user'))
        );
    }

    // Transactions: sales, purchases, payments, ledger — heavy, background mein
    public function syncTransactions(Request $request)
    {
        $from = null;
        $to   = null;

        if ($request->filled('from')) {
            try { $from = \Carbon\Carbon::parse($request->input('from'))->startOfDay(); } catch (\Exception) {}
        }
        if ($request->filled('to')) {
            try { $to = \Carbon\Carbon::parse($request->input('to'))->endOfDay(); } catch (\Exception) {}
        }

        return response()->json(
            $this->syncService->getTransactionData($request->get('auth_user'), $from, $to)
        );
    }

    // Legacy endpoint — deprecated. Now returns date-limited data (last 6 months) via getTransactionData default.
    public function sync(Request $request)
    {
        $user = $request->get('auth_user');
        return response()->json(array_merge(
            (array) $this->syncService->getCoreData($user),
            (array) $this->syncService->getMasterData($user),
            (array) $this->syncService->getTransactionData($user),
        ));
    }
}
