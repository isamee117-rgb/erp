<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Block deactivated users even if they have a valid token
        if (!$user->is_active) {
            return response()->json(['error' => 'Your account has been deactivated. Please contact your administrator.'], 401);
        }

        // Block users whose company has been suspended by super admin
        if ($user->company_id) {
            $company = Company::find($user->company_id);
            if (!$company || $company->status !== 'Active') {
                return response()->json(['error' => 'Your company account has been suspended. Please contact support.'], 401);
            }
        }

        $request->merge(['auth_user' => $user]);
        Auth::login($user);

        return $next($request);
    }
}
