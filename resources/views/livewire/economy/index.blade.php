<x-page-container class="space-y-6" wire:init="loadYnabData">
    {{-- Flash Message --}}
    @if (session('success'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 3000)"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="bg-accent/20 border border-accent/30 text-accent px-4 py-3 rounded-lg text-sm flex items-center justify-between"
        >
            <span>{{ session('success') }}</span>
            <button @click="show = false" class="text-accent hover:text-accent-hover cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    {{-- YNAB Not Configured Warning --}}
    @if(!$this->isYnabConfigured)
        <div class="bg-amber-500/10 border border-amber-500/30 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-amber-400">YNAB er ikke konfigurert</p>
                    <p class="mt-1 text-sm text-amber-400/80">
                        Legg til <code class="px-1.5 py-0.5 bg-amber-500/20 rounded text-xs">YNAB_TOKEN</code> og
                        <code class="px-1.5 py-0.5 bg-amber-500/20 rounded text-xs">YNAB_BUDGET_ID</code> i .env-filen for å aktivere YNAB-integrasjonen.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- YNAB Errors --}}
    @if(!empty($ynabErrors))
        <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-red-400">Kunne ikke hente all data fra YNAB</p>
                    <ul class="mt-1 text-sm text-red-400/80 list-disc list-inside">
                        @foreach($ynabErrors as $source => $error)
                            <li>{{ ucfirst($source) }}: {{ $error }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-xs text-muted-foreground">Prøv å synkronisere på nytt, eller sjekk YNAB API-innstillingene.</p>
                </div>
                <button wire:click="$set('ynabErrors', [])" class="text-red-400 hover:text-red-300 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col xs:flex-row xs:items-center xs:justify-between gap-3">
        <h1 class="text-2xl font-bold text-foreground">Økonomi</h1>
        <button
            wire:click="syncYnab"
            @if(!$this->isYnabConfigured) disabled @endif
            class="p-2 sm:px-4 sm:py-2 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            x-data="{ loading: false }"
            x-on:click="loading = true"
            x-init="$wire.on('syncCompleted', () => loading = false); Livewire.hook('request', ({ fail }) => { if (fail) loading = false })"
            :disabled="loading"
        >
            <template x-if="!loading">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span class="hidden sm:inline">Synkroniser YNAB</span>
                </span>
            </template>
            <template x-if="loading">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span class="hidden sm:inline">Synkroniserer...</span>
                </span>
            </template>
        </button>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-card border border-border rounded-lg p-4 sm:p-5">
            <p class="text-sm text-muted-foreground">I året før skatt</p>
            <p class="text-2xl sm:text-3xl font-bold text-foreground mt-1">kr {{ number_format($incomeSetting->yearly_gross, 0, ',', ' ') }}</p>
            <p class="text-xs text-muted-foreground mt-2">Basert på inntektsinnstillinger</p>
        </div>
        <div class="bg-card border border-border rounded-lg p-4 sm:p-5">
            <p class="text-sm text-muted-foreground">I året etter skatt</p>
            <p class="text-2xl sm:text-3xl font-bold text-accent mt-1">kr {{ number_format($incomeSetting->yearly_net, 0, ',', ' ') }}</p>
            <p class="text-xs text-muted-foreground mt-2">Netto inntekt</p>
        </div>
        <div class="bg-card border border-border rounded-lg p-4 sm:p-5">
            <p class="text-sm text-muted-foreground">Prosent skatt</p>
            <p class="text-2xl sm:text-3xl font-bold text-foreground mt-1">{{ $incomeSetting->tax_percentage }}%</p>
            <p class="text-xs text-muted-foreground mt-2">Skattetabell {{ $incomeSetting->tax_table ?? '-' }}</p>
        </div>
        <div class="bg-card border border-border rounded-lg p-4 sm:p-5">
            <p class="text-sm text-muted-foreground">Age of Money</p>
            @if($isLoadingYnab)
                <div class="h-9 sm:h-10 mt-1 flex items-center">
                    <div class="h-6 w-20 bg-muted animate-pulse rounded"></div>
                </div>
            @else
                <p class="text-2xl sm:text-3xl font-bold text-foreground mt-1">{{ $this->ageOfMoney ?? '-' }} dager</p>
            @endif
            <p class="text-xs text-muted-foreground mt-2">Fra YNAB</p>
        </div>
    </div>

    {{-- Accounts Section --}}
    <div class="bg-card border border-border rounded-lg p-4 sm:p-5">
        <div class="flex flex-col xs:flex-row xs:items-center xs:justify-between gap-1 xs:gap-0 mb-4">
            <h3 class="text-sm font-medium text-foreground">Kontoer</h3>
            @if(!$isLoadingYnab)
                <div class="text-xs text-muted-foreground text-right">
                    @if($this->lastSyncedAt)
                        <span class="block sm:inline">Sist synkronisert: {{ $this->lastSyncedAt }}</span>
                    @endif
                    @if($this->lastModifiedAt)
                        <span class="block sm:inline sm:ml-3">Endret i YNAB: {{ \Carbon\Carbon::parse($this->lastModifiedAt)->format('d.m.Y \\k\\l. H:i') }}</span>
                    @endif
                </div>
            @endif
        </div>

        @if($isLoadingYnab)
            <div class="flex flex-col items-center justify-center py-12">
                <svg class="w-8 h-8 text-accent animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-3 text-sm text-muted-foreground">Henter data fra YNAB...</p>
            </div>
        @elseif(count($this->accounts) > 0)
            @php
                $debtTypes = ['creditCard', 'lineOfCredit', 'mortgage', 'autoLoan', 'studentLoan', 'personalLoan', 'medicalDebt', 'otherDebt', 'otherLiability'];
                $regularAccounts = collect($this->accounts)->filter(fn($a) => !in_array($a['type'], $debtTypes));
                $debtAccounts = collect($this->accounts)->filter(fn($a) => in_array($a['type'], $debtTypes));
                $colors = ['blue', 'purple', 'emerald', 'orange', 'cyan', 'pink', 'amber'];
            @endphp

            {{-- Brukskontoer --}}
            @if($regularAccounts->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($regularAccounts as $index => $account)
                        @php
                            $color = $colors[$index % count($colors)];
                            $daysAgo = $account['last_reconciled_at']
                                ? (int) \Carbon\Carbon::parse($account['last_reconciled_at'])->diffInDays(now())
                                : null;
                        @endphp
                        <div class="bg-background border border-border rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-{{ $color }}-500/20 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-{{ $color }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-muted-foreground">{{ $account['name'] }}</p>
                                    <p class="text-lg font-semibold {{ $account['balance'] < 0 ? 'text-red-400' : 'text-foreground' }}">
                                        kr {{ number_format($account['balance'], 0, ',', ' ') }}
                                    </p>
                                    @if($daysAgo !== null)
                                        <p class="text-xs text-muted-foreground mt-1">
                                            Sist avstemt {{ $daysAgo == 0 ? 'i dag' : ($daysAgo == 1 ? '1 dag siden' : $daysAgo . ' dager siden') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Gjeld / Kreditt --}}
            @if($debtAccounts->count() > 0)
                <div class="mt-6 pt-4 border-t border-border">
                    <p class="text-xs text-muted-foreground mb-3">Gjeld / Kreditt</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach($debtAccounts as $index => $account)
                            @php
                                $daysAgo = $account['last_reconciled_at']
                                    ? (int) \Carbon\Carbon::parse($account['last_reconciled_at'])->diffInDays(now())
                                    : null;
                            @endphp
                            <div class="bg-background border border-border rounded-lg p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm text-muted-foreground">{{ $account['name'] }}</p>
                                        <p class="text-lg font-semibold text-red-400">
                                            kr {{ number_format($account['balance'], 0, ',', ' ') }}
                                        </p>
                                        @if($daysAgo !== null)
                                            <p class="text-xs text-muted-foreground mt-1">
                                                Sist avstemt {{ $daysAgo == 0 ? 'i dag' : ($daysAgo == 1 ? '1 dag siden' : $daysAgo . ' dager siden') }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-8 text-muted-foreground">
                @if(!$this->isYnabConfigured)
                    <p>YNAB er ikke konfigurert.</p>
                    <p class="text-xs mt-1">Legg til YNAB_TOKEN og YNAB_BUDGET_ID i .env</p>
                @else
                    <p>Ingen kontoer funnet.</p>
                @endif
            </div>
        @endif

        {{-- Total --}}
        @if(!$isLoadingYnab)
            <div class="mt-4 pt-4 border-t border-border flex items-center justify-between">
                <span class="text-sm text-muted-foreground">Total saldo</span>
                <span class="text-xl font-bold {{ $this->totalBalance < 0 ? 'text-red-400' : 'text-accent' }}">
                    kr {{ number_format($this->totalBalance, 0, ',', ' ') }}
                </span>
            </div>
        @endif
    </div>

    {{-- Chart Section --}}
    @if($isLoadingYnab)
        <div class="bg-card border border-border rounded-lg p-4 sm:p-5">
            <h3 class="text-sm font-medium text-foreground mb-4">Månedlig oversikt</h3>
            <div class="flex flex-col items-center justify-center py-16">
                <svg class="w-8 h-8 text-accent animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-3 text-sm text-muted-foreground">Laster graf...</p>
            </div>
        </div>
    @else
        @php
            $chartData = collect($this->monthlyData)->reverse()->values();
            $months = $chartData->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m)->translatedFormat('M'))->toArray();
            $expenses = $chartData->pluck('activity')->map(fn($v) => abs($v))->toArray();
            $income = $chartData->pluck('income')->toArray();
            $budgeted = $chartData->pluck('budgeted')->toArray();
            $net = $chartData->map(fn($m) => $m['income'] + $m['activity'])->toArray();
        @endphp
        <div
        wire:key="chart-{{ $chartVersion }}"
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
                    { name: 'Utgift', type: 'bar', data: @js($expenses) },
                    { name: 'Inntekt', type: 'bar', data: @js($income) },
                    { name: 'Budsjettert', type: 'bar', data: @js($budgeted) },
                    { name: 'Netto', type: 'line', data: @js($net) }
                ],
                colors: ['#666666', '#c8ff00', '#3b82f6', '#f97316'],
                plotOptions: {
                    bar: {
                        columnWidth: '70%',
                        borderRadius: 2,
                    }
                },
                stroke: {
                    width: [0, 0, 0, 3],
                    curve: 'smooth',
                },
                xaxis: {
                    categories: @js($months),
                    labels: { style: { colors: '#a3a3a3', fontSize: '10px' } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: '#a3a3a3', fontSize: '10px' },
                        formatter: (val) => 'kr ' + val.toLocaleString('nb-NO')
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
                    theme: 'dark',
                    shared: true,
                    intersect: false,
                    y: { formatter: (val) => 'kr ' + val.toLocaleString('nb-NO') }
                },
                dataLabels: { enabled: false },
            },
            renderChart() {
                if (this.chart) this.chart.destroy();
                const isMobile = window.innerWidth < 640;
                this.chart = new ApexCharts(this.$refs.chart, { ...this.chartConfig, chart: { ...this.chartConfig.chart, height: isMobile ? 280 : 320 } });
                this.chart.render();
            },
            getFullChartDimensions() {
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
        class="bg-card border border-border rounded-lg p-4 sm:p-5 overflow-hidden"
        wire:ignore
    >
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-foreground">Månedlig oversikt</h3>
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
                        <h3 class="text-lg font-medium text-foreground">Månedlig oversikt</h3>
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
    @endif

    {{-- YNAB Data Table --}}
    <div class="bg-card border border-border rounded-lg overflow-hidden">
        <div class="px-4 sm:px-5 py-4 border-b border-border">
            <h3 class="text-sm font-medium text-foreground">Tall fra YNAB</h3>
        </div>
        @if($isLoadingYnab)
            <div class="flex flex-col items-center justify-center py-12">
                <svg class="w-8 h-8 text-accent animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-3 text-sm text-muted-foreground">Laster tabelldata...</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="px-4 sm:px-5 py-3 text-left text-xs font-medium text-muted-foreground whitespace-nowrap">Måned</th>
                            <th class="px-4 sm:px-5 py-3 text-right text-xs font-medium text-muted-foreground whitespace-nowrap">Inntekter</th>
                            <th class="px-4 sm:px-5 py-3 text-right text-xs font-medium text-muted-foreground whitespace-nowrap">Utgifter</th>
                            <th class="px-4 sm:px-5 py-3 text-right text-xs font-medium text-muted-foreground whitespace-nowrap">Budsjettert</th>
                            <th class="px-4 sm:px-5 py-3 text-right text-xs font-medium text-muted-foreground whitespace-nowrap">Netto</th>
                            <th class="px-4 sm:px-5 py-3 text-right text-xs font-medium text-muted-foreground whitespace-nowrap">Age of Money</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($this->monthlyData as $month)
                            @php
                                $net = $month['income'] + $month['activity'];
                            @endphp
                            <tr class="hover:bg-card-hover transition-colors">
                                <td class="px-4 sm:px-5 py-3 text-sm text-foreground whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($month['month'])->translatedFormat('F, Y') }}
                                </td>
                                <td class="px-4 sm:px-5 py-3 text-sm text-foreground whitespace-nowrap text-right">
                                    kr {{ number_format($month['income'], 2, ',', ' ') }}
                                </td>
                                <td class="px-4 sm:px-5 py-3 text-sm text-red-400 whitespace-nowrap text-right">
                                    kr {{ number_format($month['activity'], 2, ',', ' ') }}
                                </td>
                                <td class="px-4 sm:px-5 py-3 text-sm text-foreground whitespace-nowrap text-right">
                                    kr {{ number_format($month['budgeted'], 2, ',', ' ') }}
                                </td>
                                <td class="px-4 sm:px-5 py-3 text-sm whitespace-nowrap text-right {{ $net >= 0 ? 'text-accent' : 'text-red-400' }}">
                                    kr {{ number_format($net, 2, ',', ' ') }}
                                </td>
                                <td class="px-4 sm:px-5 py-3 text-sm text-foreground whitespace-nowrap text-right">
                                    {{ $month['age_of_money'] ?? '-' }} dager
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 sm:px-5 py-8 text-sm text-muted-foreground text-center">
                                    @if(!$this->isYnabConfigured)
                                        YNAB er ikke konfigurert
                                    @else
                                        Ingen data tilgjengelig
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Income Settings Card --}}
    <div class="bg-card border border-border rounded-lg p-4 sm:p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-foreground">Inntektsinnstillinger</h3>
            <button
                @click="$dispatch('open-modal', 'income-settings')"
                class="px-3 py-1.5 text-xs font-medium text-foreground bg-card-hover border border-border rounded hover:bg-input transition-colors cursor-pointer flex items-center gap-1.5"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                <span class="hidden xs:inline">Rediger</span>
            </button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6">
            <div>
                <p class="text-xs text-muted-foreground mb-1">Før skatt (mnd)</p>
                <p class="text-base sm:text-lg font-semibold text-foreground">kr {{ number_format($incomeSetting->monthly_gross, 0, ',', ' ') }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground mb-1">Etter skatt (mnd)</p>
                <p class="text-base sm:text-lg font-semibold text-accent">kr {{ number_format($incomeSetting->monthly_net, 0, ',', ' ') }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground mb-1">Skattetabell</p>
                <p class="text-base sm:text-lg font-semibold text-foreground">{{ $incomeSetting->tax_table ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground mb-1">Grunnstønad</p>
                <p class="text-base sm:text-lg font-semibold text-foreground">kr {{ number_format($incomeSetting->base_support, 0, ',', ' ') }}</p>
            </div>
            <div class="col-span-2 sm:col-span-1">
                <p class="text-xs text-muted-foreground mb-1">Sist oppdatert</p>
                <p class="text-base sm:text-lg font-semibold text-foreground">{{ $incomeSetting->updated_at?->format('d.m.Y') ?? '-' }}</p>
            </div>
        </div>
    </div>

    {{-- Income Settings Modal --}}
    <x-modal name="income-settings" title="Rediger inntektsinnstillinger">
        <form wire:submit="saveIncomeSettings" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-input
                    wire:model="monthly_gross"
                    label="Før skatt (månedlig)"
                    type="number"
                    placeholder="37500"
                />
                <x-input
                    wire:model="monthly_net"
                    label="Etter skatt (månedlig)"
                    type="number"
                    placeholder="26250"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-input
                    wire:model="tax_table"
                    label="Skattetabell"
                    type="text"
                    placeholder="7350"
                />
                <x-input
                    wire:model="base_support"
                    label="Grunnstønad (månedlig)"
                    type="number"
                    placeholder="2800"
                />
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-4">
                <x-button type="button" variant="secondary" @click="show = false">Avbryt</x-button>
                <x-button type="submit">Lagre endringer</x-button>
            </div>
        </form>
    </x-modal>
</x-page-container>
