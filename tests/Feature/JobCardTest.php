<?php

namespace Tests\Feature;

use App\Models\JobCard;
use App\Models\Party;
use App\Models\Product;
use App\Services\DocumentSequenceService;
use PHPUnit\Framework\Attributes\Test;

class JobCardTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app(DocumentSequenceService::class)->ensureSequencesExist($this->company->id);
    }

    #[Test]
    public function can_create_a_job_card(): void
    {
        $response = $this->postJson('/api/job-cards', [
            'customerName'     => 'Ahmed Ali',
            'vehicleRegNumber' => 'ABC-123',
        ], $this->auth());

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'jobCardNo', 'status', 'vehicleRegNumber']);

        $this->assertDatabaseHas('job_cards', [
            'company_id'         => $this->company->id,
            'status'             => 'open',
            'vehicle_reg_number' => 'ABC-123',
        ]);
    }
}
