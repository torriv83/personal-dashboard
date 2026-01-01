<?php

declare(strict_types=1);

use App\Livewire\Games\Rommers;
use App\Models\RommersGame;
use App\Models\RommersPlayer;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// ====================
// Page Access
// ====================

test('can access rommers page', function () {
    $this->get(route('games.rommers'))
        ->assertOk()
        ->assertSeeLivewire(Rommers::class);
});

test('guests cannot access rommers page', function () {
    auth()->logout();

    $this->get(route('games.rommers'))
        ->assertRedirect(route('login'));
});

// ====================
// Game Listing
// ====================

test('shows active games', function () {
    // Create an active game with players
    $activeGame = RommersGame::factory()->create([
        'started_at' => now()->subHours(2),
        'finished_at' => null,
    ]);

    RommersPlayer::factory()->create([
        'game_id' => $activeGame->id,
        'name' => 'Player One',
        'sort_order' => 0,
    ]);

    RommersPlayer::factory()->create([
        'game_id' => $activeGame->id,
        'name' => 'Player Two',
        'sort_order' => 1,
    ]);

    $component = Livewire::test(Rommers::class);

    expect($component->activeGames)->toHaveCount(1);
    expect($component->activeGames->first()->id)->toBe($activeGame->id);
    expect($component->activeGames->first()->players)->toHaveCount(2);
});

test('shows finished games', function () {
    // Create a finished game with players and a winner
    $finishedGame = RommersGame::factory()->create([
        'started_at' => now()->subDays(1),
        'finished_at' => now()->subHours(1),
    ]);

    $winner = RommersPlayer::factory()->create([
        'game_id' => $finishedGame->id,
        'name' => 'Winner Player',
        'sort_order' => 0,
    ]);

    RommersPlayer::factory()->create([
        'game_id' => $finishedGame->id,
        'name' => 'Other Player',
        'sort_order' => 1,
    ]);

    // Update game with winner
    $finishedGame->update(['winner_id' => $winner->id]);

    $component = Livewire::test(Rommers::class);

    expect($component->finishedGames)->toHaveCount(1);
    expect($component->finishedGames->first()->id)->toBe($finishedGame->id);
    expect($component->finishedGames->first()->winner_id)->toBe($winner->id);
});

// ====================
// Game Creation
// ====================

test('can open new game modal', function () {
    $component = Livewire::test(Rommers::class)
        ->call('openNewGameModal')
        ->assertSet('showNewGameModal', true)
        ->assertSet('playerNames', ['', '']);

    expect($component->showNewGameModal)->toBeTrue();
    expect($component->playerNames)->toBe(['', '']);
});

test('can close new game modal', function () {
    $component = Livewire::test(Rommers::class)
        ->set('showNewGameModal', true)
        ->set('playerNames', ['Alice', 'Bob', 'Charlie'])
        ->call('closeNewGameModal')
        ->assertSet('showNewGameModal', false)
        ->assertSet('playerNames', ['', '']);

    expect($component->showNewGameModal)->toBeFalse();
    expect($component->playerNames)->toBe(['', '']);
});

test('can add player field up to maximum of 6', function () {
    $component = Livewire::test(Rommers::class);

    // Start with 2 fields (default)
    expect($component->playerNames)->toHaveCount(2);

    // Add player fields up to 6
    $component->call('addPlayerField');
    expect($component->playerNames)->toHaveCount(3);

    $component->call('addPlayerField');
    expect($component->playerNames)->toHaveCount(4);

    $component->call('addPlayerField');
    expect($component->playerNames)->toHaveCount(5);

    $component->call('addPlayerField');
    expect($component->playerNames)->toHaveCount(6);

    // Try to add beyond maximum - should stay at 6
    $component->call('addPlayerField');
    expect($component->playerNames)->toHaveCount(6);
});

test('can remove player field down to minimum of 2', function () {
    $component = Livewire::test(Rommers::class)
        ->set('playerNames', ['Alice', 'Bob', 'Charlie', 'Diana']);

    expect($component->playerNames)->toHaveCount(4);

    // Remove player at index 3
    $component->call('removePlayerField', 3);
    expect($component->playerNames)->toHaveCount(3);
    expect($component->playerNames)->toBe(['Alice', 'Bob', 'Charlie']);

    // Remove player at index 2
    $component->call('removePlayerField', 2);
    expect($component->playerNames)->toHaveCount(2);
    expect($component->playerNames)->toBe(['Alice', 'Bob']);

    // Try to remove below minimum - should stay at 2
    $component->call('removePlayerField', 1);
    expect($component->playerNames)->toHaveCount(2);
});

