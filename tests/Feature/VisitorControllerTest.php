<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Visitor;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VisitorControllerTest extends TestCase
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

    private function makeVisitor(array $attrs = []): Visitor
    {
        return Visitor::create(array_merge([
            'organization_id' => $this->organization->id,
            'name'            => 'John',
            'first_last_name' => 'Doe',
            'email'           => 'visitor+' . uniqid() . '@example.com',
            'enabled'         => true,
        ], $attrs));
    }

    // -------------------------------------------------------------------------
    // Auth guard
    // -------------------------------------------------------------------------

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/visitors')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_returns_all_visitors(): void
    {
        $this->makeVisitor(['name' => 'Alice']);
        $this->makeVisitor(['name' => 'Bob']);

        $this->auth()
            ->getJson('/api/visitors')
            ->assertOk()
            ->assertJsonCount(2, 'visitors')
            ->assertJsonStructure(['visitors' => [['id', 'organizationId', 'name', 'firstLastName', 'email', 'enabled']]]);
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_returns_visitor(): void
    {
        $visitor = $this->makeVisitor([
            'name'            => 'Carlos',
            'first_last_name' => 'Garcia',
            'email'           => 'carlos@example.com',
        ]);

        $this->auth()
            ->getJson("/api/visitors/{$visitor->id}")
            ->assertOk()
            ->assertJsonPath('visitor.id', $visitor->id)
            ->assertJsonPath('visitor.name', 'Carlos')
            ->assertJsonPath('visitor.firstLastName', 'Garcia')
            ->assertJsonPath('visitor.email', 'carlos@example.com');
    }

    public function test_show_returns_404_for_nonexistent_visitor(): void
    {
        $this->auth()
            ->getJson('/api/visitors/0')
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_visitor(): void
    {
        $this->auth()
            ->postJson('/api/visitors', [
                'organization_id' => $this->organization->id,
                'name'            => 'Diana',
                'first_last_name' => 'Prince',
                'email'           => 'diana@example.com',
                'phone'           => '555-0100',
                'company'         => 'Acme',
                'enabled'         => true,
            ])
            ->assertCreated()
            ->assertJsonPath('visitor.name', 'Diana')
            ->assertJsonPath('visitor.firstLastName', 'Prince')
            ->assertJsonPath('visitor.email', 'diana@example.com')
            ->assertJsonPath('visitor.company', 'Acme')
            ->assertJsonPath('visitor.enabled', true);

        $this->assertDatabaseHas('visitors', ['email' => 'diana@example.com']);
    }

    public function test_store_requires_name(): void
    {
        $this->auth()
            ->postJson('/api/visitors', [
                'first_last_name' => 'Doe',
                'email'           => 'noname@example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_requires_first_last_name(): void
    {
        $this->auth()
            ->postJson('/api/visitors', [
                'name'  => 'Jane',
                'email' => 'nolast@example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['first_last_name']);
    }

    public function test_store_rejects_duplicate_email(): void
    {
        $this->makeVisitor(['email' => 'taken@example.com']);

        $this->auth()
            ->postJson('/api/visitors', [
                'name'            => 'Eve',
                'first_last_name' => 'Smith',
                'email'           => 'taken@example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_patches_visitor_fields(): void
    {
        $visitor = $this->makeVisitor(['name' => 'Old', 'company' => 'OldCo']);

        $this->auth()
            ->patchJson("/api/visitors/{$visitor->id}", [
                'name'    => 'New',
                'company' => 'NewCo',
            ])
            ->assertOk()
            ->assertJsonPath('visitor.name', 'New')
            ->assertJsonPath('visitor.company', 'NewCo');
    }

    public function test_update_rejects_duplicate_email_from_another_visitor(): void
    {
        $this->makeVisitor(['email' => 'a@example.com']);
        $visitorB = $this->makeVisitor(['email' => 'b@example.com']);

        $this->auth()
            ->patchJson("/api/visitors/{$visitorB->id}", ['email' => 'a@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_soft_deletes_visitor(): void
    {
        $visitor = $this->makeVisitor();

        $this->auth()
            ->deleteJson("/api/visitors/{$visitor->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Visitor deleted');

        $this->auth()
            ->getJson('/api/visitors')
            ->assertOk()
            ->assertJsonCount(0, 'visitors');

        $this->assertSoftDeleted('visitors', ['id' => $visitor->id]);
    }
}
