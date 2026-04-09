<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    protected Company $company;
    protected User $adminUser;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company   = $this->createCompany();
        $this->adminUser = $this->createAdminUser($this->company);
        $this->token     = $this->loginAndGetToken($this->adminUser);
    }

    protected function createCompany(array $overrides = []): Company
    {
        return Company::create(array_merge([
            'id'                   => Str::uuid(),
            'name'                 => 'Test Company',
            'status'               => 'Active',
            'max_user_limit'       => 10,
            'registration_payment' => 0,
            'saas_plan'            => 'Monthly',
            'costing_method'       => 'moving_average',
        ], $overrides));
    }

    protected function createAdminUser(Company $company, array $overrides = []): User
    {
        return User::create(array_merge([
            'id'          => Str::uuid(),
            'username'    => 'testadmin',
            'name'        => 'Test Admin',
            'password'    => 'password123',
            'system_role' => 'Company Admin',
            'company_id'  => $company->id,
            'is_active'   => true,
            'api_token'   => null,
        ], $overrides));
    }

    protected function createSuperAdmin(): User
    {
        return User::create([
            'id'          => Str::uuid(),
            'username'    => 'superadmin',
            'name'        => 'Super Admin',
            'password'    => 'password123',
            'system_role' => 'Super Admin',
            'company_id'  => null,
            'is_active'   => true,
            'api_token'   => null,
        ]);
    }

    protected function loginAndGetToken(User $user): string
    {
        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'password123',
        ]);
        return $response->json('token');
    }

    protected function auth(string $token = null): array
    {
        return ['Authorization' => 'Bearer ' . ($token ?? $this->token)];
    }

    protected function createProduct(array $overrides = []): array
    {
        $response = $this->postJson('/api/products', array_merge([
            'id'          => Str::uuid(),
            'companyId'   => $this->company->id,
            'name'        => 'Test Product',
            'sku'         => 'SKU-001',
            'barcode'     => null,
            'categoryId'  => null,
            'uomId'       => null,
            'salePrice'   => 100.00,
            'costPrice'   => 60.00,
            'stock'       => 50,
            'minStock'    => 5,
            'isActive'    => true,
        ], $overrides), $this->auth());

        return $response->json();
    }

    protected function createParty(string $type = 'Customer', array $overrides = []): array
    {
        $response = $this->postJson('/api/parties', array_merge([
            'id'         => Str::uuid(),
            'companyId'  => $this->company->id,
            'name'       => 'Test ' . $type,
            'type'       => $type,
            'phone'      => null,
            'email'      => null,
            'address'    => null,
            'balance'    => 0,
        ], $overrides), $this->auth());

        return $response->json();
    }
}
