<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskList>
 */
class TaskListFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'is_shared' => false,
            'allow_assistant_add' => false,
            'assistant_id' => null,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the list is shared.
     */
    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_shared' => true,
            'assistant_id' => null,
        ]);
    }

    /**
     * Indicate that the list is assigned to an assistant.
     */
    public function forAssistant(int $assistantId): static
    {
        return $this->state(fn (array $attributes) => [
            'is_shared' => false,
            'assistant_id' => $assistantId,
        ]);
    }

    /**
     * Indicate that the list allows assistants to add tasks.
     */
    public function allowAssistantAdd(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_assistant_add' => true,
        ]);
    }
}
