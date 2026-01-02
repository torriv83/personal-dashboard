<?php

declare(strict_types=1);

use App\Livewire\Bpa\AssistantShow;
use App\Models\Assistant;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
    $this->assistant = Assistant::factory()->create(['name' => 'Test Assistent']);
});

it('generates task url when assistant has token', function () {
    $this->assistant->update(['token' => 'test-token-123']);

    $taskUrl = url('/oppgaver/' . $this->assistant->token);

    expect($taskUrl)->toContain('/oppgaver/test-token-123');
});

it('can regenerate token', function () {
    $oldToken = $this->assistant->token;

    $this->assistant->regenerateToken();

    expect($this->assistant->token)->not->toBe($oldToken);
    expect($this->assistant->token)->not->toBeNull();
    expect(strlen($this->assistant->token))->toBe(36); // UUID length
});

it('auto-generates token on assistant creation', function () {
    $assistant = Assistant::factory()->create();

    expect($assistant->token)->not->toBeNull();
    expect(strlen($assistant->token))->toBe(36);
});

it('regenerateToken updates the database', function () {
    $oldToken = $this->assistant->token;

    $this->assistant->regenerateToken();
    $this->assistant->refresh();

    $this->assertDatabaseMissing('assistants', [
        'id' => $this->assistant->id,
        'token' => $oldToken,
    ]);

    $this->assertDatabaseHas('assistants', [
        'id' => $this->assistant->id,
        'token' => $this->assistant->token,
    ]);
});

it('can regenerate token via livewire component', function () {
    $oldToken = $this->assistant->token;

    // Create component instance directly to test method without full render
    $component = new AssistantShow;
    $component->assistant = $this->assistant;
    $component->regenerateToken();

    expect($this->assistant->fresh()->token)->not->toBe($oldToken);
});

it('taskUrl returns empty string when assistant has no token', function () {
    $this->assistant->update(['token' => null]);

    $component = new AssistantShow;
    $component->assistant = $this->assistant;

    expect($component->taskUrl())->toBe('');
});

it('taskUrl returns correct url when assistant has token', function () {
    $component = new AssistantShow;
    $component->assistant = $this->assistant;

    expect($component->taskUrl())->toContain('/oppgaver/' . $this->assistant->token);
});

it('calculates total worked minutes excluding unavailable time', function () {
    $currentYear = now()->year;

    // Create worked shifts
    $this->assistant->shifts()->create([
        'starts_at' => "{$currentYear}-03-01 08:00:00",
        'ends_at' => "{$currentYear}-03-01 16:00:00",
        'is_unavailable' => false,
        'is_all_day' => false,
        'duration_minutes' => 480, // 8 hours
    ]);

    $this->assistant->shifts()->create([
        'starts_at' => "{$currentYear}-03-02 09:00:00",
        'ends_at' => "{$currentYear}-03-02 13:00:00",
        'is_unavailable' => false,
        'is_all_day' => false,
        'duration_minutes' => 240, // 4 hours
    ]);

    // Create unavailable shift (should NOT be counted)
    $this->assistant->shifts()->create([
        'starts_at' => "{$currentYear}-03-03 08:00:00",
        'ends_at' => "{$currentYear}-03-03 16:00:00",
        'is_unavailable' => true,
        'is_all_day' => false,
        'duration_minutes' => 480, // 8 hours - should be excluded
    ]);

    $component = new AssistantShow;
    $component->assistant = $this->assistant;
    $component->year = $currentYear;

    // Total should be 480 + 240 = 720 minutes = 12:00
    expect($component->totalWorkedMinutes())->toBe('12:00');
});

it('returns zero for total worked minutes when filter is away', function () {
    $currentYear = now()->year;

    // Create worked shift
    $this->assistant->shifts()->create([
        'starts_at' => "{$currentYear}-03-01 08:00:00",
        'ends_at' => "{$currentYear}-03-01 16:00:00",
        'is_unavailable' => false,
        'duration_minutes' => 480,
    ]);

    $component = new AssistantShow;
    $component->assistant = $this->assistant;
    $component->year = $currentYear;
    $component->typeFilter = 'away';

    expect($component->totalWorkedMinutes())->toBe('0:00');
});
