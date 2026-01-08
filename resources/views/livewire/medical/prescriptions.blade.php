<x-page-container class="space-y-6" data-prescriptions-component>
    {{-- Context Menu --}}
    @include('livewire.medical.prescriptions._context-menu')

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Resepter</h1>
            <p class="text-sm text-muted-foreground mt-1">Oversikt over resepter og utløpsdatoer</p>
        </div>
        <button
            wire:click="openModal"
            class="text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer flex items-center justify-center gap-2 p-2.5 sm:px-4 sm:py-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="hidden sm:inline">Opprett resept</span>
        </button>
    </div>

    {{-- Prescription Cards (Mobile) --}}
    <div class="md:hidden space-y-3">
        @forelse($this->prescriptions as $prescription)
            <div
                wire:key="prescription-mobile-{{ $prescription['id'] }}"
                class="bg-card border border-border rounded-lg p-4"
                @contextmenu.prevent="
                    const x = Math.min($event.clientX, window.innerWidth - 200);
                    const y = Math.min($event.clientY, window.innerHeight - 150);
                    $store.prescriptionMenu.open(x, y, {{ $prescription['id'] }})
                "
            >
                {{-- Header: Navn + Actions --}}
                <div class="flex items-start justify-between gap-2 mb-1">
                    <h3 class="font-medium text-foreground">{{ $prescription['name'] }}</h3>
                    <div class="flex items-center gap-1 shrink-0">
                        <button
                            wire:click="openModal({{ $prescription['id'] }})"
                            class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer"
                            title="Rediger"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button
                            wire:click="delete({{ $prescription['id'] }})"
                            wire:confirm="Er du sikker på at du vil slette denne resepten?"
                            class="p-1.5 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded transition-colors cursor-pointer"
                            title="Slett"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Gyldig til --}}
                <p class="text-sm text-muted-foreground">
                    Gyldig til: {{ \Carbon\Carbon::parse($prescription['validTo'])->format('d.m.Y') }}
                </p>

                {{-- Dager igjen + Status --}}
                <div class="flex items-center gap-3 mt-2">
                    @if($prescription['status'] === 'expired')
                        <span class="text-sm text-destructive">
                            {{ $prescription['daysLeft'] }} dager siden
                        </span>
                        <span class="inline-flex px-2 py-1 text-xs font-medium bg-destructive/20 text-destructive rounded">
                            Utløpt
                        </span>
                    @else
                        <span class="text-sm
                            @if($prescription['status'] === 'danger') text-destructive
                            @elseif($prescription['status'] === 'warning') text-yellow-400
                            @else text-accent @endif">
                            {{ $prescription['daysLeft'] }} dager igjen
                        </span>
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded
                            @if($prescription['status'] === 'danger') bg-destructive/20 text-destructive
                            @elseif($prescription['status'] === 'warning') bg-yellow-500/10 text-yellow-400
                            @else bg-accent/10 text-accent @endif">
                            @if($prescription['status'] === 'danger') Kritisk
                            @elseif($prescription['status'] === 'warning') Snart
                            @else OK @endif
                        </span>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-card border border-border rounded-lg p-8 text-center text-muted-foreground">
                <div class="flex flex-col items-center gap-2">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p>Ingen resepter funnet</p>
                </div>
            </div>
        @endforelse

        {{-- Mobile Footer --}}
        <div class="text-sm text-muted-foreground text-center py-2">
            Viser {{ count($this->prescriptions) }} resepter
        </div>
    </div>

    {{-- Prescription Table (Desktop) --}}
    <div class="hidden md:block bg-card border border-border rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-border bg-card-hover/50">
                        <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Navn</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Gyldig til</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Dager igjen</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Handlinger</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($this->prescriptions as $prescription)
                        <tr
                            wire:key="prescription-{{ $prescription['id'] }}"
                            class="hover:bg-card-hover transition-colors"
                            @contextmenu.prevent="
                                const x = Math.min($event.clientX, window.innerWidth - 200);
                                const y = Math.min($event.clientY, window.innerHeight - 150);
                                $store.prescriptionMenu.open(x, y, {{ $prescription['id'] }})
                            "
                        >
                            <td class="px-5 py-4 text-sm text-foreground font-medium">{{ $prescription['name'] }}</td>
                            <td class="px-5 py-4 text-sm text-foreground">
                                {{ \Carbon\Carbon::parse($prescription['validTo'])->format('d.m.Y') }}
                            </td>
                            <td class="px-5 py-4 text-sm">
                                <span class="font-medium
                                    @if($prescription['status'] === 'expired') text-destructive
                                    @elseif($prescription['status'] === 'danger') text-destructive
                                    @elseif($prescription['status'] === 'warning') text-yellow-400
                                    @else text-accent @endif">
                                    {{ $prescription['daysLeft'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded
                                    @if($prescription['status'] === 'expired') bg-destructive/20 text-destructive
                                    @elseif($prescription['status'] === 'danger') bg-destructive/20 text-destructive
                                    @elseif($prescription['status'] === 'warning') bg-yellow-500/10 text-yellow-400
                                    @else bg-accent/10 text-accent @endif">
                                    @if($prescription['status'] === 'expired') Utløpt
                                    @elseif($prescription['status'] === 'danger') Kritisk
                                    @elseif($prescription['status'] === 'warning') Snart
                                    @else OK @endif
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        wire:click="openModal({{ $prescription['id'] }})"
                                        class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer"
                                        title="Rediger"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        wire:click="delete({{ $prescription['id'] }})"
                                        wire:confirm="Er du sikker på at du vil slette denne resepten?"
                                        class="p-1.5 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded transition-colors cursor-pointer"
                                        title="Slett"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-muted-foreground">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p>Ingen resepter funnet</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Table Footer --}}
        <div class="px-5 py-3 border-t border-border flex items-center justify-between">
            <p class="text-sm text-muted-foreground">
                Viser {{ count($this->prescriptions) }} resepter
            </p>
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
