<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicalExpense>
 */
class MedicalExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'amount' => fake()->randomFloat(2, 50, 1500),
            'expense_date' => $date,
            'note' => fake()->optional()->randomElement([
                'Apotek 1',
                'Boots apotek',
                'Vitusapotek',
                'Fastlege',
                'Legevakt',
                'Spesialist',
                null,
            ]),
            'year' => (int) $date->format('Y'),
        ];
    }
}
