<?php

declare(strict_types=1);

namespace App\Livewire\Games;

use App\Models\RommersGame;
use App\Models\RommersPlayer;
use App\Models\RommersRound;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property-read Collection<int, RommersGame> $activeGames
 * @property-read RommersGame|null $selectedGame
 * @property-read Collection<int, RommersGame> $finishedGames
 */
#[Layout('components.layouts.app')]
class Rommers extends Component
{
    /**
     * Nivåkrav for hvert nivå i Rommers.
     *
     * @var array<int, string>
     */
    public array $levels = [
        1 => '2x tress',
        2 => '2x firs',
        3 => 'Rømmers på 4 + 1 firs',
        4 => '2 rømmers',
        5 => '1 rømmers + 2 tress',
        6 => '3 tress',
        7 => 'Rømmers på 7 + 1 tress',
        8 => '4 tress',
        9 => '3 firs',
        10 => 'Rømmers på 9 + 1 tress',
        11 => 'Rømmers på 12',
    ];

    // Selected game
    public ?int $selectedGameId = null;

    // Modal states
    public bool $showNewGameModal = false;

    public bool $showScoreModal = false;

    public bool $showEditRoundModal = false;

    public ?int $editRoundNumber = null;

    /** @var array<int, string> */
    public array $playerNames = ['', ''];

    /** @var array<int, array{score: int, completed: bool}> */
    public array $roundScores = [];

    /** @var array<int, array{score: int, completed: bool}> */
    public array $editRoundScores = [];

    public function mount(): void
    {
        // Ingen spill valgt ved oppstart - brukeren velger selv
        $this->selectedGameId = null;
    }

    /**
     * @return Collection<int, RommersGame>
     */
    #[Computed]
    public function activeGames(): Collection
    {
        return RommersGame::with(['players'])
            ->whereNull('finished_at')
            ->latest()
            ->get();
    }

    #[Computed]
    public function selectedGame(): ?RommersGame
    {
        if (! $this->selectedGameId) {
            return null;
        }

        return RommersGame::with(['players.rounds', 'winner'])
            ->find($this->selectedGameId);
    }

    /**
     * @return Collection<int, RommersGame>
     */
    #[Computed]
    public function finishedGames(): Collection
    {
        return RommersGame::with(['players', 'winner'])
            ->whereNotNull('finished_at')
            ->latest('finished_at')
            ->limit(10)
            ->get();
    }

    public function selectGame(int $gameId): void
    {
        $this->selectedGameId = $gameId;
        unset($this->selectedGame);
        $this->dispatch('game-selection-changed', hasGame: true);
    }

    public function deselectGame(): void
    {
        $this->selectedGameId = null;
        unset($this->selectedGame);
        $this->dispatch('game-selection-changed', hasGame: false);
    }

    #[On('openNewGameModal')]
    public function openNewGameModal(): void
    {
        $this->playerNames = ['', ''];
        $this->showNewGameModal = true;
    }

    public function closeNewGameModal(): void
    {
        $this->showNewGameModal = false;
        $this->playerNames = ['', ''];
    }

    public function addPlayerField(): void
    {
        if (count($this->playerNames) < 6) {
            $this->playerNames[] = '';
        }
    }

    public function removePlayerField(int $index): void
    {
        if (count($this->playerNames) > 2) {
            unset($this->playerNames[$index]);
            $this->playerNames = array_values($this->playerNames);
        }
    }

    public function startGame(): void
    {
        // Filter out empty names
        $names = array_filter($this->playerNames, fn ($name) => trim($name) !== '');

        if (count($names) < 2) {
            $this->addError('playerNames', 'Du må ha minst 2 spillere.');

            return;
        }

        // Create the game
        $game = RommersGame::create([
            'started_at' => now(),
        ]);

        // Create players
        foreach (array_values($names) as $index => $name) {
            RommersPlayer::create([
                'game_id' => $game->id,
                'name' => trim($name),
                'current_level' => 1,
                'total_score' => 0,
                'sort_order' => $index,
            ]);
        }

        $this->closeNewGameModal();
        $this->selectedGameId = $game->id;
        unset($this->activeGames, $this->selectedGame, $this->finishedGames);
        $this->dispatch('game-selection-changed', hasGame: true);
        $this->dispatch('toast', type: 'success', message: 'Nytt spill startet!');
    }

    #[On('openScoreModal')]
    public function openScoreModal(): void
    {
        if (! $this->selectedGame) {
            return;
        }

        $this->roundScores = [];
        foreach ($this->selectedGame->players as $player) {
            $this->roundScores[$player->id] = [
                'score' => 0,
                'completed' => false,
            ];
        }

        $this->showScoreModal = true;
    }

    public function closeScoreModal(): void
    {
        $this->showScoreModal = false;
        $this->roundScores = [];
    }

