<?php

namespace Tests\Feature;

use App\Models\Party;
use Illuminate\Support\Str;

class PartyTest extends ApiTestCase
{
    /** @test */
    public function can_create_a_customer(): void
    {
        $response = $this->postJson('/api/parties', [
            'id'        => Str::uuid(),
            'companyId' => $this->company->id,
            'name'      => 'Ahmed Khan',
            'type'      => 'Customer',
            'phone'     => '03001234567',
            'email'     => null,
            'address'   => 'Karachi',
            'balance'   => 0,
        ], $this->auth());

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Ahmed Khan', 'type' => 'Customer']);

        $this->assertDatabaseHas('parties', ['name' => 'Ahmed Khan', 'type' => 'Customer']);
    }

    /** @test */
    public function can_create_a_vendor(): void
    {
        $response = $this->postJson('/api/parties', [
            'id'        => Str::uuid(),
            'companyId' => $this->company->id,
            'name'      => 'ABC Suppliers',
            'type'      => 'Vendor',
            'phone'     => null,
            'email'     => 'abc@suppliers.com',
            'address'   => null,
            'balance'   => 0,
        ], $this->auth());

        $response->assertStatus(201)
                 ->assertJsonFragment(['type' => 'Vendor']);
    }

    /** @test */
    public function invalid_party_type_is_rejected(): void
    {
        $response = $this->postJson('/api/parties', [
            'id'        => Str::uuid(),
            'companyId' => $this->company->id,
            'name'      => 'Bad Party',
            'type'      => 'Employee', // invalid
        ], $this->auth());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function can_update_a_party(): void
    {
        $party = Party::create([
            'id'         => Str::uuid(),
            'company_id' => $this->company->id,
            'name'       => 'Old Name',
            'type'       => 'Customer',
            'balance'    => 0,
        ]);

        $response = $this->putJson('/api/parties/' . $party->id, [
            'name'    => 'New Name',
            'type'    => 'Customer',
            'phone'   => '0300-0000000',
            'email'   => null,
            'address' => null,
        ], $this->auth());

        $response->assertOk();
        $this->assertDatabaseHas('parties', ['id' => $party->id, 'name' => 'New Name']);
    }

    /** @test */
    public function can_delete_a_party_with_no_transactions(): void
    {
        $party = Party::create([
            'id'         => Str::uuid(),
            'company_id' => $this->company->id,
            'name'       => 'Delete Me',
            'type'       => 'Customer',
            'balance'    => 0,
        ]);

        $response = $this->deleteJson('/api/parties/' . $party->id, [], $this->auth());

        $response->assertOk();
        $this->assertDatabaseMissing('parties', ['id' => $party->id]);
    }

    /** @test */
    public function name_is_required_for_party(): void
    {
        $response = $this->postJson('/api/parties', [
            'id'        => Str::uuid(),
            'companyId' => $this->company->id,
            'type'      => 'Customer',
        ], $this->auth());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }
}
