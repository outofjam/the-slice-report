<?php

namespace Tests\Feature\Places;

use App\Models\PizzaList;
use App\Models\PizzaPlace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    private function placePayload(string $googlePlaceId = 'ChIJabc123', ?float $googleRating = null): array
    {
        return array_filter([
            'google_place_id' => $googlePlaceId,
            'name' => "Joe's Pizza",
            'address' => '7 Carmine St, New York, NY',
            'lat' => 40.7305,
            'lng' => -74.0027,
            'currency' => 'USD',
            'google_rating' => $googleRating,
        ], fn ($v) => $v !== null);
    }

    private function attach(PizzaList $list, PizzaPlace $place, User $user): void
    {
        $list->pizzaPlaces()->attach($place->id, [
            'id' => (string) Str::uuid(),
            'added_by' => $user->id,
        ]);
    }

    // -----------------------------------------------------------------------
    // Store
    // -----------------------------------------------------------------------

    public function test_store_requires_auth(): void
    {
        $list = PizzaList::factory()->create();

        $this->postJson("/api/v1/lists/{$list->slug}/places", $this->placePayload())
            ->assertUnauthorized();
    }

    public function test_store_requires_verified_email(): void
    {
        $user = User::factory()->unverified()->create();
        $list = PizzaList::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/lists/{$list->slug}/places", $this->placePayload())
            ->assertForbidden();
    }

    public function test_store_adds_place_to_list(): void
    {
        $user = User::factory()->create();
        $list = PizzaList::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/lists/{$list->slug}/places", $this->placePayload())
            ->assertStatus(201)
            ->assertJsonPath('data.name', "Joe's Pizza");

        $this->assertDatabaseHas('pizza_places', ['google_place_id' => 'ChIJabc123']);
        $this->assertDatabaseHas('list_pizza_place', ['list_id' => $list->id]);
    }

    public function test_store_persists_google_rating(): void
    {
        $user = User::factory()->create();
        $list = PizzaList::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/lists/{$list->slug}/places", $this->placePayload('ChIJabc123', 4.2))
            ->assertStatus(201)
            ->assertJsonPath('data.google_rating', 4.2);

        $this->assertDatabaseHas('pizza_places', [
            'google_place_id' => 'ChIJabc123',
            'google_rating' => 4.2,
        ]);
    }

    public function test_store_reuses_existing_place_by_google_place_id(): void
    {
        $user = User::factory()->create();
        $list = PizzaList::factory()->create(['user_id' => $user->id]);
        PizzaPlace::factory()->create(['google_place_id' => 'ChIJabc123']);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/lists/{$list->slug}/places", $this->placePayload());

        $this->assertDatabaseCount('pizza_places', 1);
    }

    public function test_store_does_not_duplicate_attachment(): void
    {
        $user = User::factory()->create();
        $list = PizzaList::factory()->create(['user_id' => $user->id]);
        $place = PizzaPlace::factory()->create(['google_place_id' => 'ChIJabc123']);
        $this->attach($list, $place, $user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/lists/{$list->slug}/places", $this->placePayload());

        $this->assertDatabaseCount('list_pizza_place', 1);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $list = PizzaList::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/lists/{$list->slug}/places", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['google_place_id', 'name', 'currency']);
    }

    // -----------------------------------------------------------------------
    // Destroy
    // -----------------------------------------------------------------------

    public function test_destroy_requires_auth(): void
    {
        $list = PizzaList::factory()->create();
        $place = PizzaPlace::factory()->create();

        $this->deleteJson("/api/v1/lists/{$list->slug}/places/{$place->google_place_id}")
            ->assertUnauthorized();
    }

    public function test_destroy_requires_list_ownership(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $list = PizzaList::factory()->create(['user_id' => $owner->id]);
        $place = PizzaPlace::factory()->create();
        $this->attach($list, $place, $owner);

        $this->actingAs($other, 'sanctum')
            ->deleteJson("/api/v1/lists/{$list->slug}/places/{$place->google_place_id}")
            ->assertForbidden();
    }

    public function test_destroy_detaches_place_from_list(): void
    {
        $user = User::factory()->create();
        $list = PizzaList::factory()->create(['user_id' => $user->id]);
        $place = PizzaPlace::factory()->create();
        $this->attach($list, $place, $user);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/lists/{$list->slug}/places/{$place->google_place_id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('list_pizza_place', [
            'list_id' => $list->id,
            'pizza_place_id' => $place->id,
        ]);
        $this->assertDatabaseHas('pizza_places', ['id' => $place->id]);
    }
}
