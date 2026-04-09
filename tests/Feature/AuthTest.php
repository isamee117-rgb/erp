<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthTest extends TestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'id'                   => Str::uuid(),
            'name'                 => 'Auth Test Company',
            'status'               => 'Active',
            'max_user_limit'       => 5,
            'registration_payment' => 0,
            'saas_plan'            => 'Monthly',
            'costing_method'       => 'moving_average',
        ]);
    }

    /** @test */
    public function login_returns_token_for_valid_credentials(): void
    {
        User::create([
            'id'          => Str::uuid(),
            'username'    => 'johndoe',
            'name'        => 'John Doe',
            'password'    => 'secret123',
            'system_role' => 'Company Admin',
            'company_id'  => $this->company->id,
            'is_active'   => true,
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'johndoe',
            'password' => 'secret123',
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['token', 'user']);
    }

    /** @test */
    public function login_fails_with_wrong_password(): void
    {
        User::create([
            'id'          => Str::uuid(),
            'username'    => 'johndoe2',
            'name'        => 'John Doe',
            'password'    => 'secret123',
            'system_role' => 'Company Admin',
            'company_id'  => $this->company->id,
            'is_active'   => true,
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'johndoe2',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['error' => 'Invalid credentials']);
    }

    /** @test */
    public function login_fails_for_inactive_user(): void
    {
        User::create([
            'id'          => Str::uuid(),
            'username'    => 'inactiveuser',
            'name'        => 'Inactive',
            'password'    => 'secret123',
            'system_role' => 'Company Admin',
            'company_id'  => $this->company->id,
            'is_active'   => false,
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'inactiveuser',
            'password' => 'secret123',
        ]);

        $response->assertStatus(401);
        $this->assertStringContainsString('deactivated', $response->json('error'));
    }

    /** @test */
    public function login_fails_for_suspended_company(): void
    {
        $suspendedCompany = Company::create([
            'id'                   => Str::uuid(),
            'name'                 => 'Suspended Co',
            'status'               => 'Suspended',
            'max_user_limit'       => 5,
            'registration_payment' => 0,
            'saas_plan'            => 'Monthly',
            'costing_method'       => 'moving_average',
        ]);

        User::create([
            'id'          => Str::uuid(),
            'username'    => 'suspendeduser',
            'name'        => 'Suspended User',
            'password'    => 'secret123',
            'system_role' => 'Company Admin',
            'company_id'  => $suspendedCompany->id,
            'is_active'   => true,
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'suspendeduser',
            'password' => 'secret123',
        ]);

        $response->assertStatus(401);
        $this->assertStringContainsString('suspended', $response->json('error'));
    }

    /** @test */
    public function sync_core_returns_data_for_authenticated_user(): void
    {
        $user = User::create([
            'id'          => Str::uuid(),
            'username'    => 'syncuser',
            'name'        => 'Sync User',
            'password'    => 'secret123',
            'system_role' => 'Company Admin',
            'company_id'  => $this->company->id,
            'is_active'   => true,
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'username' => 'syncuser',
            'password' => 'secret123',
        ]);
        $token = $loginResponse->json('token');

        $response = $this->getJson('/api/sync/core', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['companies', 'users', 'customRoles']);
    }

    /** @test */
    public function unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/sync/core');
        $response->assertStatus(401);
    }

    /** @test */
    public function login_requires_username_and_password(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['username', 'password']);
    }
}
