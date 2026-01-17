{{-- "I dag" ikon i topbar (kun mobil) - viser dagens dato --}}
@push('topbar-actions')
    <button
        @click="Livewire.dispatch('calendar-go-to-today')"
        class="relative w-8 h-8 flex items-center justify-center rounded border border-border text-foreground hover:bg-card-hover transition-colors cursor-pointer"
        title="Gå til i dag"
    >
        <span class="text-xs font-bold">{{ now()->day }}</span>
    </button>
@endpush

{{-- Header: Tittel + Navigasjon --}}
<div class="flex flex-col gap-2 mb-2 md:mb-4 px-2 pt-2 md:px-0 md:pt-0">
    {{-- Linje 1: Tittel med år (kun desktop) --}}
    <div class="hidden md:flex items-baseline justify-between gap-2">
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold text-foreground">Kalender</h1>
            <button
                wire:click="refreshGoogleCalendar"
                wire:loading.attr="disabled"
                wire:target="refreshGoogleCalendar"
                class="p-1 rounded text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer disabled:opacity-50"
                title="Oppdater Google Kalender"
            >
                <svg wire:loading.remove wire:target="refreshGoogleCalendar" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <svg wire:loading wire:target="refreshGoogleCalendar" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
            {{-- Årvelger ved siden av tittel (kun desktop) --}}
            <div x-data="{ open: false }" class="relative">
                <button
                    @click="open = !open"
                    @click.away="open = false"
                    class="text-muted text-base hover:text-foreground transition-colors cursor-pointer flex items-center gap-1"
                >
                    {{ $year }}
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute left-0 top-full mt-1 z-50 bg-card border border-border rounded-lg shadow-lg py-1 min-w-20"
                >
                    @foreach($this->availableYears as $y)
                        <button
                            wire:click="goToYear({{ $y }})"
                            @click="open = false"
                            class="w-full px-3 py-1.5 text-left text-sm cursor-pointer transition-colors {{ $year === $y ? 'bg-accent text-black font-medium' : 'text-foreground hover:bg-card-hover' }}"
                        >
                            {{ $y }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    {{-- Linje 2: Dato og M U D --}}
    {{-- Mobil: 2-kolonner (dato | M U D) | Desktop: flex med absolutt sentrert "Timer igjen" --}}
    <div class="flex justify-between md:flex items-center gap-2 md:relative">
        {{-- Venstre: Dato-info --}}
        <div class="flex items-center gap-2 min-w-0 md:flex-1">
            {{-- Dagvelger (dager i denne uken) --}}
            <div x-show="view === 'day'" x-cloak x-data="{ open: false }" class="relative">
                <button
                    @click="open = !open"
                    @click.away="open = false"
                    class="text-base md:text-xl text-muted hover:text-foreground transition-colors cursor-pointer flex items-center gap-1"
                >
                    <span class="md:hidden">{{ $this->currentDate->format('j.') }} {{ $this->currentDate->locale('nb')->shortMonthName }} {{ $year }}</span>
                    <span class="hidden md:inline">{{ $this->currentDate->format('j.') }} {{ $this->currentDate->locale('nb')->monthName }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute left-0 top-full mt-1 z-50 bg-card border border-border rounded-lg shadow-lg py-1 min-w-40"
                >
                    @foreach($this->currentWeekDays as $weekDay)
                        <button
                            wire:click="goToDay('{{ $weekDay['date'] }}')"
                            @click="open = false"
                            class="w-full px-3 py-1.5 text-left text-sm cursor-pointer transition-colors {{ $weekDay['isSelected'] ? 'bg-accent text-black font-medium' : 'text-foreground hover:bg-card-hover' }}"
                        >
                            {{ $weekDay['dayNameFull'] }} {{ \Carbon\Carbon::parse($weekDay['date'])->format('j.') }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Ukevelger (uker i denne måneden) --}}
            <div x-show="view === 'week'" x-cloak x-data="{ open: false }" class="relative">
                <button
                    @click="open = !open"
                    @click.away="open = false"
                    class="text-base md:text-xl text-muted hover:text-foreground transition-colors cursor-pointer flex items-center gap-1"
                >
                    <span class="md:hidden">{{ $this->weekRangeShort }} {{ $year }}</span>
                    <span class="hidden md:inline">{{ $this->weekRange }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute left-0 top-full mt-1 z-50 bg-card border border-border rounded-lg shadow-lg py-1 min-w-48"
                >
                    @foreach($this->weeksInMonth as $week)
                        <button
                            wire:click="goToWeek('{{ $week['date'] }}')"
                            @click="open = false"
                            class="w-full px-3 py-1.5 text-left text-sm cursor-pointer transition-colors {{ $week['isSelected'] ? 'bg-accent text-black font-medium' : 'text-foreground hover:bg-card-hover' }}"
                        >
                            {{ $week['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Månedvelger --}}
            <div x-show="view === 'month'" x-cloak x-data="{ open: false }" class="relative">
                <button
                    @click="open = !open"
                    @click.away="open = false"
                    class="text-base md:text-xl text-muted hover:text-foreground transition-colors cursor-pointer flex items-center gap-1"
                >
                    <span class="md:hidden">{{ $this->currentMonthName }} {{ $year }}</span>
                    <span class="hidden md:inline">{{ $this->currentMonthName }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute left-0 top-full mt-1 z-50 bg-card border border-border rounded-lg shadow-lg py-1 min-w-32"
                >
                    @foreach($norwegianMonths as $num => $name)
                        <button
                            wire:click="goToMonth({{ $num }})"
                            @click="open = false"
                            class="w-full px-3 py-1.5 text-left text-sm cursor-pointer transition-colors {{ $month === $num ? 'bg-accent text-black font-medium' : 'text-foreground hover:bg-card-hover' }}"
                        >
                            {{ $name }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>


        {{-- Høyre: M U D (mobil) + Timer igjen + Navigasjon + M U D (desktop) --}}
        <div class="flex items-center justify-end gap-1 md:gap-2 md:flex-1">
            {{-- Timer igjen (kun desktop, absolutt sentrert) --}}
            @php $remainingData = $this->remainingHoursData; @endphp
            <div class="hidden md:flex items-center gap-2 text-sm absolute left-1/2 -translate-x-1/2">
                <span class="text-muted">Timer igjen:</span>
                <span class="{{ $remainingData['remaining_minutes'] < 0 ? 'text-destructive font-semibold' : 'text-accent font-medium' }}">
                    {{ $remainingData['remaining_formatted'] }}
                </span>
            </div>

            {{-- Navigasjonsknapper (kun desktop) --}}
            <div class="hidden md:flex items-center gap-2">
                <button
                    x-show="view === 'day'" x-cloak
                    @click="navigatePreviousArrow('day')"
                    class="p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Forrige dag"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button
                    x-show="view === 'week'" x-cloak
                    @click="navigatePreviousArrow('week')"
                    class="p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Forrige uke"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button
                    x-show="view === 'month'" x-cloak
                    @click="navigatePreviousArrow('month')"
                    class="p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Forrige måned"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <button
                    wire:click="goToToday"
                    class="px-3 py-1.5 text-sm font-medium rounded-md text-foreground bg-card-hover hover:bg-border transition-colors cursor-pointer"
                >
                    I dag
                </button>

                <button
                    x-show="view === 'day'" x-cloak
                    @click="navigateNextArrow('day')"
                    class="p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Neste dag"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <button
                    x-show="view === 'week'" x-cloak
                    @click="navigateNextArrow('week')"
                    class="p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Neste uke"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <button
                    x-show="view === 'month'" x-cloak
                    @click="navigateNextArrow('month')"
                    class="p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Neste måned"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            {{-- Visningsvalg: M U D (Alpine + Livewire for instant switching + data refresh) --}}
            <div class="flex items-center bg-card rounded-md border border-border md:ml-4">
                <button
                    @click="setView('month'); $wire.$refresh()"
                    :class="view === 'month' ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover'"
                    class="w-8 md:w-auto md:px-3 py-1.5 text-sm font-medium rounded-l-md transition-colors cursor-pointer"
                    title="Måned"
                >
                    M
                </button>
                <button
                    @click="setView('week'); $wire.$refresh()"
                    :class="view === 'week' ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover'"
                    class="w-8 md:w-auto md:px-3 py-1.5 text-sm font-medium border-x border-border transition-colors cursor-pointer"
                    title="Uke"
                >
                    U
                </button>
                <button
                    @click="setView('day'); $wire.$refresh()"
                    :class="view === 'day' ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover'"
                    class="w-8 md:w-auto md:px-3 py-1.5 text-sm font-medium rounded-r-md transition-colors cursor-pointer"
                    title="Dag"
                >
                    D
                </button>
            </div>
        </div>
    </div>
</div>
