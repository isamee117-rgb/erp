# Database Indexes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add all missing compound indexes across the LeanERP database so that 350-user concurrent load does not cause full table scans or query timeouts.

**Architecture:** One new migration file adds all indexes. No model, controller, or service code changes. Every index is additive — zero downtime risk on existing data. The migration is fully reversible via `down()`.

**Tech Stack:** Laravel 12 migrations, MySQL, XAMPP PHP at `/c/xampp/php/php`

---

## Context: What's Already Indexed (Do NOT re-add)

| Table | Already Indexed Column(s) | How |
|-------|--------------------------|-----|
| `users` | `api_token` | `unique()` in migration |
| `settings` | `(company_id, key)` | `unique()` in migration |
| `sale_orders` | `(company_id, invoice_no)` | `unique()` in migration |
| `purchase_orders` | `(company_id, po_no)` | `unique()` in migration |
| `sale_returns` | `(company_id, return_no)` | `unique()` in migration |
| `purchase_returns` | `(company_id, return_no)` | `unique()` in migration |
| `products` | `(company_id, barcode)` | `unique()` in migration |
| `job_cards` | `(company_id, job_card_no)` | `unique()` in migration |
| `journal_entries` | `(company_id, reference_type, reference_id)` | `index()` in migration |
| `journal_entries` | `(company_id, date)` | `index()` in migration |
| All FK columns | single-column index | MySQL auto-creates for every FK |

---

## What's Missing & Why

| # | Table | Index to Add | Query That Needs It |
|---|-------|-------------|---------------------|
| 1 | `sale_orders` | `(company_id, created_at)` | `syncTransactions`: `WHERE company_id=? AND created_at >= ?` |
| 2 | `purchase_orders` | `(company_id, created_at)` | same |
| 3 | `sale_returns` | `(company_id, created_at)` | same |
| 4 | `purchase_returns` | `(company_id, created_at)` | same |
| 5 | `payments` | `(company_id, created_at)` | same |
| 6 | `inventory_ledger` | `(company_id, created_at)` | same |
| 7 | `inventory_ledger` | `(company_id, product_id)` | product/party ledger report filters |
| 8 | `inventory_cost_layers` | `(company_id, product_id, remaining_quantity)` | FIFO: `WHERE company_id=? AND product_id=? AND remaining_quantity > 0 ORDER BY created_at` |
| 9 | `document_sequences` | `(company_id, type)` | `lockForUpdate` on every sale/purchase — serialized bottleneck |
| 10 | `job_cards` | `(company_id, status)` | `WHERE company_id=? AND status='open'` |
| 11 | `job_cards` | `(company_id, created_at)` | recent closed job cards query |
| 12 | `journal_entry_lines` | `(journal_entry_id, account_id)` | balance subquery in `SyncService::getCoreData()` — JOINs journal_entries, filters by account_id |
| 13 | `company_field_settings` | `(company_id, is_enabled)` | `WHERE company_id=? AND is_enabled=1` in `getMasterData()` |

---

## Files

| Action | Path |
|--------|------|
| **Create** | `database/migrations/2026_05_12_000001_add_performance_indexes.php` |

No other files change.

---

## Task 1: Write the Migration

**File:** `database/migrations/2026_05_12_000001_add_performance_indexes.php`

- [ ] **Step 1: Create the migration file** with this exact content:

```php
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
```

---

## Task 2: Verify the Migration Runs Clean

- [ ] **Step 2: Run the migration**

```bash
/c/xampp/php/php artisan migrate
```

Expected output (exact table names in any order):
```
INFO  Running migrations.
2026_05_12_000001_add_performance_indexes ............ 1,XXXms DONE
```

If you see `SQLSTATE[42000]: Duplicate key name` it means an index with that name already exists in the DB. Fix: rename the conflicting index in the migration (e.g. `so_company_created_idx` → `so_company_created_idx2`).

- [ ] **Step 3: Verify indexes exist in MySQL**

```bash
/c/xampp/mysql/bin/mysql -u root -h 127.0.0.1 -P 3306 lean_erp -e "
  SELECT TABLE_NAME, INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS cols
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = 'lean_erp'
    AND INDEX_NAME IN (
      'so_company_created_idx',
      'po_company_created_idx',
      'sr_company_created_idx',
      'pr_company_created_idx',
      'pay_company_created_idx',
      'il_company_created_idx',
      'il_company_product_idx',
      'icl_company_product_remaining_idx',
      'ds_company_type_idx',
      'jc_company_status_idx',
      'jc_company_created_idx',
      'jel_entry_account_idx',
      'cfs_company_enabled_idx'
    )
  GROUP BY TABLE_NAME, INDEX_NAME
  ORDER BY TABLE_NAME;
"
```

Expected: **13 rows** — one per index name above. If any are missing, the migration silently failed for that table (table may not exist yet — e.g. `company_field_settings` only exists if that feature migration ran). Check with `php artisan migrate:status`.

- [ ] **Step 4: Run the test suite to confirm nothing broke**

```bash
/c/xampp/php/php artisan test
```

Expected: all tests pass (indexes are additive — no behavior change).

---

## Task 3: Commit

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_05_12_000001_add_performance_indexes.php
git commit -m "perf: add 13 compound indexes for 350-user production load

Covers syncTransactions date-range scans, FIFO costing, document
sequence lockForUpdate bottleneck, job card queries, journal entry
balance subquery, and company field settings filter."
```

---

## Self-Review Checklist

- [x] **Spec coverage:** All 13 identified missing indexes have a corresponding `$table->index()` call and a matching `dropIndex()` in `down()`.
- [x] **No placeholders:** Every step has exact code, exact commands, exact expected output.
- [x] **No re-indexing of existing indexes:** `api_token`, `settings(company_id,key)`, `sale_orders(company_id,invoice_no)`, FK columns — none re-added.
- [x] **Index names consistent:** Every name used in `up()` matches the corresponding `down()` call exactly.
- [x] **Type consistency:** All method calls use standard Laravel Blueprint API — `index()` and `dropIndex()`.
