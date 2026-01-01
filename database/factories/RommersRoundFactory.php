<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RommersRound>
 */
class RommersRoundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => \App\Models\RommersPlayer::factory(),
            'round_number' => fake()->numberBetween(1, 50),
            'level' => fake()->numberBetween(1, 11),
            'score' => fake()->numberBetween(-100, 200),
            'completed_level' => fake()->boolean(),
        ];
    }
}
