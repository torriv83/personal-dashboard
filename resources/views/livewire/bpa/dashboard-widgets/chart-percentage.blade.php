@php
    $percentageData = $this->percentageChartData;
    $prevYearKey = 'y' . (now()->year - 1);
    $currYearKey = 'y' . now()->year;
    $prevYearPercent = array_column($percentageData, $prevYearKey);
    $currYearPercent = array_column($percentageData, $currYearKey);
    $remainingPercent = array_column($percentageData, 'remaining');
    $pCurrentYear = now()->year;
    $pPreviousYear = $pCurrentYear - 1;
@endphp
<div
    x-data="percentageChart({{ Js::from($prevYearPercent) }}, {{ Js::from($currYearPercent) }}, {{ Js::from($remainingPercent) }}, {{ $pCurrentYear }}, {{ $pPreviousYear }})"
    x-init="init()"
    @keydown.escape.window="closeExpanded()"
    @resize.window.debounce.100ms="handleResize()"
    class="bg-card border border-border rounded-lg p-5 overflow-hidden h-full"
    wire:ignore
>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-medium text-foreground">Brukte timer av totalen (%)</h3>
        <button @click="openExpanded()" class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-card-hover rounded transition-colors cursor-pointer" title="Utvid">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
        </button>
    </div>
    <div x-ref="chart"></div>

    {{-- Fullscreen Modal --}}
    <template x-teleport="body">
        <div
            x-show="expanded"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
            @click.self="closeExpanded()"
        >
            <div class="bg-card border border-border rounded-xl max-h-[95vh] overflow-auto shadow-2xl">
                <div class="flex items-center justify-between p-5 border-b border-border">
                    <h3 class="text-lg font-medium text-foreground">Brukte timer av totalen (%)</h3>
                    <button @click="closeExpanded()" class="p-2 text-muted-foreground hover:text-foreground hover:bg-card-hover rounded-lg transition-colors cursor-pointer" title="Lukk">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-5">
                    <div x-ref="fullChart"></div>
                </div>
            </div>
        </div>
    </template>
</div>
