{{-- Header: Tittel + Navigasjon --}}
<div class="flex flex-col gap-2 mb-4">
    {{-- Linje 1: Tittel med kontekst --}}
    <div class="flex items-baseline gap-2">
        <h1 class="text-2xl font-bold text-foreground">Kalender</h1>
        @if($view === 'day')
            <span class="text-muted text-sm md:text-base">({{ $this->currentDate->locale('nb')->dayName }})</span>
        @elseif($view === 'month')
            {{-- Årvelger ved siden av tittel --}}
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
                {{-- Månedvelger --}}
                <div x-data="{ open: false }" class="relative">
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
