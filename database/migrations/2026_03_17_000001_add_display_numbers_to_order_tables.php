<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- sale_orders: add invoice_no display column ---
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->string('invoice_no')->nullable()->after('id');
        });
        DB::statement('UPDATE sale_orders SET invoice_no = id WHERE invoice_no IS NULL');
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->unique(['company_id', 'invoice_no'], 'sale_orders_company_invoice_unique');
        });

        // --- purchase_orders: add po_no display column ---
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('po_no')->nullable()->after('id');
        });
        DB::statement('UPDATE purchase_orders SET po_no = id WHERE po_no IS NULL');
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unique(['company_id', 'po_no'], 'purchase_orders_company_po_unique');
        });

        // --- sale_returns: add return_no, drop FK on original_sale_id ---
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->string('return_no')->nullable()->after('id');
        });
        DB::statement('UPDATE sale_returns SET return_no = id WHERE return_no IS NULL');
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropForeign(['original_sale_id']);
            $table->unique(['company_id', 'return_no'], 'sale_returns_company_return_unique');
        });

        // --- purchase_returns: add return_no, drop FK on original_purchase_id ---
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->string('return_no')->nullable()->after('id');
        });
        DB::statement('UPDATE purchase_returns SET return_no = id WHERE return_no IS NULL');
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropForeign(['original_purchase_id']);
            $table->unique(['company_id', 'return_no'], 'purchase_returns_company_return_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropUnique('sale_orders_company_invoice_unique');
            $table->dropColumn('invoice_no');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropUnique('purchase_orders_company_po_unique');
            $table->dropColumn('po_no');
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropUnique('sale_returns_company_return_unique');
            $table->dropColumn('return_no');
            $table->foreign('original_sale_id')->references('id')->on('sale_orders')->onDelete('cascade');
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropUnique('purchase_returns_company_return_unique');
            $table->dropColumn('return_no');
            $table->foreign('original_purchase_id')->references('id')->on('purchase_orders')->onDelete('cascade');
        });
    }
};
