<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Role;
use App\Models\User;
use App\Models\Zone;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleControllerTest extends TestCase
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

    private function makeRole(array $attrs = []): Role
    {
        return Role::create(array_merge([
            'organization_id' => $this->organization->id,
            'name'            => 'Test Role ' . uniqid(),
            'enabled'         => true,
        ], $attrs));
    }

    private function makeZone(array $attrs = []): Zone
    {
        return Zone::create(array_merge([
            'organization_id' => $this->organization->id,
            'name'            => 'Test Zone ' . uniqid(),
            'type'            => 'pedestrian',
        ], $attrs));
    }

    // -------------------------------------------------------------------------
    // Auth guard
    // -------------------------------------------------------------------------

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/roles')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_returns_all_roles_with_zones(): void
    {
        $zone  = $this->makeZone();
        $roleA = $this->makeRole(['name' => 'Alpha']);
        $roleB = $this->makeRole(['name' => 'Beta']);
        $roleA->zones()->attach($zone);

        $this->auth()
            ->getJson('/api/roles')
            ->assertOk()
            ->assertJsonCount(2, 'roles')
            ->assertJsonStructure(['roles' => [['id', 'name', 'zones']]]);
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_returns_role_with_zones(): void
    {
        $role  = $this->makeRole();
        $zoneA = $this->makeZone(['name' => 'Zone A']);
        $zoneB = $this->makeZone(['name' => 'Zone B']);
        $role->zones()->attach([$zoneA->id, $zoneB->id]);

        $this->auth()
            ->getJson("/api/roles/{$role->id}")
            ->assertOk()
            ->assertJsonPath('role.id', $role->id)
            ->assertJsonCount(2, 'role.zones');
    }

    public function test_show_returns_404_for_nonexistent_role(): void
    {
        $this->auth()
            ->getJson('/api/roles/0')
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_role(): void
    {
        $this->auth()
            ->postJson('/api/roles', [
                'organization_id' => $this->organization->id,
                'name'            => 'Managers',
                'description'     => 'Management team',
                'enabled'         => true,
            ])
            ->assertCreated()
            ->assertJsonPath('role.name', 'Managers')
            ->assertJsonPath('role.description', 'Management team')
            ->assertJsonPath('role.enabled', true);

        $this->assertDatabaseHas('roles', ['name' => 'Managers']);
    }

    public function test_store_with_zone_ids_syncs_zones(): void
    {
        $zoneA = $this->makeZone();
        $zoneB = $this->makeZone();

        $response = $this->auth()
            ->postJson('/api/roles', [
                'name'     => 'Operators',
                'zone_ids' => [$zoneA->id, $zoneB->id],
            ])
            ->assertCreated();

        $this->assertCount(2, $response->json('role.zones'));
    }

    public function test_store_requires_name(): void
    {
        $this->auth()
            ->postJson('/api/roles', ['description' => 'No name'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_rejects_duplicate_name(): void
    {
        $this->makeRole(['name' => 'Unique']);

        $this->auth()
            ->postJson('/api/roles', ['name' => 'Unique'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_patches_role_fields(): void
    {
        $role = $this->makeRole(['name' => 'Old Name', 'description' => 'Old']);

        $this->auth()
            ->patchJson("/api/roles/{$role->id}", [
                'name'        => 'New Name',
                'description' => 'New',
            ])
            ->assertOk()
            ->assertJsonPath('role.name', 'New Name')
            ->assertJsonPath('role.description', 'New');
    }

    public function test_update_with_zone_ids_replaces_zones(): void
    {
        $zoneA = $this->makeZone();
        $zoneB = $this->makeZone();
        $role  = $this->makeRole();
        $role->zones()->attach($zoneA);

        $response = $this->auth()
            ->patchJson("/api/roles/{$role->id}", ['zone_ids' => [$zoneB->id]])
            ->assertOk();

        $zones = $response->json('role.zones');
        $this->assertCount(1, $zones);
        $this->assertEquals($zoneB->id, $zones[0]['id']);
    }

    public function test_update_with_empty_zone_ids_detaches_all_zones(): void
    {
        $zone = $this->makeZone();
        $role = $this->makeRole();
        $role->zones()->attach($zone);

        $this->auth()
            ->patchJson("/api/roles/{$role->id}", ['zone_ids' => []])
            ->assertOk()
            ->assertJsonCount(0, 'role.zones');
    }

    public function test_update_omitting_zone_ids_leaves_zones_unchanged(): void
    {
        $zone = $this->makeZone();
        $role = $this->makeRole();
        $role->zones()->attach($zone);

        $this->auth()
            ->patchJson("/api/roles/{$role->id}", ['name' => 'Renamed'])
            ->assertOk()
            ->assertJsonCount(1, 'role.zones');
    }

    public function test_update_rejects_duplicate_name_from_another_role(): void
    {
        $this->makeRole(['name' => 'Taken']);
        $role = $this->makeRole(['name' => 'Mine']);

        $this->auth()
            ->patchJson("/api/roles/{$role->id}", ['name' => 'Taken'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_soft_deletes_role(): void
    {
        $role = $this->makeRole();

        $this->auth()
            ->deleteJson("/api/roles/{$role->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Role deleted');

        $this->auth()
            ->getJson('/api/roles')
            ->assertOk()
            ->assertJsonCount(0, 'roles');

        $this->assertSoftDeleted('roles', ['id' => $role->id]);
    }
}
