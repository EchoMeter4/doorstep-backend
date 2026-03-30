<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserControllerTest extends TestCase
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

    private function validPassword(): string
    {
        return 'SecurePass1!SecurePass1!';
    }

    // -------------------------------------------------------------------------
    // Auth guard
    // -------------------------------------------------------------------------

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_returns_all_users(): void
    {
        User::factory()->count(2)->create();

        $this->auth()
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonStructure(['users' => [['id', 'name', 'first_last_name', 'email', 'enabled']]]);

        $count = $this->auth()->getJson('/api/users')->json('users');
        $this->assertCount(3, $count); // setUp user + 2 factory
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_returns_user(): void
    {
        $target = User::factory()->create(['name' => 'Alice', 'first_last_name' => 'Wonder']);

        $this->auth()
            ->getJson("/api/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('user.id', $target->id)
            ->assertJsonPath('user.name', 'Alice')
            ->assertJsonPath('user.first_last_name', 'Wonder');
    }

    public function test_show_returns_404_for_nonexistent_user(): void
    {
        $this->auth()
            ->getJson('/api/users/0')
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_user(): void
    {
        $this->auth()
            ->postJson('/api/users', [
                'name'            => 'Bob',
                'first_last_name' => 'Builder',
                'email'           => 'bob@example.com',
                'password'        => $this->validPassword(),
                'enabled'         => true,
            ])
            ->assertCreated()
            ->assertJsonPath('user.name', 'Bob')
            ->assertJsonPath('user.first_last_name', 'Builder')
            ->assertJsonPath('user.email', 'bob@example.com');

        $this->assertDatabaseHas('users', ['email' => 'bob@example.com']);
    }

    public function test_store_requires_name(): void
    {
        $this->auth()
            ->postJson('/api/users', [
                'first_last_name' => 'Doe',
                'email'           => 'noname@example.com',
                'password'        => $this->validPassword(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_requires_first_last_name(): void
    {
        $this->auth()
            ->postJson('/api/users', [
                'name'     => 'Jane',
                'email'    => 'nolast@example.com',
                'password' => $this->validPassword(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['first_last_name']);
    }

    public function test_store_requires_email(): void
    {
        $this->auth()
            ->postJson('/api/users', [
                'name'            => 'Jane',
                'first_last_name' => 'Doe',
                'password'        => $this->validPassword(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_store_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->auth()
            ->postJson('/api/users', [
                'name'            => 'Eve',
                'first_last_name' => 'Smith',
                'email'           => 'taken@example.com',
                'password'        => $this->validPassword(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_store_requires_password(): void
    {
        $this->auth()
            ->postJson('/api/users', [
                'name'            => 'Jane',
                'first_last_name' => 'Doe',
                'email'           => 'nopwd@example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_patches_user_fields(): void
    {
        $target = User::factory()->create(['name' => 'Old', 'enabled' => true]);

        $this->auth()
            ->patchJson("/api/users/{$target->id}", ['name' => 'New', 'enabled' => false])
            ->assertOk()
            ->assertJsonPath('user.name', 'New')
            ->assertJsonPath('user.enabled', false);
    }

    public function test_update_rejects_duplicate_email_from_another_user(): void
    {
        User::factory()->create(['email' => 'a@example.com']);
        $target = User::factory()->create(['email' => 'b@example.com']);

        $this->auth()
            ->patchJson("/api/users/{$target->id}", ['email' => 'a@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_soft_deletes_user(): void
    {
        $target = User::factory()->create();

        $this->auth()
            ->deleteJson("/api/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('message', 'User deleted');

        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }
}
