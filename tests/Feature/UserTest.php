<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Str;

class UserTest extends ApiTestCase
{
    /** @test */
    public function admin_can_create_a_new_user(): void
    {
        $response = $this->postJson('/api/users', [
            'id'          => Str::uuid(),
            'companyId'   => $this->company->id,
            'username'    => 'newstaff',
            'name'        => 'New Staff',
            'password'    => 'staffpass1',
            'systemRole'  => 'Staff',
            'roleId'      => null,
            'isActive'    => true,
        ], $this->auth());

        $response->assertStatus(201)
                 ->assertJsonFragment(['username' => 'newstaff']);

        $this->assertDatabaseHas('users', ['username' => 'newstaff']);
    }

    /** @test */
    public function plain_text_password_is_not_stored(): void
    {
        $this->postJson('/api/users', [
            'id'          => Str::uuid(),
            'companyId'   => $this->company->id,
            'username'    => 'securestaffuser',
            'name'        => 'Secure Staff',
            'password'    => 'plainpass123',
            'systemRole'  => 'Staff',
            'roleId'      => null,
            'isActive'    => true,
        ], $this->auth());

        $user = User::where('username', 'securestaffuser')->first();
        $this->assertNotNull($user);
        // Hashed password must not equal the plain text
        $this->assertNotEquals('plainpass123', $user->password);
        // The column 'password_plain' must not exist
        $this->assertFalse(
            in_array('password_plain', array_keys($user->getAttributes())),
            'password_plain column should not exist'
        );
    }

    /** @test */
    public function admin_can_update_user_details(): void
    {
        $user = User::create([
            'id'          => Str::uuid(),
            'username'    => 'editme',
            'name'        => 'Old Name',
            'password'    => 'pass123',
            'system_role' => 'Staff',
            'company_id'  => $this->company->id,
            'is_active'   => true,
        ]);

        $response = $this->putJson('/api/users/' . $user->id, [
            'name'       => 'New Name',
            'username'   => 'editme',
            'systemRole' => 'Staff',
            'roleId'     => null,
        ], $this->auth());

        $response->assertOk();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    /** @test */
    public function admin_can_change_user_password(): void
    {
        $user = User::create([
            'id'          => Str::uuid(),
            'username'    => 'passchange',
            'name'        => 'Pass Change',
            'password'    => 'oldpass123',
            'system_role' => 'Staff',
            'company_id'  => $this->company->id,
            'is_active'   => true,
        ]);

        $response = $this->putJson('/api/users/' . $user->id . '/password', [
            'password' => 'newpass456',
        ], $this->auth());

        $response->assertOk();

        // New password should work for login
        $loginResponse = $this->postJson('/api/login', [
            'username' => 'passchange',
            'password' => 'newpass456',
        ]);
        $loginResponse->assertOk()->assertJsonStructure(['token']);
    }

    /** @test */
    public function admin_can_deactivate_a_user(): void
    {
        $user = User::create([
            'id'          => Str::uuid(),
            'username'    => 'deactivateme',
            'name'        => 'Deactivate Me',
            'password'    => 'pass123',
            'system_role' => 'Staff',
            'company_id'  => $this->company->id,
            'is_active'   => true,
        ]);

        $response = $this->putJson('/api/users/' . $user->id . '/status', [
            'isActive' => false,
        ], $this->auth());

        $response->assertOk();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
    }

    /** @test */
    public function duplicate_username_is_rejected(): void
    {
        $response = $this->postJson('/api/users', [
            'id'          => Str::uuid(),
            'companyId'   => $this->company->id,
            'username'    => $this->adminUser->username, // already exists
            'name'        => 'Duplicate',
            'password'    => 'somepass1',
            'systemRole'  => 'Staff',
            'roleId'      => null,
            'isActive'    => true,
        ], $this->auth());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['username']);
    }
}