test('can create game with 2 players', function () {
    expect(RommersGame::count())->toBe(0);
    expect(RommersPlayer::count())->toBe(0);

    Livewire::test(Rommers::class)
        ->set('playerNames', ['Alice', 'Bob'])
        ->call('startGame')
        ->assertDispatched('toast', message: 'Nytt spill startet!', type: 'success')
        ->assertDispatched('game-selection-changed', hasGame: true)
        ->assertSet('showNewGameModal', false);

    expect(RommersGame::count())->toBe(1);
    expect(RommersPlayer::count())->toBe(2);

    $game = RommersGame::first();
    expect($game->started_at)->not->toBeNull();
    expect($game->finished_at)->toBeNull();

    $players = RommersPlayer::orderBy('sort_order')->get();
    expect($players[0]->name)->toBe('Alice');
    expect($players[0]->current_level)->toBe(1);
    expect($players[0]->total_score)->toBe(0);
    expect($players[0]->sort_order)->toBe(0);

    expect($players[1]->name)->toBe('Bob');
    expect($players[1]->current_level)->toBe(1);
    expect($players[1]->total_score)->toBe(0);
    expect($players[1]->sort_order)->toBe(1);
});

test('can create game with 6 players', function () {
    expect(RommersGame::count())->toBe(0);
    expect(RommersPlayer::count())->toBe(0);

    Livewire::test(Rommers::class)
        ->set('playerNames', ['Alice', 'Bob', 'Charlie', 'Diana', 'Eve', 'Frank'])
        ->call('startGame')
        ->assertDispatched('toast', message: 'Nytt spill startet!', type: 'success')
        ->assertDispatched('game-selection-changed', hasGame: true);

    expect(RommersGame::count())->toBe(1);
    expect(RommersPlayer::count())->toBe(6);

    $players = RommersPlayer::orderBy('sort_order')->get();
    expect($players->pluck('name')->toArray())->toBe(['Alice', 'Bob', 'Charlie', 'Diana', 'Eve', 'Frank']);
});

test('validates minimum 2 players required', function () {
    Livewire::test(Rommers::class)
        ->set('playerNames', ['Alice', ''])
        ->call('startGame')
        ->assertHasErrors(['playerNames' => 'Du må ha minst 2 spillere.']);

    expect(RommersGame::count())->toBe(0);
    expect(RommersPlayer::count())->toBe(0);
});

test('filters out empty player names when creating game', function () {
    expect(RommersGame::count())->toBe(0);

    Livewire::test(Rommers::class)
        ->set('playerNames', ['Alice', '', 'Bob', '  ', 'Charlie'])
        ->call('startGame')
        ->assertDispatched('toast', message: 'Nytt spill startet!', type: 'success');

    expect(RommersPlayer::count())->toBe(3);

    $players = RommersPlayer::orderBy('sort_order')->get();
    expect($players->pluck('name')->toArray())->toBe(['Alice', 'Bob', 'Charlie']);
});

test('selects newly created game after creation', function () {
    $component = Livewire::test(Rommers::class)
        ->set('playerNames', ['Alice', 'Bob'])
        ->call('startGame');

    $game = RommersGame::first();
    expect($component->selectedGameId)->toBe($game->id);
});

// ====================
// Game Selection and Deletion
// ====================

test('can select a game', function () {
    $game = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Player One',
        'sort_order' => 0,
    ]);

    $component = Livewire::test(Rommers::class)
        ->call('selectGame', $game->id)
        ->assertSet('selectedGameId', $game->id)
        ->assertDispatched('game-selection-changed', hasGame: true);

    expect($component->selectedGameId)->toBe($game->id);
    expect($component->selectedGame)->not->toBeNull();
    expect($component->selectedGame->id)->toBe($game->id);
});

