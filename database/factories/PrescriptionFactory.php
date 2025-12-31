<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prescription>
 */
class PrescriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'valid_to' => fake()->dateTimeBetween('now', '+1 year'),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_to' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_to' => fake()->dateTimeBetween('+1 day', '+7 days'),
        ]);
    }

    public function expiringWarning(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_to' => fake()->dateTimeBetween('+8 days', '+30 days'),
        ]);
    }
}
