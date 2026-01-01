<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HjelpemiddelKategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hjelpemiddel>
 */
class HjelpemiddelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hjelpemiddel_kategori_id' => HjelpemiddelKategori::factory(),
            'parent_id' => null,
            'name' => fake()->words(3, true),
            'url' => fake()->optional(0.3)->url(),
            'custom_fields' => null,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
