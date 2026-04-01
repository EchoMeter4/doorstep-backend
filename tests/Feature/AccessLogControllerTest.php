<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Zone;
use App\Models\AccessLog;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccessLogControllerTest extends TestCase
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

    private function makeLog(array $attrs = []): AccessLog
    {
        return AccessLog::create(array_merge([
            'credential_type'  => 'rfid',
            'credential_value' => 'CARD-' . uniqid(),
            'action_type'      => 'entry',
            'is_authorized'    => true,
        ], $attrs));
    }

    // -------------------------------------------------------------------------
    // Auth guard
    // -------------------------------------------------------------------------

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/logs?from=2026-01-01&to=2026-01-31')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function test_requires_from_parameter(): void
    {
        $this->auth()
            ->getJson('/api/logs?to=2026-01-31')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['from']);
    }

    public function test_requires_to_parameter(): void
    {
        $this->auth()
            ->getJson('/api/logs?from=2026-01-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    }

    public function test_requires_to_after_or_equal_from(): void
    {
        $this->auth()
            ->getJson('/api/logs?from=2026-01-31&to=2026-01-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    }

    // -------------------------------------------------------------------------
    // Filtering
    // -------------------------------------------------------------------------

    public function test_returns_logs_filtered_by_date_range(): void
    {
        $inside  = $this->makeLog();
        $outside = $this->makeLog();

        $inside->created_at  = now()->setDate(2026, 3, 15)->setTime(10, 0);
        $outside->created_at = now()->setDate(2026, 3, 20)->setTime(10, 0);
        $inside->save();
        $outside->save();

        $this->auth()
            ->getJson('/api/logs?from=2026-03-01&to=2026-03-17')
            ->assertOk()
            ->assertJsonCount(1, 'logs');
    }

    public function test_today_only_query_returns_full_day(): void
    {
        $morning  = $this->makeLog();
        $evening  = $this->makeLog();
        $tomorrow = $this->makeLog();

        $today = now()->format('Y-m-d');

        $morning->created_at  = now()->startOfDay()->addHours(6);
        $evening->created_at  = now()->startOfDay()->addHours(22);
        $tomorrow->created_at = now()->addDay()->startOfDay()->addHours(6);
        $morning->save();
        $evening->save();
        $tomorrow->save();

        $this->auth()
            ->getJson("/api/logs?from={$today}&to={$today}")
            ->assertOk()
            ->assertJsonCount(2, 'logs');
    }

    public function test_returns_empty_array_when_no_logs_in_range(): void
    {
        $this->makeLog()->update(['created_at' => now()->subDays(30)]);

        $this->auth()
            ->getJson('/api/logs?from=2025-01-01&to=2025-01-31')
            ->assertOk()
            ->assertJsonCount(0, 'logs');
    }

    // -------------------------------------------------------------------------
    // Response shape
    // -------------------------------------------------------------------------

    public function test_log_shape_includes_required_fields(): void
    {
        $org  = Organization::create(['name' => 'Test Org']);
        $zone = Zone::create(['organization_id' => $org->id, 'name' => 'Main Gate', 'type' => 'pedestrian']);
        $log  = $this->makeLog(['zone_id' => $zone->id, 'log_code' => 'LOG-20260330-0001', 'is_authorized' => true]);
        $log->users()->attach($this->user);

        $today = now()->format('Y-m-d');

        $response = $this->auth()
            ->getJson("/api/logs?from={$today}&to={$today}")
            ->assertOk()
            ->assertJsonStructure(['logs' => [[
                'id', 'credentialType', 'credentialValue', 'users', 'zone', 'timestamp', 'authorized',
            ]]]);

        $entry = $response->json('logs.0');

        $this->assertEquals('LOG-20260330-0001', $entry['id']);
        $this->assertTrue($entry['authorized']);
        $this->assertEquals('Main Gate', $entry['zone']['name']);
        $this->assertEquals('pedestrian', $entry['zone']['type']);
        $this->assertCount(1, $entry['users']);
        $this->assertEquals($this->user->id, $entry['users'][0]['id']);
        $this->assertArrayHasKey('credential', $entry['users'][0]);
        $this->assertEquals($log->credential_type, $entry['users'][0]['credential']['type']);
        $this->assertEquals($log->credential_value, $entry['users'][0]['credential']['number']);
    }
}
