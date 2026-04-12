<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_tiers', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('product_id');
            $table->string('company_id');
            $table->string('category');
            $table->decimal('price', 15, 4);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique(['product_id', 'category'], 'unique_product_category_tier');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_tiers');
    }
};
