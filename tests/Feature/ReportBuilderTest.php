<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\ReportLineMapping;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

class ReportBuilderTest extends ApiTestCase
{
    private function makeAccount(string $type, string $code = '4100'): ChartOfAccount
    {
        return ChartOfAccount::create([
            'id'         => 'COA-' . Str::random(9),
            'company_id' => $this->company->id,
            'code'       => $code,
            'name'       => $code . ' Account',
            'type'       => $type,
            'sub_type'   => null,
            'is_system'  => false,
            'is_active'  => true,
        ]);
    }

    #[Test]
    public function get_profit_loss_config_returns_three_lines_with_empty_accounts(): void
    {
        $response = $this->getJson('/api/report-builder/profit_loss', $this->auth());

        $response->assertOk()
                 ->assertJsonPath('reportType', 'profit_loss')
                 ->assertJsonCount(3, 'lines');

        $keys = collect($response->json('lines'))->pluck('lineKey')->toArray();
        $this->assertEqualsCanonicalizing(
            ['sales_revenue', 'cogs', 'operating_expenses'],
            $keys
        );
        // Each line starts with empty accounts
        foreach ($response->json('lines') as $line) {
            $this->assertIsArray($line['accounts']);
            $this->assertEmpty($line['accounts']);
        }
    }

    #[Test]
    public function get_balance_sheet_config_returns_six_lines(): void
    {
        $response = $this->getJson('/api/report-builder/balance_sheet', $this->auth());

        $response->assertOk()
                 ->assertJsonCount(6, 'lines');

        $keys = collect($response->json('lines'))->pluck('lineKey')->toArray();
        $this->assertEqualsCanonicalizing(
            ['current_assets', 'fixed_assets', 'other_assets',
             'current_liabilities', 'long_term_liabilities', 'owners_equity'],
            $keys
        );
    }

    #[Test]
    public function invalid_report_type_returns_422(): void
    {
        $this->getJson('/api/report-builder/invalid_type', $this->auth())
             ->assertStatus(422);
    }

    #[Test]
    public function super_admin_cannot_access_report_builder(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $token      = $this->loginAndGetToken($superAdmin);

        $this->getJson('/api/report-builder/profit_loss', $this->auth($token))
             ->assertStatus(403);
    }

    #[Test]
    public function save_and_retrieve_profit_loss_mappings(): void
    {
        $revenue = $this->makeAccount('Revenue', '4100');
        $cogs    = $this->makeAccount('Expense', '5000');

        $response = $this->putJson('/api/report-builder/profit_loss', [
            'mappings' => [
                'sales_revenue'      => [$revenue->id],
                'cogs'               => [$cogs->id],
                'operating_expenses' => [],
            ],
        ], $this->auth());

        $response->assertOk();

        $salesLine = collect($response->json('lines'))->firstWhere('lineKey', 'sales_revenue');
        $this->assertCount(1, $salesLine['accounts']);
        $this->assertEquals($revenue->id, $salesLine['accounts'][0]['id']);
        $this->assertEquals('4100', $salesLine['accounts'][0]['code']);

        $this->assertDatabaseHas('report_line_mappings', [
            'company_id'  => $this->company->id,
            'report_type' => 'profit_loss',
            'line_key'    => 'sales_revenue',
            'account_id'  => $revenue->id,
        ]);
    }

    #[Test]
    public function saving_replaces_previous_mappings(): void
    {
        $acc1 = $this->makeAccount('Revenue', '4100');
        $acc2 = $this->makeAccount('Revenue', '4200');

        $this->putJson('/api/report-builder/profit_loss', [
            'mappings' => [
                'sales_revenue'      => [$acc1->id],
                'cogs'               => [],
                'operating_expenses' => [],
            ],
        ], $this->auth());

        // Replace acc1 with acc2
        $this->putJson('/api/report-builder/profit_loss', [
            'mappings' => [
                'sales_revenue'      => [$acc2->id],
                'cogs'               => [],
                'operating_expenses' => [],
            ],
        ], $this->auth());

        $this->assertDatabaseMissing('report_line_mappings', ['account_id' => $acc1->id]);
        $this->assertDatabaseHas('report_line_mappings', ['account_id' => $acc2->id]);
    }

    #[Test]
    public function cannot_map_same_account_to_two_lines(): void
    {
        $acc = $this->makeAccount('Revenue', '4100');

        $this->putJson('/api/report-builder/profit_loss', [
            'mappings' => [
                'sales_revenue'      => [$acc->id],
                'operating_expenses' => [$acc->id],
                'cogs'               => [],
            ],
        ], $this->auth())->assertStatus(422);
    }

    #[Test]
    public function cannot_map_account_from_other_company(): void
    {
        $otherCompany  = $this->createCompany(['name' => 'Other Co']);
        $otherAccount  = ChartOfAccount::create([
            'id'         => 'COA-' . Str::random(9),
            'company_id' => $otherCompany->id,
            'code'       => '9999',
            'name'       => 'Foreign Account',
            'type'       => 'Revenue',
            'sub_type'   => null,
            'is_system'  => false,
            'is_active'  => true,
        ]);

        $this->putJson('/api/report-builder/profit_loss', [
            'mappings' => [
                'sales_revenue'      => [$otherAccount->id],
                'cogs'               => [],
                'operating_expenses' => [],
            ],
        ], $this->auth())->assertStatus(422);
    }
}
