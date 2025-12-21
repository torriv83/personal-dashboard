<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WishlistItem>
 */
class WishlistItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => null,
            'name' => fake()->words(4, true),
            'url' => fake()->url(),
            'image_url' => fake()->boolean(30) ? fake()->imageUrl() : null,
            'price' => fake()->numberBetween(100, 50000),
            'quantity' => fake()->numberBetween(1, 5),
            'status' => fake()->randomElement(['waiting', 'saving', 'saved', 'purchased']),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the item belongs to a group.
     */
    public function inGroup(int $groupId): static
    {
        return $this->state(fn (array $attributes) => [
            'group_id' => $groupId,
            'sort_order' => 0,
        ]);
    }

    /**
     * Indicate that the item is standalone (not in a group).
     */
    public function standalone(): static
    {
        return $this->state(fn (array $attributes) => [
            'group_id' => null,
        ]);
    }

    /**
     * Indicate that the item is waiting.
     */
    public function waiting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'waiting',
        ]);
    }

    /**
     * Indicate that the item is in saving status.
     */
    public function saving(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'saving',
        ]);
    }

    /**
     * Indicate that the item is saved.
     */
    public function saved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'saved',
        ]);
    }

    /**
     * Indicate that the item is purchased.
     */
    public function purchased(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'purchased',
        ]);
    }
}
