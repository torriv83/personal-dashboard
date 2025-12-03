<?php

namespace Database\Factories;

use App\Models\Assistant;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('-1 month', '+1 month');
        $duration = fake()->randomElement([180, 240, 300, 360, 420, 480]); // 3-8 hours
        $endsAt = (clone $startsAt)->modify("+{$duration} minutes");

        return [
            'assistant_id' => Assistant::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'duration_minutes' => $duration,
            'is_unavailable' => false,
            'is_all_day' => false,
            'is_archived' => false,
            'note' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function upcoming(): static
    {
        return $this->state(function (array $attributes) {
            $startsAt = fake()->dateTimeBetween('+1 day', '+2 weeks');
            $duration = $attributes['duration_minutes'] ?? 240;
            $endsAt = (clone $startsAt)->modify("+{$duration} minutes");

            return [
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ];
        });
    }

    public function past(): static
    {
        return $this->state(function (array $attributes) {
            $startsAt = fake()->dateTimeBetween('-6 months', '-1 day');
            $duration = $attributes['duration_minutes'] ?? 240;
            $endsAt = (clone $startsAt)->modify("+{$duration} minutes");

            return [
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ];
        });
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_unavailable' => true,
        ]);
    }

    public function allDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_all_day' => true,
            'duration_minutes' => 0,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_archived' => true,
        ]);
    }

    public function forYear(int $year): static
    {
        return $this->state(function (array $attributes) use ($year) {
            $startsAt = fake()->dateTimeBetween("{$year}-01-01", "{$year}-12-31");
            $duration = $attributes['duration_minutes'] ?? 240;
            $endsAt = (clone $startsAt)->modify("+{$duration} minutes");

            return [
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ];
        });
    }
}
