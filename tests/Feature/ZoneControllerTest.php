<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Zone;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ZoneControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::create(['name' => 'Test Org']);
        $this->user = User::factory()->create();
    }

    private function auth(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function makeZone(array $attrs = []): Zone
    {
        return Zone::create(array_merge([
            'organization_id' => $this->organization->id,
            'name'            => 'Test Zone ' . uniqid(),
            'type'            => 'pedestrian',
            'enabled'         => true,
        ], $attrs));
    }

    // -------------------------------------------------------------------------
    // Auth guard
    // -------------------------------------------------------------------------

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/zones')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_returns_all_zones(): void
    {
        $this->makeZone(['name' => 'Lobby']);
        $this->makeZone(['name' => 'Parking']);

        $this->auth()
            ->getJson('/api/zones')
            ->assertOk()
            ->assertJsonCount(2, 'zones')
            ->assertJsonStructure(['zones' => [['id', 'organization_id', 'name', 'type', 'enabled']]]);
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_returns_zone(): void
    {
        $zone = $this->makeZone([
            'name'        => 'Server Room',
            'description' => 'Restricted area',
            'type'        => 'vehicular',
        ]);

        $this->auth()
            ->getJson("/api/zones/{$zone->id}")
            ->assertOk()
            ->assertJsonPath('zone.id', $zone->id)
            ->assertJsonPath('zone.name', 'Server Room')
            ->assertJsonPath('zone.description', 'Restricted area')
            ->assertJsonPath('zone.type', 'vehicular')
            ->assertJsonPath('zone.enabled', true);
    }

    public function test_show_returns_404_for_nonexistent_zone(): void
    {
        $this->auth()
            ->getJson('/api/zones/9999')
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_zone(): void
    {
        $this->auth()
            ->postJson('/api/zones', [
                'organization_id' => $this->organization->id,
                'name'            => 'Main Entrance',
                'description'     => 'Front door',
                'type'            => 'mixed',
                'enabled'         => true,
            ])
            ->assertCreated()
            ->assertJsonPath('zone.name', 'Main Entrance')
            ->assertJsonPath('zone.type', 'mixed')
            ->assertJsonPath('zone.description', 'Front door');

        $this->assertDatabaseHas('zones', ['name' => 'Main Entrance']);
    }

    public function test_store_requires_name(): void
    {
        $this->auth()
            ->postJson('/api/zones', ['organization_id' => $this->organization->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_requires_organization_id(): void
    {
        $this->auth()
            ->postJson('/api/zones', ['name' => 'Orphan Zone'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['organization_id']);
    }

    public function test_store_rejects_invalid_type(): void
    {
        $this->auth()
            ->postJson('/api/zones', [
                'organization_id' => $this->organization->id,
                'name'            => 'Rooftop',
                'type'            => 'rooftop',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_store_defaults_type_to_pedestrian(): void
    {
        $this->auth()
            ->postJson('/api/zones', [
                'organization_id' => $this->organization->id,
                'name'            => 'Garden',
            ])
            ->assertCreated()
            ->assertJsonPath('zone.type', 'pedestrian');
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_patches_zone_fields(): void
    {
        $zone = $this->makeZone(['name' => 'Old', 'type' => 'pedestrian']);

        $this->auth()
            ->patchJson("/api/zones/{$zone->id}", [
                'name' => 'New',
                'type' => 'vehicular',
            ])
            ->assertOk()
            ->assertJsonPath('zone.name', 'New')
            ->assertJsonPath('zone.type', 'vehicular');
    }

    public function test_update_ignores_absent_fields(): void
    {
        $zone = $this->makeZone([
            'name'    => 'Stable',
            'type'    => 'mixed',
            'enabled' => true,
        ]);

        $this->auth()
            ->patchJson("/api/zones/{$zone->id}", ['name' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('zone.type', 'mixed')
            ->assertJsonPath('zone.enabled', true);
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_soft_deletes_zone(): void
    {
        $zone = $this->makeZone();

        $this->auth()
            ->deleteJson("/api/zones/{$zone->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Zone deleted');

        $this->auth()
            ->getJson('/api/zones')
            ->assertOk()
            ->assertJsonCount(0, 'zones');

        $this->assertSoftDeleted('zones', ['id' => $zone->id]);
    }
}
