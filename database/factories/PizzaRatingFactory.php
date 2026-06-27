<?php

namespace Database\Factories;

use App\Models\PizzaList;
use App\Models\PizzaPlace;
use App\Models\PizzaRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PizzaRating>
 */
class PizzaRatingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'pizza_place_id' => PizzaPlace::factory(),
            'list_id' => PizzaList::factory(),
            'price' => fake()->randomFloat(2, 1, 12),
            'currency' => 'USD',
            'rating' => fake()->randomFloat(1, 0, 5),
            'note' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
