<x-page-container class="w-full h-full flex flex-col"
    x-data="calendar($wire.entangle('view'))"
    @keydown.window="handleKeydown($event)">
    {{-- Header: Tittel + Navigasjon --}}
    @include('livewire.bpa.calendar._header')

    {{-- Kalendervisninger (alle rendres, Alpine toggler synlighet) --}}
    <div x-show="view === 'month'" x-cloak class="flex-1 flex flex-col">
        @include('livewire.bpa.calendar._month-view')
    </div>
    <div x-show="view === 'week'" x-cloak class="flex-1 flex flex-col">
        @include('livewire.bpa.calendar._week-view')
    </div>
    <div x-show="view === 'day'" x-cloak class="flex-1 flex flex-col">
        @include('livewire.bpa.calendar._day-view')
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
