<?php

namespace Database\Factories;

use App\Models\PizzaPlace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PizzaPlace>
 */
class PizzaPlaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'google_place_id' => 'ChIJ'.fake()->unique()->bothify('##########??????????'),
            'name' => fake()->company().' Pizza',
            'address' => fake()->streetAddress().', '.fake()->city().', '.fake()->stateAbbr(),
            'lat' => fake()->latitude(),
            'lng' => fake()->longitude(),
            'currency' => 'USD',
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
