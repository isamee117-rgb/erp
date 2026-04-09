<?php

namespace Tests\Feature;

use App\Models\Party;
use App\Models\Payment;
use Illuminate\Support\Str;

class PaymentTest extends ApiTestCase
{
    private array $customer;
    private array $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = $this->createParty('Customer', ['name' => 'Ali Raza']);
        $this->vendor   = $this->createParty('Vendor',   ['name' => 'XYZ Traders']);
    }

    /** @test */
    public function can_record_a_receipt_from_customer(): void
    {
        $response = $this->postJson('/api/payments', [
            'partyId'       => $this->customer['id'],
            'amount'        => 5000.00,
            'paymentMethod' => 'Cash',
            'type'          => 'Receipt',
            'referenceNo'   => 'RCP-001',
            'notes'         => 'Monthly payment',
        ], $this->auth());

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'amount', 'type']);

        $this->assertDatabaseHas('payments', [
            'party_id' => $this->customer['id'],
            'type'     => 'Receipt',
        ]);
    }

    /** @test */
    public function receipt_decreases_customer_balance(): void
    {
        // First set a positive balance on the customer
        $party = Party::find($this->customer['id']);
        $party->current_balance = 10000;
        $party->save();

        $this->postJson('/api/payments', [
            'partyId'       => $this->customer['id'],
            'amount'        => 3000.00,
            'paymentMethod' => 'Cash',
            'type'          => 'Receipt',
        ], $this->auth());

        $partyAfter = Party::find($this->customer['id']);
        $this->assertEquals(7000.0, (float) $partyAfter->current_balance);
    }

    /** @test */
    public function can_record_a_payment_to_vendor(): void
    {
        $response = $this->postJson('/api/payments', [
            'partyId'       => $this->vendor['id'],
            'amount'        => 15000.00,
            'paymentMethod' => 'Bank Transfer',
            'type'          => 'Payment',
            'referenceNo'   => 'PAY-V-001',
            'notes'         => 'Invoice settlement',
        ], $this->auth());

        $response->assertStatus(201)
                 ->assertJsonFragment(['type' => 'Payment']);
    }

    /** @test */
    public function payment_to_vendor_decreases_vendor_balance(): void
    {
        $party = Party::find($this->vendor['id']);
        $party->current_balance = 8000;
        $party->save();

        $this->postJson('/api/payments', [
            'partyId'       => $this->vendor['id'],
            'amount'        => 2000.00,
            'paymentMethod' => 'Cash',
            'type'          => 'Payment',
        ], $this->auth());

        $partyAfter = Party::find($this->vendor['id']);
        $this->assertEquals(6000.0, (float) $partyAfter->current_balance);
    }

    /** @test */
    public function can_delete_a_payment_and_balance_is_reversed(): void
    {
        $party = Party::find($this->customer['id']);
        $party->current_balance = 10000;
        $party->save();

        $payResponse = $this->postJson('/api/payments', [
            'partyId'       => $this->customer['id'],
            'amount'        => 4000.00,
            'paymentMethod' => 'Cash',
            'type'          => 'Receipt',
        ], $this->auth());

        $paymentId     = $payResponse->json('id');
        $balanceAfterPay = (float) Party::find($this->customer['id'])->current_balance;
        $this->assertEquals(6000.0, $balanceAfterPay);

        // Delete the payment — balance should go back to 10000
        $deleteResponse = $this->deleteJson('/api/payments/' . $paymentId, [], $this->auth());
        $deleteResponse->assertOk();

        $balanceAfterDelete = (float) Party::find($this->customer['id'])->current_balance;
        $this->assertEquals(10000.0, $balanceAfterDelete);

        $this->assertDatabaseMissing('payments', ['id' => $paymentId]);
    }

    /** @test */
    public function payment_requires_amount_greater_than_zero(): void
    {
        $response = $this->postJson('/api/payments', [
            'partyId'       => $this->customer['id'],
            'amount'        => 0,
            'paymentMethod' => 'Cash',
            'type'          => 'Receipt',
        ], $this->auth());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function payment_requires_valid_party(): void
    {
        $response = $this->postJson('/api/payments', [
            'partyId'       => 'nonexistent-party-id',
            'amount'        => 1000.00,
            'paymentMethod' => 'Cash',
            'type'          => 'Receipt',
        ], $this->auth());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['partyId']);
    }
}
