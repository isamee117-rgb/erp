<?php
// app/Config/DynamicFields.php
namespace App\Config;

class DynamicFields
{
    public static function all(): array
    {
        return array_merge(self::productFields(), self::customerFields());
    }

    public static function productFields(): array
    {
        return [
            ['key' => 'brand_name',           'label' => 'Brand Name',                          'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'retail'],
            ['key' => 'size',                  'label' => 'Size',                                'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'retail'],
            ['key' => 'color',                 'label' => 'Color',                               'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'retail'],
            ['key' => 'style',                 'label' => 'Style',                               'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'retail'],
            ['key' => 'bin_shelf_location',    'label' => 'Bin/Shelf Location',                  'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'retail'],
            ['key' => 'expiry_date',           'label' => 'Expiry Date',                         'entity' => 'product', 'type' => 'date',     'options' => [], 'industry_hint' => 'grocery/pharmacy'],
            ['key' => 'batch_lot_number',      'label' => 'Batch/Lot Number',                    'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'grocery/pharmacy'],
            ['key' => 'storage_condition',     'label' => 'Storage Condition',                   'entity' => 'product', 'type' => 'dropdown', 'options' => ['Ambient', 'Chilled', 'Frozen'], 'industry_hint' => 'grocery/pharmacy'],
            ['key' => 'drug_composition',      'label' => 'Drug Composition / Generic Name',     'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'pharmacy'],
            ['key' => 'schedule_category',     'label' => 'Schedule Category',                   'entity' => 'product', 'type' => 'dropdown', 'options' => ['H', 'H1', 'X', 'OTC'], 'industry_hint' => 'pharmacy'],
            ['key' => 'manufacturer_name',     'label' => 'Manufacturer Name',                   'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'grocery/pharmacy'],
            ['key' => 'dosage_form',           'label' => 'Dosage Form',                         'entity' => 'product', 'type' => 'dropdown', 'options' => ['Tablet', 'Syrup', 'Injection', 'Capsule'], 'industry_hint' => 'pharmacy'],
            ['key' => 'storage_temp_req',      'label' => 'Storage Temperature Requirements',    'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'grocery/pharmacy'],
            ['key' => 'vehicle_compatibility', 'label' => 'Make/Model/Year',                        'entity' => 'product', 'type' => 'text', 'options' => [], 'industry_hint' => 'automobile'],
            ['key' => 'core_charge_flag',      'label' => 'Core Charge/Exchange Item Flag',      'entity' => 'product', 'type' => 'boolean',  'options' => [], 'industry_hint' => 'automobile'],
            ['key' => 'warranty_period',       'label' => 'Warranty Period',                     'entity' => 'product', 'type' => 'text',     'options' => [], 'industry_hint' => 'automobile'],
            ['key' => 'technical_specs',       'label' => 'Technical Specifications',            'entity' => 'product', 'type' => 'textarea', 'options' => [], 'industry_hint' => 'automobile'],
        ];
    }

    public static function customerFields(): array
    {
        return [
            ['key' => 'make_model_year',         'label' => 'Make/Model/Year',                     'entity' => 'customer', 'type' => 'text',   'options' => [], 'industry_hint' => 'automobile'],
            ['key' => 'vehicle_reg_number',    'label' => 'Vehicle Registration Number (Plate)', 'entity' => 'customer', 'type' => 'text',   'options' => [], 'industry_hint' => 'automobile'],
            ['key' => 'vin_chassis_number',     'label' => 'VIN/Chassis Number',                  'entity' => 'customer', 'type' => 'text',   'options' => [], 'industry_hint' => 'automobile'],
            ['key' => 'engine_number',          'label' => 'Engine Number',                       'entity' => 'customer', 'type' => 'text',   'options' => [], 'industry_hint' => 'automobile'],
            ['key' => 'last_odometer_reading',  'label' => 'Last Odometer Reading',               'entity' => 'customer', 'type' => 'number', 'options' => [], 'industry_hint' => 'automobile'],
        ];
    }

    /** Returns all valid field keys for an entity type */
    public static function keysFor(string $entityType): array
    {
        $fields = $entityType === 'product' ? self::productFields() : self::customerFields();
        return array_column($fields, 'key');
    }

    /** Returns a single field definition by key, or null */
    public static function find(string $key): ?array
    {
        foreach (self::all() as $field) {
            if ($field['key'] === $key) return $field;
        }
        return null;
    }
}
