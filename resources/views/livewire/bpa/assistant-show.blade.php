<x-page-container class="w-full h-full flex flex-col">
    {{-- Header med tilbake-knapp og rediger --}}
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('bpa.assistants') }}" class="inline-flex items-center gap-2 text-muted hover:text-foreground transition-colors cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <span>Tilbake til assistenter</span>
        </a>
        <button
            x-on:click="$dispatch('open-modal', 'edit-assistant')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-accent text-black rounded-md hover:bg-accent-hover transition-colors cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            <span class="hidden sm:inline">Rediger</span>
        </button>
    </div>

    {{-- Assistent-info header --}}
    <div class="bg-card border border-border rounded-lg p-4 sm:p-6 mb-6">
        {{-- Første rad: Avatar + Navn + Nummer + Type-badge + Oppgave-ikon --}}
        <div class="flex items-start gap-3 mb-3">
            {{-- Avatar (mindre) --}}
            <div
                class="w-10 h-10 sm:w-12 sm:h-12 rounded-full border-2 flex items-center justify-center text-sm sm:text-base font-bold shrink-0"
                style="background-color: {{ $assistant->color }}20; border-color: {{ $assistant->color }}50; color: {{ $assistant->color }}"
            >
                {{ $assistant->initials }}
            </div>

            {{-- Navn, nummer og type --}}
            <div class="flex flex-wrap items-center gap-2 min-w-0 flex-1">
                <h1 class="text-lg sm:text-xl font-bold text-foreground">{{ $assistant->name }}</h1>
                <span class="text-muted-foreground text-sm">{{ $assistant->formatted_number }}</span>

                @php
                    $typeClasses = match($assistant->type) {
                        'primary' => 'bg-accent/10 text-accent border-accent/30',
                        'substitute' => 'bg-card-hover text-muted border-border',
                        'oncall' => 'bg-card-hover text-muted-foreground border-border',
                        default => 'bg-card-hover text-muted border-border',
                    };
                @endphp

                <span class="px-2 py-0.5 text-xs rounded-md border {{ $typeClasses }}">
                    {{ $assistant->type_label }}
                </span>
            </div>

            {{-- Oppgave-tilgang ikon med popup --}}
            @if($this->taskUrl)
                <div
                    x-data="{ showTaskPopup: false, copied: false }"
                    class="relative shrink-0"
                >
                    <button
                        x-on:click="showTaskPopup = !showTaskPopup"
                        class="p-2 text-muted hover:text-accent bg-card border border-border rounded-md transition-colors cursor-pointer"
                        :class="showTaskPopup && 'bg-card-hover text-accent'"
                        title="Oppgave-tilgang"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </button>

                    {{-- Popup --}}
                    <div
                        x-show="showTaskPopup"
                        x-cloak
                        x-transition
                        x-on:click.outside="showTaskPopup = false"
                        class="absolute right-0 top-full mt-2 w-72 sm:w-80 p-4 bg-card border border-border rounded-lg shadow-lg z-20"
                    >
                        <h3 class="text-sm font-medium text-foreground mb-2">Oppgave-tilgang</h3>
                        <p class="text-xs text-muted mb-3">Assistenten kan bruke denne lenken for å se og fullføre oppgaver.</p>

                        <div class="flex items-center gap-2 mb-3">
                            <code class="flex-1 text-xs bg-input border border-border rounded-md px-2 py-1.5 text-foreground truncate">
                                {{ $this->taskUrl }}
                            </code>
                            <button
                                x-on:click="
                                    navigator.clipboard.writeText('{{ $this->taskUrl }}');
                                    copied = true;
                                    setTimeout(() => copied = false, 2000);
                                    $dispatch('toast', { type: 'success', message: 'Lenke kopiert til utklippstavle' });
                                "
                                class="shrink-0 p-1.5 text-muted hover:text-accent bg-input border border-border rounded-md transition-colors cursor-pointer"
                                title="Kopier lenke"
                            >
                                <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <svg x-show="copied" x-cloak class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        </div>

                        <button
                            wire:click="regenerateToken"
                            wire:confirm="Er du sikker på at du vil generere en ny lenke? Den gamle lenken vil slutte å fungere."
                            class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm text-muted hover:text-foreground bg-card-hover border border-border rounded-md transition-colors cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Generer ny lenke</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- Andre rad: Ansatt-varighet --}}
        <div class="text-sm text-muted mb-2">
            Ansatt i {{ $this->employmentDuration }}
        </div>

        {{-- Tredje rad: Kontaktinfo --}}
        <div class="flex flex-wrap items-center gap-3">
            @if($assistant->phone)
                <a href="tel:{{ $assistant->phone }}" class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-accent transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    {{ $assistant->phone }}
                </a>
            @endif
            @if($assistant->email)
                <a href="mailto:{{ $assistant->email }}" class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-accent transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    {{ $assistant->email }}
                </a>
            @endif
            @if($assistant->send_monthly_report)
                <span class="text-xs text-accent flex items-center gap-1">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                    </svg>
                    Månedlig rapport
                </span>
            @endif
        </div>
    </div>

    {{-- Statistikk-kort --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-card border border-border rounded-lg p-4">
            <div class="text-sm text-muted mb-1">Timer i år</div>
            <div class="text-2xl font-bold text-foreground">{{ $this->stats['hours_this_year'] }}</div>
        </div>
        <div class="bg-card border border-border rounded-lg p-4">
            <div class="text-sm text-muted mb-1">Timer denne mnd</div>
            <div class="text-2xl font-bold text-foreground">{{ $this->stats['hours_this_month'] }}</div>
        </div>
        <div class="bg-card border border-border rounded-lg p-4">
            <div class="text-sm text-muted mb-1">Antall vakter</div>
            <div class="text-2xl font-bold text-foreground">{{ $this->stats['total_shifts'] }}</div>
        </div>
        <div class="bg-card border border-border rounded-lg p-4">
            <div class="text-sm text-muted mb-1">Snitt per vakt</div>
            <div class="text-2xl font-bold text-foreground">{{ $this->stats['average_per_shift'] }}</div>
        </div>
    </div>

    {{-- Arbeidshistorikk --}}
    <div class="bg-card border border-border rounded-lg overflow-hidden mb-6">
        {{-- Header med filtre --}}
        <div x-data="{ showFilters: false }" class="relative px-4 sm:px-6 py-4 border-b border-border space-y-3">
            {{-- Første rad: Tittel + filter-ikon (mobil) / Tittel + type-filter + dato-filtre (desktop) --}}
            <div class="flex items-center justify-between sm:justify-start gap-3">
                {{-- Tittel --}}
                <h2 class="text-lg font-semibold text-foreground shrink-0">Arbeidshistorikk</h2>

                {{-- Filter-knapp (kun mobil) --}}
                <button
                    x-on:click="showFilters = !showFilters"
                    class="sm:hidden p-2 text-muted hover:text-foreground bg-card border border-border rounded-md transition-colors cursor-pointer"
                    :class="showFilters && 'bg-card-hover text-foreground'"
                    title="Filtrer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                </button>

                {{-- Type-filter (kun desktop) --}}
                <div class="hidden sm:flex items-center bg-card border border-border rounded-md overflow-hidden">
                    <button
                        wire:click="setTypeFilter(null)"
                        class="px-3 py-1.5 text-sm transition-colors cursor-pointer {{ $typeFilter === null ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
                    >
                        Alle
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

                {{-- Dato og per-side filtre (kun desktop) --}}
                <div class="hidden sm:flex items-center gap-3 ml-auto">
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-muted">År:</label>
                        <x-select wire:model.live="year" :inline="true" placeholder="" size="sm">
                            @foreach($this->availableYears as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-muted">Måned:</label>
                        <x-select wire:model.live="month" :inline="true" placeholder="Alle" size="sm">
                            <option value="1">Januar</option>
                            <option value="2">Februar</option>
                            <option value="3">Mars</option>
                            <option value="4">April</option>
                            <option value="5">Mai</option>
                            <option value="6">Juni</option>
                            <option value="7">Juli</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </x-select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-muted">Per side:</label>
                        <x-select wire:model.live="perPage" :inline="true" placeholder="" size="sm">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </x-select>
                    </div>
                </div>
            </div>

            {{-- Type-filter (kun mobil, alltid synlig) --}}
            <div class="flex sm:hidden items-stretch bg-card border border-border rounded-md overflow-hidden">
                <button
                    wire:click="setTypeFilter(null)"
                    class="flex-1 px-2 py-2 text-sm transition-colors cursor-pointer {{ $typeFilter === null ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
                >
                    Alle
                </button>
                <button
                    wire:click="setTypeFilter('worked')"
                    class="flex-1 px-2 py-2 text-sm transition-colors cursor-pointer {{ $typeFilter === 'worked' ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
                >
                    Jobbet
                </button>
                <button
                    wire:click="setTypeFilter('away')"
                    class="flex-1 px-2 py-2 text-sm transition-colors cursor-pointer {{ $typeFilter === 'away' ? 'bg-warning text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
                >
                    Borte
                </button>
                <button
                    wire:click="setTypeFilter('fullday')"
                    class="flex-1 px-2 py-2 text-sm transition-colors cursor-pointer {{ $typeFilter === 'fullday' ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
                >
                    Hel dag
                </button>
                <button
                    wire:click="setTypeFilter('archived')"
                    class="flex-1 px-2 py-2 text-sm transition-colors cursor-pointer {{ $typeFilter === 'archived' ? 'bg-muted-foreground text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
                >
                    Arkivert
                </button>
            </div>

            {{-- Dato og per-side filtre dropdown (kun mobil) --}}
            <div
                x-show="showFilters"
                x-cloak
                x-transition
                x-on:click.outside="showFilters = false"
                class="sm:hidden p-4 bg-card-hover border border-border rounded-lg space-y-3"
            >
                <div class="flex items-center gap-2">
                    <label class="text-sm text-muted w-16">År:</label>
                    <x-select wire:model.live="year" :inline="true" placeholder="" wrapperClass="flex-1">
                        @foreach($this->availableYears as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-muted w-16">Måned:</label>
                    <x-select wire:model.live="month" :inline="true" placeholder="Alle" wrapperClass="flex-1">
                        <option value="1">Januar</option>
                        <option value="2">Februar</option>
                        <option value="3">Mars</option>
                        <option value="4">April</option>
                        <option value="5">Mai</option>
                        <option value="6">Juni</option>
                        <option value="7">Juli</option>
                        <option value="8">August</option>
                        <option value="9">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </x-select>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-muted w-16">Per side:</label>
                    <x-select wire:model.live="perPage" :inline="true" placeholder="" wrapperClass="flex-1">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </x-select>
                </div>
            </div>
        </div>

        {{-- Tabell --}}
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="hidden sm:table-header-group">
                    <tr class="bg-card-hover/50 text-left text-sm font-medium text-muted">
                        <th class="px-4 sm:px-6 py-3">Dato</th>
                        <th class="px-4 sm:px-6 py-3">Tid</th>
                        <th class="px-4 sm:px-6 py-3">Varighet</th>
                        <th class="px-4 sm:px-6 py-3">Notat</th>
                        <th class="px-4 sm:px-6 py-3 w-24"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($this->shifts as $shift)
                        <tr class="hover:bg-card-hover transition-colors {{ $shift->trashed() ? 'opacity-50' : '' }} {{ $shift->is_unavailable ? 'bg-warning/5' : '' }}" wire:key="shift-{{ $shift->id }}">
                            {{-- Mobil: Kompakt layout --}}
                            <td class="sm:hidden px-4 py-3" colspan="5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-medium text-foreground">
                                            {{ $shift->starts_at->translatedFormat('j. M Y') }}
                                        </div>
                                        <div class="text-sm text-muted flex items-center gap-1">
                                            <span>{{ $shift->time_range }}</span>
                                            @unless($shift->is_all_day)
                                                <button
                                                    x-data="{ copied: false }"
                                                    x-on:click.stop="
                                                        navigator.clipboard.writeText('{{ $shift->compact_time_range }}');
                                                        copied = true;
                                                        setTimeout(() => copied = false, 1500);
                                                    "
                                                    class="p-0.5 text-muted hover:text-accent transition-colors cursor-pointer"
                                                    title="Kopier tid"
                                                >
                                                    <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                    <svg x-show="copied" x-cloak class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                            @endunless
                                            @if($shift->is_unavailable)
                                                <span class="ml-1 text-warning">(Borte)</span>
                                            @endif
                                        </div>
                                        @if($shift->note)
                                            <div class="text-sm text-muted-foreground mt-1">{{ $shift->note }}</div>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-foreground font-medium">{{ $shift->formatted_duration }}</span>
                                        <div class="flex items-center gap-1">
                                            <button
                                                wire:click="openEditShiftModal({{ $shift->id }})"
                                                class="p-1.5 text-muted hover:text-accent transition-colors cursor-pointer"
                                                title="Rediger"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            @if($shift->trashed())
                                                <button
                                                    wire:click="forceDeleteShift({{ $shift->id }})"
                                                    wire:confirm="Er du sikker på at du vil slette denne oppføringen permanent? Dette kan ikke angres."
                                                    class="p-1.5 text-muted hover:text-destructive transition-colors cursor-pointer"
                                                    title="Slett permanent"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            @else
                                                <button
                                                    wire:click="archiveShift({{ $shift->id }})"
                                                    class="p-1.5 text-muted hover:text-muted-foreground transition-colors cursor-pointer"
                                                    title="Arkiver"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Desktop: Full layout --}}
                            <td class="hidden sm:table-cell px-4 sm:px-6 py-3 text-foreground whitespace-nowrap">
                                {{ $shift->starts_at->translatedFormat('j. M Y') }}
                                @if($shift->is_unavailable)
                                    <span class="ml-2 px-1.5 py-0.5 text-xs rounded bg-warning/10 text-warning border border-warning/30">Borte</span>
                                @endif
                            </td>
                            <td class="hidden sm:table-cell px-4 sm:px-6 py-3 text-muted whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span>{{ $shift->time_range }}</span>
                                    @unless($shift->is_all_day)
                                        <button
                                            x-data="{ copied: false }"
                                            x-on:click.stop="
                                                navigator.clipboard.writeText('{{ $shift->compact_time_range }}');
                                                copied = true;
                                                setTimeout(() => copied = false, 1500);
                                            "
                                            class="p-1 text-muted hover:text-accent transition-colors cursor-pointer"
                                            title="Kopier tid"
                                        >
                                            <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <svg x-show="copied" x-cloak class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    @endunless
                                </div>
                            </td>
                            <td class="hidden sm:table-cell px-4 sm:px-6 py-3 text-foreground font-medium whitespace-nowrap">
                                {{ $shift->formatted_duration }}
                            </td>
                            <td class="hidden sm:table-cell px-4 sm:px-6 py-3 text-muted-foreground">
                                {{ $shift->note ?? '-' }}
                            </td>
                            <td class="hidden sm:table-cell px-4 sm:px-6 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- Rediger --}}
                                    <button
                                        wire:click="openEditShiftModal({{ $shift->id }})"
                                        class="p-1.5 text-muted hover:text-accent transition-colors cursor-pointer"
                                        title="Rediger"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    @if($shift->trashed())
                                        {{-- Slett permanent --}}
                                        <button
                                            wire:click="forceDeleteShift({{ $shift->id }})"
                                            wire:confirm="Er du sikker på at du vil slette denne oppføringen permanent? Dette kan ikke angres."
                                            class="p-1.5 text-muted hover:text-destructive transition-colors cursor-pointer"
                                            title="Slett permanent"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @else
                                        {{-- Arkiver --}}
                                        <button
                                            wire:click="archiveShift({{ $shift->id }})"
                                            class="p-1.5 text-muted hover:text-muted-foreground transition-colors cursor-pointer"
                                            title="Arkiver"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 sm:px-6 py-8 text-center text-muted">
                                Ingen vakter registrert for {{ $year }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($this->shifts->hasPages())
            <div class="px-4 sm:px-6 py-4 border-t border-border">
                {{ $this->shifts->links() }}
            </div>
        @endif
    </div>

    {{-- Kommende utilgjengelighet --}}
    @if($this->upcomingUnavailability->isNotEmpty())
        <div class="bg-card border border-border rounded-lg p-4 sm:p-6">
            <h2 class="text-lg font-semibold text-foreground mb-4">Kommende utilgjengelighet</h2>
            <div class="space-y-3">
                @foreach($this->upcomingUnavailability as $unavailable)
                    <div class="flex items-start gap-3 p-3 bg-warning/5 border border-warning/20 rounded-lg">
                        <svg class="w-5 h-5 text-warning shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-foreground">
                                @if($unavailable->is_all_day)
                                    {{ $unavailable->starts_at->translatedFormat('j. M Y') }}
                                    @if($unavailable->starts_at->toDateString() !== $unavailable->ends_at->toDateString())
                                        - {{ $unavailable->ends_at->translatedFormat('j. M Y') }}
                                    @endif
                                @else
                                    {{ $unavailable->starts_at->translatedFormat('j. M Y') }}
                                    <span class="text-muted font-normal">({{ $unavailable->time_range }})</span>
                                @endif
                            </div>
                            @if($unavailable->note)
                                <div class="text-sm text-muted mt-0.5">{{ $unavailable->note }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Rediger assistent modal --}}
    <x-modal name="edit-assistant" title="Rediger assistent" maxWidth="xl">
        <div class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <x-input type="text" wire:model="editName" label="Navn" :required="true" />
                <x-input type="number" wire:model="editEmployeeNumber" label="Assistent nummer" :required="true" />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <x-input type="email" wire:model="editEmail" label="E-post" :required="true" />
                <x-input type="tel" wire:model="editPhone" label="Telefon" />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <x-select wire:model="editType" label="Type" :required="true" placeholder="">
                    <option value="primary">Fast ansatt</option>
                    <option value="substitute">Vikar</option>
                    <option value="oncall">Tilkalling</option>
                </x-select>
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
                        <div class="flex items-center justify-between w-full bg-input border border-border rounded-lg px-3 py-2 text-foreground cursor-pointer">
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

            <x-toggle wire:model.live="editSendMonthlyReport" label="Månedlig e-postrapport" description="Send oversikt over arbeidstimer ved månedsslutt" />

            <div class="flex justify-end gap-2 pt-4">
                <x-button variant="ghost" x-on:click="$dispatch('close-modal', 'edit-assistant')">Avbryt</x-button>
                <x-button wire:click="updateAssistant">Lagre endringer</x-button>
            </div>
        </div>
    </x-modal>

    {{-- Rediger vakt modal --}}
    @if($showShiftModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="closeShiftModal"></div>

            {{-- Modal panel --}}
            <div class="relative z-10 w-full max-w-lg bg-card rounded-lg text-left overflow-hidden shadow-xl border border-border">
                <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-semibold text-foreground mb-4">Rediger oppføring</h3>

                    <div class="space-y-4">
                        {{-- Dato --}}
                        <div>
                            <label class="block text-sm font-medium text-muted mb-1">Dato <span class="text-destructive">*</span></label>
                            <input
                                type="date"
                                wire:model="shiftDate"
                                class="w-full bg-input border border-border rounded-md px-3 py-2 text-foreground focus:ring-2 focus:ring-accent"
                                required
                            >
                        </div>

                        {{-- Tid --}}
                        @unless($shiftIsAllDay)
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-muted mb-1">Fra</label>
                                    <input
                                        type="time"
                                        wire:model="shiftStartTime"
                                        class="w-full bg-input border border-border rounded-md px-3 py-2 text-foreground focus:ring-2 focus:ring-accent"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-muted mb-1">Til</label>
                                    <input
                                        type="time"
                                        wire:model="shiftEndTime"
                                        class="w-full bg-input border border-border rounded-md px-3 py-2 text-foreground focus:ring-2 focus:ring-accent"
                                    >
                                </div>
                            </div>
                        @endunless

                        {{-- Notat --}}
                        <x-input type="text" wire:model="shiftNote" label="Notat" placeholder="Valgfritt notat..." />

                        {{-- Checkboxer --}}
                        <div class="flex items-center gap-6">
                            <x-checkbox wire:model.live="shiftIsUnavailable" label="Borte" size="sm" />
                            <x-checkbox wire:model.live="shiftIsAllDay" label="Hel dag" size="sm" />
                        </div>
                    </div>
                </div>

                <div class="bg-card-hover px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <x-button wire:click="saveShift" class="w-full sm:w-auto">Lagre</x-button>
                    <x-button variant="ghost" wire:click="closeShiftModal" class="mt-2 sm:mt-0 w-full sm:w-auto">Avbryt</x-button>
                </div>
            </div>
        </div>
    @endif
</x-page-container>
