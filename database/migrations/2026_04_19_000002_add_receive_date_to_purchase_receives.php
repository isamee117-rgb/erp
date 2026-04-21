<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('purchase_receives', function (Blueprint $table) {
            $table->date('receive_date')->nullable()->after('notes');
        });
    }
    public function down(): void {
        Schema::table('purchase_receives', function (Blueprint $table) {
            $table->dropColumn('receive_date');
        });
    }
};
