<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Group 1: syncTransactions date-range queries ─────────────────────
        // SyncService::getTransactionData() does WHERE company_id=? AND created_at >= ?
        // on all 6 of these tables on every page load. Without this index MySQL
        // does a full table scan filtered only by the FK company_id index.

        Schema::table('sale_orders', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'so_company_created_idx');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'po_company_created_idx');
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'sr_company_created_idx');
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'pr_company_created_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'pay_company_created_idx');
        });

        Schema::table('inventory_ledger', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'il_company_created_idx');
        });

        // ── Group 2: Inventory costing & ledger report queries ───────────────

        // Party ledger / product ledger: WHERE company_id=? AND product_id=?
        // Two separate FK indexes exist but MySQL can't use both simultaneously
        // for a compound filter — a compound index is required.
        Schema::table('inventory_ledger', function (Blueprint $table) {
            $table->index(['company_id', 'product_id'], 'il_company_product_idx');
        });

        // FIFO: WHERE company_id=? AND product_id=? AND remaining_quantity > 0
        // ORDER BY created_at ASC. The 3-column compound index covers the WHERE
        // clause; MySQL uses it to skip exhausted (remaining_quantity=0) layers.
        Schema::table('inventory_cost_layers', function (Blueprint $table) {
            $table->index(
                ['company_id', 'product_id', 'remaining_quantity'],
                'icl_company_product_remaining_idx'
            );
        });

        // ── Group 3: Document sequence lock bottleneck ───────────────────────
        // DocumentSequenceService::getNextNumber() does:
        //   SELECT ... WHERE company_id=? AND type=? FOR UPDATE
        // on every single sale and purchase creation. A compound index here
        // means the lock is acquired in microseconds instead of a table scan.
        Schema::table('document_sequences', function (Blueprint $table) {
            $table->index(['company_id', 'type'], 'ds_company_type_idx');
        });

        // ── Group 4: Job card queries ────────────────────────────────────────
        // SyncService: WHERE company_id=? AND status='open'
        Schema::table('job_cards', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'jc_company_status_idx');
        });

        // SyncService: WHERE company_id=? AND status='closed' AND created_at >= ?
        Schema::table('job_cards', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'jc_company_created_idx');
        });

        // ── Group 5: Journal entry lines balance subquery ────────────────────
        // SyncService::getCoreData() runs a correlated subquery per account:
        //   SELECT SUM(jel.debit), SUM(jel.credit)
        //   FROM journal_entry_lines jel
        //   JOIN journal_entries je ON je.id = jel.journal_entry_id
        //   WHERE je.is_posted = 1 AND jel.account_id = ?
        // The FK creates individual indexes on journal_entry_id and account_id
        // but not a compound one. This covers the JOIN + WHERE together.
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->index(['journal_entry_id', 'account_id'], 'jel_entry_account_idx');
        });

        // ── Group 6: Company field settings ─────────────────────────────────
        // SyncService::getMasterData(): WHERE company_id=? AND is_enabled=1
        Schema::table('company_field_settings', function (Blueprint $table) {
            $table->index(['company_id', 'is_enabled'], 'cfs_company_enabled_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropIndex('so_company_created_idx');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('po_company_created_idx');
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropIndex('sr_company_created_idx');
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropIndex('pr_company_created_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('pay_company_created_idx');
        });

        Schema::table('inventory_ledger', function (Blueprint $table) {
            $table->dropIndex('il_company_created_idx');
            $table->dropIndex('il_company_product_idx');
        });

        Schema::table('inventory_cost_layers', function (Blueprint $table) {
            $table->dropIndex('icl_company_product_remaining_idx');
        });

        Schema::table('document_sequences', function (Blueprint $table) {
            $table->dropIndex('ds_company_type_idx');
        });

        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropIndex('jc_company_status_idx');
            $table->dropIndex('jc_company_created_idx');
        });

        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropIndex('jel_entry_account_idx');
        });

        Schema::table('company_field_settings', function (Blueprint $table) {
            $table->dropIndex('cfs_company_enabled_idx');
        });
    }
};
