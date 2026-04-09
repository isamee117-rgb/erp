<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->integer('returned_quantity')->default(0);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->integer('returned_quantity')->default(0);
        });

        Schema::table('sale_orders', function (Blueprint $table) {
            $table->string('return_status', 20)->default('none');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('return_status', 20)->default('none');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('returned_quantity');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('returned_quantity');
        });

        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropColumn('return_status');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('return_status');
        });
    }
};
