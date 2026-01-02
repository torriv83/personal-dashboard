<x-page-container>
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-foreground">Kjøregodtgjørelse</h1>
        <p class="text-sm text-muted-foreground mt-1">Beregn avstand til destinasjoner for kjøregodtgjørelse</p>
    </div>

    <div class="mt-6 space-y-6 max-w-2xl">
        {{-- No Home Address Warning --}}
        @if(empty($homeAddress))
            <div class="bg-warning/10 border border-warning/30 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-warning shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <p class="text-sm font-medium text-foreground">Hjemmeadresse ikke satt</p>
                    <p class="text-xs text-muted-foreground mt-1">
                        Du må legge inn hjemmeadressen din i
                        <a href="{{ route('settings') }}" wire:navigate class="text-accent hover:underline">innstillinger</a>
                        før du kan beregne avstander.
                    </p>
                </div>
            </div>
        @endif

        {{-- Destinations List --}}
        @if($this->destinations->count() > 0)
            <div class="bg-card border border-border rounded-lg">
                <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-medium text-foreground">Destinasjoner</h2>
                    <x-toggle
                        label="Tur/retur"
                        :checked="$roundTrip"
                        @click="$wire.$toggle('roundTrip')"
                        size="sm"
                    />
                </div>
                <div class="divide-y divide-border" x-sort="$wire.updateOrder($item, $position)" wire:ignore.self>
                    @foreach($this->destinations as $destination)
                        <div
                            wire:key="destination-{{ $destination->id }}"
                            x-sort:item="{{ $destination->id }}"
                            class="px-6 py-4 flex items-center justify-between hover:bg-card-hover transition-colors"
                        >
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                {{-- Drag Handle --}}
                                <svg class="w-4 h-4 text-muted-foreground cursor-grab shrink-0" x-sort:handle fill="currentColor" viewBox="0 0 24 24">
                                    <circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" />
                                    <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                                    <circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" />
                                </svg>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-medium text-foreground">{{ $destination->name }}</h3>
                                    <p class="text-xs text-muted-foreground truncate">{{ $destination->address }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 ml-4">
                                <div class="text-right">
                                    @if($destination->distance_km)
                                        <div class="text-xl font-mono font-bold text-accent">
                                            {{ number_format($this->getDisplayDistance($destination->distance_km), 1, ',', ' ') }} km
                                        </div>
                                        @if($roundTrip)
                                            <p class="text-xs text-muted-foreground">
                                                ({{ number_format($destination->distance_km, 1, ',', ' ') }} km enkeltur)
                                            </p>
                                        @endif
                                    @else
                                        <span class="text-sm text-muted-foreground">Ikke beregnet</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1.5">
                                    {{-- Copy Button --}}
                                    @if($destination->distance_km)
                                        <button
                                            x-data="{ copied: false }"
                                            x-on:click="
                                                navigator.clipboard.writeText('{{ number_format($this->getDisplayDistance($destination->distance_km), 1, ',', ' ') }}');
                                                copied = true;
                                                setTimeout(() => copied = false, 2000);
                                            "
                                            class="p-1.5 text-foreground hover:bg-input rounded-lg transition-colors cursor-pointer"
                                            title="Kopier km"
                                        >
                                            <template x-if="!copied">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </template>
                                            <template x-if="copied">
                                                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </template>
                                        </button>
                                    @endif

                                    {{-- Desktop: Separate buttons --}}
                                    <div class="hidden md:flex items-center gap-1.5">
                                        <button
                                            wire:click="recalculateDistance({{ $destination->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="recalculateDistance({{ $destination->id }})"
                                            class="p-1.5 text-foreground hover:bg-input rounded-lg transition-colors cursor-pointer disabled:opacity-50"
                                            title="Beregn avstand på nytt"
                                        >
                                            <svg
                                                class="w-4 h-4"
                                                wire:loading.class="animate-spin"
                                                wire:target="recalculateDistance({{ $destination->id }})"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button
                                            wire:click="deleteDestination({{ $destination->id }})"
                                            wire:confirm="Er du sikker på at du vil slette denne destinasjonen?"
                                            class="p-1.5 text-destructive hover:bg-input rounded-lg transition-colors cursor-pointer"
                                            title="Slett destinasjon"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Mobile: Actions Dropdown --}}
                                    <div x-data="{ open: false }" class="relative md:hidden">
                                        <button
                                            @click="open = !open"
                                            class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded-lg transition-colors cursor-pointer"
                                            title="Flere handlinger"
                                        >
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="6" r="1.5" />
                                                <circle cx="12" cy="12" r="1.5" />
                                                <circle cx="12" cy="18" r="1.5" />
                                            </svg>
                                        </button>
                                        <div
                                            x-show="open"
                                            @click.outside="open = false"
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="opacity-0 scale-95"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            class="absolute right-0 mt-1 w-44 bg-card border border-border rounded-lg shadow-lg z-50 overflow-hidden"
                                        >
                                            <button
                                                wire:click="recalculateDistance({{ $destination->id }})"
                                                @click="open = false"
                                                class="w-full px-3 py-2 text-left text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer flex items-center gap-2"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    wire:loading.class="animate-spin"
                                                    wire:target="recalculateDistance({{ $destination->id }})"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                Beregn på nytt
                                            </button>
                                            <button
                                                wire:click="deleteDestination({{ $destination->id }})"
                                                wire:confirm="Er du sikker på at du vil slette denne destinasjonen?"
                                                @click="open = false"
                                                class="w-full px-3 py-2 text-left text-sm text-destructive hover:bg-card-hover transition-colors cursor-pointer flex items-center gap-2"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Slett
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Add New Destination Form --}}
        <div class="bg-card border border-border rounded-lg p-6">
            <div class="mb-4">
                <h2 class="text-lg font-medium text-foreground">Legg til ny destinasjon</h2>
                <p class="mt-1 text-sm text-muted-foreground">Legg til et sted du ofte kjører til</p>
            </div>

            <form wire:submit="addDestination" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="newDestinationName" class="block text-sm font-medium text-foreground mb-1.5">Navn</label>
                        <input
                            type="text"
                            id="newDestinationName"
                            wire:model="newDestinationName"
                            placeholder="F.eks. Tennishallen Sandnes"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                        />
                        @error('newDestinationName')
                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="newDestinationAddress" class="block text-sm font-medium text-foreground mb-1.5">Adresse</label>
                        <input
                            type="text"
                            id="newDestinationAddress"
                            wire:model="newDestinationAddress"
                            placeholder="F.eks. Kirkeveien 20, 0368 Oslo"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                        />
                        @error('newDestinationAddress')
                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <x-button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="addDestination"
                >
                    <span wire:loading.remove wire:target="addDestination">Legg til destinasjon</span>
                    <span wire:loading wire:target="addDestination">Legger til...</span>
                </x-button>
            </form>
        </div>
    </div>
</x-page-container>
