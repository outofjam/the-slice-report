<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PizzaPlaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'google_place_id' => $this->google_place_id,
            'name' => $this->name,
            'address' => $this->address,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'currency' => $this->currency,
            'is_active' => $this->is_active,
            'avg_rating' => $this->whenNotNull($this->avg_rating ?? null),
            'avg_price' => $this->whenNotNull($this->avg_price ?? null),
            'rating_count' => $this->whenNotNull($this->rating_count ?? null),
            'ratings' => PizzaRatingResource::collection($this->whenLoaded('ratings')),
        ];
    }
}
