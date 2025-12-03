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
                {{-- Slett-knapp (kun for eksisterende vakter) --}}
                <div>
                    @if($editingShiftId)
                        <button
                            wire:click="deleteShift"
                            wire:confirm="Er du sikker på at du vil slette denne vakten?"
                            class="px-4 py-2 text-sm font-medium text-destructive hover:text-white hover:bg-destructive rounded-lg transition-colors cursor-pointer"
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
                    <button
                        wire:click="saveShift"
                        class="px-5 py-2 text-sm font-semibold text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                    >
                        {{ $editingShiftId ? 'Lagre' : 'Opprett' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
