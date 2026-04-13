<?php

namespace App\Services;

use App\Config\DynamicFields;
use App\Models\CompanyFieldSetting;
use App\Models\Party;
use App\Models\Product;
use Illuminate\Support\Str;

class FieldSettingService
{
    public function getSettings(string $companyId): array
    {
        $rows = CompanyFieldSetting::forCompany($companyId)
            ->get()
            ->keyBy(fn($r) => $r->entity_type . '.' . $r->field_key);

        return array_map(function ($field) use ($rows) {
            $rowKey = $field['entity'] . '.' . $field['key'];
            $row    = $rows->get($rowKey);
            return array_merge($field, [
                'is_enabled' => $row ? (bool) $row->is_enabled : false,
            ]);
        }, DynamicFields::all());
    }

    public function updateSetting(string $companyId, string $entityType, string $fieldKey, bool $isEnabled): void
    {
        $field = DynamicFields::find($fieldKey);
        if (!$field || $field['entity'] !== $entityType) {
            throw new \RuntimeException("Unknown field key: {$fieldKey}");
        }

        if (!$isEnabled) {
            [$canDisable, $count] = $this->canDisable($companyId, $entityType, $fieldKey);
            if (!$canDisable) {
                throw new \RuntimeException("Cannot disable — {$count} record(s) have data in this field");
            }
        }

        $existing = CompanyFieldSetting::where('company_id', $companyId)
            ->where('entity_type', $entityType)
            ->where('field_key', $fieldKey)
            ->first();

        if ($existing) {
            $existing->update(['is_enabled' => $isEnabled]);
        } else {
            CompanyFieldSetting::create([
                'id'          => 'CFS-' . Str::random(9),
                'company_id'  => $companyId,
                'entity_type' => $entityType,
                'field_key'   => $fieldKey,
                'is_enabled'  => $isEnabled,
            ]);
        }
    }

    public function canDisable(string $companyId, string $entityType, string $fieldKey): array
    {
        $count = match ($entityType) {
            'product'  => Product::where('company_id', $companyId)
                ->whereNotNull($fieldKey)
                ->where($fieldKey, '!=', '')
                ->count(),
            'customer' => Party::where('company_id', $companyId)
                ->whereNotNull($fieldKey)
                ->where($fieldKey, '!=', '')
                ->count(),
            default    => 0,
        };

        return $count > 0 ? [false, $count] : [true, 0];
    }
}
