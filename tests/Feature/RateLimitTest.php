<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;

class RateLimitTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Flush entire file cache so login throttle and rate limit counters
        // from previous tests don't bleed into this one.
        Cache::flush();
    }

    #[Test]
    public function sync_transactions_is_blocked_after_ten_requests(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/api/sync/transactions', $this->auth())
                 ->assertStatus(200);
        }

        $this->getJson('/api/sync/transactions', $this->auth())
             ->assertStatus(429)
             ->assertJson(['error' => 'Too many requests. Please slow down.']);
    }

    #[Test]
    public function sync_core_is_blocked_after_thirty_requests(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->getJson('/api/sync/core', $this->auth())
                 ->assertStatus(200);
        }

        $this->getJson('/api/sync/core', $this->auth())
             ->assertStatus(429)
             ->assertJson(['error' => 'Too many requests. Please slow down.']);
    }

    #[Test]
    public function mutations_are_blocked_after_sixty_requests(): void
    {
        // PUT /api/settings/currency is idempotent — safe to call 60+ times
        for ($i = 0; $i < 60; $i++) {
            $this->putJson('/api/settings/currency', ['currency' => 'Rs.'], $this->auth())
                 ->assertStatus(200);
        }

        $this->putJson('/api/settings/currency', ['currency' => 'Rs.'], $this->auth())
             ->assertStatus(429)
             ->assertJson(['error' => 'Too many requests. Please slow down.']);
    }

    #[Test]
    public function reads_are_blocked_after_one_hundred_twenty_requests(): void
    {
        for ($i = 0; $i < 120; $i++) {
            $this->getJson('/api/settings/document-sequences', $this->auth())
                 ->assertStatus(200);
        }

        $this->getJson('/api/settings/document-sequences', $this->auth())
             ->assertStatus(429)
             ->assertJson(['error' => 'Too many requests. Please slow down.']);
    }

    #[Test]
    public function different_users_have_independent_rate_limit_counters(): void
    {
        $company2 = $this->createCompany(['name' => 'Company Two']);
        $user2    = $this->createAdminUser($company2, ['username' => 'testadmin2']);
        $token2   = $this->loginAndGetToken($user2);
        $auth2    = ['Authorization' => 'Bearer ' . $token2];

        RateLimiter::clear('sync-heavy:' . $user2->id);

        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/api/sync/transactions', $this->auth())->assertStatus(200);
        }
        $this->getJson('/api/sync/transactions', $this->auth())->assertStatus(429);

        // user2 is unaffected — different user ID = different rate limit bucket
        $this->getJson('/api/sync/transactions', $auth2)->assertStatus(200);
    }
}
