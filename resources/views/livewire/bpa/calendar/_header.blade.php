{{-- Header: Tittel + Navigasjon --}}
<div class="flex flex-col gap-2 mb-4">
    {{-- Linje 1: Tittel med år --}}
    <div class="flex items-baseline justify-between gap-2">
        <div class="flex items-baseline gap-2">
            <h1 class="text-2xl font-bold text-foreground">Kalender</h1>
            {{-- Årvelger ved siden av tittel (vises alltid, samme for alle views) --}}
            <div x-data="{ open: false }" class="relative">
                <button
                    @click="open = !open"
                    @click.away="open = false"
                    class="text-muted text-sm md:text-base hover:text-foreground transition-colors cursor-pointer flex items-center gap-1"
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

    {{-- Linje 2: Dato, navigasjon og M U D --}}
    {{-- Mobil: 3-kolonner grid | Desktop: flex med absolutt sentrert "Timer igjen" --}}
    <div class="grid grid-cols-[auto_1fr_auto] md:flex items-center gap-2 md:relative">
        {{-- Venstre: Dato-info --}}
        <div class="flex items-center gap-2 min-w-0 md:flex-1">
            {{-- Dagvelger (dager i denne uken) --}}
            <div x-show="view === 'day'" x-cloak x-data="{ open: false }" class="relative">
                <button
                    @click="open = !open"
                    @click.away="open = false"
                    class="text-base md:text-xl text-muted hover:text-foreground transition-colors cursor-pointer flex items-center gap-1"
                >
                    <span class="md:hidden">{{ $this->currentDate->format('j.') }} {{ $this->currentDate->locale('nb')->shortMonthName }}</span>
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
                    <span class="md:hidden">{{ $this->weekRangeShort }}</span>
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
                            wire:click="goToDay('{{ $week['date'] }}')"
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
                    {{ $this->currentMonthName }}
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

        {{-- Midt: Navigasjon (mobil: sentrert i grid, desktop: del av høyre) --}}
        <div class="flex items-center justify-center gap-1 md:hidden">
            <button
                x-show="view === 'day'" x-cloak
                wire:click="previousDay"
                class="p-1.5 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                title="Forrige dag"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button
                x-show="view === 'week'" x-cloak
                wire:click="previousWeek"
                class="p-1.5 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                title="Forrige uke"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button
                x-show="view === 'month'" x-cloak
                wire:click="previousMonth"
                class="p-1.5 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                title="Forrige måned"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <button
                wire:click="goToToday"
                class="px-2 py-1 text-xs font-medium rounded-md text-foreground bg-card-hover hover:bg-border transition-colors cursor-pointer"
            >
                I dag
            </button>

            <button
                x-show="view === 'day'" x-cloak
                wire:click="nextDay"
                class="p-1.5 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                title="Neste dag"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <button
                x-show="view === 'week'" x-cloak
                wire:click="nextWeek"
                class="p-1.5 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                title="Neste uke"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <button
                x-show="view === 'month'" x-cloak
                wire:click="nextMonth"
                class="p-1.5 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                title="Neste måned"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        {{-- Høyre: M U D (mobil) + Timer igjen + Navigasjon + M U D (desktop) --}}
        <div class="flex items-center justify-end gap-1 md:gap-2 md:flex-1">
            {{-- Timer igjen (kun desktop, absolutt sentrert) --}}
            @php $remainingData = $this->remainingHoursData; @endphp
            <div class="hidden md:flex items-center gap-2 text-sm absolute left-1/2 -translate-x-1/2">
                <span class="text-muted">Timer igjen:</span>
                <span class="{{ $remainingData['remaining_minutes'] < 0 ? 'text-red-400 font-semibold' : 'text-accent font-medium' }}">
                    {{ $remainingData['remaining_formatted'] }}
                </span>
            </div>

            {{-- Navigasjonsknapper (kun desktop) --}}
            <div class="hidden md:flex items-center gap-2">
                <button
                    x-show="view === 'day'" x-cloak
                    wire:click="previousDay"
                    class="p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Forrige dag"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button
                    x-show="view === 'week'" x-cloak
                    wire:click="previousWeek"
                    class="p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Forrige uke"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button
                    x-show="view === 'month'" x-cloak
                    wire:click="previousMonth"
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
                    wire:click="nextDay"
                    class="p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Neste dag"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <button
                    x-show="view === 'week'" x-cloak
                    wire:click="nextWeek"
                    class="p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Neste uke"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <button
                    x-show="view === 'month'" x-cloak
                    wire:click="nextMonth"
                    class="p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Neste måned"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            {{-- Visningsvalg: M U D (Alpine for instant switching) --}}
            <div class="flex items-center bg-card rounded-md border border-border md:ml-4">
                <button
                    @click="setView('month')"
                    :class="view === 'month' ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover'"
                    class="w-8 md:w-auto md:px-3 py-1.5 text-sm font-medium rounded-l-md transition-colors cursor-pointer"
                    title="Måned"
                >
                    M
                </button>
                <button
                    @click="setView('week')"
                    :class="view === 'week' ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover'"
                    class="w-8 md:w-auto md:px-3 py-1.5 text-sm font-medium border-x border-border transition-colors cursor-pointer"
                    title="Uke"
                >
                    U
                </button>
                <button
                    @click="setView('day')"
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
