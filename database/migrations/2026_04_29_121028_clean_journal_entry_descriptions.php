<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('journal_entries')
            ->where('reference_type', 'sale_order')
            ->where('description', 'like', 'Sales Invoice #%')
            ->update(['description' => 'Sales Invoice']);

        DB::table('journal_entries')
            ->where('reference_type', 'sale_return')
            ->where('description', 'like', 'Sale Return #%')
            ->update(['description' => 'Sale Return']);

        DB::table('journal_entries')
            ->where('reference_type', 'purchase_receive')
            ->where('description', 'like', 'Goods Received%')
            ->update(['description' => 'Goods Received']);

        DB::table('journal_entries')
            ->where('reference_type', 'purchase_return')
            ->where('description', 'like', 'Purchase Return #%')
            ->update(['description' => 'Purchase Return']);
    }

    public function down(): void
    {
        // Original values are not recoverable — description was redundant data
    }
};
