<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_line_mappings', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_id');
            $table->enum('report_type', ['profit_loss', 'balance_sheet']);
            $table->string('line_key', 50);
            $table->string('account_id');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('chart_of_accounts')->onDelete('cascade');
            $table->unique(['company_id', 'report_type', 'account_id']);
            $table->index(['company_id', 'report_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_line_mappings');
    }
};
