<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_receive_items', function (Blueprint $table) {
            $table->string('batch_no', 255)->nullable()->after('unit_cost');
            $table->date('mfg_date')->nullable()->after('batch_no');
            $table->date('expiry_date')->nullable()->after('mfg_date');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_receive_items', function (Blueprint $table) {
            $table->dropIndex(['expiry_date']);
            $table->dropColumn(['batch_no', 'mfg_date', 'expiry_date']);
        });
    }
};
