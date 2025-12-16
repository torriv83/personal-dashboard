<x-page-container>
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-foreground">Kjøregodtgjørelse</h1>
        <p class="text-sm text-muted-foreground mt-1">Beregn avstand til destinasjoner for kjøregodtgjørelse</p>
    </div>

    <div class="mt-6 space-y-6 max-w-2xl">
        {{-- Home Address Card --}}
        <div class="bg-card border border-border rounded-lg p-6">
            <div class="mb-4">
                <h2 class="text-lg font-medium text-foreground">Hjemmeadresse</h2>
                <p class="mt-1 text-sm text-muted-foreground">Startpunkt for alle avstandsberegninger</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label for="homeAddress" class="block text-sm font-medium text-foreground mb-1.5">Adresse</label>
                    <input
                        type="text"
                        id="homeAddress"
                        wire:model="homeAddress"
                        placeholder="F.eks. Storgata 1, 0001 Oslo"
                        class="w-full bg-input border border-border rounded-lg px-3 py-2 text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                    />
                    @error('homeAddress')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    wire:click="saveHomeAddress"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="saveHomeAddress">Lagre hjemmeadresse</span>
                    <span wire:loading wire:target="saveHomeAddress">Lagrer...</span>
                </button>
            </div>
        </div>

        {{-- Round Trip Toggle --}}
        <div class="bg-card border border-border rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-medium text-foreground">Tur/retur</h3>
                    <p class="text-xs text-muted-foreground mt-0.5">Dobler avstanden for tur/retur-beregning</p>
                </div>
                <button
                    wire:click="$toggle('roundTrip')"
                    class="relative w-12 h-7 rounded-full transition-colors cursor-pointer {{ $roundTrip ? 'bg-accent' : 'bg-border' }}"
                >
                    <span
                        class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-transform {{ $roundTrip ? 'translate-x-5' : '' }}"
                    ></span>
                </button>
            </div>
        </div>

        {{-- Destinations List --}}
        @if($this->destinations->count() > 0)
            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-border">
                    <h2 class="text-lg font-medium text-foreground">Destinasjoner</h2>
                </div>
                <div class="divide-y divide-border">
                    @foreach($this->destinations as $destination)
                        <div wire:key="destination-{{ $destination->id }}" class="px-6 py-4 flex items-center justify-between hover:bg-card-hover transition-colors">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-medium text-foreground">{{ $destination->name }}</h3>
                                <p class="text-xs text-muted-foreground truncate">{{ $destination->address }}</p>
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
                                <div class="flex items-center gap-2">
                                    {{-- Copy Button --}}
                                    @if($destination->distance_km)
                                        <button
                                            x-data="{ copied: false }"
                                            x-on:click="
                                                navigator.clipboard.writeText('{{ number_format($this->getDisplayDistance($destination->distance_km), 1, ',', ' ') }}');
                                                copied = true;
                                                setTimeout(() => copied = false, 2000);
                                            "
                                            class="p-2 text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
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

                                    {{-- Recalculate Button --}}
                                    <button
                                        wire:click="recalculateDistance({{ $destination->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="recalculateDistance({{ $destination->id }})"
                                        class="p-2 text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer disabled:opacity-50"
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

                                    {{-- Delete Button --}}
                                    <button
                                        wire:click="deleteDestination({{ $destination->id }})"
                                        wire:confirm="Er du sikker på at du vil slette denne destinasjonen?"
                                        class="p-2 text-red-500 bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                                        title="Slett destinasjon"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
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
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="newDestinationAddress" class="block text-sm font-medium text-foreground mb-1.5">Adresse</label>
                        <input
                            type="text"
                            id="newDestinationAddress"
                            wire:model="newDestinationAddress"
                            placeholder="F.eks. Storgata 1, 0001 Oslo"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                        />
                        @error('newDestinationAddress')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="addDestination"
                    class="px-4 py-2 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="addDestination">Legg til destinasjon</span>
                    <span wire:loading wire:target="addDestination">Legger til...</span>
                </button>
            </form>
        </div>
    </div>
</x-page-container>
