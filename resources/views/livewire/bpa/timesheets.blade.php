<div class="w-full flex flex-col">
    {{-- Header --}}
    <div class="flex flex-col xs:flex-row xs:items-center xs:justify-between gap-3 mb-4">
        <div class="flex items-center gap-2">
            <h1 class="text-xl xs:text-2xl font-bold text-foreground">Timelister</h1>
            <span class="text-sm text-muted">({{ $this->totalShiftCount }})</span>
        </div>

        <button
            wire:click="openCreateModal"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-accent text-black rounded-md hover:bg-accent-hover transition-colors cursor-pointer shrink-0"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Ny oppføring</span>
        </button>
    </div>

    {{-- Modal for Create/Edit --}}
    @if($showModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            x-on:keydown.escape.window="$wire.closeModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closeModal"
            ></div>

            {{-- Modal Content --}}
            <div class="relative bg-card border border-border rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                {{-- Header --}}
                <div class="flex items-center justify-between p-4 border-b border-border">
                    <h2 class="text-lg font-semibold text-foreground">
                        {{ $editingShiftId ? 'Rediger oppføring' : 'Ny timelistoppføring' }}
                    </h2>
                    <button
                        wire:click="closeModal"
                        class="p-1 text-muted hover:text-foreground transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-4 space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-muted mb-1">Assistent</label>
                            <select
                                wire:model="assistant_id"
                                class="w-full bg-input border border-border rounded-md px-3 py-2 text-foreground focus:ring-2 focus:ring-accent cursor-pointer"
                            >
                                <option value="">Velg assistent...</option>
                                @foreach($this->assistants as $assistant)
                                    <option value="{{ $assistant->id }}">{{ $assistant->name }}</option>
                                @endforeach
                            </select>
                            @error('assistant_id') <span class="text-sm text-destructive">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-muted mb-1">Dato</label>
                            <div
                                x-data="{
                                    value: $wire.entangle('date'),
                                    get formatted() {
                                        if (!this.value) return 'Velg dato...';
                                        const d = new Date(this.value + 'T00:00:00');
                                        return d.toLocaleDateString('nb-NO', { day: '2-digit', month: '2-digit', year: 'numeric' });
                                    }
                                }"
                                class="relative"
                            >
                                <div class="flex items-center justify-between w-full bg-input border border-border rounded-md px-3 py-2 text-foreground cursor-pointer">
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
                            @error('date') <span class="text-sm text-destructive">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-muted mb-1">Fra</label>
                            <div
                                x-data="{
                                    value: $wire.entangle('start_time'),
                                    disabled: $wire.entangle('is_all_day'),
                                    get formatted() {
                                        if (!this.value) return 'Velg tid...';
                                        return this.value;
                                    }
                                }"
                                class="relative"
                            >
                                <div
                                    class="flex items-center justify-between w-full bg-input border border-border rounded-md px-3 py-2 cursor-pointer transition-colors"
                                    :class="disabled ? 'opacity-50 cursor-not-allowed' : ''"
                                >
                                    <span x-text="formatted" :class="value ? 'text-foreground' : 'text-muted'"></span>
                                    <svg class="w-5 h-5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <input
                                    type="time"
                                    x-model="value"
                                    :disabled="disabled"
                                    class="datepicker-overlay disabled:cursor-not-allowed"
                                >
                            </div>
                            @error('start_time') <span class="text-sm text-destructive">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-muted mb-1">Til</label>
                            <div
                                x-data="{
                                    value: $wire.entangle('end_time'),
                                    disabled: $wire.entangle('is_all_day'),
                                    get formatted() {
                                        if (!this.value) return 'Velg tid...';
                                        return this.value;
                                    }
                                }"
                                class="relative"
                            >
                                <div
                                    class="flex items-center justify-between w-full bg-input border border-border rounded-md px-3 py-2 cursor-pointer transition-colors"
                                    :class="disabled ? 'opacity-50 cursor-not-allowed' : ''"
                                >
                                    <span x-text="formatted" :class="value ? 'text-foreground' : 'text-muted'"></span>
                                    <svg class="w-5 h-5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <input
                                    type="time"
                                    x-model="value"
                                    :disabled="disabled"
                                    class="datepicker-overlay disabled:cursor-not-allowed"
                                >
                            </div>
                            @error('end_time') <span class="text-sm text-destructive">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted mb-1">Beskrivelse</label>
                        <textarea
                            wire:model="note"
                            class="w-full bg-input border border-border rounded-md px-3 py-2 text-foreground focus:ring-2 focus:ring-accent"
                            rows="3"
                        ></textarea>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                wire:model.live="is_unavailable"
                                class="w-5 h-5 rounded border-border text-warning focus:ring-warning cursor-pointer"
                            >
                            <span class="text-sm text-muted">Borte / Utilgjengelig</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                wire:model.live="is_all_day"
                                class="w-5 h-5 rounded border-border text-accent focus:ring-accent cursor-pointer"
                            >
                            <span class="text-sm text-muted">Hele dagen</span>
                        </label>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-2 p-4 border-t border-border">
                    <button
                        wire:click="closeModal"
                        class="px-4 py-2 text-sm text-muted hover:text-foreground transition-colors cursor-pointer"
                    >
                        Avbryt
                    </button>
                    <button
                        wire:click="save"
                        class="px-4 py-2 bg-accent text-black text-sm rounded-md hover:opacity-90 transition-opacity cursor-pointer"
                    >
                        {{ $editingShiftId ? 'Oppdater' : 'Lagre oppføring' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Månedskort --}}
    <div class="flex gap-3 mb-4 overflow-x-auto pb-2">
        @foreach($this->monthSummaries as $summary)
            <div class="flex-shrink-0 bg-card border border-border rounded-lg p-4 min-w-[120px]">
                <div class="text-sm text-muted">{{ $summary['month'] }}</div>
                <div class="text-2xl font-bold {{ $summary['minutes'] > 0 ? 'text-accent' : 'text-muted' }}">{{ $summary['formatted'] }}</div>
                <div class="text-xs text-muted-foreground">timer</div>
            </div>
        @endforeach
        @if($this->totalShiftCount === 0)
            <div class="text-muted text-sm">Ingen data for valgt periode</div>
        @endif
    </div>

    {{-- År-filter, type-filter og per-side velger --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        {{-- År-filter --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-2 sm:pb-0">
            <div class="flex items-center bg-card border border-border rounded-md overflow-hidden shrink-0">
                <button
                    wire:click="setYear(null)"
                    class="px-3 py-1.5 text-sm transition-colors cursor-pointer {{ $selectedYear === null ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
                >
                    Alle
                </button>
                @foreach($this->availableYears as $year)
                    <button
                        wire:click="setYear({{ $year }})"
                        class="px-3 py-1.5 text-sm transition-colors cursor-pointer {{ $selectedYear === $year ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
                    >
                        {{ $year }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Type-filter og per-side velger --}}
        <div class="flex items-center gap-3 shrink-0">
            {{-- Type-filter --}}
            <div class="flex items-center bg-card border border-border rounded-md overflow-hidden shrink-0">
                <button
                    wire:click="setTypeFilter(null)"
                    class="px-3 py-1.5 text-sm transition-colors cursor-pointer {{ $typeFilter === null ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
                >
                    Alle typer
                </button>
                <button
                    wire:click="setTypeFilter('worked')"
                    class="px-3 py-1.5 text-sm transition-colors cursor-pointer {{ $typeFilter === 'worked' ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
                >
                    Jobbet
                </button>
                <button
                    wire:click="setTypeFilter('away')"
                    class="px-3 py-1.5 text-sm transition-colors cursor-pointer {{ $typeFilter === 'away' ? 'bg-warning text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
                >
                    Borte
                </button>
                <button
                    wire:click="setTypeFilter('fullday')"
                    class="px-3 py-1.5 text-sm transition-colors cursor-pointer {{ $typeFilter === 'fullday' ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
                >
                    Hel dag
                </button>
                <button
                    wire:click="setTypeFilter('archived')"
                    class="px-3 py-1.5 text-sm transition-colors cursor-pointer {{ $typeFilter === 'archived' ? 'bg-muted-foreground text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
                >
                    Arkivert
                </button>
            </div>

            {{-- Per-side velger --}}
            <div class="flex items-center gap-2">
                <span class="text-sm text-muted">Vis</span>
                <select
                    wire:model.live="perPage"
                    class="bg-card border border-border rounded-md px-2 py-1.5 text-sm text-foreground focus:ring-2 focus:ring-accent cursor-pointer"
                >
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="250">250</option>
                </select>
                <span class="text-sm text-muted">per side</span>
            </div>
        </div>
    </div>

    {{-- Timelistetabell --}}
    <div class="bg-card border border-border rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-card-hover/50 text-sm font-medium text-muted">
                    <tr>
                        <th class="px-4 py-3 text-left whitespace-nowrap">Assistent</th>
                        <th class="px-4 py-3 text-left whitespace-nowrap w-24">Dato</th>
                        <th class="px-4 py-3 text-left whitespace-nowrap w-20">Fra</th>
                        <th class="px-4 py-3 text-left whitespace-nowrap w-20">Til</th>
                        <th class="px-4 py-3 text-left whitespace-nowrap">Beskrivelse</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap w-20">Totalt</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap w-20">Borte</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap w-20">Hel dag</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap w-20">Arkivert</th>
                        <th class="px-4 py-3 w-24"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($this->shifts as $shift)
                        <tr wire:key="shift-{{ $shift->id }}" class="hover:bg-card-hover transition-colors {{ $shift->trashed() ? 'opacity-50' : '' }} {{ $shift->is_unavailable ? 'bg-warning/5' : '' }}">
                            <td class="px-4 py-3 font-medium text-foreground whitespace-nowrap">
                                {{ $shift->assistant?->name ?? 'Ukjent' }}
                            </td>
                            <td class="px-4 py-3 text-muted">{{ $shift->starts_at->format('d.m.Y') }}</td>
                            <td class="px-4 py-3 text-muted">{{ $shift->is_all_day ? '-' : $shift->starts_at->format('H:i') }}</td>
                            <td class="px-4 py-3 text-muted">{{ $shift->is_all_day ? '-' : $shift->ends_at->format('H:i') }}</td>
                            <td class="px-4 py-3 text-muted text-sm truncate max-w-[200px]">{{ $shift->note ?: '-' }}</td>
                            <td class="px-4 py-3 text-right font-medium text-foreground">{{ $shift->formatted_duration }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-center">
                                    <button wire:click="toggleField({{ $shift->id }}, 'away')" class="inline-flex cursor-pointer" title="Toggle borte">
                                        @if($shift->is_unavailable)
                                            <span class="w-5 h-5 rounded-full bg-warning/20 border border-warning/50 flex items-center justify-center">
                                                <svg class="w-3 h-3 text-warning" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        @else
                                            <span class="w-5 h-5 rounded-full bg-card-hover border border-border hover:border-warning/50 transition-colors"></span>
                                        @endif
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-center">
                                    <button wire:click="toggleField({{ $shift->id }}, 'fullDay')" class="inline-flex cursor-pointer" title="Toggle hel dag">
                                        @if($shift->is_all_day)
                                            <span class="w-5 h-5 rounded-full bg-accent/20 border border-accent/50 flex items-center justify-center">
                                                <svg class="w-3 h-3 text-accent" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        @else
                                            <span class="w-5 h-5 rounded-full bg-card-hover border border-border hover:border-accent/50 transition-colors"></span>
                                        @endif
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-center">
                                    <button wire:click="toggleArchived({{ $shift->id }})" class="inline-flex cursor-pointer" title="Toggle arkivert">
                                        @if($shift->trashed())
                                            <span class="w-5 h-5 rounded-full bg-muted-foreground/20 border border-muted-foreground/50 flex items-center justify-center">
                                                <svg class="w-3 h-3 text-muted-foreground" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        @else
                                            <span class="w-5 h-5 rounded-full bg-card-hover border border-border hover:border-muted-foreground/50 transition-colors"></span>
                                        @endif
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @unless($shift->trashed())
                                        <button
                                            wire:click="openEditModal({{ $shift->id }})"
                                            class="p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                                            title="Rediger"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                    @endunless
                                    @if($shift->trashed())
                                        <button
                                            wire:click="forceDelete({{ $shift->id }})"
                                            wire:confirm="Er du sikker på at du vil slette denne oppføringen permanent? Dette kan ikke angres."
                                            class="p-2 rounded-md text-muted hover:text-destructive hover:bg-card-hover transition-colors cursor-pointer"
                                            title="Slett permanent"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-8 text-center text-muted">
                                Ingen timelister registrert for valgt periode.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($this->shifts->isNotEmpty())
                    <tfoot class="bg-card-hover/30 border-t border-border">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-sm text-muted">
                                Viser {{ $this->shifts->firstItem() }}-{{ $this->shifts->lastItem() }} av {{ $this->totalEntryCount }} oppføringer
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="text-xs text-muted">Sum (totalt)</div>
                                <div class="font-bold text-accent">{{ $this->totalSum }}</div>
                            </td>
                            <td colspan="4" class="px-4 py-3 text-left">
                                <div class="text-xs text-muted">Snitt</div>
                                <div class="font-medium text-foreground">{{ $this->averageTime }}</div>
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Pagination --}}
        @if($this->shifts->hasPages())
            <div class="px-4 py-3 border-t border-border">
                {{ $this->shifts->links() }}
            </div>
        @endif
    </div>
</div>
