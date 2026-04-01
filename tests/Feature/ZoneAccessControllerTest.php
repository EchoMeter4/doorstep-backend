<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Credential;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

class ZoneAccessControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Zone $zone;
    private string $credentialCode = 'A-00124';

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

        $this->user = User::factory()->create();

        $role = Role::create([
            'organization_id' => $org->id,
            'name'            => 'Staff',
            'enabled'         => true,
        ]);

        $role->zones()->attach($this->zone->id);
        $this->user->roles()->attach($role->id);

        Credential::create([
            'user_id'         => $this->user->id,
            'credential_code' => $this->credentialCode,
            'is_active'       => true,
            'issued_at'       => now(),
        ]);
    }

    private function auth(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function credentialImage(): UploadedFile
    {
        $path = storage_path('app/test_credential.png');

        return new UploadedFile($path, 'test_credential.png', 'image/png', null, true);
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

    private function postAccess(int $userId, int $zoneId, UploadedFile $image): \Illuminate\Testing\TestResponse
    {
        return $this->auth()->post('/api/access', [
            'user_id' => $userId,
            'zone_id' => $zoneId,
            'image'   => $image,
        ]);
    }

    // -------------------------------------------------------------------------
    // Access granted
    // -------------------------------------------------------------------------

    public function test_valid_credential_image_grants_access(): void
    {
        $response = $this->postAccess($this->user->id, $this->zone->id, $this->credentialImage())
            ->assertOk()
            ->assertJsonStructure(['authorized', 'log_code', 'message']);

        $this->assertTrue($response->json('authorized'));
        $this->assertEquals('Access granted', $response->json('message'));
        $this->assertNotNull($response->json('log_code'));
    }

    public function test_access_attempt_creates_access_log(): void
    {
        $this->postAccess($this->user->id, $this->zone->id, $this->credentialImage());

        $this->assertDatabaseHas('access_logs', [
            'user_id'       => $this->user->id,
            'zone_id'       => $this->zone->id,
            'action_type'   => 'zone_access',
            'is_authorized' => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // Access denied — no role for zone
    // -------------------------------------------------------------------------

    public function test_valid_credential_but_no_zone_role_denies_access(): void
    {
        $org       = Organization::create(['name' => 'Other Org']);
        $otherZone = Zone::create([
            'organization_id' => $org->id,
            'name'            => 'Restricted',
            'type'            => 'pedestrian',
            'enabled'         => true,
        ]);

        $response = $this->postAccess($this->user->id, $otherZone->id, $this->credentialImage())
            ->assertOk();

        $this->assertFalse($response->json('authorized'));
        $this->assertEquals('Access denied', $response->json('message'));
    }

    // -------------------------------------------------------------------------
    // Access denied — inactive credential
    // -------------------------------------------------------------------------

    public function test_inactive_credential_denies_access(): void
    {
        Credential::where('user_id', $this->user->id)->update(['is_active' => false]);

        $response = $this->postAccess($this->user->id, $this->zone->id, $this->credentialImage())
            ->assertOk();

        $this->assertFalse($response->json('authorized'));
    }
}
