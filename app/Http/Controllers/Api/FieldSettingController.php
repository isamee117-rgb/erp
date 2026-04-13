<?php

namespace App\Http\Controllers\Api;

use App\Config\DynamicFields;
use App\Http\Controllers\Controller;
use App\Http\Resources\FieldSettingResource;
use App\Services\FieldSettingService;
use Illuminate\Http\Request;

class FieldSettingController extends Controller
{
    public function __construct(protected FieldSettingService $service) {}

    public function index(Request $request)
    {
        $user = $request->get('auth_user');

        if ($user->company_id === null) {
            return response()->json(['error' => 'Super Admin cannot manage company field settings.'], 403);
        }

        $settings = $this->service->getSettings($user->company_id);

        return response()->json([
            'data' => array_map(fn($s) => (new FieldSettingResource($s))->resolve(), $settings),
        ]);
    }

    public function update(Request $request, string $fieldKey)
    {
        $user = $request->get('auth_user');

        if ($user->company_id === null) {
            return response()->json(['error' => 'Super Admin cannot manage company field settings.'], 403);
        }

        $entityType = $request->input('entity_type');
        $isEnabled  = (bool) $request->input('is_enabled');

        $field = DynamicFields::find($fieldKey);
        if (!$field) {
            return response()->json(['error' => "Unknown field key: {$fieldKey}"], 422);
        }

        try {
            $this->service->updateSetting($user->company_id, $entityType, $fieldKey, $isEnabled);
            return response()->json(['success' => true]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
