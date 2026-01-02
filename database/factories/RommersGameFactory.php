<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RommersGame>
 */
class RommersGameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'started_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'finished_at' => null,
            'winner_id' => null,
        ];
    }

    /**
     * Indicate that the game is active (in progress).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'finished_at' => null,
            'winner_id' => null,
        ]);
    }

    /**
     * Indicate that the game is finished.
     */
    public function finished(): static
    {
        return $this->state(function (array $attributes) {
            $finishedAt = fake()->dateTimeBetween($attributes['started_at'], 'now');

            return [
                'finished_at' => $finishedAt,
            ];
        });
    }
}
