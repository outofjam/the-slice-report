<?php

namespace Database\Factories;

use App\Models\PizzaList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PizzaList>
 */
class PizzaListFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'user_id' => User::factory(),
            'name' => ucwords($name),
            'city' => fake()->city(),
            'is_public' => true,
            'slug' => Str::slug($name).'-'.strtolower(Str::random(6)),
        ];
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }
}
