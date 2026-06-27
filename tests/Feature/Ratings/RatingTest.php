<?php

namespace Tests\Feature\Ratings;

use App\Models\PizzaList;
use App\Models\PizzaPlace;
use App\Models\PizzaRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Index (public)
    // -----------------------------------------------------------------------

    public function test_index_returns_active_ratings_for_place(): void
    {
        $place = PizzaPlace::factory()->create();
        PizzaRating::factory()->count(3)->create(['pizza_place_id' => $place->id, 'is_active' => true]);

        $this->getJson("/api/v1/places/{$place->google_place_id}/ratings")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_excludes_inactive_ratings(): void
    {
        $place = PizzaPlace::factory()->create();
        PizzaRating::factory()->count(2)->create(['pizza_place_id' => $place->id, 'is_active' => true]);
        PizzaRating::factory()->count(1)->create(['pizza_place_id' => $place->id, 'is_active' => false]);

        $this->getJson("/api/v1/places/{$place->google_place_id}/ratings")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    // -----------------------------------------------------------------------
    // Store
    // -----------------------------------------------------------------------

    public function test_store_requires_auth(): void
    {
        $place = PizzaPlace::factory()->create();
        $list = PizzaList::factory()->create();

        $this->postJson("/api/v1/places/{$place->google_place_id}/ratings", [
            'list_id' => $list->id,
            'price' => 4.50,
            'rating' => 4.5,
        ])->assertUnauthorized();
    }

    public function test_store_requires_verified_email(): void
    {
        $user = User::factory()->unverified()->create();
        $place = PizzaPlace::factory()->create();
        $list = PizzaList::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/places/{$place->google_place_id}/ratings", [
                'list_id' => $list->id,
                'price' => 4.50,
                'rating' => 4.5,
            ])->assertForbidden();
    }

    public function test_store_creates_rating(): void
    {
        $user = User::factory()->create();
        $place = PizzaPlace::factory()->create(['currency' => 'USD']);
        $list = PizzaList::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/places/{$place->google_place_id}/ratings", [
                'list_id' => $list->id,
                'price' => 4.50,
                'rating' => 4.5,
                'note' => 'Crispy crust, perfect char.',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('pizza_ratings', [
            'user_id' => $user->id,
            'pizza_place_id' => $place->id,
            'list_id' => $list->id,
        ]);
    }

    public function test_store_updates_existing_rating(): void
    {
        $user = User::factory()->create();
        $place = PizzaPlace::factory()->create(['currency' => 'USD']);
        $list = PizzaList::factory()->create(['user_id' => $user->id]);

        PizzaRating::factory()->create([
            'user_id' => $user->id,
            'pizza_place_id' => $place->id,
            'list_id' => $list->id,
            'rating' => 3.0,
            'price' => 3.00,
            'currency' => 'USD',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/places/{$place->google_place_id}/ratings", [
                'list_id' => $list->id,
                'price' => 4.50,
                'rating' => 4.0,
            ]);

        $this->assertDatabaseCount('pizza_ratings', 1);
        $this->assertDatabaseHas('pizza_ratings', [
            'user_id' => $user->id,
            'pizza_place_id' => $place->id,
        ]);
    }

    public function test_store_validates_rating_above_max(): void
    {
        $user = User::factory()->create();
        $place = PizzaPlace::factory()->create();
        $list = PizzaList::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/places/{$place->google_place_id}/ratings", [
                'list_id' => $list->id,
                'price' => 4.50,
                'rating' => 6,
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_store_validates_price_below_minimum(): void
    {
        $user = User::factory()->create();
        $place = PizzaPlace::factory()->create();
        $list = PizzaList::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/places/{$place->google_place_id}/ratings", [
                'list_id' => $list->id,
                'price' => -1,
                'rating' => 8.5,
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    public function test_store_requires_existing_list_id(): void
    {
        $user = User::factory()->create();
        $place = PizzaPlace::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/places/{$place->google_place_id}/ratings", [
                'list_id' => '00000000-0000-0000-0000-000000000000',
                'price' => 4.50,
                'rating' => 8.5,
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['list_id']);
    }

    // -----------------------------------------------------------------------
    // Destroy
    // -----------------------------------------------------------------------

    public function test_destroy_requires_auth(): void
    {
        $place = PizzaPlace::factory()->create();

        $this->deleteJson("/api/v1/places/{$place->google_place_id}/ratings")
            ->assertUnauthorized();
    }

    public function test_destroy_removes_rating(): void
    {
        $user = User::factory()->create();
        $place = PizzaPlace::factory()->create();
        $list = PizzaList::factory()->create(['user_id' => $user->id]);

        PizzaRating::factory()->create([
            'user_id' => $user->id,
            'pizza_place_id' => $place->id,
            'list_id' => $list->id,
            'currency' => 'USD',
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/places/{$place->google_place_id}/ratings")
            ->assertNoContent();

        $this->assertSoftDeleted('pizza_ratings', [
            'user_id' => $user->id,
            'pizza_place_id' => $place->id,
        ]);
    }

    public function test_destroy_returns_404_if_no_rating_exists(): void
    {
        $user = User::factory()->create();
        $place = PizzaPlace::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/places/{$place->google_place_id}/ratings")
            ->assertNotFound();
    }
}
