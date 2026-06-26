<?php

namespace Tests\Feature\Lists;

use App\Models\PizzaList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Index
    // -----------------------------------------------------------------------

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/v1/lists')
            ->assertUnauthorized();
    }

    public function test_index_returns_users_lists(): void
    {
        $user = User::factory()->create();
        PizzaList::factory()->count(3)->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/lists')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_does_not_return_other_users_lists(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        PizzaList::factory()->count(2)->create(['user_id' => $other->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/lists')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // -----------------------------------------------------------------------
    // Store
    // -----------------------------------------------------------------------

    public function test_store_requires_auth(): void
    {
        $this->postJson('/api/v1/lists', ['name' => 'My List'])
            ->assertUnauthorized();
    }

    public function test_store_requires_verified_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/lists', ['name' => 'My List'])
            ->assertForbidden();
    }

    public function test_store_creates_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/lists', [
                'name' => 'NYC Slices',
                'city' => 'New York',
                'is_public' => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'NYC Slices')
            ->assertJsonPath('data.city', 'New York');

        $this->assertDatabaseHas('lists', ['user_id' => $user->id, 'name' => 'NYC Slices']);
    }

    public function test_store_requires_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/lists', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_generates_slug(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/lists', ['name' => 'Best Slices']);

        $response->assertStatus(201);
        $this->assertStringStartsWith('best-slices-', $response->json('data.slug'));
    }

    // -----------------------------------------------------------------------
    // Show (public)
    // -----------------------------------------------------------------------

    public function test_show_returns_list_by_slug(): void
    {
        PizzaList::factory()->create(['slug' => 'my-list-abc123']);

        $this->getJson('/api/v1/lists/my-list-abc123')
            ->assertOk()
            ->assertJsonPath('data.slug', 'my-list-abc123');
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->getJson('/api/v1/lists/does-not-exist')
            ->assertNotFound();
    }

    // -----------------------------------------------------------------------
    // Update
    // -----------------------------------------------------------------------

    public function test_update_requires_auth(): void
    {
        $list = PizzaList::factory()->create();

        $this->patchJson("/api/v1/lists/{$list->slug}", ['name' => 'Updated'])
            ->assertUnauthorized();
    }

    public function test_update_requires_verified_email(): void
    {
        $user = User::factory()->unverified()->create();
        $list = PizzaList::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/lists/{$list->slug}", ['name' => 'Updated'])
            ->assertForbidden();
    }

    public function test_update_requires_ownership(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $list = PizzaList::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other, 'sanctum')
            ->patchJson("/api/v1/lists/{$list->slug}", ['name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_update_modifies_list(): void
    {
        $user = User::factory()->create();
        $list = PizzaList::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/lists/{$list->slug}", [
                'name' => 'Updated Name',
                'is_public' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('lists', ['id' => $list->id, 'name' => 'Updated Name']);
    }

    // -----------------------------------------------------------------------
    // Destroy
    // -----------------------------------------------------------------------

    public function test_destroy_requires_auth(): void
    {
        $list = PizzaList::factory()->create();

        $this->deleteJson("/api/v1/lists/{$list->slug}")
            ->assertUnauthorized();
    }

    public function test_destroy_requires_ownership(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $list = PizzaList::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other, 'sanctum')
            ->deleteJson("/api/v1/lists/{$list->slug}")
            ->assertForbidden();
    }

    public function test_destroy_soft_deletes_list(): void
    {
        $user = User::factory()->create();
        $list = PizzaList::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/lists/{$list->slug}")
            ->assertNoContent();

        $this->assertSoftDeleted('lists', ['id' => $list->id]);
    }
}
