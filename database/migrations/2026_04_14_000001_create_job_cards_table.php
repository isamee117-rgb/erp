<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_cards', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('job_card_no');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->string('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('vehicle_reg_number')->nullable();
            $table->string('vin_chassis_number')->nullable();
            $table->string('engine_number')->nullable();
            $table->string('make_model_year')->nullable();
            $table->string('lift_number')->nullable();
            $table->decimal('current_odometer', 15, 2)->nullable();
            $table->enum('payment_method', ['Cash', 'Credit'])->default('Cash');
            $table->decimal('parts_subtotal', 15, 2)->default(0);
            $table->decimal('services_subtotal', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->enum('discount_type', ['fixed', 'percent'])->default('fixed');
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->string('created_by');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('parties')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->unique(['company_id', 'job_card_no']);
        });

        Schema::create('job_card_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('job_card_id');
            $table->enum('item_type', ['part', 'service']);
            $table->string('product_id');
            $table->string('product_name');
            $table->decimal('quantity', 10, 3)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total_line_price', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('job_card_id')->references('id')->on('job_cards')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_card_items');
        Schema::dropIfExists('job_cards');
    }
};
