<x-page-container :mobile-full-width="true" class="w-full h-full flex flex-col"
    x-data="calendar({
        initialYear: {{ $year }},
        initialMonth: {{ $month }},
        initialDay: {{ $day }},
        initialView: '{{ $view }}'
    })"
    @keydown.window="handleKeydown($event)">

    {{-- Loading skeleton shown during initial API data fetch --}}
    <div x-show="isLoading" x-cloak class="flex-1 flex flex-col">
        <div class="flex items-center justify-between mb-4 px-2 pt-2 md:px-0 md:pt-0">
            <div class="h-8 w-48 bg-border rounded animate-pulse"></div>
            <div class="flex gap-2">
                <div class="h-8 w-20 bg-border rounded animate-pulse"></div>
                <div class="h-8 w-24 bg-border rounded animate-pulse"></div>
            </div>
        </div>
        <div class="flex-1 bg-card border border-border rounded-lg overflow-hidden animate-pulse">
            <div class="grid grid-cols-8 border-b border-border bg-card p-2 gap-2">
                <div class="h-4 bg-border rounded"></div>
                <div class="h-4 bg-border rounded"></div>
                <div class="h-4 bg-border rounded"></div>
                <div class="h-4 bg-border rounded"></div>
                <div class="h-4 bg-border rounded"></div>
                <div class="h-4 bg-border rounded"></div>
                <div class="h-4 bg-border rounded"></div>
                <div class="h-4 bg-border rounded"></div>
            </div>
            <div class="p-2 space-y-2">
                @for($i = 0; $i < 6; $i++)
                    <div class="grid grid-cols-8 gap-2">
                        <div class="h-16 bg-border/50 rounded"></div>
                        <div class="h-16 bg-border/50 rounded"></div>
                        <div class="h-16 bg-border/50 rounded"></div>
                        <div class="h-16 bg-border/50 rounded"></div>
                        <div class="h-16 bg-border/50 rounded"></div>
                        <div class="h-16 bg-border/50 rounded"></div>
                        <div class="h-16 bg-border/50 rounded"></div>
                        <div class="h-16 bg-border/50 rounded"></div>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    {{-- Calendar content (hidden during initial load) --}}
    <div x-show="!isLoading" x-cloak class="flex-1 flex flex-col" wire:ignore>
        {{-- Header: Tittel + Navigasjon --}}
        @include('livewire.bpa.calendar._header')

        {{-- Kalendervisninger wrapper med overflow-hidden for swipe --}}
        <div class="flex-1 flex flex-col overflow-hidden relative"
            @touchstart="handleTouchStart($event)"
            @touchmove="handleTouchMove($event)"
            @touchend="handleTouchEnd($event)">
            {{-- Skeleton loader for swipe navigation (uses global store to persist across re-renders) --}}
            <div x-show="$store.swipeLoader.loading"
                class="absolute inset-0 flex flex-col p-4 space-y-3 animate-pulse bg-card z-50">
                <div class="flex gap-2">
                    <div class="w-12 h-12 bg-border rounded"></div>
                    <div class="flex-1 h-12 bg-border rounded"></div>
                </div>
                <div class="flex gap-2">
                    <div class="w-12 h-12 bg-border rounded"></div>
                    <div class="flex-1 h-12 bg-border rounded"></div>
                </div>
                <div class="flex gap-2">
                    <div class="w-12 h-12 bg-border rounded"></div>
                    <div class="flex-1 h-12 bg-border rounded"></div>
                </div>
                <div class="flex gap-2">
                    <div class="w-12 h-12 bg-border rounded"></div>
                    <div class="flex-1 h-12 bg-border rounded"></div>
                </div>
                <div class="flex gap-2">
                    <div class="w-12 h-12 bg-border rounded"></div>
                    <div class="flex-1 h-12 bg-border rounded"></div>
                </div>
                <div class="flex gap-2">
                    <div class="w-12 h-12 bg-border rounded"></div>
                    <div class="flex-1 h-12 bg-border rounded"></div>
                </div>
                <div class="flex gap-2">
                    <div class="w-12 h-12 bg-border rounded"></div>
                    <div class="flex-1 h-12 bg-border rounded"></div>
                </div>
                <div class="flex gap-2">
                    <div class="w-12 h-12 bg-border rounded"></div>
                    <div class="flex-1 h-12 bg-border rounded"></div>
                </div>
            </div>

            {{-- Navigation loading overlay --}}
            <div x-show="isNavigating && !$store.swipeLoader.loading" x-cloak
                class="absolute inset-0 bg-card/50 z-40 flex items-center justify-center">
                <div class="flex items-center gap-2 text-muted">
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm">Laster...</span>
                </div>
            </div>

            {{-- Inner container som flyttes under swipe --}}
            <div class="flex-1 flex flex-col"
                :class="{ 'transition-transform duration-150 ease-out': isAnimatingSwipe || swipeOffsetX === 0 }"
                :style="swipeOffsetX !== 0 ? `transform: translateX(${swipeOffsetX}px)` : ''">
                <div x-show="view === 'month'" x-cloak class="flex-1 flex flex-col">
                    @include('livewire.bpa.calendar._month-view')
                </div>
                <div x-show="view === 'week'" x-cloak class="flex-1 flex flex-col">
                    @include('livewire.bpa.calendar._week-view')
                </div>
                <div x-show="view === 'day'" x-cloak class="flex-1 flex flex-col">
                    @include('livewire.bpa.calendar._day-view')
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Opprett/Rediger vakt --}}
    @include('livewire.bpa.calendar._shift-modal')

    {{-- Quick Create Popover --}}
    @include('livewire.bpa.calendar._quick-create')

    {{-- Context Menu (Hoyreklikk-meny) --}}
    @include('livewire.bpa.calendar._context-menu')

    {{-- Absence Popup (for multi-day selection in month view) --}}
    @include('livewire.bpa.calendar._absence-popup')
</x-page-container>
