<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->update(['password_plain' => null]);
        DB::table('companies')->update(['admin_password_plain' => null]);
    }

    public function down(): void
    {
        // Passwords cannot be recovered — intentionally irreversible
    }
};
