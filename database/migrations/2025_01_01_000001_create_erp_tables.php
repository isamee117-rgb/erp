<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'purchase_return_items', 'purchase_returns',
            'sale_return_items', 'sale_returns',
            'inventory_ledger',
            'purchase_items', 'purchase_orders',
            'sale_items', 'sale_orders',
            'payments',
            'products',
            'parties',
            'categories', 'units_of_measure',
            'users', 'custom_roles',
            'companies',
            'entity_types', 'business_categories',
            'settings',
            'personal_access_tokens', 'password_reset_tokens', 'password_resets',
            'failed_jobs', 'job_batches', 'jobs',
            'cache_locks', 'cache', 'sessions',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $t) {
            DB::statement("DROP TABLE IF EXISTS `{$t}`");
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Schema::create('companies', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('status');
            $table->integer('max_user_limit');
            $table->decimal('registration_payment', 15, 2);
            $table->string('saas_plan');
            $table->string('info_name')->nullable();
            $table->string('info_tagline')->nullable();
            $table->text('info_address')->nullable();
            $table->string('info_phone')->nullable();
            $table->string('info_email')->nullable();
            $table->string('info_website')->nullable();
            $table->string('info_tax_id')->nullable();
            $table->string('info_logo_url')->nullable();
            $table->timestamps();
        });

        Schema::create('custom_roles', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('name');
            $table->text('description');
            $table->json('permissions');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('system_role');
            $table->string('role_id')->nullable();
            $table->string('company_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('custom_roles')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::create('entity_types', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('business_categories', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('sku');
            $table->string('name');
            $table->string('type');
            $table->string('uom');
            $table->string('category_id');
            $table->integer('current_stock')->default(0);
            $table->integer('reorder_level')->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });

        Schema::create('parties', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('code');
            $table->string('type');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('sub_type')->nullable();
            $table->string('payment_terms')->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->string('bank_details')->nullable();
            $table->string('category')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::create('sale_orders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('customer_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->string('payment_method');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->boolean('is_returned')->default(false);
            $table->timestamp('updated_at')->nullable();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('parties')->onDelete('set null');
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('sale_order_id');
            $table->string('product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total_line_price', 15, 2);
            $table->timestamps();

            $table->foreign('sale_order_id')->references('id')->on('sale_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('vendor_id');
            $table->timestamp('created_at')->nullable();
            $table->string('status');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('parties')->onDelete('cascade');
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('purchase_order_id');
            $table->string('product_id');
            $table->integer('quantity');
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_line_cost', 15, 2);
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('party_id');
            $table->bigInteger('date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method');
            $table->string('type');
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('party_id')->references('id')->on('parties')->onDelete('cascade');
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('original_sale_id');
            $table->string('customer_id')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->text('reason');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('original_sale_id')->references('id')->on('sale_orders')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('parties')->onDelete('set null');
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('sale_return_id');
            $table->string('product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total_line_price', 15, 2);
            $table->timestamps();

            $table->foreign('sale_return_id')->references('id')->on('sale_returns')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('original_purchase_id');
            $table->string('vendor_id');
            $table->decimal('total_amount', 15, 2);
            $table->text('reason');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('original_purchase_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('parties')->onDelete('cascade');
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('purchase_return_id');
            $table->string('product_id');
            $table->integer('quantity');
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_line_cost', 15, 2);
            $table->timestamps();

            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('inventory_ledger', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('product_id');
            $table->timestamp('created_at')->nullable();
            $table->string('transaction_type');
            $table->integer('quantity_change');
            $table->string('reference_id');
            $table->timestamp('updated_at')->nullable();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('inventory_ledger');
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sale_orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('parties');
        Schema::dropIfExists('units_of_measure');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('users');
        Schema::dropIfExists('custom_roles');
        Schema::dropIfExists('business_categories');
        Schema::dropIfExists('entity_types');
        Schema::dropIfExists('companies');
    }
};
