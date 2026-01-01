<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RommersPlayer>
 */
class RommersPlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => \App\Models\RommersGame::factory(),
            'name' => fake()->name(),
            'current_level' => fake()->numberBetween(1, 11),
            'total_score' => fake()->numberBetween(0, 1000),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
