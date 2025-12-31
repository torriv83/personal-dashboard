<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WeightEntry>
 */
class WeightEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recorded_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'weight' => fake()->randomFloat(2, 70, 100),
            'note' => fake()->optional(0.3)->sentence(),
        ];
    }
}
