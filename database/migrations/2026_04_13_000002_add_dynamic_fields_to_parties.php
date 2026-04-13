<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->string('vehicle_reg_number', 100)->nullable()->after('current_balance');
            $table->string('vin_chassis_number', 100)->nullable()->after('vehicle_reg_number');
            $table->string('engine_number', 100)->nullable()->after('vin_chassis_number');
            $table->decimal('last_odometer_reading', 10, 2)->nullable()->after('engine_number');
        });
    }

    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn([
                'vehicle_reg_number', 'vin_chassis_number',
                'engine_number', 'last_odometer_reading',
            ]);
        });
    }
};
