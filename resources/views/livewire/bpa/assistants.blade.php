<x-page-container class="w-full h-full flex flex-col" data-assistants-component>
    {{-- Context Menu --}}
    @include('livewire.bpa.assistants._context-menu')

    {{-- Header --}}
    <div class="flex flex-col xs:flex-row xs:items-center xs:justify-between gap-3 mb-4">
        <div class="flex items-center gap-2">
            <h1 class="text-xl xs:text-2xl font-bold text-foreground">Assistenter</h1>
            <span class="text-sm text-muted">({{ $this->activeCount }})</span>
        </div>

        <button 
            x-on:click="$dispatch('open-modal', 'add-assistant')"
            class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 bg-accent text-black rounded-md hover:bg-accent-hover transition-colors cursor-pointer shrink-0"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="hidden sm:inline">Legg til assistent</span>
        </button>
    </div>

    {{-- Filter toggle --}}
    <div class="flex items-center gap-2 mb-4">
        <div class="flex items-center bg-card border border-border rounded-md overflow-hidden">
            <button
                wire:click="$set('showAll', false)"
                class="px-3 py-1.5 text-sm transition-colors cursor-pointer {{ !$showAll ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
            >
                Aktive
            </button>
            <button
                wire:click="$set('showAll', true)"
                class="px-3 py-1.5 text-sm transition-colors cursor-pointer {{ $showAll ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
            >
                Alle ({{ $this->totalCount }})
            </button>
        </div>
    </div>

    {{-- Assistentliste - Kort på mobil, tabell på md+ --}}
    <div class="bg-card border border-border rounded-lg overflow-hidden">

        {{-- Desktop: Tabell-header (skjult på mobil) --}}
        <div class="hidden md:grid grid-cols-[1fr_8rem_12rem_10rem_8rem_5rem] gap-4 px-4 py-3 border-b border-border bg-card-hover/50 text-sm font-medium text-muted">
            <div>Navn</div>
            <div>Type</div>
            <div>E-post</div>
            <div>Telefon</div>
            <div>Ansatt</div>
            <div></div>
        </div>

        {{-- Innhold --}}
        <div class="divide-y divide-border">
            @forelse($this->assistants as $index => $assistant)
                @php
                    $isDeleted = $assistant->trashed();
                    $typeClasses = match($assistant->type) {
                        'primary' => 'bg-accent/10 text-accent border-accent/30',
                        'substitute' => 'bg-card-hover text-muted border-border',
                        'oncall' => 'bg-card-hover text-muted-foreground border-border',
                        default => 'bg-card-hover text-muted border-border',
                    };
                    // Check if this is the first deleted assistant (for separator)
                    $showSeparator = $showAll && $isDeleted && ($index === 0 || !$this->assistants[$index - 1]->trashed());
                @endphp

                {{-- Separator between active and deleted --}}
                @if($showSeparator)
                    <div class="px-4 py-2 bg-card-hover/30 border-y border-border">
                        <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Avsluttede ansatte</span>
                    </div>
                @endif

                {{-- Mobil: Kort-layout --}}
                <div
                    class="md:hidden p-4 hover:bg-card-hover transition-colors {{ $isDeleted ? 'opacity-60' : '' }}"
                    @contextmenu.prevent="
                        const x = Math.min($event.clientX, window.innerWidth - 200);
                        const y = Math.min($event.clientY, window.innerHeight - 200);
                        $store.assistantMenu.open(x, y, {{ $assistant->id }}, {{ $isDeleted ? 'true' : 'false' }})
                    "
                >
                    {{-- Rad 1: Avatar + Navn + Handlinger --}}
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full {{ $isDeleted ? 'bg-muted-foreground/20 border-muted-foreground/30 text-muted-foreground' : 'bg-accent/20 border-accent/30 text-accent' }} border flex items-center justify-center text-sm font-medium shrink-0">
                            {{ $assistant->initials }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                @if($isDeleted)
                                    <span class="font-medium text-muted">{{ $assistant->name }}</span>
                                @else
                                    <a href="{{ route('bpa.assistants.show', $assistant) }}" class="font-medium text-foreground hover:text-accent transition-colors cursor-pointer">{{ $assistant->name }}</a>
                                @endif
                                <span class="text-xs text-muted-foreground">{{ $assistant->formatted_number }}</span>
                            </div>
                        </div>
                        {{-- Handlinger --}}
                        <div class="flex items-center gap-1 shrink-0">
                            @if($isDeleted)
                                <button
                                    wire:click="restoreAssistant({{ $assistant->id }})"
                                    class="p-2 rounded-md text-muted hover:text-accent hover:bg-card-hover transition-colors cursor-pointer"
                                    title="Gjenaktiver"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </button>
                                <button
                                    wire:click="forceDeleteAssistant({{ $assistant->id }})"
                                    wire:confirm="Er du sikker på at du vil slette {{ $assistant->name }} permanent? Dette kan ikke angres."
                                    class="p-2 rounded-md text-muted hover:text-destructive hover:bg-card-hover transition-colors cursor-pointer"
                                    title="Slett permanent"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @else
                                <button
                                    wire:click="editAssistant({{ $assistant->id }})"
                                    class="p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                                    title="Rediger"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <button
                                    wire:click="deleteAssistant({{ $assistant->id }})"
                                    wire:confirm="Er du sikker på at du vil avslutte arbeidsforholdet?"
                                    class="p-2 rounded-md text-muted hover:text-destructive hover:bg-card-hover transition-colors cursor-pointer"
                                    title="Avslutt arbeidsforhold"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Rad 2+: Innhold med full bredde --}}
                    <div class="mt-2 pl-13">
                        <div class="flex items-center gap-2 mb-1">
                            @if($isDeleted)
                                <span class="px-2 py-0.5 text-xs rounded-md border bg-destructive/10 text-destructive border-destructive/30">
                                    Avsluttet
                                </span>
                            @else
                                <span class="px-2 py-0.5 text-xs rounded-md border {{ $typeClasses }}">
                                    {{ $assistant->type_label }}
                                </span>
                            @endif
                        </div>
                        <div class="text-sm text-muted">{{ $assistant->email }}</div>
                        <div class="mt-2 flex items-start gap-6 text-sm">
                            <div>
                                <div class="text-xs text-muted-foreground">Telefon</div>
                                <div class="text-muted whitespace-nowrap">{{ $assistant->phone ?? '-' }}</div>
                            </div>
                            <div>
                                @if($isDeleted)
                                    <div class="text-xs text-muted-foreground">Avsluttet</div>
                                    <div class="text-destructive whitespace-nowrap">{{ $assistant->deleted_at->format('d.m.Y') }}</div>
                                @else
                                    <div class="text-xs text-muted-foreground">Ansatt</div>
                                    <div class="text-muted whitespace-nowrap">{{ $assistant->hired_at->format('d.m.Y') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Desktop: Tabell-rad --}}
                <div
                    class="hidden md:grid grid-cols-[1fr_8rem_12rem_10rem_8rem_5rem] gap-4 px-4 py-3 items-center hover:bg-card-hover transition-colors {{ $isDeleted ? 'opacity-60' : '' }}"
                    @contextmenu.prevent="
                        const x = Math.min($event.clientX, window.innerWidth - 200);
                        const y = Math.min($event.clientY, window.innerHeight - 200);
                        $store.assistantMenu.open(x, y, {{ $assistant->id }}, {{ $isDeleted ? 'true' : 'false' }})
                    "
                >
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full {{ $isDeleted ? 'bg-muted-foreground/20 border-muted-foreground/30 text-muted-foreground' : 'bg-accent/20 border-accent/30 text-accent' }} border flex items-center justify-center text-sm font-medium">
                            {{ $assistant->initials }}
                        </div>
                        <div>
                            @if($isDeleted)
                                <div class="font-medium text-muted">{{ $assistant->name }}</div>
                            @else
                                <a href="{{ route('bpa.assistants.show', $assistant) }}" class="font-medium text-foreground hover:text-accent transition-colors cursor-pointer block">{{ $assistant->name }}</a>
                            @endif
                            <div class="text-xs text-muted-foreground">{{ $assistant->formatted_number }}</div>
                        </div>
                    </div>
                    <div>
                        @if($isDeleted)
                            <span class="px-2.5 py-1 text-xs rounded-md border bg-destructive/10 text-destructive border-destructive/30">
                                Avsluttet
                            </span>
                        @else
                            <span class="px-2.5 py-1 text-xs rounded-md border {{ $typeClasses }}">
                                {{ $assistant->type_label }}
                            </span>
                        @endif
                    </div>
                    <div class="text-muted text-sm truncate">{{ $assistant->email }}</div>
                    <div class="text-muted">{{ $assistant->phone ?? '-' }}</div>
                    <div class="text-muted text-sm">
                        @if($isDeleted)
                            <span class="text-destructive">{{ $assistant->deleted_at->format('d.m.Y') }}</span>
                        @else
                            {{ $assistant->hired_at->format('d.m.Y') }}
                        @endif
                    </div>
                    <div class="flex items-center justify-end gap-1">
                        @if($isDeleted)
                            <button
                                wire:click="restoreAssistant({{ $assistant->id }})"
                                class="p-2 rounded-md text-muted hover:text-accent hover:bg-card-hover transition-colors cursor-pointer"
                                title="Gjenaktiver"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                            <button
                                wire:click="forceDeleteAssistant({{ $assistant->id }})"
                                wire:confirm="Er du sikker på at du vil slette {{ $assistant->name }} permanent? Dette kan ikke angres."
                                class="p-2 rounded-md text-muted hover:text-destructive hover:bg-card-hover transition-colors cursor-pointer"
                                title="Slett permanent"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        @else
                            <button
                                wire:click="editAssistant({{ $assistant->id }})"
                                class="p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                                title="Rediger"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                            <button
                                wire:click="deleteAssistant({{ $assistant->id }})"
                                wire:confirm="Er du sikker på at du vil avslutte arbeidsforholdet?"
                                class="p-2 rounded-md text-muted hover:text-destructive hover:bg-card-hover transition-colors cursor-pointer"
                                title="Avslutt arbeidsforhold"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-muted">
                    @if($showAll)
                        Ingen assistenter registrert enda.
                    @else
                        Ingen aktive assistenter. <button wire:click="$set('showAll', true)" class="text-accent hover:underline cursor-pointer">Vis alle</button>
                    @endif
                </div>
            @endforelse
        </div>
    </div>

    {{-- Add Assistant Modal --}}
    <x-modal name="add-assistant" title="Legg til ny assistent" maxWidth="xl">
        <div class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Navn <span class="text-destructive">*</span></label>
                    <input type="text" wire:model="createName" class="w-full bg-input border border-border rounded-md px-3 py-2 text-foreground focus:ring-2 focus:ring-accent" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Assistent nummer <span class="text-destructive">*</span></label>
                    <input type="number" wire:model="createEmployeeNumber" class="w-full bg-input border border-border rounded-md px-3 py-2 text-foreground focus:ring-2 focus:ring-accent" required>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">E-post <span class="text-destructive">*</span></label>
                    <input type="email" wire:model="createEmail" class="w-full bg-input border border-border rounded-md px-3 py-2 text-foreground focus:ring-2 focus:ring-accent" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Telefon</label>
                    <input type="tel" wire:model="createPhone" class="w-full bg-input border border-border rounded-md px-3 py-2 text-foreground focus:ring-2 focus:ring-accent">
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Type <span class="text-destructive">*</span></label>
                    <select wire:model="createType" class="w-full bg-input border border-border rounded-md px-3 py-2 text-foreground focus:ring-2 focus:ring-accent" required>
                        <option value="primary">Fast ansatt</option>
                        <option value="substitute">Vikar</option>
                        <option value="oncall">Tilkalling</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Ansatt dato <span class="text-destructive">*</span></label>
                    <div
                        x-data="{
                            value: $wire.entangle('createHiredAt'),
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
                            required
                        >
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-foreground">Månedlig e-postrapport</p>
                    <p class="text-xs text-muted">Send oversikt over arbeidstimer ved månedsslutt</p>
                </div>
                <button
                    type="button"
                    wire:click="$toggle('createSendMonthlyReport')"
                    class="relative w-12 h-7 rounded-full transition-colors cursor-pointer {{ $createSendMonthlyReport ? 'bg-accent' : 'bg-border' }}"
                >
                    <span class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-transform {{ $createSendMonthlyReport ? 'translate-x-5' : '' }}"></span>
                </button>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <button
                    x-on:click="$dispatch('close-modal', 'add-assistant')"
                    class="px-4 py-2 text-sm text-muted hover:text-foreground transition-colors cursor-pointer"
                >
                    Avbryt
                </button>
                <button wire:click="createAssistant" class="px-4 py-2 bg-accent text-black text-sm rounded-md hover:opacity-90 transition-opacity cursor-pointer">
                    Lagre assistent
                </button>
            </div>
        </div>
    </x-modal>

    {{-- Edit Assistant Modal --}}
    <x-modal name="edit-assistant" title="Rediger assistent" maxWidth="xl">
        <div class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Navn <span class="text-destructive">*</span></label>
                    <input type="text" wire:model="editName" class="w-full bg-input border border-border rounded-md px-3 py-2 text-foreground focus:ring-2 focus:ring-accent" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Assistent nummer <span class="text-destructive">*</span></label>
                    <input type="number" wire:model="editEmployeeNumber" class="w-full bg-input border border-border rounded-md px-3 py-2 text-foreground focus:ring-2 focus:ring-accent" required>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">E-post <span class="text-destructive">*</span></label>
                    <input type="email" wire:model="editEmail" class="w-full bg-input border border-border rounded-md px-3 py-2 text-foreground focus:ring-2 focus:ring-accent" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Telefon</label>
                    <input type="tel" wire:model="editPhone" class="w-full bg-input border border-border rounded-md px-3 py-2 text-foreground focus:ring-2 focus:ring-accent">
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Type <span class="text-destructive">*</span></label>
                    <select wire:model="editType" class="w-full bg-input border border-border rounded-md px-3 py-2 text-foreground focus:ring-2 focus:ring-accent" required>
                        <option value="primary">Fast ansatt</option>
                        <option value="substitute">Vikar</option>
                        <option value="oncall">Tilkalling</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Ansatt dato <span class="text-destructive">*</span></label>
                    <div
                        x-data="{
                            value: $wire.entangle('editHiredAt'),
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
                            required
                        >
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-foreground">Månedlig e-postrapport</p>
                    <p class="text-xs text-muted">Send oversikt over arbeidstimer ved månedsslutt</p>
                </div>
                <button
                    type="button"
                    wire:click="$toggle('editSendMonthlyReport')"
                    class="relative w-12 h-7 rounded-full transition-colors cursor-pointer {{ $editSendMonthlyReport ? 'bg-accent' : 'bg-border' }}"
                >
                    <span class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-transform {{ $editSendMonthlyReport ? 'translate-x-5' : '' }}"></span>
                </button>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <button
                    x-on:click="$dispatch('close-modal', 'edit-assistant')"
                    class="px-4 py-2 text-sm text-muted hover:text-foreground transition-colors cursor-pointer"
                >
                    Avbryt
                </button>
                <button wire:click="updateAssistant" class="px-4 py-2 bg-accent text-black text-sm rounded-md hover:opacity-90 transition-opacity cursor-pointer">
                    Lagre endringer
                </button>
            </div>
        </div>
    </x-modal>
</x-page-container>
