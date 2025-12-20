<x-page-container class="space-y-6" data-prescriptions-component>
    {{-- Context Menu --}}
    @include('livewire.medical.prescriptions._context-menu')

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Resepter</h1>
            <p class="text-sm text-muted-foreground mt-1">Oversikt over resepter og utløpsdatoer</p>
        </div>
        <button
            wire:click="openModal"
            class="p-2 sm:px-4 sm:py-2 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="hidden sm:inline">Opprett resept</span>
        </button>
    </div>

    {{-- Compact Prescriptions Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        @forelse($this->prescriptions as $prescription)
            <div
                wire:key="prescription-{{ $prescription['id'] }}"
                class="bg-card border-l-4 rounded-lg p-4 hover:bg-card-hover transition-colors group
                    @if($prescription['status'] === 'expired') border-l-red-500
                    @elseif($prescription['status'] === 'danger') border-l-red-500
                    @elseif($prescription['status'] === 'warning') border-l-yellow-500
                    @else border-l-accent @endif"
                @contextmenu.prevent="
                    const x = Math.min($event.clientX, window.innerWidth - 200);
                    const y = Math.min($event.clientY, window.innerHeight - 150);
                    $store.prescriptionMenu.open(x, y, {{ $prescription['id'] }})
                "
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-semibold text-foreground">{{ $prescription['name'] }}</h3>
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ \Carbon\Carbon::parse($prescription['validTo'])->format('d.m.Y') }}
                        </p>
                    </div>
                    <div class="text-right shrink-0">
                        @if($prescription['status'] === 'expired')
                            <span class="text-xs font-medium px-2 py-1 bg-red-500/20 text-red-400 rounded">Utløpt</span>
                        @else
                            <p class="text-lg font-bold
                                @if($prescription['status'] === 'danger') text-red-400
                                @elseif($prescription['status'] === 'warning') text-yellow-400
                                @else text-accent @endif">
                                {{ $prescription['daysLeft'] }}
                            </p>
                            <p class="text-xs text-muted-foreground">dager</p>
                        @endif
                    </div>
                </div>

                {{-- Actions - always visible on mobile, hover on desktop --}}
                <div class="flex items-center gap-1 mt-3 pt-3 border-t border-border md:opacity-0 md:group-hover:opacity-100 transition-opacity">
                    <button
                        wire:click="openModal({{ $prescription['id'] }})"
                        class="flex-1 px-2 py-1 text-xs text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer flex items-center justify-center gap-1"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Rediger
                    </button>
                    <button
                        wire:click="delete({{ $prescription['id'] }})"
                        wire:confirm="Er du sikker på at du vil slette denne resepten?"
                        class="flex-1 px-2 py-1 text-xs text-muted-foreground hover:text-red-400 hover:bg-red-500/10 rounded transition-colors cursor-pointer flex items-center justify-center gap-1"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Slett
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-card border border-border rounded-lg p-12 text-center">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-accent/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <p class="text-muted-foreground">Ingen resepter registrert</p>
                    <button
                        wire:click="openModal"
                        class="mt-2 px-4 py-2 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                    >
                        Opprett din første resept
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
        <span class="font-medium text-foreground">Fargekoder:</span>
        <div class="flex items-center gap-1.5">
            <div class="w-2 h-2 rounded-full bg-accent"></div>
            <span>OK (30+ dager)</span>
        </div>
        <div class="flex items-center gap-1.5">
            <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
            <span>Snart (8-30 dager)</span>
        </div>
        <div class="flex items-center gap-1.5">
            <div class="w-2 h-2 rounded-full bg-red-500"></div>
            <span>Kritisk (≤7 dager)</span>
        </div>
    </div>

    {{-- Modal --}}
    @if($showModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.closeModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closeModal"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-md mx-4">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        {{ $editingId ? 'Rediger resept' : 'Opprett resept' }}
                    </h2>
                    <button
                        wire:click="closeModal"
                        class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Navn på resept *</label>
                        <input
                            type="text"
                            wire:model="name"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="F.eks. Aerius Tab 5mg"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Gyldig til *</label>
                        <div
                            x-data="{
                                value: $wire.entangle('validTo'),
                                get formatted() {
                                    if (!this.value) return 'Velg dato...';
                                    const d = new Date(this.value + 'T00:00:00');
                                    return d.toLocaleDateString('nb-NO', { day: '2-digit', month: '2-digit', year: 'numeric' });
                                }
                            }"
                            class="relative"
                        >
                            <div class="flex items-center justify-between w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground cursor-pointer">
                                <span x-text="formatted" :class="value ? 'text-foreground' : 'text-muted'"></span>
                                <svg class="w-5 h-5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input
                                type="date"
                                x-model="value"
                                class="datepicker-overlay"
                            >
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                    <x-button variant="secondary" wire:click="closeModal">Avbryt</x-button>
                    <x-button wire:click="save">{{ $editingId ? 'Lagre' : 'Opprett' }}</x-button>
                </div>
            </div>
        </div>
    @endif
</x-page-container>
