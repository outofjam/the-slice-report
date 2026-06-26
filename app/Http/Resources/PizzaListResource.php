<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PizzaListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'city' => $this->city,
            'is_public' => $this->is_public,
            'slug' => $this->slug,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'pizza_places' => PizzaPlaceResource::collection($this->whenLoaded('pizzaPlaces')),
            'created_at' => $this->created_at,
        ];
    }
}
