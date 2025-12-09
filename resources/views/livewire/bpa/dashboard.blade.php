<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-foreground">Dashbord</h1>
        <button
            wire:click="toggleEditMode"
            class="flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-colors cursor-pointer {{ $editMode ? 'bg-accent text-background' : 'bg-card border border-border text-foreground hover:bg-card-hover' }}"
        >
            @if($editMode)
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Ferdig
            @else
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16m-7 6h7" />
                </svg>
                Tilpass
            @endif
        </button>
    </div>

    {{-- Edit Mode Banner --}}
    @if($editMode)
        <div class="bg-accent/10 border border-accent/30 rounded-lg px-4 py-3 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-accent shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-foreground">
                <span class="font-medium">Tilpasningsmodus aktiv.</span>
                Dra kortene for å endre rekkefølgen. Klikk "Ferdig" når du er fornøyd.
            </p>
        </div>
    @endif

    {{-- Stat Cards --}}
    @php
        $stats = $this->stats;
    @endphp
    <div
        x-sort="$wire.updateStatCardOrder($item, $position)"
        wire:ignore.self
        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4"
    >
        @foreach($statCardOrder as $statId)
            @if(isset($stats[$statId]))
                @php $stat = $stats[$statId]; @endphp
                <div
                    x-sort:item="'{{ $stat['id'] }}'"
                    wire:key="stat-{{ $stat['id'] }}"
                    class="relative"
                >
                    {{-- Drag handle overlay - always present but only visible/interactive in edit mode --}}
                    <div
                        x-sort:handle
                        class="absolute inset-0 rounded-lg z-10 transition-all {{ $editMode ? 'bg-accent/5 border-2 border-dashed border-accent/30 cursor-grab' : 'pointer-events-none' }}"
                    ></div>

                    @if(!empty($stat['link']) && !$editMode)
                        <a href="{{ $stat['link'] }}" class="bg-card border border-border rounded-lg p-5 hover:bg-card-hover hover:border-accent/50 transition-colors cursor-pointer block h-full">
                            <p class="text-sm text-muted-foreground">{{ $stat['label'] }}</p>
                            <p class="text-3xl font-bold text-foreground mt-1">
                                {{ $stat['value'] }}
                                @if(!empty($stat['valueSuffix']))
                                    <span class="text-xs font-normal text-muted-foreground">{{ $stat['valueSuffix'] }}</span>
                                @endif
                            </p>
                            @if($stat['description'])
                                <p class="text-xs text-accent mt-2">{{ $stat['description'] }}</p>
                            @endif
                        </a>
                    @else
                        <div class="bg-card border border-border rounded-lg p-5 h-full">
                            <p class="text-sm text-muted-foreground">{{ $stat['label'] }}</p>
                            <p class="text-3xl font-bold text-foreground mt-1">
                                {{ $stat['value'] }}
                                @if(!empty($stat['valueSuffix']))
                                    <span class="text-xs font-normal text-muted-foreground">{{ $stat['valueSuffix'] }}</span>
                                @endif
                            </p>
                            @if($stat['description'])
                                <p class="text-xs text-accent mt-2">{{ $stat['description'] }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        @endforeach
    </div>

    {{-- Widgets (Charts and Tables) --}}
    <div
        x-sort="$wire.updateWidgetOrder($item, $position)"
        wire:ignore.self
        class="grid grid-cols-1 md:grid-cols-2 gap-6"
    >
        @foreach($widgetOrder as $widgetId)
            <div
                x-sort:item="'{{ $widgetId }}'"
                wire:key="widget-{{ $widgetId }}"
                class="relative"
            >
                {{-- Drag handle overlay - always present but only visible/interactive in edit mode --}}
                <div
                    x-sort:handle
                    class="absolute inset-0 rounded-lg z-10 transition-all {{ $editMode ? 'bg-accent/5 border-2 border-dashed border-accent/30 cursor-grab' : 'pointer-events-none' }}"
                ></div>

                {{-- Widget content --}}
                @switch($widgetId)
                    @case('chart_monthly')
                        @include('livewire.bpa.dashboard-widgets.chart-monthly')
                        @break
                    @case('chart_percentage')
                        @include('livewire.bpa.dashboard-widgets.chart-percentage')
                        @break
                    @case('table_weekly')
                        @include('livewire.bpa.dashboard-widgets.table-weekly')
                        @break
                    @case('table_shifts')
                        @include('livewire.bpa.dashboard-widgets.table-shifts')
                        @break
                    @case('table_unavailable')
                        @include('livewire.bpa.dashboard-widgets.table-unavailable')
                        @break
                    @case('table_employees')
                        @include('livewire.bpa.dashboard-widgets.table-employees')
                        @break
                @endswitch
            </div>
        @endforeach
    </div>

    {{-- Styling for ghost elements during drag --}}
    <style>
        .sortable-ghost {
            opacity: 0.4;
        }
    </style>
</div>
