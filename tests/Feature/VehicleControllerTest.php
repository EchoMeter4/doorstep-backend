<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VehicleControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function auth(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function makeVehicle(array $attrs = []): Vehicle
    {
        return Vehicle::create(array_merge([
            'plate_number' => 'PLT-' . uniqid(),
        ], $attrs));
    }

    // -------------------------------------------------------------------------
    // Auth guard
    // -------------------------------------------------------------------------

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/vehicles')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_returns_all_vehicles_with_users(): void
    {
        $vehicleA = $this->makeVehicle();
        $vehicleB = $this->makeVehicle();
        $vehicleA->users()->attach($this->user);

        $this->auth()
            ->getJson('/api/vehicles')
            ->assertOk()
            ->assertJsonCount(2, 'vehicles')
            ->assertJsonStructure(['vehicles' => [['id', 'plateNumber', 'make', 'model', 'year', 'color', 'type', 'users']]]);
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_returns_vehicle_with_users(): void
    {
        $vehicle = $this->makeVehicle(['make' => 'Toyota', 'model' => 'Corolla']);
        $vehicle->users()->attach($this->user);

        $this->auth()
            ->getJson("/api/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('vehicle.id', $vehicle->id)
            ->assertJsonPath('vehicle.make', 'Toyota')
            ->assertJsonPath('vehicle.model', 'Corolla')
            ->assertJsonCount(1, 'vehicle.users');
    }

    public function test_show_returns_404_for_nonexistent_vehicle(): void
    {
        $this->auth()
            ->getJson('/api/vehicles/0')
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_vehicle(): void
    {
        $this->auth()
            ->postJson('/api/vehicles', [
                'plate_number' => 'ABC-1234',
                'make'         => 'Ford',
                'model'        => 'Mustang',
                'year'         => 2022,
                'color'        => 'Red',
                'type'         => 'sedan',
            ])
            ->assertCreated()
            ->assertJsonPath('vehicle.plateNumber', 'ABC-1234')
            ->assertJsonPath('vehicle.make', 'Ford')
            ->assertJsonPath('vehicle.year', 2022);

        $this->assertDatabaseHas('vehicles', ['plate_number' => 'ABC-1234']);
    }

    public function test_store_with_user_ids_syncs_users(): void
    {
        $otherUser = User::factory()->create();

        $this->auth()
            ->postJson('/api/vehicles', [
                'plate_number' => 'XYZ-9999',
                'user_ids'     => [$this->user->id, $otherUser->id],
            ])
            ->assertCreated()
            ->assertJsonCount(2, 'vehicle.users');
    }

    public function test_store_requires_plate_number(): void
    {
        $this->auth()
            ->postJson('/api/vehicles', ['make' => 'Honda'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plate_number']);
    }

    public function test_store_rejects_duplicate_plate_number(): void
    {
        $this->makeVehicle(['plate_number' => 'DUP-0001']);

        $this->auth()
            ->postJson('/api/vehicles', ['plate_number' => 'DUP-0001'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plate_number']);
    }

    public function test_store_rejects_nonexistent_user(): void
    {
        $this->auth()
            ->postJson('/api/vehicles', [
                'plate_number' => 'USR-0000',
                'user_ids'     => [0],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_ids.0']);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_patches_vehicle_fields(): void
    {
        $vehicle = $this->makeVehicle(['make' => 'OldMake', 'color' => 'Blue']);

        $this->auth()
            ->patchJson("/api/vehicles/{$vehicle->id}", ['make' => 'NewMake', 'color' => 'Green'])
            ->assertOk()
            ->assertJsonPath('vehicle.make', 'NewMake')
            ->assertJsonPath('vehicle.color', 'Green');
    }

    public function test_update_with_user_ids_replaces_users(): void
    {
        $otherUser = User::factory()->create();
        $vehicle   = $this->makeVehicle();
        $vehicle->users()->attach($this->user);

        $response = $this->auth()
            ->patchJson("/api/vehicles/{$vehicle->id}", ['user_ids' => [$otherUser->id]])
            ->assertOk();

        $users = $response->json('vehicle.users');
        $this->assertCount(1, $users);
        $this->assertEquals($otherUser->id, $users[0]['id']);
    }

    public function test_update_with_empty_user_ids_detaches_all_users(): void
    {
        $vehicle = $this->makeVehicle();
        $vehicle->users()->attach($this->user);

        $this->auth()
            ->patchJson("/api/vehicles/{$vehicle->id}", ['user_ids' => []])
            ->assertOk()
            ->assertJsonCount(0, 'vehicle.users');
    }

    public function test_update_omitting_user_ids_leaves_users_unchanged(): void
    {
        $vehicle = $this->makeVehicle();
        $vehicle->users()->attach($this->user);

        $this->auth()
            ->patchJson("/api/vehicles/{$vehicle->id}", ['color' => 'Black'])
            ->assertOk()
            ->assertJsonCount(1, 'vehicle.users');
    }

    public function test_update_rejects_duplicate_plate_number(): void
    {
        $this->makeVehicle(['plate_number' => 'TAKEN-01']);
        $vehicle = $this->makeVehicle(['plate_number' => 'MINE-01']);

        $this->auth()
            ->patchJson("/api/vehicles/{$vehicle->id}", ['plate_number' => 'TAKEN-01'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plate_number']);
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_soft_deletes_vehicle(): void
    {
        $vehicle = $this->makeVehicle();

        $this->auth()
            ->deleteJson("/api/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Vehicle deleted');

        $this->auth()
            ->getJson('/api/vehicles')
            ->assertOk()
            ->assertJsonCount(0, 'vehicles');

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
    }
}
