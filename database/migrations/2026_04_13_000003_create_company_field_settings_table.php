<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_field_settings', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('company_id', 20);
            $table->string('entity_type', 20); // 'product' | 'customer'
            $table->string('field_key', 50);
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique(['company_id', 'entity_type', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_field_settings');
    }
};
