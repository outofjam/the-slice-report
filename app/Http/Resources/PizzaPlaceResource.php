<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PizzaPlaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ratingsLoaded = $this->relationLoaded('ratings');
        $ratings = $ratingsLoaded ? $this->ratings : null;

        $avgRating = $ratings ? round((float) $ratings->avg('rating'), 2) : null;
        $avgPrice = $ratings ? round((float) $ratings->avg('price'), 2) : null;
        $ratingCount = $ratings ? $ratings->count() : null;

        $googleRating = $this->google_rating !== null ? (float) $this->google_rating : null;

        $hypeIndex = ($googleRating !== null && $avgRating !== null && $ratingCount > 0)
            ? round($googleRating - $avgRating, 1)
            : null;

        $myRating = null;
        if ($ratings && $request->user()) {
            $mine = $ratings->firstWhere('user_id', $request->user()->id);
            $myRating = $mine ? new PizzaRatingResource($mine) : null;
        }

        return [
            'id' => $this->id,
            'google_place_id' => $this->google_place_id,
            'name' => $this->name,
            'address' => $this->address,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'currency' => $this->currency,
            'google_rating' => $googleRating,
            'is_active' => $this->is_active,
            'avg_rating' => $avgRating > 0 ? $avgRating : null,
            'avg_price' => $avgPrice > 0 ? $avgPrice : null,
            'rating_count' => $ratingCount,
            'hype_index' => $hypeIndex,
            'my_rating' => $myRating,
            'ratings' => PizzaRatingResource::collection($this->whenLoaded('ratings')),
        ];
    }
}
