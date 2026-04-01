<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Credential;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class ZoneAccessControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Zone $zone;
    private Zone $vehicleZone;
    private Vehicle $vehicle;
    private string $credentialCode = '100124001';

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::create(['name' => 'Test Org']);

        $this->zone = Zone::create([
            'organization_id' => $org->id,
            'name'            => 'Main Entrance',
            'type'            => 'pedestrian',
            'enabled'         => true,
        ]);

        $this->vehicleZone = Zone::create([
            'organization_id' => $org->id,
            'name'            => 'Parking',
            'type'            => 'vehicular',
            'enabled'         => true,
        ]);

        $this->user = User::factory()->create();

        $role = Role::create([
            'organization_id' => $org->id,
            'name'            => 'Staff',
            'enabled'         => true,
        ]);

        $role->zones()->attach([$this->zone->id, $this->vehicleZone->id]);
        $this->user->roles()->attach($role->id);

        Credential::create([
            'user_id'         => $this->user->id,
            'credential_code' => $this->credentialCode,
            'is_active'       => true,
            'issued_at'       => now(),
        ]);

        $this->vehicle = Vehicle::create(['plate_number' => 'ABC-123']);
        $this->vehicle->users()->attach($this->user->id);
    }

    private function auth(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function fakeAzureOcr(string $text = '100124001'): void
    {
        Http::fake([
            '*/computervision/imageanalysis:analyze*' => Http::response([
                'readResult' => [
                    'blocks' => [[
                        'lines' => [[
                            'words' => [['text' => $text]],
                        ]],
                    ]],
                ],
            ], 200),
        ]);
    }

    private function fakePlateRecognizer(string $plate = 'ABC-123'): void
    {
        Http::fake([
            '*/v1/plate-reader/*' => Http::response([
                'results' => [['plate' => $plate, 'score' => 0.9]],
            ], 201),
        ]);
    }

    private function postAccess(?UploadedFile $image = null): \Illuminate\Testing\TestResponse
    {
        return $this->auth()->post('/api/access', [
            'user_id' => $this->user->id,
            'zone_id' => $this->zone->id,
            'image'   => $image ?? UploadedFile::fake()->image('credential.png'),
        ]);
    }

    // -------------------------------------------------------------------------
    // Auth guard
    // -------------------------------------------------------------------------

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->postJson('/api/access')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function test_missing_fields_fail_validation(): void
    {
        $this->auth()
            ->postJson('/api/access', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'zone_id', 'image']);
    }

    // -------------------------------------------------------------------------
    // Pedestrian zone — access granted
    // -------------------------------------------------------------------------

    public function test_valid_credential_image_grants_access(): void
    {
        $this->fakeAzureOcr('100124001');

        $response = $this->postAccess()
            ->assertOk()
            ->assertJsonStructure(['authorized', 'log_code', 'message']);

        $this->assertTrue($response->json('authorized'));
        $this->assertEquals('Access granted', $response->json('message'));
        $this->assertNotNull($response->json('log_code'));
    }

    public function test_access_attempt_creates_access_log(): void
    {
        $this->fakeAzureOcr('100124001');

        $this->postAccess();

        $this->assertDatabaseHas('access_logs', [
            'user_id'       => $this->user->id,
            'zone_id'       => $this->zone->id,
            'action_type'   => 'zone_access',
            'is_authorized' => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // Pedestrian zone — access denied
    // -------------------------------------------------------------------------

    public function test_valid_credential_but_no_zone_role_denies_access(): void
    {
        $this->fakeAzureOcr('100124001');

        $org       = Organization::create(['name' => 'Other Org']);
        $otherZone = Zone::create([
            'organization_id' => $org->id,
            'name'            => 'Restricted',
            'type'            => 'pedestrian',
            'enabled'         => true,
        ]);

        $response = $this->auth()->post('/api/access', [
            'user_id' => $this->user->id,
            'zone_id' => $otherZone->id,
            'image'   => UploadedFile::fake()->image('credential.png'),
        ])->assertOk();

        $this->assertFalse($response->json('authorized'));
        $this->assertEquals('Access denied', $response->json('message'));
    }

    public function test_inactive_credential_denies_access(): void
    {
        $this->fakeAzureOcr('100124001');

        Credential::where('user_id', $this->user->id)->update(['is_active' => false]);

        $response = $this->postAccess()->assertOk();

        $this->assertFalse($response->json('authorized'));
    }

    // -------------------------------------------------------------------------
    // Vehicle zone
    // -------------------------------------------------------------------------

    public function test_vehicle_zone_with_valid_plate_grants_access(): void
    {
        $this->fakePlateRecognizer('ABC-123');

        $response = $this->auth()->post('/api/access', [
            'user_id' => $this->user->id,
            'zone_id' => $this->vehicleZone->id,
            'image'   => UploadedFile::fake()->image('plate.png'),
        ])->assertOk();

        $this->assertTrue($response->json('authorized'));
        $this->assertEquals('Access granted', $response->json('message'));
    }

    public function test_vehicle_zone_plate_not_belonging_to_user_denies_access(): void
    {
        $this->fakePlateRecognizer('XYZ-999');

        $response = $this->auth()->post('/api/access', [
            'user_id' => $this->user->id,
            'zone_id' => $this->vehicleZone->id,
            'image'   => UploadedFile::fake()->image('plate.png'),
        ])->assertOk();

        $this->assertFalse($response->json('authorized'));
    }

    public function test_vehicle_zone_no_plate_detected_denies_access(): void
    {
        Http::fake([
            '*/v1/plate-reader/*' => Http::response(['results' => []], 201),
        ]);

        $response = $this->auth()->post('/api/access', [
            'user_id' => $this->user->id,
            'zone_id' => $this->vehicleZone->id,
            'image'   => UploadedFile::fake()->image('plate.png'),
        ])->assertOk();

        $this->assertFalse($response->json('authorized'));
    }
}
