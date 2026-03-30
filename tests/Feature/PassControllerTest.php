<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Pass;
use App\Models\User;
use App\Models\Zone;
use App\Models\Visitor;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PassControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Organization $organization;
    private Visitor $visitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::create(['name' => 'Test Org']);
        $this->user = User::factory()->create();
        $this->visitor = Visitor::create([
            'organization_id' => $this->organization->id,
            'name'            => 'Test',
            'first_last_name' => 'Visitor',
            'email'           => 'visitor@test.com',
            'enabled'         => true,
        ]);
    }

    private function auth(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function makePass(array $attrs = []): Pass
    {
        return Pass::create(array_merge([
            'visitor_id'  => $this->visitor->id,
            'created_by'  => $this->user->id,
            'valid_from'  => now()->addDay(),
            'valid_until' => now()->addDays(7),
            'status'      => 'active',
        ], $attrs));
    }

    private function makeZone(array $attrs = []): Zone
    {
        return Zone::create(array_merge([
            'organization_id' => $this->organization->id,
            'name'            => 'Zone ' . uniqid(),
            'type'            => 'pedestrian',
        ], $attrs));
    }

    // -------------------------------------------------------------------------
    // Auth guard
    // -------------------------------------------------------------------------

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/passes')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_returns_all_passes_with_visitor_and_zones(): void
    {
        $zone  = $this->makeZone();
        $passA = $this->makePass();
        $passB = $this->makePass();
        $passA->zones()->attach($zone);

        $this->auth()
            ->getJson('/api/passes')
            ->assertOk()
            ->assertJsonCount(2, 'passes')
            ->assertJsonStructure(['passes' => [['id', 'visitorId', 'visitor', 'validFrom', 'validUntil', 'status', 'zones']]]);
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_returns_pass_with_visitor_and_zones(): void
    {
        $pass  = $this->makePass();
        $zoneA = $this->makeZone();
        $zoneB = $this->makeZone();
        $pass->zones()->attach([$zoneA->id, $zoneB->id]);

        $this->auth()
            ->getJson("/api/passes/{$pass->id}")
            ->assertOk()
            ->assertJsonPath('pass.id', $pass->id)
            ->assertJsonPath('pass.visitorId', $this->visitor->id)
            ->assertJsonCount(2, 'pass.zones');
    }

    public function test_show_returns_404_for_nonexistent_pass(): void
    {
        $this->auth()
            ->getJson('/api/passes/0')
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_pass(): void
    {
        $this->auth()
            ->postJson('/api/passes', [
                'visitor_id'  => $this->visitor->id,
                'valid_from'  => now()->addDay()->toDateTimeString(),
                'valid_until' => now()->addDays(7)->toDateTimeString(),
                'status'      => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('pass.visitorId', $this->visitor->id)
            ->assertJsonPath('pass.status', 'active')
            ->assertJsonPath('pass.createdBy', $this->user->id);

        $this->assertDatabaseHas('passes', ['visitor_id' => $this->visitor->id]);
    }

    public function test_store_sets_created_by_from_authenticated_user(): void
    {
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser, 'sanctum')
            ->postJson('/api/passes', [
                'visitor_id'  => $this->visitor->id,
                'valid_from'  => now()->addDay()->toDateTimeString(),
                'valid_until' => now()->addDays(7)->toDateTimeString(),
            ])
            ->assertCreated()
            ->assertJsonPath('pass.createdBy', $otherUser->id);
    }

    public function test_store_with_zone_ids_syncs_zones(): void
    {
        $zoneA = $this->makeZone();
        $zoneB = $this->makeZone();

        $this->auth()
            ->postJson('/api/passes', [
                'visitor_id'  => $this->visitor->id,
                'valid_from'  => now()->addDay()->toDateTimeString(),
                'valid_until' => now()->addDays(7)->toDateTimeString(),
                'zone_ids'    => [$zoneA->id, $zoneB->id],
            ])
            ->assertCreated()
            ->assertJsonCount(2, 'pass.zones');
    }

    public function test_store_requires_visitor_id(): void
    {
        $this->auth()
            ->postJson('/api/passes', [
                'valid_from'  => now()->addDay()->toDateTimeString(),
                'valid_until' => now()->addDays(7)->toDateTimeString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['visitor_id']);
    }

    public function test_store_requires_valid_from(): void
    {
        $this->auth()
            ->postJson('/api/passes', [
                'visitor_id'  => $this->visitor->id,
                'valid_until' => now()->addDays(7)->toDateTimeString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['valid_from']);
    }

    public function test_store_requires_valid_until(): void
    {
        $this->auth()
            ->postJson('/api/passes', [
                'visitor_id' => $this->visitor->id,
                'valid_from' => now()->addDay()->toDateTimeString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['valid_until']);
    }

    public function test_store_requires_valid_until_after_valid_from(): void
    {
        $this->auth()
            ->postJson('/api/passes', [
                'visitor_id'  => $this->visitor->id,
                'valid_from'  => now()->addDays(7)->toDateTimeString(),
                'valid_until' => now()->addDay()->toDateTimeString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['valid_until']);
    }

    public function test_store_rejects_invalid_status(): void
    {
        $this->auth()
            ->postJson('/api/passes', [
                'visitor_id'  => $this->visitor->id,
                'valid_from'  => now()->addDay()->toDateTimeString(),
                'valid_until' => now()->addDays(7)->toDateTimeString(),
                'status'      => 'pending',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_store_rejects_nonexistent_visitor(): void
    {
        $this->auth()
            ->postJson('/api/passes', [
                'visitor_id'  => 0,
                'valid_from'  => now()->addDay()->toDateTimeString(),
                'valid_until' => now()->addDays(7)->toDateTimeString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['visitor_id']);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_patches_pass_fields(): void
    {
        $pass = $this->makePass(['status' => 'active']);

        $this->auth()
            ->patchJson("/api/passes/{$pass->id}", ['status' => 'revoked'])
            ->assertOk()
            ->assertJsonPath('pass.status', 'revoked');
    }

    public function test_update_with_zone_ids_replaces_zones(): void
    {
        $zoneA = $this->makeZone();
        $zoneB = $this->makeZone();
        $pass  = $this->makePass();
        $pass->zones()->attach($zoneA);

        $response = $this->auth()
            ->patchJson("/api/passes/{$pass->id}", ['zone_ids' => [$zoneB->id]])
            ->assertOk();

        $zones = $response->json('pass.zones');
        $this->assertCount(1, $zones);
        $this->assertEquals($zoneB->id, $zones[0]['id']);
    }

    public function test_update_with_empty_zone_ids_detaches_all_zones(): void
    {
        $zone = $this->makeZone();
        $pass = $this->makePass();
        $pass->zones()->attach($zone);

        $this->auth()
            ->patchJson("/api/passes/{$pass->id}", ['zone_ids' => []])
            ->assertOk()
            ->assertJsonCount(0, 'pass.zones');
    }

    public function test_update_omitting_zone_ids_leaves_zones_unchanged(): void
    {
        $zone = $this->makeZone();
        $pass = $this->makePass();
        $pass->zones()->attach($zone);

        $this->auth()
            ->patchJson("/api/passes/{$pass->id}", ['status' => 'expired'])
            ->assertOk()
            ->assertJsonCount(1, 'pass.zones');
    }

    public function test_update_rejects_invalid_status(): void
    {
        $pass = $this->makePass();

        $this->auth()
            ->patchJson("/api/passes/{$pass->id}", ['status' => 'unknown'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_soft_deletes_pass(): void
    {
        $pass = $this->makePass();

        $this->auth()
            ->deleteJson("/api/passes/{$pass->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Pass deleted');

        $this->auth()
            ->getJson('/api/passes')
            ->assertOk()
            ->assertJsonCount(0, 'passes');

        $this->assertSoftDeleted('passes', ['id' => $pass->id]);
    }
}
