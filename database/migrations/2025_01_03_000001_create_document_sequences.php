<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('type'); // po_number, sale_invoice, customer_no, vendor_no, item_no, sku
            $table->string('prefix')->default('');
            $table->integer('next_number')->default(1);
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'type']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
