<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('costing_method')->default('moving_average');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->integer('received_quantity')->default(0);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('cogs', 15, 2)->default(0);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('received_amount', 15, 2)->default(0);
        });

        Schema::create('inventory_cost_layers', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('product_id');
            $table->integer('quantity');
            $table->integer('remaining_quantity');
            $table->decimal('unit_cost', 15, 2);
            $table->string('reference_id');
            $table->string('reference_type');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('purchase_receives', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('purchase_order_id');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
        });

        Schema::create('purchase_receive_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('purchase_receive_id');
            $table->string('purchase_item_id');
            $table->string('product_id');
            $table->integer('quantity');
            $table->decimal('unit_cost', 15, 2);
            $table->timestamps();

            $table->foreign('purchase_receive_id')->references('id')->on('purchase_receives')->onDelete('cascade');
            $table->foreign('purchase_item_id')->references('id')->on('purchase_items')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_receive_items');
        Schema::dropIfExists('purchase_receives');
        Schema::dropIfExists('inventory_cost_layers');

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('received_amount');
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('cogs');
        });
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('received_quantity');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('costing_method');
        });
    }
};
