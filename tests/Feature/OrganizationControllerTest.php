<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrganizationControllerTest extends TestCase
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

    private function makeOrganization(array $attrs = []): Organization
    {
        return Organization::create(array_merge([
            'name' => 'Org ' . uniqid(),
        ], $attrs));
    }

    // -------------------------------------------------------------------------
    // Auth guard
    // -------------------------------------------------------------------------

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/organizations')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_returns_all_organizations(): void
    {
        $this->makeOrganization(['name' => 'Alpha']);
        $this->makeOrganization(['name' => 'Beta']);

        $this->auth()
            ->getJson('/api/organizations')
            ->assertOk()
            ->assertJsonCount(2, 'organizations')
            ->assertJsonStructure(['organizations' => [['id', 'name']]]);
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_returns_organization(): void
    {
        $org = $this->makeOrganization(['name' => 'Acme']);

        $this->auth()
            ->getJson("/api/organizations/{$org->id}")
            ->assertOk()
            ->assertJsonPath('organization.id', $org->id)
            ->assertJsonPath('organization.name', 'Acme');
    }

    public function test_show_returns_404_for_nonexistent(): void
    {
        $this->auth()
            ->getJson('/api/organizations/0')
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_organization(): void
    {
        $this->auth()
            ->postJson('/api/organizations', ['name' => 'Globex'])
            ->assertCreated()
            ->assertJsonPath('organization.name', 'Globex');

        $this->assertDatabaseHas('organizations', ['name' => 'Globex']);
    }

    public function test_store_requires_name(): void
    {
        $this->auth()
            ->postJson('/api/organizations', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_patches_organization_name(): void
    {
        $org = $this->makeOrganization(['name' => 'Old Name']);

        $this->auth()
            ->patchJson("/api/organizations/{$org->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('organization.name', 'New Name');
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_soft_deletes_organization(): void
    {
        $org = $this->makeOrganization();

        $this->auth()
            ->deleteJson("/api/organizations/{$org->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Organization deleted');

        $this->auth()
            ->getJson('/api/organizations')
            ->assertOk()
            ->assertJsonCount(0, 'organizations');

        $this->assertSoftDeleted('organizations', ['id' => $org->id]);
    }
}