test('can deselect a game', function () {
    $game = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Player One',
        'sort_order' => 0,
    ]);

    $component = Livewire::test(Rommers::class)
        ->set('selectedGameId', $game->id)
        ->call('deselectGame')
        ->assertSet('selectedGameId', null)
        ->assertDispatched('game-selection-changed', hasGame: false);

    expect($component->selectedGameId)->toBeNull();
    expect($component->selectedGame)->toBeNull();
});

test('can delete a game', function () {
    $game = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Player One',
        'sort_order' => 0,
    ]);

    expect(RommersGame::count())->toBe(1);

    Livewire::test(Rommers::class)
        ->call('deleteGame', $game->id)
        ->assertDispatched('toast', message: 'Spillet ble slettet.', type: 'info');

    expect(RommersGame::count())->toBe(0);
    expect(RommersPlayer::count())->toBe(0);
});

test('deleting selected game clears selection', function () {
    $game = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Player One',
        'sort_order' => 0,
    ]);

    $component = Livewire::test(Rommers::class)
        ->set('selectedGameId', $game->id);

    expect($component->selectedGameId)->toBe($game->id);

    $component
        ->call('deleteGame', $game->id)
        ->assertSet('selectedGameId', null)
        ->assertDispatched('game-selection-changed', hasGame: false)
        ->assertDispatched('toast', message: 'Spillet ble slettet.', type: 'info');

    expect($component->selectedGameId)->toBeNull();
    expect(RommersGame::count())->toBe(0);
});

test('deleting non-selected game keeps selection', function () {
    $game1 = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    $game2 = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    RommersPlayer::factory()->create([
        'game_id' => $game1->id,
        'name' => 'Player One',
        'sort_order' => 0,
    ]);

    RommersPlayer::factory()->create([
        'game_id' => $game2->id,
        'name' => 'Player Two',
        'sort_order' => 0,
    ]);

    $component = Livewire::test(Rommers::class)
        ->set('selectedGameId', $game1->id);

    expect($component->selectedGameId)->toBe($game1->id);

    $component
        ->call('deleteGame', $game2->id)
        ->assertSet('selectedGameId', $game1->id);

    expect($component->selectedGameId)->toBe($game1->id);
    expect(RommersGame::count())->toBe(1);
    expect(RommersGame::first()->id)->toBe($game1->id);
});

test('deleting non-existent game does nothing', function () {
    $game = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Player One',
        'sort_order' => 0,
    ]);

    expect(RommersGame::count())->toBe(1);

    Livewire::test(Rommers::class)
        ->call('deleteGame', 99999);

    expect(RommersGame::count())->toBe(1);
});

// ====================
// Round Scoring and Level Progression
// ====================

test('can save a round and create round records for all players', function () {
    $game = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    $player1 = RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Alice',
        'current_level' => 1,
        'total_score' => 0,
        'sort_order' => 0,
    ]);

    $player2 = RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Bob',
        'current_level' => 1,
        'total_score' => 0,
        'sort_order' => 1,
    ]);

    expect(\App\Models\RommersRound::count())->toBe(0);

    Livewire::test(Rommers::class)
        ->set('selectedGameId', $game->id)
        ->set('roundScores', [
            $player1->id => ['score' => 10, 'completed' => false],
            $player2->id => ['score' => 15, 'completed' => false],
        ])
        ->call('saveRound')
        ->assertDispatched('toast', message: 'Runde registrert!', type: 'success');

    expect(\App\Models\RommersRound::count())->toBe(2);

    $round1 = \App\Models\RommersRound::where('player_id', $player1->id)->first();
    expect($round1->round_number)->toBe(1);
    expect($round1->level)->toBe(1);
    expect($round1->score)->toBe(10);
    expect($round1->completed_level)->toBeFalse();

    $round2 = \App\Models\RommersRound::where('player_id', $player2->id)->first();
    expect($round2->round_number)->toBe(1);
    expect($round2->level)->toBe(1);
    expect($round2->score)->toBe(15);
    expect($round2->completed_level)->toBeFalse();
});

