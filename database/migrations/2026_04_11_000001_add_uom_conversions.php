<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // New table: per-product UOM conversion rules
        Schema::create('product_uom_conversions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('product_id');
            $table->string('uom_id');
            $table->decimal('multiplier', 15, 6);  // base units per 1 of this UOM
            $table->boolean('is_default_purchase_unit')->default(false);
            $table->boolean('is_default_sales_unit')->default(false);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('uom_id')->references('id')->on('units_of_measure')->onDelete('cascade');
            $table->unique(['product_id', 'uom_id']);
        });

        // Products: base unit reference
        Schema::table('products', function (Blueprint $table) {
            $table->string('base_uom_id')->nullable()->after('uom');
            $table->foreign('base_uom_id')->references('id')->on('units_of_measure')->onDelete('set null');
        });

        // Sale items: track which UOM was used + multiplier snapshot
        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('uom_id')->nullable()->after('product_id');
            $table->decimal('uom_multiplier', 15, 6)->default(1)->after('uom_id');
        });

        // Purchase items: same
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->string('uom_id')->nullable()->after('product_id');
            $table->decimal('uom_multiplier', 15, 6)->default(1)->after('uom_id');
        });

        // Sale return items: same
        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->string('uom_id')->nullable()->after('product_id');
            $table->decimal('uom_multiplier', 15, 6)->default(1)->after('uom_id');
        });

        // Purchase return items: same
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->string('uom_id')->nullable()->after('product_id');
            $table->decimal('uom_multiplier', 15, 6)->default(1)->after('uom_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropColumn(['uom_id', 'uom_multiplier']);
        });
        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->dropColumn(['uom_id', 'uom_multiplier']);
        });
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['uom_id', 'uom_multiplier']);
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['uom_id', 'uom_multiplier']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['base_uom_id']);
            $table->dropColumn('base_uom_id');
        });
        Schema::dropIfExists('product_uom_conversions');
    }
};
