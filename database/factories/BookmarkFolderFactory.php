<?php

namespace Database\Factories;

use App\Models\BookmarkFolder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookmarkFolder>
 */
class BookmarkFolderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_default' => false,
        ];
    }

    /**
     * Indicate that this folder is the default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Set this folder as a child of the given parent folder.
     */
    public function withParent(BookmarkFolder $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
        ]);
    }
}
