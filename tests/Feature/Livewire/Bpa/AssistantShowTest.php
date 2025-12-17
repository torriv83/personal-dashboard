<?php

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

    $taskUrl = url('/oppgaver/'.$this->assistant->token);

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

    expect($component->taskUrl())->toContain('/oppgaver/'.$this->assistant->token);
});
