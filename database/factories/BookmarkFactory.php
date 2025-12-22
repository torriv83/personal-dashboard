<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bookmark>
 */
class BookmarkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'folder_id' => null,
            'url' => fake()->url(),
            'title' => fake()->sentence(4),
            'description' => fake()->boolean(50) ? fake()->paragraph() : null,
            'favicon_path' => null,
            'is_read' => false,
            'is_dead' => false,
            'is_pinned' => false,
            'pinned_order' => null,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the bookmark belongs to a folder.
     */
    public function inFolder(int $folderId): static
    {
        return $this->state(fn (array $attributes) => [
            'folder_id' => $folderId,
        ]);
    }

    /**
     * Indicate that the bookmark is standalone (not in a folder).
     */
    public function standalone(): static
    {
        return $this->state(fn (array $attributes) => [
            'folder_id' => null,
        ]);
    }

    /**
     * Indicate that the bookmark has been read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
        ]);
    }

    /**
     * Indicate that the bookmark is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
        ]);
    }

    /**
     * Indicate that the bookmark is a dead link.
     */
    public function dead(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_dead' => true,
        ]);
    }

    /**
     * Indicate that the bookmark is pinned.
     */
    public function pinned(int $order = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
            'pinned_order' => $order,
        ]);
    }
}