    public function saveRound(): void
    {
        if (! $this->selectedGame) {
            return;
        }

        // Determine round number
        $maxRound = RommersRound::whereIn('player_id', $this->selectedGame->players->pluck('id'))
            ->max('round_number') ?? 0;
        $roundNumber = $maxRound + 1;

        $winner = null;

        foreach ($this->selectedGame->players as $player) {
            $scoreData = $this->roundScores[$player->id] ?? ['score' => 0, 'completed' => false];

            // Create round record
            RommersRound::create([
                'player_id' => $player->id,
                'round_number' => $roundNumber,
                'level' => $player->current_level,
                'score' => $scoreData['score'],
                'completed_level' => $scoreData['completed'],
            ]);

            // Update player totals
            $player->total_score += $scoreData['score'];

            if ($scoreData['completed']) {
                $player->current_level++;

                // Check for winner (completed level 11)
                if ($player->current_level > 11 && ! $winner) {
                    $winner = $player;
                }
            }

            $player->save();
        }

        // If we have a winner, finish the game
        if ($winner) {
            $this->selectedGame->update([
                'finished_at' => now(),
                'winner_id' => $winner->id,
            ]);
            $this->selectedGameId = null;
            $this->dispatch('game-selection-changed', hasGame: false);
            $this->dispatch('toast', type: 'success', message: "{$winner->name} vant spillet!");
        } else {
            $this->dispatch('toast', type: 'success', message: 'Runde registrert!');
        }

        $this->closeScoreModal();
        unset($this->activeGames, $this->selectedGame, $this->finishedGames);
    }

    public function openEditRoundModal(int $roundNumber): void
    {
        if (! $this->selectedGame) {
            return;
        }

        $this->editRoundNumber = $roundNumber;
        $this->editRoundScores = [];

        foreach ($this->selectedGame->players as $player) {
            $round = $player->rounds->firstWhere('round_number', $roundNumber);
            $this->editRoundScores[$player->id] = [
                'score' => $round?->score ?? 0,
                'completed' => (bool) ($round?->completed_level ?? false),
            ];
        }

        $this->showEditRoundModal = true;
    }

    public function closeEditRoundModal(): void
    {
        $this->showEditRoundModal = false;
        $this->editRoundNumber = null;
        $this->editRoundScores = [];
    }

    public function saveEditRound(): void
    {
        if (! $this->selectedGame || ! $this->editRoundNumber) {
            return;
        }

        // Oppdater runde-poster
        foreach ($this->selectedGame->players as $player) {
            $scoreData = $this->editRoundScores[$player->id] ?? ['score' => 0, 'completed' => false];

            RommersRound::where('player_id', $player->id)
                ->where('round_number', $this->editRoundNumber)
                ->update([
                    'score' => $scoreData['score'],
                    'completed_level' => $scoreData['completed'],
                ]);
        }

        // Refresh og rekalkuler alle spillere fra scratch
        unset($this->selectedGame);
        $this->recalculatePlayerStats();

        $this->closeEditRoundModal();
        unset($this->activeGames, $this->selectedGame, $this->finishedGames);
        $this->dispatch('toast', type: 'success', message: 'Runde oppdatert!');
    }

    /**
     * Rekalkulerer current_level og total_score for alle spillere i valgt spill
     * basert på alle registrerte runder.
     */
    private function recalculatePlayerStats(): void
    {
        if (! $this->selectedGame) {
            return;
        }

        foreach ($this->selectedGame->players as $player) {
            $level = 1;
            $totalScore = 0;

            $rounds = $player->rounds->sortBy('round_number');
            foreach ($rounds as $round) {
                $totalScore += $round->score;
                if ($round->completed_level) {
                    $level++;
                }
            }

            $player->update([
                'current_level' => $level,
                'total_score' => $totalScore,
            ]);
        }

        // Sjekk om spillet bør markeres som ferdig (eller gjenåpnes)
        $winner = $this->selectedGame->players->fresh()->first(fn (RommersPlayer $p) => $p->current_level > 11);

        if ($winner && ! $this->selectedGame->isFinished()) {
            $this->selectedGame->update([
                'finished_at' => now(),
                'winner_id' => $winner->id,
            ]);
            $this->selectedGameId = null;
            $this->dispatch('game-selection-changed', hasGame: false);
            $this->dispatch('toast', type: 'success', message: "{$winner->name} vant spillet!");
        } elseif (! $winner && $this->selectedGame->isFinished()) {
            // Redigering fjernet vinneren - gjenåpne spillet
            $this->selectedGame->update([
                'finished_at' => null,
                'winner_id' => null,
            ]);
        }
    }

    public function deleteGame(int $gameId): void
    {
        $game = RommersGame::find($gameId);
        if (! $game) {
            return;
        }

        $game->delete();

        // Hvis det slettede spillet var valgt, fjern valget
        if ($this->selectedGameId === $gameId) {
            $this->selectedGameId = null;
            $this->dispatch('game-selection-changed', hasGame: false);
        }

        unset($this->activeGames, $this->selectedGame, $this->finishedGames);
        $this->dispatch('toast', type: 'info', message: 'Spillet ble slettet.');
    }

    public function render()
    {
        return view('livewire.games.rommers');
    }
}
