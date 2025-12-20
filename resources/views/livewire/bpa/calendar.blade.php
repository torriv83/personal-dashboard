<x-page-container class="w-full h-full flex flex-col"
    x-data="calendar($wire.entangle('view'))"
    @keydown.window="handleKeydown($event)">
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
            {{-- Skeleton rows --}}
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

    {{-- Modal: Opprett/Rediger vakt --}}
    @include('livewire.bpa.calendar._shift-modal')

    {{-- Quick Create Popover --}}
    @include('livewire.bpa.calendar._quick-create')

    {{-- Context Menu (Høyreklikk-meny) --}}
    @include('livewire.bpa.calendar._context-menu')

    {{-- Absence Popup (for multi-day selection in month view) --}}
    @include('livewire.bpa.calendar._absence-popup')
</x-page-container>
