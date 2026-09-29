<?php

namespace Database\Factories;

use App\Models\Avanto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Avanto>
 */
class AvantoFactory extends Factory
{
    protected $model = Avanto::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => fake()->date(),
            'location' => fake()->city(),
            'water_temperature' => fake()->randomFloat(1, 0, 10),
            'duration_minutes' => fake()->numberBetween(1, 30),
            'duration_seconds' => fake()->numberBetween(0, 59),
            'swear_words' => fake()->numberBetween(0, 5),
            'feeling_before' => fake()->numberBetween(1, 10),
            'feeling_after' => fake()->numberBetween(1, 10),
            'sauna' => fake()->boolean(),
            'sauna_duration' => fake()->optional()->numberBetween(5, 60),
        ];
    }
}
