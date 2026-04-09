<?php

namespace Tests\Feature;

use App\Models\AccountMapping;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Party;
use App\Models\Product;
use App\Services\DocumentSequenceService;
use Illuminate\Support\Str;

class AccountingTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app(DocumentSequenceService::class)->ensureSequencesExist($this->company->id);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createAccount(array $overrides = []): array
    {
        $response = $this->postJson('/api/accounting/coa', array_merge([
            'code'    => '1001',
            'name'    => 'Cash in Hand',
            'type'    => 'Asset',
            'subType' => 'current_asset',
        ], $overrides), $this->auth());

        return $response->json();
    }

    private function seedMappedAccounts(): array
    {
        $accounts = [
            ['code' => '1001', 'name' => 'Cash',                  'type' => 'Asset',   'subType' => 'current_asset'],
            ['code' => '1100', 'name' => 'Accounts Receivable',    'type' => 'Asset',   'subType' => 'current_asset'],
            ['code' => '1200', 'name' => 'Inventory',              'type' => 'Asset',   'subType' => 'current_asset'],
            ['code' => '2001', 'name' => 'Accounts Payable',       'type' => 'Liability','subType' => 'current_liability'],
            ['code' => '4001', 'name' => 'Sales Revenue',          'type' => 'Revenue', 'subType' => 'operating_revenue'],
            ['code' => '5001', 'name' => 'Cost of Goods Sold',     'type' => 'Expense', 'subType' => 'cost_of_goods_sold'],
        ];

        $created = [];
        foreach ($accounts as $acc) {
            $model = ChartOfAccount::create([
                'id'         => 'COA-' . Str::random(9),
                'company_id' => $this->company->id,
                'code'       => $acc['code'],
                'name'       => $acc['name'],
                'type'       => $acc['type'],
                'sub_type'   => $acc['subType'],
                'is_system'  => false,
                'is_active'  => true,
            ]);
            $created[$acc['code']] = $model;
        }

        $mappingDefs = [
            'cash_account'       => '1001',
            'accounts_receivable'=> '1100',
            'inventory_asset'    => '1200',
            'accounts_payable'   => '2001',
            'sales_revenue'      => '4001',
            'cost_of_goods_sold' => '5001',
        ];

        foreach ($mappingDefs as $key => $code) {
            AccountMapping::create([
                'id'          => 'MAP-' . Str::random(9),
                'company_id'  => $this->company->id,
                'mapping_key' => $key,
                'account_id'  => $created[$code]->id,
            ]);
        }

        return $created;
    }

    // ── Chart of Accounts ─────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_create_a_chart_of_account(): void
    {
        $response = $this->postJson('/api/accounting/coa', [
            'code'    => '1001',
            'name'    => 'Cash in Hand',
            'type'    => 'Asset',
            'subType' => 'current_asset',
        ], $this->auth());

        $response->assertStatus(201)
                 ->assertJsonFragment(['code' => '1001', 'name' => 'Cash in Hand']);

        $this->assertDatabaseHas('chart_of_accounts', [
            'company_id' => $this->company->id,
            'code'       => '1001',
            'type'       => 'Asset',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_create_account_with_duplicate_code(): void
    {
        $this->createAccount(['code' => '1001']);

        $response = $this->postJson('/api/accounting/coa', [
            'code'    => '1001',
            'name'    => 'Another Account',
            'type'    => 'Asset',
            'subType' => 'current_asset',
        ], $this->auth());

        $response->assertStatus(422)
                 ->assertJsonFragment(['error' => 'Account code already exists']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_create_account_with_invalid_type(): void
    {
        $response = $this->postJson('/api/accounting/coa', [
            'code'    => '9999',
            'name'    => 'Bad Account',
            'type'    => 'InvalidType',
            'subType' => null,
        ], $this->auth());

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_update_account_name_and_status(): void
    {
        $account = $this->createAccount();

        $response = $this->putJson('/api/accounting/coa/' . $account['id'], [
            'name'     => 'Updated Cash Account',
            'isActive' => false,
        ], $this->auth());

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Updated Cash Account', 'isActive' => false]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_delete_account_without_transactions(): void
    {
        $account = $this->createAccount(['code' => '9990']);

        $response = $this->deleteJson('/api/accounting/coa/' . $account['id'], [], $this->auth());

        $response->assertStatus(200);
        $this->assertDatabaseMissing('chart_of_accounts', ['id' => $account['id']]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_delete_account_with_transactions(): void
    {
        $accounts = $this->seedMappedAccounts();
        $cashId   = $accounts['1001']->id;

        // Create a journal entry line against this account
        $entry = JournalEntry::create([
            'id'         => 'JE-' . Str::random(9),
            'company_id' => $this->company->id,
            'entry_no'   => 'JE-00001',
            'date'       => now()->toDateString(),
            'description'=> 'Test entry',
            'is_posted'  => true,
            'created_by' => $this->adminUser->id,
        ]);
        JournalEntryLine::create([
            'id'               => 'JEL-' . Str::random(9),
            'journal_entry_id' => $entry->id,
            'account_id'       => $cashId,
            'debit'            => 1000,
            'credit'           => 0,
        ]);

        $response = $this->deleteJson('/api/accounting/coa/' . $cashId, [], $this->auth());

        $response->assertStatus(422)
                 ->assertJsonFragment(['error' => 'Account has transactions and cannot be deleted']);
    }

    // ── Account Mappings ──────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_save_account_mappings(): void
    {
        $account = $this->createAccount(['code' => '1001']);

        $response = $this->putJson('/api/accounting/mappings', [
            'mappings' => [
                ['mappingKey' => 'cash_account', 'accountId' => $account['id']],
            ],
        ], $this->auth());

        $response->assertStatus(200)
                 ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('account_mappings', [
            'company_id'  => $this->company->id,
            'mapping_key' => 'cash_account',
            'account_id'  => $account['id'],
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function updating_mapping_does_not_create_duplicate(): void
    {
        $account1 = $this->createAccount(['code' => '1001', 'name' => 'Cash']);
        $account2 = $this->createAccount(['code' => '1002', 'name' => 'Bank']);

        // Save initial mapping
        $this->putJson('/api/accounting/mappings', [
            'mappings' => [['mappingKey' => 'cash_account', 'accountId' => $account1['id']]],
        ], $this->auth());

        // Update mapping to new account
        $this->putJson('/api/accounting/mappings', [
            'mappings' => [['mappingKey' => 'cash_account', 'accountId' => $account2['id']]],
        ], $this->auth());

        // Should be exactly one mapping for this key
        $count = AccountMapping::where('company_id', $this->company->id)
            ->where('mapping_key', 'cash_account')
            ->count();

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('account_mappings', [
            'mapping_key' => 'cash_account',
            'account_id'  => $account2['id'],
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function mapping_requires_valid_account_id(): void
    {
        $response = $this->putJson('/api/accounting/mappings', [
            'mappings' => [
                ['mappingKey' => 'cash_account', 'accountId' => 'COA-nonexistent'],
            ],
        ], $this->auth());

        $response->assertStatus(422);
    }

    // ── Journal Entries ───────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_create_a_draft_journal_entry(): void
    {
        $accounts = $this->seedMappedAccounts();
        $cashId   = $accounts['1001']->id;
        $revId    = $accounts['4001']->id;

        $response = $this->postJson('/api/accounting/journals', [
            'date'             => now()->toDateString(),
            'description'      => 'Test manual entry',
            'postImmediately'  => false,
            'lines'            => [
                ['accountId' => $cashId, 'debit' => 1000, 'credit' => 0,    'description' => 'Cash DR'],
                ['accountId' => $revId,  'debit' => 0,    'credit' => 1000, 'description' => 'Revenue CR'],
            ],
        ], $this->auth());

        $response->assertStatus(201)
                 ->assertJsonFragment(['isPosted' => false]);

        $this->assertDatabaseHas('journal_entries', [
            'company_id'     => $this->company->id,
            'is_posted'      => false,
            'reference_type' => 'manual',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_create_and_post_journal_entry_immediately(): void
    {
        $accounts = $this->seedMappedAccounts();

        $response = $this->postJson('/api/accounting/journals', [
            'date'             => now()->toDateString(),
            'description'      => 'Posted immediately',
            'postImmediately'  => true,
            'lines'            => [
                ['accountId' => $accounts['1001']->id, 'debit' => 500, 'credit' => 0],
                ['accountId' => $accounts['4001']->id, 'debit' => 0,   'credit' => 500],
            ],
        ], $this->auth());

        $response->assertStatus(201)
                 ->assertJsonFragment(['isPosted' => true]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function journal_entry_rejected_when_not_balanced(): void
    {
        $accounts = $this->seedMappedAccounts();

        $response = $this->postJson('/api/accounting/journals', [
            'date'        => now()->toDateString(),
            'description' => 'Unbalanced entry',
            'lines'       => [
                ['accountId' => $accounts['1001']->id, 'debit' => 1000, 'credit' => 0],
                ['accountId' => $accounts['4001']->id, 'debit' => 0,    'credit' => 999],
            ],
        ], $this->auth());

        $response->assertStatus(422)
                 ->assertJsonFragment(['error' => 'Journal entry is not balanced. Debits must equal credits.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_post_a_draft_journal_entry(): void
    {
        $accounts = $this->seedMappedAccounts();

        $created = $this->postJson('/api/accounting/journals', [
            'date'        => now()->toDateString(),
            'description' => 'Draft to post',
            'lines'       => [
                ['accountId' => $accounts['1001']->id, 'debit' => 200, 'credit' => 0],
                ['accountId' => $accounts['4001']->id, 'debit' => 0,   'credit' => 200],
            ],
        ], $this->auth());

        $entryId = $created->json('id');

        $response = $this->postJson('/api/accounting/journals/' . $entryId . '/post', [], $this->auth());

        $response->assertStatus(200)
                 ->assertJsonFragment(['isPosted' => true]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_post_an_already_posted_entry(): void
    {
        $accounts = $this->seedMappedAccounts();

        $created = $this->postJson('/api/accounting/journals', [
            'date'            => now()->toDateString(),
            'description'     => 'Already posted',
            'postImmediately' => true,
            'lines'           => [
                ['accountId' => $accounts['1001']->id, 'debit' => 100, 'credit' => 0],
                ['accountId' => $accounts['4001']->id, 'debit' => 0,   'credit' => 100],
            ],
        ], $this->auth());

        $entryId = $created->json('id');

        $response = $this->postJson('/api/accounting/journals/' . $entryId . '/post', [], $this->auth());

        $response->assertStatus(422)
                 ->assertJsonFragment(['error' => 'Entry is already posted']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_delete_a_draft_journal_entry(): void
    {
        $accounts = $this->seedMappedAccounts();

        $created = $this->postJson('/api/accounting/journals', [
            'date'        => now()->toDateString(),
            'description' => 'Draft to delete',
            'lines'       => [
                ['accountId' => $accounts['1001']->id, 'debit' => 300, 'credit' => 0],
                ['accountId' => $accounts['4001']->id, 'debit' => 0,   'credit' => 300],
            ],
        ], $this->auth());

        $entryId = $created->json('id');

        $response = $this->deleteJson('/api/accounting/journals/' . $entryId, [], $this->auth());

        $response->assertStatus(200);
        $this->assertDatabaseMissing('journal_entries', ['id' => $entryId]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_delete_a_posted_journal_entry(): void
    {
        $accounts = $this->seedMappedAccounts();

        $created = $this->postJson('/api/accounting/journals', [
            'date'            => now()->toDateString(),
            'description'     => 'Posted - no delete',
            'postImmediately' => true,
            'lines'           => [
                ['accountId' => $accounts['1001']->id, 'debit' => 500, 'credit' => 0],
                ['accountId' => $accounts['4001']->id, 'debit' => 0,   'credit' => 500],
            ],
        ], $this->auth());

        $entryId = $created->json('id');

        $response = $this->deleteJson('/api/accounting/journals/' . $entryId, [], $this->auth());

        $response->assertStatus(422)
                 ->assertJsonFragment(['error' => 'Posted entries cannot be deleted']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function journal_entry_requires_minimum_two_lines(): void
    {
        $accounts = $this->seedMappedAccounts();

        $response = $this->postJson('/api/accounting/journals', [
            'date'        => now()->toDateString(),
            'description' => 'Only one line',
            'lines'       => [
                ['accountId' => $accounts['1001']->id, 'debit' => 100, 'credit' => 0],
            ],
        ], $this->auth());

        $response->assertStatus(422);
    }

    // ── Reports ───────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function profit_loss_returns_correct_structure(): void
    {
        $accounts = $this->seedMappedAccounts();

        // Post a revenue entry
        $this->postJson('/api/accounting/journals', [
            'date'            => now()->toDateString(),
            'description'     => 'Revenue entry',
            'postImmediately' => true,
            'lines'           => [
                ['accountId' => $accounts['1001']->id, 'debit' => 2000, 'credit' => 0],
                ['accountId' => $accounts['4001']->id, 'debit' => 0,    'credit' => 2000],
            ],
        ], $this->auth());

        $from = now()->startOfMonth()->toDateString();
        $to   = now()->endOfMonth()->toDateString();

        $response = $this->getJson('/api/reports/profit-loss?from=' . $from . '&to=' . $to, $this->auth());

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'period',
                     'revenue',
                     'totalRevenue',
                     'cogs',
                     'totalCogs',
                     'grossProfit',
                     'expenses',
                     'totalExpenses',
                     'netProfit',
                 ]);

        $this->assertEquals(2000, $response->json('totalRevenue'));
        $this->assertEquals(2000, $response->json('grossProfit'));
        $this->assertEquals(2000, $response->json('netProfit'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function profit_loss_excludes_draft_entries(): void
    {
        $accounts = $this->seedMappedAccounts();

        // Draft entry — should NOT appear in report
        $this->postJson('/api/accounting/journals', [
            'date'            => now()->toDateString(),
            'description'     => 'Draft revenue',
            'postImmediately' => false,
            'lines'           => [
                ['accountId' => $accounts['1001']->id, 'debit' => 5000, 'credit' => 0],
                ['accountId' => $accounts['4001']->id, 'debit' => 0,    'credit' => 5000],
            ],
        ], $this->auth());

        $from = now()->startOfMonth()->toDateString();
        $to   = now()->endOfMonth()->toDateString();

        $response = $this->getJson('/api/reports/profit-loss?from=' . $from . '&to=' . $to, $this->auth());

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('totalRevenue'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function balance_sheet_returns_correct_structure(): void
    {
        $accounts = $this->seedMappedAccounts();

        // Post an asset + liability entry
        $this->postJson('/api/accounting/journals', [
            'date'            => now()->toDateString(),
            'description'     => 'Balance sheet entry',
            'postImmediately' => true,
            'lines'           => [
                ['accountId' => $accounts['1001']->id, 'debit' => 3000, 'credit' => 0],
                ['accountId' => $accounts['2001']->id, 'debit' => 0,    'credit' => 3000],
            ],
        ], $this->auth());

        $asOf = now()->toDateString();

        $response = $this->getJson('/api/reports/balance-sheet?as_of=' . $asOf, $this->auth());

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'asOf',
                     'assets',
                     'totalAssets',
                     'liabilities',
                     'totalLiabilities',
                     'equity',
                     'retainedEarnings',
                     'totalEquity',
                     'totalLiabEquity',
                 ]);

        $this->assertEquals(3000, $response->json('totalAssets'));
        $this->assertEquals(3000, $response->json('totalLiabilities'));
    }

    // ── Mapping Index & Guards ────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_mappings_index_returns_results_keyed_by_mapping_key(): void
    {
        $account = $this->createAccount(['code' => '4001', 'name' => 'Sales Revenue', 'type' => 'Revenue', 'subType' => 'operating_revenue']);

        $this->putJson('/api/accounting/mappings', [
            'mappings' => [
                ['mappingKey' => 'sales_revenue', 'accountId' => $account['id']],
            ],
        ], $this->auth());

        $response = $this->getJson('/api/accounting/mappings', $this->auth());

        $response->assertStatus(200)
                 ->assertJsonPath('sales_revenue.mapping_key', 'sales_revenue')
                 ->assertJsonPath('sales_revenue.account_id', $account['id']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function super_admin_cannot_save_account_mappings(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $token      = $this->loginAndGetToken($superAdmin);
        $account    = $this->createAccount(['code' => '1001']);

        $response = $this->putJson('/api/accounting/mappings', [
            'mappings' => [
                ['mappingKey' => 'cash_account', 'accountId' => $account['id']],
            ],
        ], $this->auth($token));

        $response->assertStatus(403)
                 ->assertJsonFragment(['error' => 'Account mappings are not available for Super Admin.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function all_seven_modal_mapping_keys_can_be_saved(): void
    {
        $accounts = $this->seedMappedAccounts();

        // Add discount accounts needed for new keys
        $discAllowed  = ChartOfAccount::create([
            'id' => 'COA-' . Str::random(9), 'company_id' => $this->company->id,
            'code' => '6001', 'name' => 'Discount Allowed', 'type' => 'Expense',
            'sub_type' => 'operating_expense', 'is_system' => false, 'is_active' => true,
        ]);
        $discReceived = ChartOfAccount::create([
            'id' => 'COA-' . Str::random(9), 'company_id' => $this->company->id,
            'code' => '7001', 'name' => 'Discount Received', 'type' => 'Revenue',
            'sub_type' => 'other_revenue', 'is_system' => false, 'is_active' => true,
        ]);

        $response = $this->putJson('/api/accounting/mappings', [
            'mappings' => [
                ['mappingKey' => 'sales_revenue',      'accountId' => $accounts['4001']->id],
                ['mappingKey' => 'cost_of_goods_sold', 'accountId' => $accounts['5001']->id],
                ['mappingKey' => 'inventory_asset',    'accountId' => $accounts['1200']->id],
                ['mappingKey' => 'accounts_receivable','accountId' => $accounts['1100']->id],
                ['mappingKey' => 'accounts_payable',   'accountId' => $accounts['2001']->id],
                ['mappingKey' => 'discount_allowed',   'accountId' => $discAllowed->id],
                ['mappingKey' => 'discount_received',  'accountId' => $discReceived->id],
            ],
        ], $this->auth());

        $response->assertStatus(200)->assertJsonFragment(['success' => true]);

        foreach (['sales_revenue', 'cost_of_goods_sold', 'inventory_asset', 'accounts_receivable', 'accounts_payable', 'discount_allowed', 'discount_received'] as $key) {
            $this->assertDatabaseHas('account_mappings', [
                'company_id'  => $this->company->id,
                'mapping_key' => $key,
            ]);
        }
    }

    // ── Auto-Posting (JournalPostingService) ─────────────────────────────────

    /** Create product + customer directly via Eloquent (category_id is NOT nullable, bypasses API FK issue). */
    private function seedSaleFixtures(string $sku = 'SKU-AUTO-001'): array
    {
        $category = Category::create([
            'id'         => 'CAT-' . Str::random(9),
            'company_id' => $this->company->id,
            'name'       => 'General',
        ]);
        $product = Product::create([
            'id'            => 'PRD-' . Str::random(9),
            'company_id'    => $this->company->id,
            'sku'           => $sku,
            'name'          => 'Test Item',
            'type'          => 'product',
            'uom'           => 'pcs',
            'category_id'   => $category->id,
            'current_stock' => 10,
            'unit_cost'     => 60.00,
            'unit_price'    => 100.00,
        ]);
        $customer = Party::create([
            'id'              => 'CUS-' . Str::random(9),
            'company_id'      => $this->company->id,
            'code'            => 'C-' . Str::random(4),
            'type'            => 'Customer',
            'name'            => 'Walk-in Customer',
            'current_balance' => 0,
            'opening_balance' => 0,
            'credit_limit'    => 0,
        ]);
        return [$product, $customer];
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sale_auto_posts_journal_entry_when_mappings_configured(): void
    {
        $this->company->update(['industry' => 'Retail']);
        $this->seedMappedAccounts();
        [$product, $customer] = $this->seedSaleFixtures('SKU-AUTO-001');

        $this->postJson('/api/sales', [
            'customerId'    => $customer->id,
            'paymentMethod' => 'Cash',
            'items'         => [
                ['productId' => $product->id, 'quantity' => 1, 'discount' => 0],
            ],
        ], $this->auth());

        $this->assertDatabaseHas('journal_entries', [
            'company_id'     => $this->company->id,
            'reference_type' => 'sale_order',
            'is_posted'      => true,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sale_does_not_post_journal_when_no_mappings(): void
    {
        // No industry set, no mappings — should skip auto-posting silently
        [$product, $customer] = $this->seedSaleFixtures('SKU-AUTO-002');

        $response = $this->postJson('/api/sales', [
            'customerId'    => $customer->id,
            'paymentMethod' => 'Cash',
            'items'         => [
                ['productId' => $product->id, 'quantity' => 1, 'discount' => 0],
            ],
        ], $this->auth());

        // Sale should still succeed
        $response->assertStatus(201);

        // No journal entry should exist
        $this->assertDatabaseMissing('journal_entries', [
            'company_id'     => $this->company->id,
            'reference_type' => 'sale_order',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function purchase_receive_auto_posts_journal_entry_when_mappings_configured(): void
    {
        $this->company->update(['industry' => 'Retail']);
        $this->seedMappedAccounts();

        // Create vendor and product directly (bypasses API category FK)
        $vendor = Party::create([
            'id'              => 'VEN-' . Str::random(9),
            'company_id'      => $this->company->id,
            'code'            => 'V-' . Str::random(4),
            'type'            => 'Vendor',
            'name'            => 'Test Vendor',
            'current_balance' => 0,
            'opening_balance' => 0,
            'credit_limit'    => 0,
        ]);
        [$product] = $this->seedSaleFixtures('SKU-PUR-001');

        // Create PO
        $poResponse = $this->postJson('/api/purchases', [
            'vendorId' => $vendor->id,
            'items'    => [['productId' => $product->id, 'quantity' => 10, 'unitCost' => 60.00]],
        ], $this->auth());
        $poId = $poResponse->json('id');

        // Receive the PO
        $this->putJson('/api/purchases/' . $poId . '/receive', [
            'items' => [['productId' => $product->id, 'quantity' => 10, 'unitCost' => 60.00]],
        ], $this->auth());

        $this->assertDatabaseHas('journal_entries', [
            'company_id'     => $this->company->id,
            'reference_type' => 'purchase_receive',
            'is_posted'      => true,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function payment_receipt_auto_posts_journal_entry_when_mappings_configured(): void
    {
        $this->company->update(['industry' => 'Retail']);
        $this->seedMappedAccounts();

        $customer = Party::create([
            'id'              => 'CUS-' . Str::random(9),
            'company_id'      => $this->company->id,
            'code'            => 'C-' . Str::random(4),
            'type'            => 'Customer',
            'name'            => 'Test Customer',
            'current_balance' => 5000,
            'opening_balance' => 0,
            'credit_limit'    => 0,
        ]);

        $this->postJson('/api/payments', [
            'partyId'       => $customer->id,
            'amount'        => 2000.00,
            'paymentMethod' => 'Cash',
            'type'          => 'Receipt',
        ], $this->auth());

        $this->assertDatabaseHas('journal_entries', [
            'company_id'     => $this->company->id,
            'reference_type' => 'payment',
            'is_posted'      => true,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function journal_entries_are_scoped_to_company(): void
    {
        $otherCompany = $this->createCompany(['name' => 'Other Co']);
        $otherUser    = $this->createAdminUser($otherCompany, ['username' => 'otheradmin']);
        $otherToken   = $this->loginAndGetToken($otherUser);

        app(DocumentSequenceService::class)->ensureSequencesExist($this->company->id);
        app(DocumentSequenceService::class)->ensureSequencesExist($otherCompany->id);

        $accounts = $this->seedMappedAccounts();

        // Create a journal entry for this company
        $this->postJson('/api/accounting/journals', [
            'date'            => now()->toDateString(),
            'description'     => 'Company A entry',
            'postImmediately' => true,
            'lines'           => [
                ['accountId' => $accounts['1001']->id, 'debit' => 1000, 'credit' => 0],
                ['accountId' => $accounts['4001']->id, 'debit' => 0,    'credit' => 1000],
            ],
        ], $this->auth());

        // Other company's journal list should be empty
        $response = $this->getJson('/api/accounting/journals', $this->auth($otherToken));

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }
}