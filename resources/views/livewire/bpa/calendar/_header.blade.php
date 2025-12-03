{{-- Header: Tittel + Navigasjon --}}
<div class="flex flex-col gap-2 mb-4">
    {{-- Linje 1: Tittel med kontekst --}}
    <div class="flex items-baseline gap-2">
        <h1 class="text-2xl font-bold text-foreground">Kalender</h1>
        @if($view === 'day')
            <span class="text-muted text-sm md:text-base">({{ $this->currentDate->locale('nb')->dayName }})</span>
        @elseif($view === 'month')
            <span class="text-muted text-sm md:text-base">{{ $year }}</span>
        @endif
    </div>

    {{-- Linje 2: Dato, navigasjon og M U D --}}
    <div class="flex items-center justify-between gap-2">
        {{-- Venstre: Dato-info --}}
        <div class="flex items-center gap-2 min-w-0">
            @if($view === 'day')
                <span class="text-base md:text-xl text-muted truncate">
                    <span class="md:hidden">{{ $this->currentDate->format('j.') }} {{ $this->currentDate->locale('nb')->shortMonthName }}</span>
                    <span class="hidden md:inline">{{ $this->currentDate->format('j.') }} {{ $this->currentDate->locale('nb')->monthName }} {{ $year }}</span>
                </span>
            @elseif($view === 'week')
                <span class="text-base md:text-xl text-muted truncate">{{ $this->weekRange }}</span>
            @else
                <span class="text-base md:text-xl text-muted">{{ $this->currentMonthName }}</span>
            @endif
        </div>

        {{-- Høyre: Navigasjon og M U D --}}
        <div class="flex items-center gap-1 md:gap-2 shrink-0">
            {{-- Navigasjonsknapper --}}
            @if($view === 'day')
                <button
                    wire:click="previousDay"
                    class="p-1.5 md:p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Forrige dag"
                >
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            @elseif($view === 'week')
                <button
                    wire:click="previousWeek"
                    class="p-1.5 md:p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Forrige uke"
                >
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            @else
                <button
                    wire:click="previousMonth"
                    class="p-1.5 md:p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Forrige måned"
                >
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            @endif

            <button
                wire:click="goToToday"
                class="px-2 md:px-3 py-1 md:py-1.5 text-xs md:text-sm font-medium rounded-md text-foreground bg-card-hover hover:bg-border transition-colors cursor-pointer"
            >
                I dag
            </button>

            @if($view === 'day')
                <button
                    wire:click="nextDay"
                    class="p-1.5 md:p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Neste dag"
                >
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            @elseif($view === 'week')
                <button
                    wire:click="nextWeek"
                    class="p-1.5 md:p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Neste uke"
                >
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            @else
                <button
                    wire:click="nextMonth"
                    class="p-1.5 md:p-2 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Neste måned"
                >
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            @endif

            {{-- Visningsvalg: M U D --}}
            <div class="flex items-center bg-card rounded-md border border-border ml-1 md:ml-4">
                <button
                    wire:click="setView('month')"
                    class="w-8 md:w-auto md:px-3 py-1.5 text-sm font-medium rounded-l-md transition-colors cursor-pointer {{ $view === 'month' ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
                    title="Måned"
                >
                    M
                </button>
                <button
                    wire:click="setView('week')"
                    class="w-8 md:w-auto md:px-3 py-1.5 text-sm font-medium border-x border-border transition-colors cursor-pointer {{ $view === 'week' ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
                    title="Uke"
                >
                    U
                </button>
                <button
                    wire:click="setView('day')"
                    class="w-8 md:w-auto md:px-3 py-1.5 text-sm font-medium rounded-r-md transition-colors cursor-pointer {{ $view === 'day' ? 'bg-accent text-black' : 'text-muted hover:text-foreground hover:bg-card-hover' }}"
                    title="Dag"
                >
                    D
                </button>
            </div>
        </div>
    </div>
</div>
