<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand_name', 255)->nullable()->after('unit_price');
            $table->string('size', 100)->nullable()->after('brand_name');
            $table->string('color', 100)->nullable()->after('size');
            $table->string('style', 100)->nullable()->after('color');
            $table->string('bin_shelf_location', 255)->nullable()->after('style');
            $table->date('expiry_date')->nullable()->after('bin_shelf_location');
            $table->string('batch_lot_number', 255)->nullable()->after('expiry_date');
            $table->string('storage_condition', 50)->nullable()->after('batch_lot_number');
            $table->string('drug_composition', 255)->nullable()->after('storage_condition');
            $table->string('schedule_category', 20)->nullable()->after('drug_composition');
            $table->string('manufacturer_name', 255)->nullable()->after('schedule_category');
            $table->string('dosage_form', 50)->nullable()->after('manufacturer_name');
            $table->string('storage_temp_req', 255)->nullable()->after('dosage_form');
            $table->string('part_number', 255)->nullable()->after('storage_temp_req');
            $table->string('vehicle_compatibility', 255)->nullable()->after('part_number');
            $table->boolean('core_charge_flag')->nullable()->after('vehicle_compatibility');
            $table->string('warranty_period', 255)->nullable()->after('core_charge_flag');
            $table->text('technical_specs')->nullable()->after('warranty_period');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'brand_name', 'size', 'color', 'style', 'bin_shelf_location',
                'expiry_date', 'batch_lot_number', 'storage_condition',
                'drug_composition', 'schedule_category', 'manufacturer_name',
                'dosage_form', 'storage_temp_req', 'part_number',
                'vehicle_compatibility', 'core_charge_flag',
                'warranty_period', 'technical_specs',
            ]);
        });
    }
};
