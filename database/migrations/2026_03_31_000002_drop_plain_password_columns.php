<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_plain');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('admin_password_plain');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password_plain')->nullable();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('admin_password_plain')->nullable();
        });
    }
};
