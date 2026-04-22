<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->decimal('opening_balance', 15, 2)->default(0)->after('sub_type');
        });
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn('opening_balance');
        });
    }
};
