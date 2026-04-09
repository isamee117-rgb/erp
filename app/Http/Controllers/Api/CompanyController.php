<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyDetailsRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function store(StoreCompanyRequest $request)
    {
        $data          = $request->validated();
        $coId          = 'tenant-' . Str::random(9);
        $adminPassword = $data['adminPassword'];

        $company = Company::create([
            'id'                   => $coId,
            'name'                 => $data['name'],
            'status'               => 'Active',
            'max_user_limit'       => $data['maxUserLimit'] ?? 10,
            'registration_payment' => $data['registrationPayment'] ?? 0,
            'saas_plan'            => $data['saasPlan'] ?? 'Monthly',
            'info_name'            => $data['name'],
            'info_tagline'         => '',
            'info_address'         => '',
            'info_phone'           => '',
            'info_email'           => '',
            'info_website'         => '',
            'info_tax_id'          => '',
            'info_logo_url'        => '',
        ]);

        User::create([
            'id'          => 'user-' . Str::random(9),
            'username'    => $data['adminUsername'],
            'password'    => $adminPassword,
            'system_role' => 'Company Admin',
            'role_id'     => null,
            'company_id'  => $coId,
            'is_active'   => true,
        ]);

        return new CompanyResource($company);
    }

    public function updateStatus(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $company->update(['status' => $request->input('status')]);
        return response()->json(['success' => true]);
    }

    public function updateLimit(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $company->update(['max_user_limit' => $request->input('maxUserLimit') ?? $request->input('limit')]);
        return response()->json(['success' => true]);
    }

    public function updateAdminPassword(Request $request, $id)
    {
        $newPassword = $request->input('password');
        $admin = User::where('company_id', $id)->where('system_role', 'Company Admin')->first();
        if ($admin) {
            $admin->update(['password' => $newPassword]);
        }
        return response()->json(['success' => true]);
    }

    public function updateDetails(UpdateCompanyDetailsRequest $request, $id)
    {
        $company = Company::findOrFail($id);
        $data    = $request->validated();

        $map = [
            'name'                => 'name',
            'saasPlan'            => 'saas_plan',
            'registrationPayment' => 'registration_payment',
            'maxUserLimit'        => 'max_user_limit',
            'infoName'            => 'info_name',
            'infoTagline'         => 'info_tagline',
            'infoAddress'         => 'info_address',
            'infoPhone'           => 'info_phone',
            'infoEmail'           => 'info_email',
            'infoWebsite'         => 'info_website',
            'infoTaxId'           => 'info_tax_id',
        ];

        $update = [];
        foreach ($map as $input => $column) {
            if (array_key_exists($input, $data)) {
                $update[$column] = $data[$input];
            }
        }

        if (!empty($update)) $company->update($update);

        return response()->json(['success' => true]);
    }

    public function updateInfo(Request $request)
    {
        $user    = $request->get('auth_user');
        $company = Company::find($user->company_id);
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $info = $request->input('info') ?? $request->all();
        $company->update([
            'name'          => $info['name']    ?? $company->name,
            'info_name'     => $info['name']    ?? $company->info_name,
            'info_tagline'  => $info['tagline'] ?? $company->info_tagline,
            'info_address'  => $info['address'] ?? $company->info_address,
            'info_phone'    => $info['phone']   ?? $company->info_phone,
            'info_email'    => $info['email']   ?? $company->info_email,
            'info_website'  => $info['website'] ?? $company->info_website,
            'info_tax_id'   => $info['taxId']   ?? $company->info_tax_id,
            'info_logo_url' => $info['logoUrl'] ?? $company->info_logo_url,
        ]);

        return response()->json(['success' => true]);
    }

    public function uploadLogo(Request $request)
    {
        $user    = $request->get('auth_user');
        $company = Company::find($user->company_id);
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        if (!$request->hasFile('logo')) {
            return response()->json(['error' => 'No file uploaded'], 422);
        }

        $file = $request->file('logo');
        $ext  = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return response()->json(['error' => 'Invalid file type. Allowed: jpg, png, gif, webp'], 422);
        }
        if ($file->getSize() > 2 * 1024 * 1024) {
            return response()->json(['error' => 'File size must be under 2MB'], 422);
        }

        $logosDir = public_path('logos');
        if (!is_dir($logosDir)) {
            mkdir($logosDir, 0755, true);
        }

        $filename = $user->company_id . '.' . $ext;
        $file->move($logosDir, $filename);

        $url = asset('logos/' . $filename) . '?v=' . time();
        $company->update(['info_logo_url' => $url]);

        return response()->json(['success' => true, 'url' => $url]);
    }
}
