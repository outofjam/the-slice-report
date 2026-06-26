<?php

namespace App\Services;

use App\Models\PizzaList;
use App\Models\PizzaPlace;
use App\Models\User;
use Illuminate\Support\Str;

class PizzaPlaceService
{
    public function addToList(PizzaList $list, User $addedBy, string $googlePlaceId, string $name, ?string $address, ?float $lat, ?float $lng, string $currency): PizzaPlace
    {
        $place = PizzaPlace::firstOrCreate(
            ['google_place_id' => $googlePlaceId],
            [
                'name' => $name,
                'address' => $address,
                'lat' => $lat,
                'lng' => $lng,
                'currency' => strtoupper($currency),
            ]
        );

        if (! $list->pizzaPlaces()->where('pizza_place_id', $place->id)->exists()) {
            $list->pizzaPlaces()->attach($place->id, [
                'id' => (string) Str::uuid(),
                'added_by' => $addedBy->id,
            ]);
        }

        return $place;
    }

    public function removeFromList(PizzaList $list, PizzaPlace $place): void
    {
        $list->pizzaPlaces()->detach($place->id);
    }
}
