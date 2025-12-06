<div class="w-full h-full flex flex-col"
    x-data="calendar('{{ $view }}')"
    @keydown.window="handleKeydown($event)">
    {{-- Header: Tittel + Navigasjon --}}
    @include('livewire.bpa.calendar._header')

    {{-- Kalendervisninger --}}
    @if($view === 'week')
        @include('livewire.bpa.calendar._week-view')
    @elseif($view === 'day')
        @include('livewire.bpa.calendar._day-view')
    @else
        @include('livewire.bpa.calendar._month-view')
    @endif

    {{-- Modal: Opprett/Rediger vakt --}}
    @include('livewire.bpa.calendar._shift-modal')

    {{-- Quick Create Popover --}}
    @include('livewire.bpa.calendar._quick-create')
</div>
