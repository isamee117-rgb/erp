<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->string('entry_no');
            $table->date('date');
            $table->text('description');
            $table->string('reference_type')->nullable();
            // sale_order | purchase_receive | payment | sale_return | purchase_return | manual
            $table->string('reference_id')->nullable();
            $table->boolean('is_posted')->default(true);
            $table->string('created_by');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'reference_type', 'reference_id']);
            $table->index(['company_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};