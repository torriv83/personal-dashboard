<div class="p-4 sm:p-6 space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-foreground">Dashbord</h1>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($this->stats as $stat)
            @if(!empty($stat['link']))
                <a href="{{ $stat['link'] }}" class="bg-card border border-border rounded-lg p-5 hover:bg-card-hover hover:border-accent/50 transition-colors cursor-pointer block">
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
                <div class="bg-card border border-border rounded-lg p-5">
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
        @endforeach
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Timer brukt hver måned (Area Chart) --}}
        @php
            $monthlyData = $this->monthlyChartData;
            $currentYearValues = array_column($monthlyData, 'current');
            $previousYearValues = array_column($monthlyData, 'previous');
            $currentYear = now()->year;
            $previousYear = $currentYear - 1;
        @endphp
        <div
            x-data="{
                expanded: false,
                chart: null,
                fullChart: null,
                chartConfig: {
                    chart: {
                        type: 'area',
                        background: 'transparent',
                        toolbar: { show: false },
                        zoom: { enabled: false },
                    },
                    series: [
                        { name: '{{ $currentYear }}', data: {{ json_encode($currentYearValues) }} },
                        { name: '{{ $previousYear }}', data: {{ json_encode($previousYearValues) }} }
                    ],
                    colors: ['#c8ff00', '#f97316'],
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            opacityTo: 0.2,
                        }
                    },
                    stroke: { curve: 'smooth', width: 2 },
                    xaxis: {
                        categories: ['jan', 'feb', 'mar', 'apr', 'mai', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'des'],
                        labels: { style: { colors: '#a3a3a3', fontSize: '12px' } },
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                    },
                    yaxis: {
                        labels: { style: { colors: '#a3a3a3', fontSize: '12px' } },
                    },
                    grid: {
                        borderColor: '#333',
                        strokeDashArray: 3,
                    },
                    legend: {
                        position: 'bottom',
                        labels: { colors: '#a3a3a3' },
                        markers: { radius: 2 },
                    },
                    tooltip: {
                        theme: 'dark',
                        x: { show: true },
                    },
                    dataLabels: { enabled: false },
                },
                renderChart() {
                    if (this.chart) this.chart.destroy();
                    this.chart = new ApexCharts(this.$refs.chart, { ...this.chartConfig, chart: { ...this.chartConfig.chart, height: 200 } });
                    this.chart.render();
                },
                getFullChartDimensions() {
                    // Use 90% of viewport minus padding
                    const height = Math.max(400, Math.floor(window.innerHeight * 0.90) - 140);
                    const width = Math.max(600, Math.floor(window.innerWidth * 0.90) - 80);
                    return { height, width };
                },
                renderFullChart() {
                    if (this.fullChart) this.fullChart.destroy();
                    this.$nextTick(() => {
                        const { height, width } = this.getFullChartDimensions();
                        this.fullChart = new ApexCharts(this.$refs.fullChart, { ...this.chartConfig, chart: { ...this.chartConfig.chart, height, width } });
                        this.fullChart.render();
                    });
                },
                openExpanded() {
                    this.expanded = true;
                    this.renderFullChart();
                    document.body.style.overflow = 'hidden';
                },
                closeExpanded() {
                    this.expanded = false;
                    if (this.fullChart) this.fullChart.destroy();
                    document.body.style.overflow = '';
                }
            }"
            x-init="$nextTick(() => renderChart())"
            @keydown.escape.window="closeExpanded()"
            @resize.window.debounce.100ms="if (expanded) renderFullChart()"
            class="bg-card border border-border rounded-lg p-5 overflow-hidden"
            wire:ignore
        >
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-foreground">Timer brukt hver måned</h3>
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
                            <h3 class="text-lg font-medium text-foreground">Timer brukt hver måned</h3>
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

        {{-- Brukte timer av totalen (%) --}}
        @php
            $percentageData = $this->percentageChartData;
            $prevYearKey = 'y' . (now()->year - 1);
            $currYearKey = 'y' . now()->year;
            $prevYearPercent = array_column($percentageData, $prevYearKey);
            $currYearPercent = array_column($percentageData, $currYearKey);
            $remainingPercent = array_column($percentageData, 'remaining');
        @endphp
        <div
            x-data="{
                expanded: false,
                chart: null,
                fullChart: null,
                chartConfig: {
                    chart: {
                        type: 'bar',
                        background: 'transparent',
                        toolbar: { show: false },
                        zoom: { enabled: false },
                    },
                    series: [
                        { name: '{{ now()->year - 1 }}', type: 'bar', data: {{ json_encode($prevYearPercent) }} },
                        { name: '{{ now()->year }}', type: 'bar', data: {{ json_encode($currYearPercent) }} },
                        { name: 'Gjenstår', type: 'line', data: {{ json_encode($remainingPercent) }} }
                    ],
                    colors: ['#666666', '#3b82f6', '#c8ff00'],
                    plotOptions: {
                        bar: {
                            columnWidth: '70%',
                            borderRadius: 2,
                        }
                    },
                    stroke: {
                        width: [0, 0, 2],
                        curve: 'smooth',
                    },
                    xaxis: {
                        categories: ['jan', 'feb', 'mar', 'apr', 'mai', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'des'],
                        labels: { style: { colors: '#a3a3a3', fontSize: '12px' } },
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                    },
                    yaxis: {
                        max: 100,
                        labels: {
                            style: { colors: '#a3a3a3', fontSize: '12px' },
                            formatter: (val) => val + '%'
                        },
                    },
                    grid: {
                        borderColor: '#333',
                        strokeDashArray: 3,
                    },
                    legend: {
                        position: 'bottom',
                        labels: { colors: '#a3a3a3' },
                        markers: { radius: 2 },
                    },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        theme: 'dark',
                        y: { formatter: (val) => val + '%' }
                    },
                    dataLabels: { enabled: false },
                },
                renderChart() {
                    if (this.chart) this.chart.destroy();
                    this.chart = new ApexCharts(this.$refs.chart, { ...this.chartConfig, chart: { ...this.chartConfig.chart, height: 200 } });
                    this.chart.render();
                },
                getFullChartDimensions() {
                    // Use 90% of viewport minus padding
                    const height = Math.max(400, Math.floor(window.innerHeight * 0.90) - 140);
                    const width = Math.max(600, Math.floor(window.innerWidth * 0.90) - 80);
                    return { height, width };
                },
                renderFullChart() {
                    if (this.fullChart) this.fullChart.destroy();
                    this.$nextTick(() => {
                        const { height, width } = this.getFullChartDimensions();
                        this.fullChart = new ApexCharts(this.$refs.fullChart, { ...this.chartConfig, chart: { ...this.chartConfig.chart, height, width } });
                        this.fullChart.render();
                    });
                },
                openExpanded() {
                    this.expanded = true;
                    this.renderFullChart();
                    document.body.style.overflow = 'hidden';
                },
                closeExpanded() {
                    this.expanded = false;
                    if (this.fullChart) this.fullChart.destroy();
                    document.body.style.overflow = '';
                }
            }"
            x-init="$nextTick(() => renderChart())"
            @keydown.escape.window="closeExpanded()"
            @resize.window.debounce.100ms="if (expanded) renderFullChart()"
            class="bg-card border border-border rounded-lg p-5 overflow-hidden"
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
    </div>

    {{-- Tables Row 1 --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Timer i uka --}}
        <div class="bg-card border border-border rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-border">
                <h3 class="text-sm font-medium text-foreground">Timer i uka</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Uke</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Totalt</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Gjennomsnitt</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Antall</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($this->weeklyHours as $row)
                            <tr class="hover:bg-card-hover transition-colors">
                                <td class="px-5 py-3 text-sm text-foreground">{{ $row['week'] }}</td>
                                <td class="px-5 py-3 text-sm text-foreground">{{ $row['total'] }}</td>
                                <td class="px-5 py-3 text-sm text-foreground">{{ $row['average'] }}</td>
                                <td class="px-5 py-3 text-sm text-foreground">{{ $row['count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-border flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-muted-foreground">Pr. side</span>
                    <select wire:model.live="weeklyPerPage" class="bg-input border border-border rounded px-2 py-1 text-xs text-foreground cursor-pointer">
                        <option value="3">3</option>
                        <option value="5">5</option>
                        <option value="10">10</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    @if($weeklyPage > 1)
                        <button wire:click="prevPage('weekly')" class="px-3 py-1.5 text-xs font-medium text-foreground bg-card-hover border border-border rounded hover:bg-input transition-colors cursor-pointer">
                            Forrige
                        </button>
                    @endif
                    <span class="text-xs text-muted-foreground">{{ $weeklyPage }} / {{ $this->weeklyTotalPages }}</span>
                    @if($weeklyPage < $this->weeklyTotalPages)
                        <button wire:click="nextPage('weekly')" class="px-3 py-1.5 text-xs font-medium text-foreground bg-card-hover border border-border rounded hover:bg-input transition-colors cursor-pointer">
                            Neste
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- De neste arbeidstidene --}}
        <div class="bg-card border border-border rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-border">
                <h3 class="text-sm font-medium text-foreground">De neste arbeidstidene</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Hvem</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Fra</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Til</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-muted-foreground">Timer</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($this->upcomingShifts as $shift)
                            <tr class="hover:bg-card-hover transition-colors">
                                <td class="px-5 py-3 text-sm text-foreground whitespace-nowrap">{{ $shift['name'] }}</td>
                                <td class="px-5 py-3 text-sm text-foreground whitespace-nowrap">{{ $shift['from'] }}</td>
                                <td class="px-5 py-3 text-sm text-foreground whitespace-nowrap">{{ $shift['to'] }}</td>
                                <td class="px-5 py-3 text-sm text-foreground whitespace-nowrap text-right">{{ $shift['duration'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-border bg-card-hover/50">
                            <td colspan="3" class="px-5 py-3 text-sm font-medium text-foreground">Totalt</td>
                            <td class="px-5 py-3 text-sm font-medium text-accent whitespace-nowrap text-right">{{ $this->upcomingShiftsTotal }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-border flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-muted-foreground">Pr. side</span>
                    <select wire:model.live="shiftsPerPage" class="bg-input border border-border rounded px-2 py-1 text-xs text-foreground cursor-pointer">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    @if($shiftsPage > 1)
                        <button wire:click="prevPage('shifts')" class="px-3 py-1.5 text-xs font-medium text-foreground bg-card-hover border border-border rounded hover:bg-input transition-colors cursor-pointer">
                            Forrige
                        </button>
                    @endif
                    <span class="text-xs text-muted-foreground">{{ $shiftsPage }} / {{ $this->shiftsTotalPages }}</span>
                    @if($shiftsPage < $this->shiftsTotalPages)
                        <button wire:click="nextPage('shifts')" class="px-3 py-1.5 text-xs font-medium text-foreground bg-card-hover border border-border rounded hover:bg-input transition-colors cursor-pointer">
                            Neste
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Tables Row 2 --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Ansatte kan ikke jobbe --}}
        <div class="bg-card border border-border rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-border">
                <h3 class="text-sm font-medium text-foreground">Ansatte kan ikke jobbe</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Navn</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Fra</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Til</th>
                            <th class="px-5 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($this->unavailableEmployees as $unavailable)
                            <tr wire:key="unavailable-{{ $unavailable['id'] }}" class="hover:bg-card-hover transition-colors group">
                                <td class="px-5 py-3 text-sm text-foreground whitespace-nowrap">{{ $unavailable['name'] }}</td>
                                <td class="px-5 py-3 text-sm text-foreground whitespace-nowrap">{{ $unavailable['from'] }}</td>
                                <td class="px-5 py-3 text-sm text-foreground whitespace-nowrap">{{ $unavailable['to'] }}</td>
                                <td class="px-5 py-3 text-right">
                                    <button
                                        wire:click="deleteUnavailable({{ $unavailable['id'] }})"
                                        wire:confirm="Er du sikker på at du vil slette dette fraværet?"
                                        type="button"
                                        class="p-1.5 text-muted-foreground hover:text-red-400 hover:bg-red-400/10 rounded transition-colors opacity-0 group-hover:opacity-100 cursor-pointer"
                                        title="Slett"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-6">
                                    @if($showQuickAddForm)
                                        {{-- Quick Add Form --}}
                                        <div class="space-y-4">
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                                <div>
                                                    <label class="block text-xs text-muted-foreground mb-1">Ansatt</label>
                                                    <select
                                                        wire:model="quickAddAssistantId"
                                                        class="w-full bg-input border border-border rounded px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent cursor-pointer"
                                                    >
                                                        <option value="">Velg ansatt...</option>
                                                        @foreach($this->allAssistants as $assistant)
                                                            <option value="{{ $assistant->id }}">{{ $assistant->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('quickAddAssistantId')
                                                        <span class="text-xs text-red-400">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-muted-foreground mb-1">Fra dato</label>
                                                    <input
                                                        type="date"
                                                        wire:model="quickAddFromDate"
                                                        class="w-full bg-input border border-border rounded px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent cursor-pointer"
                                                    >
                                                    @error('quickAddFromDate')
                                                        <span class="text-xs text-red-400">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-muted-foreground mb-1">Til dato</label>
                                                    <input
                                                        type="date"
                                                        wire:model="quickAddToDate"
                                                        class="w-full bg-input border border-border rounded px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent cursor-pointer"
                                                    >
                                                    @error('quickAddToDate')
                                                        <span class="text-xs text-red-400">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-end gap-2">
                                                <button
                                                    wire:click="closeQuickAddForm"
                                                    type="button"
                                                    class="px-3 py-1.5 text-xs font-medium text-foreground bg-card-hover border border-border rounded hover:bg-input transition-colors cursor-pointer"
                                                >
                                                    Avbryt
                                                </button>
                                                <button
                                                    wire:click="saveQuickAdd"
                                                    type="button"
                                                    class="px-3 py-1.5 text-xs font-medium text-background bg-accent rounded hover:bg-accent/90 transition-colors cursor-pointer"
                                                >
                                                    Lagre
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        {{-- Empty State with Add Button --}}
                                        <div class="text-center">
                                            <p class="text-sm text-accent mb-3">Alle kan jobbe!</p>
                                            <button
                                                wire:click="openQuickAddForm"
                                                type="button"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-foreground bg-card-hover border border-border rounded hover:bg-input hover:border-accent/50 transition-colors cursor-pointer"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                                </svg>
                                                Legg til fravær
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Ansatte --}}
        <div class="bg-card border border-border rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-border">
                <h3 class="text-sm font-medium text-foreground">Ansatte</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Navn</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Stilling</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">E-post</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Telefon</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">
                                <button
                                    wire:click="sortEmployeesByHours"
                                    class="flex items-center gap-1 hover:text-foreground transition-colors cursor-pointer"
                                >
                                    Jobbet i år
                                    @if($employeesSortDirection === 'desc')
                                        <svg class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    @else
                                        <svg class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                        </svg>
                                    @endif
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($this->employees as $employee)
                            <tr class="hover:bg-card-hover transition-colors">
                                <td class="px-5 py-3 text-sm text-foreground whitespace-nowrap">{{ $employee['name'] }}</td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    @if($employee['positionColor'] === 'accent')
                                        <span class="px-2 py-1 text-xs font-medium bg-accent/20 text-accent rounded whitespace-nowrap">
                                            {{ $employee['position'] }}
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium bg-blue-500/20 text-blue-400 rounded whitespace-nowrap">
                                            {{ $employee['position'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-sm text-muted-foreground whitespace-nowrap">{{ $employee['email'] }}</td>
                                <td class="px-5 py-3 text-sm text-foreground whitespace-nowrap">{{ $employee['phone'] }}</td>
                                <td class="px-5 py-3 text-sm text-foreground whitespace-nowrap">{{ $employee['hoursThisYear'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-border flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-muted-foreground">Pr. side</span>
                    <select wire:model.live="employeesPerPage" class="bg-input border border-border rounded px-2 py-1 text-xs text-foreground cursor-pointer">
                        <option value="3">3</option>
                        <option value="5">5</option>
                        <option value="10">10</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    @if($employeesPage > 1)
                        <button wire:click="prevPage('employees')" class="px-3 py-1.5 text-xs font-medium text-foreground bg-card-hover border border-border rounded hover:bg-input transition-colors cursor-pointer">
                            Forrige
                        </button>
                    @endif
                    <span class="text-xs text-muted-foreground">{{ $employeesPage }} / {{ $this->employeesTotalPages }}</span>
                    @if($employeesPage < $this->employeesTotalPages)
                        <button wire:click="nextPage('employees')" class="px-3 py-1.5 text-xs font-medium text-foreground bg-card-hover border border-border rounded hover:bg-input transition-colors cursor-pointer">
                            Neste
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
