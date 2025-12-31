<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Assistant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assistant>
 */
class AssistantFactory extends Factory
{
    protected $model = Assistant::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['primary', 'substitute', 'oncall']);

        return [
            'employee_number' => fake()->unique()->numberBetween(1, 999),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'type' => $type,
            'color' => match ($type) {
                'primary' => '#3b82f6',
                'substitute' => '#a855f7',
                'oncall' => '#f97316',
            },
            'hired_at' => fake()->dateTimeBetween('-5 years', 'now'),
            'send_monthly_report' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'primary',
            'color' => '#3b82f6',
        ]);
    }

    public function substitute(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'substitute',
            'color' => '#a855f7',
        ]);
    }

    public function oncall(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'oncall',
            'color' => '#f97316',
        ]);
    }

    public function withMonthlyReport(): static
    {
        return $this->state(fn (array $attributes) => [
            'send_monthly_report' => true,
        ]);
    }
}
