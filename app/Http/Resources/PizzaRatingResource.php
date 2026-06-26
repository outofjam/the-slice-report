<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PizzaRatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'pizza_place_id' => $this->pizza_place_id,
            'list_id' => $this->list_id,
            'price' => $this->price,
            'currency' => $this->currency,
            'rating' => $this->rating,
            'note' => $this->note,
            'is_active' => $this->is_active,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
