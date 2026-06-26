<?php

namespace App\Services;

use App\Models\PizzaPlace;
use App\Models\PizzaRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class PizzaRatingService
{
    public function forPlace(PizzaPlace $place): Collection
    {
        return $place->ratings()
            ->with('user')
            ->where('is_active', true)
            ->get();
    }

    public function upsert(User $user, PizzaPlace $place, string $listId, float $price, float $rating, ?string $note): PizzaRating
    {
        $pizzaRating = PizzaRating::updateOrCreate(
            [
                'user_id' => $user->id,
                'pizza_place_id' => $place->id,
            ],
            [
                'list_id' => $listId,
                'price' => $price,
                'currency' => $place->currency,
                'rating' => $rating,
                'note' => $note,
                'is_active' => true,
                'deleted_at' => null,
            ]
        );

        return $pizzaRating->load('user');
    }

    public function deleteForUser(User $user, PizzaPlace $place): void
    {
        PizzaRating::where('user_id', $user->id)
            ->where('pizza_place_id', $place->id)
            ->firstOrFail()
            ->delete();
    }
}