test('saveRound increments player level when completed is true', function () {
    $game = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    $player1 = RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Alice',
        'current_level' => 1,
        'total_score' => 0,
        'sort_order' => 0,
    ]);

    $player2 = RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Bob',
        'current_level' => 1,
        'total_score' => 0,
        'sort_order' => 1,
    ]);

    Livewire::test(Rommers::class)
        ->set('selectedGameId', $game->id)
        ->set('roundScores', [
            $player1->id => ['score' => 20, 'completed' => true],
            $player2->id => ['score' => 10, 'completed' => false],
        ])
        ->call('saveRound');

    $player1->refresh();
    $player2->refresh();

    expect($player1->current_level)->toBe(2);
    expect($player2->current_level)->toBe(1);

    $round1 = \App\Models\RommersRound::where('player_id', $player1->id)->first();
    expect($round1->completed_level)->toBeTrue();

    $round2 = \App\Models\RommersRound::where('player_id', $player2->id)->first();
    expect($round2->completed_level)->toBeFalse();
});

test('saveRound does not increment player level when completed is false', function () {
    $game = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    $player = RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Alice',
        'current_level' => 3,
        'total_score' => 0,
        'sort_order' => 0,
    ]);

    Livewire::test(Rommers::class)
        ->set('selectedGameId', $game->id)
        ->set('roundScores', [
            $player->id => ['score' => 15, 'completed' => false],
        ])
        ->call('saveRound');

    $player->refresh();

    expect($player->current_level)->toBe(3);
});

test('saveRound accumulates total score correctly', function () {
    $game = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    $player = RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Alice',
        'current_level' => 1,
        'total_score' => 0,
        'sort_order' => 0,
    ]);

    Livewire::test(Rommers::class)
        ->set('selectedGameId', $game->id)
        ->set('roundScores', [
            $player->id => ['score' => 25, 'completed' => false],
        ])
        ->call('saveRound');

    $player->refresh();
    expect($player->total_score)->toBe(25);
});

test('saveRound accumulates total score across multiple rounds', function () {
    $game = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    $player = RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Alice',
        'current_level' => 1,
        'total_score' => 0,
        'sort_order' => 0,
    ]);

    $component = Livewire::test(Rommers::class)
        ->set('selectedGameId', $game->id);

    // Round 1
    $component
        ->set('roundScores', [
            $player->id => ['score' => 10, 'completed' => true],
        ])
        ->call('saveRound');

    $player->refresh();
    expect($player->total_score)->toBe(10);
    expect($player->current_level)->toBe(2);

    // Round 2
    $component
        ->set('roundScores', [
            $player->id => ['score' => 15, 'completed' => true],
        ])
        ->call('saveRound');

    $player->refresh();
    expect($player->total_score)->toBe(25);
    expect($player->current_level)->toBe(3);

    // Round 3
    $component
        ->set('roundScores', [
            $player->id => ['score' => 20, 'completed' => false],
        ])
        ->call('saveRound');

    $player->refresh();
    expect($player->total_score)->toBe(45);
    expect($player->current_level)->toBe(3);
});

test('saveRound increments round number correctly', function () {
    $game = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    $player = RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Alice',
        'current_level' => 1,
        'total_score' => 0,
        'sort_order' => 0,
    ]);

    $component = Livewire::test(Rommers::class)
        ->set('selectedGameId', $game->id);

    // Round 1
    $component
        ->set('roundScores', [
            $player->id => ['score' => 10, 'completed' => true],
        ])
        ->call('saveRound');

    $round1 = \App\Models\RommersRound::where('player_id', $player->id)->where('round_number', 1)->first();
    expect($round1)->not->toBeNull();
    expect($round1->level)->toBe(1);

    // Round 2
    $component
        ->set('roundScores', [
            $player->id => ['score' => 15, 'completed' => true],
        ])
        ->call('saveRound');

    $round2 = \App\Models\RommersRound::where('player_id', $player->id)->where('round_number', 2)->first();
    expect($round2)->not->toBeNull();
    expect($round2->level)->toBe(2);

    // Round 3
    $component
        ->set('roundScores', [
            $player->id => ['score' => 20, 'completed' => false],
        ])
        ->call('saveRound');

    $round3 = \App\Models\RommersRound::where('player_id', $player->id)->where('round_number', 3)->first();
    expect($round3)->not->toBeNull();
    expect($round3->level)->toBe(3);

    expect(\App\Models\RommersRound::where('player_id', $player->id)->count())->toBe(3);
});

// ====================
// Winner Detection
// ====================

