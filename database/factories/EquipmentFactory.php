<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipment>
 */
class EquipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['Tape', 'Plaster', 'Bandasje', 'Kateter', 'Pose']),
            'name' => fake()->words(2, true),
            'article_number' => fake()->optional(0.7)->numerify('######'),
            'link' => fake()->optional(0.3)->url(),
            'category_id' => Category::factory(),
            'quantity' => fake()->optional(0.5)->randomElement(['1 stk', '10 stk', '1 pakke', '1 boks']),
        ];
    }
}
