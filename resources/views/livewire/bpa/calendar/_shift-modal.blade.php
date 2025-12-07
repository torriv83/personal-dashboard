{{-- Modal: Opprett/Rediger vakt --}}
@if($showModal)
<div
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="$wire.closeModal()"
    class="fixed inset-0 z-50 overflow-y-auto"
>
    {{-- Backdrop --}}
    <div
        wire:click="closeModal"
        class="fixed inset-0 bg-background/90 backdrop-blur-sm cursor-pointer"
    ></div>

    {{-- Modal innhold --}}
    <div
        class="relative min-h-screen flex items-center justify-center p-4"
    >
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            @click.stop
            class="relative w-full max-w-md bg-card border border-border rounded-xl shadow-2xl"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-border">
                <h2 class="text-lg font-semibold text-foreground">
                    {{ $editingShiftId ? 'Rediger vakt' : 'Opprett vakt' }}
                </h2>
                <button
                    wire:click="closeModal"
                    class="p-1.5 rounded-lg text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-5 space-y-5">
                {{-- Validation errors --}}
                @if($errors->any())
                    <div class="p-3 bg-destructive/10 border border-destructive/30 rounded-lg">
                        <ul class="text-sm text-destructive space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Assistent-seksjon --}}
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-medium text-foreground">Assistent</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Velg hvem som skal jobbe</p>
                    </div>

                    {{-- Assistent dropdown --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">
                            Hvem <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <select
                                wire:model="assistantId"
                                class="w-full bg-input border border-border rounded-lg px-3 py-2.5 text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent transition-colors appearance-none cursor-pointer"
                            >
                                <option value="">Velg assistent...</option>
                                @foreach($this->assistants as $assistant)
                                    @php $isUnavailable = in_array($assistant->id, $this->unavailableAssistantIds); @endphp
                                    <option
                                        value="{{ $assistant->id }}"
                                        @if($isUnavailable) disabled class="text-muted" @endif
                                    >{{ $assistant->name }}@if($isUnavailable) (Borte)@endif</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        @if(count($this->unavailableAssistantIds) > 0 && !$this->isUnavailable)
                            <p class="text-xs text-muted mt-1.5">Assistenter markert med (Borte) er utilgjengelige på valgt tid</p>
                        @endif
                    </div>

                    {{-- Checkboxes --}}
                    <div class="flex items-center gap-6">
                        <label class="inline-flex items-center gap-2.5 cursor-pointer group">
                            <input
                                type="checkbox"
                                wire:model.live="isUnavailable"
                                class="w-5 h-5 rounded border-border text-destructive focus:ring-destructive/50 cursor-pointer"
                            >
                            <span class="text-sm text-foreground select-none">Ikke tilgjengelig</span>
                        </label>

                        <label class="inline-flex items-center gap-2.5 cursor-pointer group">
                            <input
                                type="checkbox"
                                wire:model.live="isAllDay"
                                class="w-5 h-5 rounded border-border text-accent focus:ring-accent/50 cursor-pointer"
                            >
                            <span class="text-sm text-foreground select-none">Hele dagen</span>
                        </label>
                    </div>

                    {{-- Eksisterende gjentakende indikator --}}
                    @if($isExistingRecurring && $editingShiftId)
                        <div class="mt-4 p-3 bg-destructive/10 rounded-lg border border-destructive/30">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-destructive shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span class="text-sm font-medium text-destructive">Del av gjentakende serie</span>
                            </div>
                            <p class="text-xs text-muted mt-1.5">
                                Endringer i denne vakten kan påvirke andre vakter i serien.
                            </p>
                        </div>
                    @endif

                    {{-- Gjentakende (kun for "Ikke tilgjengelig") --}}
                    <div
                        x-show="$wire.isUnavailable && !{{ $isExistingRecurring ? 'true' : 'false' }}"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        class="mt-4 p-4 bg-card-hover rounded-lg border border-border space-y-4"
                    >
                        <label class="inline-flex items-center gap-2.5 cursor-pointer">
                            <input
                                type="checkbox"
                                wire:model.live="isRecurring"
                                class="w-5 h-5 rounded border-border text-accent focus:ring-accent/50 cursor-pointer"
                            >
                            <span class="text-sm font-medium text-foreground select-none">Gjentakende</span>
                        </label>

                        @if($isRecurring)
                            <div class="space-y-4 pt-2">
                                @if($editingShiftId)
                                    <p class="text-xs text-muted-foreground">
                                        Oppretter nye gjentakende oppføringer basert på denne vakten.
                                    </p>
                                @endif

                                {{-- Intervall --}}
                                <div>
                                    <label class="block text-sm font-medium text-foreground mb-1.5">Gjenta</label>
                                    <select
                                        wire:model.live="recurringInterval"
                                        class="w-full bg-input border border-border rounded-lg px-3 py-2 text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent transition-colors appearance-none cursor-pointer"
                                    >
                                        <option value="weekly">Ukentlig</option>
                                        <option value="biweekly">Hver 2. uke</option>
                                        <option value="monthly">Månedlig</option>
                                    </select>
                                </div>

                                {{-- Avslutt --}}
                                <div class="space-y-3">
                                    <label class="block text-sm font-medium text-foreground">Avslutt</label>

                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input
                                            type="radio"
                                            wire:model.live="recurringEndType"
                                            value="count"
                                            class="w-4 h-4 text-accent focus:ring-accent/50 cursor-pointer"
                                        >
                                        <span class="text-sm text-foreground">Etter</span>
                                        <input
                                            type="number"
                                            wire:model.live="recurringCount"
                                            min="1"
                                            max="52"
                                            class="w-16 bg-input border border-border rounded-lg px-2 py-1 text-foreground text-center focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent"
                                            @if($recurringEndType !== 'count') disabled @endif
                                        >
                                        <span class="text-sm text-foreground">ganger</span>
                                    </label>

                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input
                                            type="radio"
                                            wire:model.live="recurringEndType"
                                            value="date"
                                            class="w-4 h-4 text-accent focus:ring-accent/50 cursor-pointer"
                                        >
                                        <span class="text-sm text-foreground">På dato</span>
                                        <input
                                            type="date"
                                            wire:model.live="recurringEndDate"
                                            class="bg-input border border-border rounded-lg px-2 py-1 text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent cursor-pointer"
                                            @if($recurringEndType !== 'date') disabled @endif
                                        >
                                    </label>
                                </div>

                                {{-- Forhåndsvisning --}}
                                @php
                                    $previewDates = $this->getRecurringPreviewDates();
                                    // Skip first date when editing (it's the current shift)
                                    if ($editingShiftId && count($previewDates) > 0) {
                                        $previewDates = array_slice($previewDates, 1);
                                    }
                                @endphp
                                @if(count($previewDates) > 0)
                                    <div class="pt-2 border-t border-border">
                                        <p class="text-xs text-muted mb-2">
                                            Forhåndsvisning: {{ count($previewDates) }} {{ $editingShiftId ? 'nye' : '' }} oppføringer
                                        </p>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach(array_slice($previewDates, 0, 8) as $date)
                                                <span class="text-xs px-2 py-1 bg-destructive/20 text-destructive rounded">
                                                    {{ \Carbon\Carbon::parse($date)->format('d.m') }}
                                                </span>
                                            @endforeach
                                            @if(count($previewDates) > 8)
                                                <span class="text-xs px-2 py-1 bg-muted/20 text-muted rounded">
                                                    +{{ count($previewDates) - 8 }} til
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @elseif($editingShiftId)
                                    <div class="pt-2 border-t border-border">
                                        <p class="text-xs text-muted">
                                            Velg flere ganger for å opprette nye oppføringer
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Separator --}}
                <div class="border-t border-border"></div>

                {{-- Tid-seksjon --}}
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-medium text-foreground">Tid</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Velg start og slutt</p>
                    </div>

                    {{-- Dato og tid --}}
                    <div class="grid grid-cols-2 gap-3">
                        {{-- Fra dato --}}
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">
                                Fra <span class="text-destructive">*</span>
                            </label>
                            <div
                                x-data="{
                                    value: $wire.entangle('fromDate'),
                                    get formatted() {
                                        if (!this.value) return 'Velg dato...';
                                        const d = new Date(this.value + 'T00:00:00');
                                        return d.toLocaleDateString('nb-NO', { day: '2-digit', month: '2-digit', year: 'numeric' });
                                    }
                                }"
                                class="relative"
                            >
                                <div class="flex items-center justify-between w-full bg-input border border-border rounded-lg px-3 py-2.5 text-foreground cursor-pointer">
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

                        {{-- Fra tid --}}
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">
                                Kl <span class="text-destructive">*</span>
                            </label>
                            <div
                                x-data="{
                                    value: $wire.entangle('fromTime'),
                                    disabled: $wire.entangle('isAllDay'),
                                    get formatted() {
                                        if (!this.value) return 'Velg tid...';
                                        return this.value;
                                    }
                                }"
                                class="relative"
                            >
                                <div
                                    class="flex items-center justify-between w-full bg-input border border-border rounded-lg px-3 py-2.5 cursor-pointer transition-colors"
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
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        {{-- Til dato --}}
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">
                                Til <span class="text-destructive">*</span>
                            </label>
                            <div
                                x-data="{
                                    value: $wire.entangle('toDate'),
                                    get formatted() {
                                        if (!this.value) return 'Velg dato...';
                                        const d = new Date(this.value + 'T00:00:00');
                                        return d.toLocaleDateString('nb-NO', { day: '2-digit', month: '2-digit', year: 'numeric' });
                                    }
                                }"
                                class="relative"
                            >
                                <div class="flex items-center justify-between w-full bg-input border border-border rounded-lg px-3 py-2.5 text-foreground cursor-pointer">
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

                        {{-- Til tid --}}
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">
                                Kl <span class="text-destructive">*</span>
                            </label>
                            <div
                                x-data="{
                                    value: $wire.entangle('toTime'),
                                    disabled: $wire.entangle('isAllDay'),
                                    get formatted() {
                                        if (!this.value) return 'Velg tid...';
                                        return this.value;
                                    }
                                }"
                                class="relative"
                            >
                                <div
                                    class="flex items-center justify-between w-full bg-input border border-border rounded-lg px-3 py-2.5 cursor-pointer transition-colors"
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
                        </div>
                    </div>

                    {{-- Total tid --}}
                    <div class="flex items-center justify-between py-3 px-4 bg-card-hover rounded-lg">
                        <span class="text-sm text-muted">Total tid</span>
                        <span
                            class="text-sm font-semibold"
                            :class="totalTime === 'Ugyldig tid' ? 'text-destructive' : 'text-accent'"
                            x-text="totalTime || '-'"
                        ></span>
                    </div>

                    {{-- Notat --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">
                            Notat
                        </label>
                        <textarea
                            wire:model="note"
                            rows="2"
                            placeholder="Valgfritt notat..."
                            class="w-full bg-input border border-border rounded-lg px-3 py-2.5 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent transition-colors resize-none"
                        ></textarea>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between px-5 py-4 border-t border-border">
                {{-- Slett/Arkiver-knapper (kun for eksisterende vakter) --}}
                <div class="flex items-center gap-2">
                    @if($editingShiftId)
                        @php
                            $editingShift = \App\Models\Shift::find($editingShiftId);
                            $isRecurringShift = $editingShift && $editingShift->isRecurring();
                        @endphp
                        <button
                            wire:click="archiveShift"
                            @unless($isRecurringShift)
                                wire:confirm="Er du sikker på at du vil arkivere denne oppføringen?"
                            @endunless
                            class="px-4 py-2 text-sm font-medium text-warning hover:text-white hover:bg-warning rounded-lg transition-colors cursor-pointer"
                            title="Arkiver (kan gjenopprettes)"
                        >
                            Arkiver
                        </button>
                        <button
                            wire:click="deleteShift"
                            @unless($isRecurringShift)
                                wire:confirm="Er du sikker på at du vil slette denne oppføringen permanent?"
                            @endunless
                            class="px-4 py-2 text-sm font-medium text-destructive hover:text-white hover:bg-destructive rounded-lg transition-colors cursor-pointer"
                            title="Slett permanent"
                        >
                            Slett
                        </button>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <button
                        wire:click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-muted hover:text-foreground transition-colors cursor-pointer"
                    >
                        Avbryt
                    </button>
                    @unless($editingShiftId)
                        <button
                            wire:click="saveShift(true)"
                            class="px-4 py-2 text-sm font-medium text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                        >
                            Opprett & ny
                        </button>
                    @endunless
                    @if($editingShiftId && isset($isRecurringShift) && $isRecurringShift)
                        <button
                            wire:click="initiateEditRecurring"
                            class="px-5 py-2 text-sm font-semibold text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                        >
                            Lagre
                        </button>
                    @else
                        <button
                            wire:click="saveShift"
                            class="px-5 py-2 text-sm font-semibold text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                        >
                            {{ $editingShiftId ? 'Lagre' : 'Opprett' }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Recurring Action Dialog --}}
@if($showRecurringDialog)
<div
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="$wire.closeRecurringDialog()"
    class="fixed inset-0 z-[60] overflow-y-auto"
>
    {{-- Backdrop --}}
    <div
        wire:click="closeRecurringDialog"
        class="fixed inset-0 bg-background/95 backdrop-blur-sm cursor-pointer"
    ></div>

    {{-- Dialog --}}
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop
            class="relative w-full max-w-sm bg-card border border-border rounded-xl shadow-2xl"
        >
            {{-- Header --}}
            <div class="px-5 py-4 border-b border-border">
                <h3 class="text-lg font-semibold text-foreground">
                    @if($recurringAction === 'delete')
                        Slett gjentakende oppføring
                    @elseif($recurringAction === 'archive')
                        Arkiver gjentakende oppføring
                    @elseif($recurringAction === 'move')
                        Flytt gjentakende oppføring
                    @else
                        Rediger gjentakende oppføring
                    @endif
                </h3>
                <p class="text-sm text-muted mt-1">
                    Denne oppføringen er del av en gjentakende serie.
                </p>
            </div>

            @php
                $actionMethod = match($recurringAction) {
                    'delete' => 'confirmDeleteShift',
                    'archive' => 'confirmArchiveShift',
                    'move' => 'confirmMoveRecurring',
                    default => 'confirmEditRecurring',
                };
                $actionLabel = match($recurringAction) {
                    'delete' => 'Slett',
                    'archive' => 'Arkiver',
                    'move' => 'Flytt',
                    default => 'Endre',
                };
            @endphp

            {{-- Options --}}
            <div class="p-5 space-y-3">
                <button
                    wire:click="{{ $actionMethod }}('single')"
                    class="w-full text-left px-4 py-3 rounded-lg border border-border hover:bg-card-hover transition-colors cursor-pointer"
                >
                    <span class="block text-sm font-medium text-foreground">Kun denne</span>
                    <span class="block text-xs text-muted mt-0.5">{{ $actionLabel }} bare denne ene oppføringen</span>
                </button>

                <button
                    wire:click="{{ $actionMethod }}('future')"
                    class="w-full text-left px-4 py-3 rounded-lg border border-border hover:bg-card-hover transition-colors cursor-pointer"
                >
                    <span class="block text-sm font-medium text-foreground">Denne og fremtidige</span>
                    <span class="block text-xs text-muted mt-0.5">{{ $actionLabel }} denne og alle fremtidige i serien</span>
                </button>

                <button
                    wire:click="{{ $actionMethod }}('all')"
                    class="w-full text-left px-4 py-3 rounded-lg border border-border hover:bg-card-hover transition-colors cursor-pointer"
                >
                    <span class="block text-sm font-medium text-foreground">Alle i serien</span>
                    <span class="block text-xs text-muted mt-0.5">{{ $actionLabel }} alle oppføringer i denne serien</span>
                </button>
            </div>

            {{-- Footer --}}
            <div class="px-5 py-4 border-t border-border">
                <button
                    wire:click="closeRecurringDialog"
                    class="w-full px-4 py-2 text-sm font-medium text-muted hover:text-foreground transition-colors cursor-pointer"
                >
                    Avbryt
                </button>
            </div>
        </div>
    </div>
</div>
@endif