test('completing level 11 wins the game', function () {
    $game = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    $player = RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Alice',
        'current_level' => 11,
        'total_score' => 100,
        'sort_order' => 0,
    ]);

    expect($game->fresh()->finished_at)->toBeNull();
    expect($game->fresh()->winner_id)->toBeNull();

    $component = Livewire::test(Rommers::class)
        ->set('selectedGameId', $game->id)
        ->set('roundScores', [
            $player->id => ['score' => 50, 'completed' => true],
        ])
        ->call('saveRound')
        ->assertDispatched('toast', message: 'Alice vant spillet!', type: 'success')
        ->assertSet('selectedGameId', null)
        ->assertDispatched('game-selection-changed', hasGame: false);

    $game->refresh();
    $player->refresh();

    expect($player->current_level)->toBe(12);
    expect($player->total_score)->toBe(150);
    expect($game->finished_at)->not->toBeNull();
    expect($game->winner_id)->toBe($player->id);
});

test('game is marked as finished when level 11 is completed', function () {
    $game = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
        'winner_id' => null,
    ]);

    $player1 = RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Winner',
        'current_level' => 11,
        'total_score' => 200,
        'sort_order' => 0,
    ]);

    $player2 = RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Loser',
        'current_level' => 10,
        'total_score' => 180,
        'sort_order' => 1,
    ]);

    expect(RommersGame::whereNotNull('finished_at')->count())->toBe(0);

    Livewire::test(Rommers::class)
        ->set('selectedGameId', $game->id)
        ->set('roundScores', [
            $player1->id => ['score' => 40, 'completed' => true],
            $player2->id => ['score' => 30, 'completed' => false],
        ])
        ->call('saveRound');

    $game->refresh();

    expect($game->finished_at)->not->toBeNull();
    expect($game->winner_id)->toBe($player1->id);
    expect(RommersGame::whereNotNull('finished_at')->count())->toBe(1);
});

test('first player to complete level 11 wins when multiple players complete in same round', function () {
    $game = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    $player1 = RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Alice',
        'current_level' => 11,
        'total_score' => 100,
        'sort_order' => 0,
    ]);

    $player2 = RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Bob',
        'current_level' => 11,
        'total_score' => 95,
        'sort_order' => 1,
    ]);

    Livewire::test(Rommers::class)
        ->set('selectedGameId', $game->id)
        ->set('roundScores', [
            $player1->id => ['score' => 50, 'completed' => true],
            $player2->id => ['score' => 55, 'completed' => true],
        ])
        ->call('saveRound')
        ->assertDispatched('toast', message: 'Alice vant spillet!', type: 'success');

    $game->refresh();

    // Alice wins because she's processed first (sort_order 0)
    expect($game->winner_id)->toBe($player1->id);
    expect($game->finished_at)->not->toBeNull();
});

test('completing level below 11 does not finish game', function () {
    $game = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    $player = RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Alice',
        'current_level' => 10,
        'total_score' => 100,
        'sort_order' => 0,
    ]);

    Livewire::test(Rommers::class)
        ->set('selectedGameId', $game->id)
        ->set('roundScores', [
            $player->id => ['score' => 30, 'completed' => true],
        ])
        ->call('saveRound')
        ->assertDispatched('toast', message: 'Runde registrert!', type: 'success');

    $game->refresh();
    $player->refresh();

    expect($player->current_level)->toBe(11);
    expect($game->finished_at)->toBeNull();
    expect($game->winner_id)->toBeNull();
});

test('not completing level 11 does not finish game', function () {
    $game = RommersGame::factory()->create([
        'started_at' => now(),
        'finished_at' => null,
    ]);

    $player = RommersPlayer::factory()->create([
        'game_id' => $game->id,
        'name' => 'Alice',
        'current_level' => 11,
        'total_score' => 100,
        'sort_order' => 0,
    ]);

    Livewire::test(Rommers::class)
        ->set('selectedGameId', $game->id)
        ->set('roundScores', [
            $player->id => ['score' => 20, 'completed' => false],
        ])
        ->call('saveRound')
        ->assertDispatched('toast', message: 'Runde registrert!', type: 'success');

    $game->refresh();
    $player->refresh();

    expect($player->current_level)->toBe(11);
    expect($player->total_score)->toBe(120);
    expect($game->finished_at)->toBeNull();
    expect($game->winner_id)->toBeNull();
});
