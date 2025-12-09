<div class="py-4 sm:p-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Rømmers</h1>
            <p class="text-sm text-muted-foreground mt-1 hidden sm:block">Poengføring for kortspillet Rømmers</p>
        </div>
        <button
            wire:click="openNewGameModal"
            class="px-4 py-2 text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer flex items-center gap-2"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="hidden sm:inline">Nytt spill</span>
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Venstre kolonne --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Pågående spill (liste) --}}
            @if($this->activeGames->isNotEmpty())
                <div class="bg-card border border-border rounded-lg overflow-hidden">
                    <div class="px-4 py-3 border-b border-border">
                        <h2 class="text-sm font-medium text-foreground">Pågående spill</h2>
                    </div>
                    <div class="divide-y divide-border">
                        @foreach($this->activeGames as $game)
                            <div class="flex items-center {{ $selectedGameId === $game->id ? 'bg-accent/10 border-l-2 border-accent' : '' }}">
                                <button
                                    wire:click="selectGame({{ $game->id }})"
                                    class="flex-1 px-4 py-3 text-left hover:bg-card-hover transition-colors cursor-pointer flex items-center justify-between"
                                >
                                    <div>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ $game->players->pluck('name')->join(', ') }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            Startet {{ $game->started_at->format('d.m.Y H:i') }}
                                        </p>
                                    </div>
                                    @if($selectedGameId === $game->id)
                                        <svg class="w-5 h-5 text-accent" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </button>
                                <button
                                    wire:click="deleteGame({{ $game->id }})"
                                    wire:confirm="Er du sikker på at du vil slette dette spillet?"
                                    class="p-3 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 transition-colors cursor-pointer"
                                    title="Slett spill"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Valgt spill --}}
            @if($this->selectedGame)
                <div class="bg-card border border-border rounded-lg overflow-hidden">
                    <div class="px-4 py-3 border-b border-border">
                        <h2 class="text-sm font-medium text-foreground">{{ $this->selectedGame->players->pluck('name')->join(' vs ') }}</h2>
                        <p class="text-xs text-muted-foreground">Startet {{ $this->selectedGame->started_at->format('d.m.Y H:i') }}</p>
                    </div>

                    {{-- Spillertabell --}}
                    @php
                        // Sortert etter nivå (høyest først), deretter poeng (lavest først)
                        $sortedPlayers = $this->selectedGame->players->sortBy([
                            ['current_level', 'desc'],
                            ['total_score', 'asc'],
                        ]);
                    @endphp
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-card-hover/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Spiller</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase tracking-wider">Nivå</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase tracking-wider hidden sm:table-cell">Krav</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Poeng</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach($sortedPlayers as $player)
                                    <tr class="hover:bg-card-hover transition-colors">
                                        <td class="px-4 py-3">
                                            <span class="text-sm font-medium text-foreground">{{ $player->name }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center justify-center w-8 h-8 bg-accent/10 text-accent text-sm font-bold rounded-full">
                                                {{ $player->current_level }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center hidden sm:table-cell">
                                            <span class="text-sm text-muted-foreground">
                                                {{ $levels[$player->current_level] ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="text-sm font-medium text-foreground">{{ $player->total_score }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Registrer runde knapp --}}
                <div class="flex justify-center">
                    <button
                        wire:click="openScoreModal"
                        class="w-full sm:w-auto px-6 py-3 text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer flex items-center justify-center gap-2 font-medium"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Registrer runde
                    </button>
                </div>

                {{-- Rundehistorikk --}}
                @php
                    $maxRounds = $this->selectedGame->players->max(fn($p) => $p->rounds->count());
                @endphp
                @if($maxRounds > 0)
                    <div class="bg-card border border-border rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b border-border">
                            <h2 class="text-sm font-medium text-foreground">Rundehistorikk</h2>
                        </div>
                        <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-card-hover/50">
                                            <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground">Runde</th>
                                            @foreach($this->selectedGame->players as $player)
                                                <th class="px-4 py-2 text-center text-xs font-medium text-muted-foreground">{{ $player->name }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border">
                                        @for($i = 1; $i <= $maxRounds; $i++)
                                            <tr class="hover:bg-card-hover/50 transition-colors">
                                                <td class="px-4 py-2 text-muted-foreground">{{ $i }}</td>
                                                @foreach($this->selectedGame->players as $player)
                                                    @php
                                                        $round = $player->rounds->firstWhere('round_number', $i);
                                                    @endphp
                                                    <td class="px-4 py-2 text-center">
                                                        @if($round)
                                                            <span class="@if($round->completed_level) text-accent font-medium @else text-foreground @endif">
                                                                {{ $round->score }}
                                                                @if($round->completed_level)
                                                                    <svg class="inline w-3 h-3 ml-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                                    </svg>
                                                                @endif
                                                            </span>
                                                        @else
                                                            <span class="text-muted-foreground">—</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endfor
                                    </tbody>
                                    <tfoot class="border-t border-border bg-card-hover/30">
                                        <tr>
                                            <td class="px-4 py-2 text-xs font-medium text-muted-foreground uppercase">Totalt</td>
                                            @foreach($this->selectedGame->players as $player)
                                                <td class="px-4 py-2 text-center font-medium text-foreground">
                                                    {{ $player->total_score }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    </tfoot>
                                </table>
                        </div>
                    </div>
                @endif
            @elseif($this->activeGames->isEmpty())
                {{-- Ingen spill i det hele tatt --}}
                <div class="bg-card border border-border rounded-lg p-8 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-card-hover rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-foreground mb-2">Ingen pågående spill</h3>
                    <p class="text-sm text-muted-foreground mb-4">Start et nytt spill for å begynne å føre poeng</p>
                    <button
                        wire:click="openNewGameModal"
                        class="px-4 py-2 text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                    >
                        Start nytt spill
                    </button>
                </div>
            @else
                {{-- Har aktive spill men ingen valgt --}}
                <div class="bg-card border border-border rounded-lg p-8 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-card-hover rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-foreground mb-2">Velg et spill</h3>
                    <p class="text-sm text-muted-foreground">Klikk på et av spillene i listen over for å fortsette</p>
                </div>
            @endif

            {{-- Tidligere spill --}}
            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-border">
                    <h2 class="text-sm font-medium text-foreground">Avsluttede spill</h2>
                </div>
                @if($this->finishedGames->isEmpty())
                    <div class="p-4 text-center text-muted-foreground text-sm">
                        Ingen avsluttede spill funnet
                    </div>
                @else
                    <div class="divide-y divide-border">
                        @foreach($this->finishedGames as $game)
                            <div class="flex items-center">
                                <div class="flex-1 px-4 py-3 hover:bg-card-hover transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-foreground">
                                                {{ $game->finished_at->format('d.m.Y') }}
                                                @if($game->winner)
                                                    — <span class="text-accent">{{ $game->winner->name }}</span> vant
                                                @endif
                                            </p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ $game->players->pluck('name')->join(', ') }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm text-muted-foreground">
                                                {{ $game->players->max('total_score') }} poeng
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <button
                                    wire:click="deleteGame({{ $game->id }})"
                                    wire:confirm="Er du sikker på at du vil slette dette spillet?"
                                    class="p-3 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 transition-colors cursor-pointer"
                                    title="Slett spill"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Høyre kolonne: Nivåoversikt --}}
        <div class="space-y-6">
            {{-- Nivåkrav --}}
            <div
                x-data="{
                    expanded: false,
                    openExpanded() {
                        this.expanded = true;
                        document.body.style.overflow = 'hidden';
                    },
                    closeExpanded() {
                        this.expanded = false;
                        document.body.style.overflow = '';
                    }
                }"
                @keydown.escape.window="closeExpanded()"
                class="bg-card border border-border rounded-lg overflow-hidden"
            >
                <div class="px-4 py-3 border-b border-border flex items-center justify-between">
                    <h2 class="text-sm font-medium text-foreground">Nivåkrav</h2>
                    <button
                        @click="openExpanded()"
                        class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-card-hover rounded transition-colors cursor-pointer"
                        title="Utvid"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                    </button>
                </div>
                <div class="divide-y divide-border">
                    @foreach($levels as $level => $requirement)
                        <div class="px-4 py-3 flex items-center gap-3 hover:bg-card-hover transition-colors">
                            <span class="w-7 h-7 flex items-center justify-center bg-accent/10 text-accent text-sm font-bold rounded-full shrink-0">
                                {{ $level }}
                            </span>
                            <span class="text-sm text-foreground">{{ $requirement }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Fullscreen Modal --}}
                <template x-teleport="body">
                    <div
                        x-show="expanded"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
                        @click.self="closeExpanded()"
                    >
                        <div class="bg-card border border-border rounded-xl max-h-[95vh] w-full max-w-lg overflow-auto shadow-2xl">
                            <div class="flex items-center justify-between p-5 border-b border-border">
                                <h3 class="text-lg font-medium text-foreground">Nivåkrav</h3>
                                <button @click="closeExpanded()" class="p-2 text-muted-foreground hover:text-foreground hover:bg-card-hover rounded-lg transition-colors cursor-pointer" title="Lukk">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div class="divide-y divide-border">
                                @foreach($levels as $level => $requirement)
                                    <div class="px-5 py-4 flex items-center gap-4 hover:bg-card-hover transition-colors">
                                        <span class="w-10 h-10 flex items-center justify-center bg-accent/10 text-accent text-lg font-bold rounded-full shrink-0">
                                            {{ $level }}
                                        </span>
                                        <span class="text-base text-foreground">{{ $requirement }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Poengberegning --}}
            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-border">
                    <h2 class="text-sm font-medium text-foreground">Poengberegning</h2>
                </div>
                <div class="p-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">3-7</span>
                        <span class="text-foreground font-medium">5 poeng</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">8-K</span>
                        <span class="text-foreground font-medium">10 poeng</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">2, A (Ess)</span>
                        <span class="text-foreground font-medium">20 poeng</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Nytt spill Modal --}}
    @if($showNewGameModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.closeNewGameModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closeNewGameModal"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-md mx-4">
                {{-- Header --}}
                <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">Nytt spill</h2>
                    <button
                        wire:click="closeNewGameModal"
                        class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-4 sm:px-6 py-4 space-y-4">
                    <p class="text-sm text-muted-foreground">Legg til spillere (minst 2):</p>

                    @error('playerNames')
                        <p class="text-sm text-red-400">{{ $message }}</p>
                    @enderror

                    <div class="space-y-3">
                        @foreach($playerNames as $index => $name)
                            <div class="flex items-center gap-2">
                                <input
                                    type="text"
                                    wire:model="playerNames.{{ $index }}"
                                    class="flex-1 bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                    placeholder="Spillernavn"
                                >
                                @if(count($playerNames) > 2)
                                    <button
                                        wire:click="removePlayerField({{ $index }})"
                                        class="p-2 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 rounded transition-colors cursor-pointer"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if(count($playerNames) < 6)
                        <button
                            wire:click="addPlayerField"
                            class="w-full px-4 py-2 text-sm text-muted-foreground border border-dashed border-border rounded-lg hover:border-accent hover:text-accent transition-colors cursor-pointer flex items-center justify-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Legg til spiller
                        </button>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-4 sm:px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                    <button
                        wire:click="closeNewGameModal"
                        class="px-4 py-2 text-sm text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                    >
                        Avbryt
                    </button>
                    <button
                        wire:click="startGame"
                        class="px-4 py-2 text-sm text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                    >
                        Start spill
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Registrer runde Modal --}}
    @if($showScoreModal && $this->selectedGame)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.closeScoreModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closeScoreModal"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                {{-- Header --}}
                <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">Registrer runde</h2>
                    <button
                        wire:click="closeScoreModal"
                        class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-4 sm:px-6 py-4 space-y-4">
                    @foreach($this->selectedGame->players as $player)
                        <div class="p-4 bg-card-hover/50 rounded-lg space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-foreground">{{ $player->name }}</span>
                                <span class="text-xs text-muted-foreground">
                                    Nivå {{ $player->current_level }}: {{ $levels[$player->current_level] ?? '—' }}
                                </span>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <label class="block text-xs text-muted-foreground mb-1">Poeng</label>
                                    <input
                                        type="number"
                                        wire:model="roundScores.{{ $player->id }}.score"
                                        class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                        placeholder="0"
                                        min="0"
                                    >
                                </div>
                                <div class="pt-5">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            wire:model="roundScores.{{ $player->id }}.completed"
                                            class="w-5 h-5 rounded border-border text-accent focus:ring-accent focus:ring-offset-0 bg-input cursor-pointer"
                                        >
                                        <span class="text-sm text-foreground">Fullført nivå</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Footer --}}
                <div class="px-4 sm:px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                    <button
                        wire:click="closeScoreModal"
                        class="px-4 py-2 text-sm text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                    >
                        Avbryt
                    </button>
                    <button
                        wire:click="saveRound"
                        class="px-4 py-2 text-sm text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                    >
                        Lagre runde
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
