<?php

namespace Tests\Feature;

use Tests\Feature\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Carbon;

class SyncDateLimitTest extends ApiTestCase
{
    #[Test]
    public function transactions_default_to_last_six_months(): void
    {
        $company = $this->company;
        $token   = $this->token;

        // Old sale — 8 months ago (should be excluded by default)
        \App\Models\SaleOrder::forceCreate([
            'id'             => 'SO-OLD000001',
            'invoice_no'     => 'INV-OLD-001',
            'company_id'     => $company->id,
            'customer_id'    => null,
            'payment_method' => 'cash',
            'total_amount'   => 100,
            'is_returned'    => false,
            'return_status'  => 'none',
            'created_at'     => Carbon::now()->subMonths(8),
            'updated_at'     => Carbon::now()->subMonths(8),
        ]);

        // Recent sale — 1 month ago (should be included)
        \App\Models\SaleOrder::forceCreate([
            'id'             => 'SO-NEW000001',
            'invoice_no'     => 'INV-NEW-001',
            'company_id'     => $company->id,
            'customer_id'    => null,
            'payment_method' => 'cash',
            'total_amount'   => 200,
            'is_returned'    => false,
            'return_status'  => 'none',
            'created_at'     => Carbon::now()->subMonth(),
            'updated_at'     => Carbon::now()->subMonth(),
        ]);

        $response = $this->getJson('/api/sync/transactions', $this->auth($token));

        $response->assertOk();
        $ids = collect($response->json('sales'))->pluck('id')->all();
        $this->assertContains('INV-NEW-001', $ids);
        $this->assertNotContains('INV-OLD-001', $ids);
        $this->assertArrayHasKey('loadedFrom', $response->json());
    }

    #[Test]
    public function transactions_respect_explicit_from_param(): void
    {
        $company = $this->company;
        $token   = $this->token;

        \App\Models\SaleOrder::forceCreate([
            'id'             => 'SO-OLD000002',
            'invoice_no'     => 'INV-OLD-002',
            'company_id'     => $company->id,
            'customer_id'    => null,
            'payment_method' => 'cash',
            'total_amount'   => 100,
            'is_returned'    => false,
            'return_status'  => 'none',
            'created_at'     => Carbon::now()->subMonths(8),
            'updated_at'     => Carbon::now()->subMonths(8),
        ]);

        $from = Carbon::now()->subMonths(9)->toDateString();
        $response = $this->getJson('/api/sync/transactions?from=' . $from, $this->auth($token));

        $response->assertOk();
        $ids = collect($response->json('sales'))->pluck('id')->all();
        $this->assertContains('INV-OLD-002', $ids);
    }

    #[Test]
    public function invalid_date_param_is_silently_ignored(): void
    {
        $response = $this->getJson('/api/sync/transactions?from=not-a-date', $this->auth($this->token));

        $response->assertOk();
        $this->assertArrayHasKey('loadedFrom', $response->json());
    }
}
