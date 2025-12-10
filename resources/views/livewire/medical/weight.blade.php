<div class="p-4 md:p-6 space-y-6">
    {{-- Header with Quick Add --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Vekt</h1>
            <p class="text-sm text-muted-foreground mt-1">Registrer og spor vekten din over tid</p>
        </div>

        {{-- Quick Add Form --}}
        <div class="flex items-center gap-2 bg-card border border-border rounded-lg p-2">
            <input
                type="number"
                step="0.1"
                wire:model="weight"
                wire:keydown.enter="save"
                class="w-24 bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                placeholder="Vekt"
                autofocus
            >
            <span class="text-sm text-muted-foreground">kg</span>
            <button
                wire:click="save"
                class="px-4 py-2 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="hidden sm:inline">Registrer</span>
            </button>
        </div>
    </div>

    @if($this->entries->isEmpty())
        {{-- Empty State --}}
        <div class="bg-card border border-border rounded-xl p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-accent/10 flex items-center justify-center">
                <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-foreground mb-2">Ingen vektregistreringer enda</h3>
            <p class="text-muted-foreground mb-6 max-w-sm mx-auto">
                Begynn å spore vekten din ved å registrere dagens vekt i feltet over.
            </p>
            <div class="flex items-center justify-center gap-2 text-sm text-muted-foreground">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <span>Se utviklingen over tid med grafer og statistikk</span>
            </div>
        </div>
    @else
        {{-- Stats Cards with Icons --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Current Weight - Hero Card --}}
            <div class="col-span-2 lg:col-span-1 bg-gradient-to-br from-accent/20 to-accent/5 border border-accent/30 rounded-xl p-5 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-accent/10 rounded-full -translate-y-8 translate-x-8"></div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-accent mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                        </svg>
                        <span class="text-sm font-medium">Nåværende</span>
                    </div>
                    <p class="text-4xl font-bold text-foreground">{{ number_format($this->stats['current'], 1, ',', ' ') }}</p>
                    <p class="text-sm text-muted-foreground mt-1">kilogram</p>
                </div>
            </div>

            {{-- Change --}}
            <div class="bg-card border border-border rounded-xl p-5">
                <div class="flex items-center gap-2 text-muted-foreground mb-2">
                    @if($this->stats['change'] !== null && $this->stats['change'] < 0)
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                        </svg>
                    @elseif($this->stats['change'] !== null && $this->stats['change'] > 0)
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14" />
                        </svg>
                    @endif
                    <span class="text-sm font-medium">Endring</span>
                </div>
                @if($this->stats['change'] !== null)
                    <p class="text-2xl font-bold {{ $this->stats['change'] < 0 ? 'text-green-400' : ($this->stats['change'] > 0 ? 'text-red-400' : 'text-foreground') }}">
                        {{ $this->stats['change'] > 0 ? '+' : '' }}{{ number_format($this->stats['change'], 1, ',', ' ') }} kg
                    </p>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ $this->stats['changePercent'] > 0 ? '+' : '' }}{{ $this->stats['changePercent'] }}% totalt
                    </p>
                @else
                    <p class="text-2xl font-bold text-muted-foreground">-</p>
                    <p class="text-sm text-muted-foreground mt-1">Trenger flere målinger</p>
                @endif
            </div>

            {{-- Min --}}
            <div class="bg-card border border-border rounded-xl p-5">
                <div class="flex items-center gap-2 text-muted-foreground mb-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                    <span class="text-sm font-medium">Laveste</span>
                </div>
                <p class="text-2xl font-bold text-foreground">{{ number_format($this->stats['min'], 1, ',', ' ') }} kg</p>
                <p class="text-sm text-muted-foreground mt-1">All-time low</p>
            </div>

            {{-- Max --}}
            <div class="bg-card border border-border rounded-xl p-5">
                <div class="flex items-center gap-2 text-muted-foreground mb-2">
                    <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                    <span class="text-sm font-medium">Høyeste</span>
                </div>
                <p class="text-2xl font-bold text-foreground">{{ number_format($this->stats['max'], 1, ',', ' ') }} kg</p>
                <p class="text-sm text-muted-foreground mt-1">All-time high</p>
            </div>
        </div>

        {{-- Period Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Week Stats --}}
            <div class="bg-card border border-border rounded-xl p-5">
                <div class="flex items-center gap-2 text-muted-foreground mb-3">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-sm font-medium">Denne uken</span>
                </div>
                @if($this->stats['weekAverage'] !== null)
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-foreground">{{ number_format($this->stats['weekAverage'], 1, ',', ' ') }} kg</p>
                            <p class="text-sm text-muted-foreground">snitt denne uken</p>
                        </div>
                        @if($this->stats['weekChange'] !== null)
                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg
                                {{ $this->stats['weekChange'] < 0 ? 'bg-green-500/10' : ($this->stats['weekChange'] > 0 ? 'bg-red-500/10' : 'bg-card-hover') }}">
                                @if($this->stats['weekChange'] < 0)
                                    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                    </svg>
                                @elseif($this->stats['weekChange'] > 0)
                                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14" />
                                    </svg>
                                @endif
                                <span class="text-sm font-medium {{ $this->stats['weekChange'] < 0 ? 'text-green-400' : ($this->stats['weekChange'] > 0 ? 'text-red-400' : 'text-foreground') }}">
                                    {{ $this->stats['weekChange'] > 0 ? '+' : '' }}{{ number_format($this->stats['weekChange'], 1, ',', ' ') }}
                                </span>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-muted-foreground text-sm">Ingen data denne uken</p>
                @endif
            </div>

            {{-- Month Stats --}}
            <div class="bg-card border border-border rounded-xl p-5">
                <div class="flex items-center gap-2 text-muted-foreground mb-3">
                    <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span class="text-sm font-medium">Denne måneden</span>
                </div>
                @if($this->stats['monthAverage'] !== null)
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-foreground">{{ number_format($this->stats['monthAverage'], 1, ',', ' ') }} kg</p>
                            <p class="text-sm text-muted-foreground">snitt i {{ now()->translatedFormat('F') }}</p>
                        </div>
                        @if($this->stats['monthChange'] !== null)
                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg
                                {{ $this->stats['monthChange'] < 0 ? 'bg-green-500/10' : ($this->stats['monthChange'] > 0 ? 'bg-red-500/10' : 'bg-card-hover') }}">
                                @if($this->stats['monthChange'] < 0)
                                    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                    </svg>
                                @elseif($this->stats['monthChange'] > 0)
                                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14" />
                                    </svg>
                                @endif
                                <span class="text-sm font-medium {{ $this->stats['monthChange'] < 0 ? 'text-green-400' : ($this->stats['monthChange'] > 0 ? 'text-red-400' : 'text-foreground') }}">
                                    {{ $this->stats['monthChange'] > 0 ? '+' : '' }}{{ number_format($this->stats['monthChange'], 1, ',', ' ') }}
                                </span>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-muted-foreground text-sm">Ingen data denne måneden</p>
                @endif
            </div>
        </div>

        {{-- Chart --}}
        @if(count($this->chartData) > 1)
            <div class="bg-card border border-border rounded-xl p-5" wire:key="chart-{{ count($this->chartData) }}-{{ $this->entries->first()?->id }}">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-foreground">Vektutvikling</h2>
                        <p class="text-sm text-muted-foreground">Siste {{ count($this->chartData) }} registreringer</p>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="w-3 h-3 rounded-full bg-accent"></span>
                        <span class="text-muted-foreground">Vekt (kg)</span>
                    </div>
                </div>

                <div
                    class="h-64 relative"
                    x-data="{
                        data: @js($this->chartData),
                        hoveredIndex: null,
                        get minWeight() { return Math.min(...this.data.map(d => d.weight)) - 0.5 },
                        get maxWeight() { return Math.max(...this.data.map(d => d.weight)) + 0.5 },
                        get range() { return this.maxWeight - this.minWeight || 1 },
                        getY(weight) { return ((this.maxWeight - weight) / this.range * 100) },
                        getX(i) { return (i / (this.data.length - 1)) * 100 },
                        getPath() {
                            if (this.data.length < 2) return '';
                            return this.data.map((d, i) => {
                                const x = this.getX(i);
                                const y = this.getY(d.weight);
                                return `${i === 0 ? 'M' : 'L'} ${x} ${y}`;
                            }).join(' ');
                        },
                        getAreaPath() {
                            if (this.data.length < 2) return '';
                            let path = this.data.map((d, i) => {
                                const x = this.getX(i);
                                const y = this.getY(d.weight);
                                return `${i === 0 ? 'M' : 'L'} ${x} ${y}`;
                            }).join(' ');
                            path += ` L 100 100 L 0 100 Z`;
                            return path;
                        }
                    }"
                >
                    {{-- Y-axis labels --}}
                    <div class="absolute left-0 top-0 bottom-8 w-12 flex flex-col justify-between text-xs text-muted-foreground">
                        <span x-text="maxWeight.toFixed(1)"></span>
                        <span x-text="((maxWeight + minWeight) / 2).toFixed(1)"></span>
                        <span x-text="minWeight.toFixed(1)"></span>
                    </div>

                    {{-- Chart Area --}}
                    <div class="absolute left-14 right-0 top-0 bottom-8">
                        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                            {{-- Gradient Definition --}}
                            <defs>
                                <linearGradient id="areaGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" style="stop-color: var(--color-accent); stop-opacity: 0.3" />
                                    <stop offset="100%" style="stop-color: var(--color-accent); stop-opacity: 0" />
                                </linearGradient>
                            </defs>

                            {{-- Horizontal grid lines --}}
                            <line x1="0" y1="0" x2="100" y2="0" stroke="currentColor" stroke-opacity="0.1" vector-effect="non-scaling-stroke" />
                            <line x1="0" y1="25" x2="100" y2="25" stroke="currentColor" stroke-opacity="0.05" vector-effect="non-scaling-stroke" stroke-dasharray="4 4" />
                            <line x1="0" y1="50" x2="100" y2="50" stroke="currentColor" stroke-opacity="0.1" vector-effect="non-scaling-stroke" />
                            <line x1="0" y1="75" x2="100" y2="75" stroke="currentColor" stroke-opacity="0.05" vector-effect="non-scaling-stroke" stroke-dasharray="4 4" />
                            <line x1="0" y1="100" x2="100" y2="100" stroke="currentColor" stroke-opacity="0.1" vector-effect="non-scaling-stroke" />

                            {{-- Area fill --}}
                            <path
                                x-bind:d="getAreaPath()"
                                fill="url(#areaGradient)"
                            />

                            {{-- Line --}}
                            <path
                                x-bind:d="getPath()"
                                fill="none"
                                stroke="var(--color-accent)"
                                stroke-width="2.5"
                                vector-effect="non-scaling-stroke"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />

                            {{-- Data points --}}
                            <template x-for="(point, i) in data" :key="i">
                                <g>
                                    {{-- Hover area --}}
                                    <circle
                                        x-bind:cx="getX(i)"
                                        x-bind:cy="getY(point.weight)"
                                        r="10"
                                        fill="transparent"
                                        class="cursor-pointer"
                                        vector-effect="non-scaling-stroke"
                                        @mouseenter="hoveredIndex = i"
                                        @mouseleave="hoveredIndex = null"
                                    />
                                    {{-- Visible point --}}
                                    <circle
                                        x-bind:cx="getX(i)"
                                        x-bind:cy="getY(point.weight)"
                                        x-bind:r="hoveredIndex === i ? 5 : 3"
                                        fill="var(--color-accent)"
                                        vector-effect="non-scaling-stroke"
                                        class="transition-all duration-150"
                                    />
                                    {{-- Outer ring on hover --}}
                                    <circle
                                        x-show="hoveredIndex === i"
                                        x-bind:cx="getX(i)"
                                        x-bind:cy="getY(point.weight)"
                                        r="8"
                                        fill="none"
                                        stroke="var(--color-accent)"
                                        stroke-width="2"
                                        stroke-opacity="0.3"
                                        vector-effect="non-scaling-stroke"
                                    />
                                </g>
                            </template>
                        </svg>

                        {{-- Tooltip --}}
                        <template x-if="hoveredIndex !== null">
                            <div
                                class="absolute pointer-events-none z-10 bg-card border border-border rounded-lg shadow-lg px-3 py-2 text-sm transform -translate-x-1/2"
                                x-bind:style="`left: ${getX(hoveredIndex)}%; top: ${getY(data[hoveredIndex].weight) - 15}%; transform: translate(-50%, -100%)`"
                            >
                                <p class="font-semibold text-foreground" x-text="data[hoveredIndex].weight.toFixed(1) + ' kg'"></p>
                                <p class="text-xs text-muted-foreground" x-text="data[hoveredIndex].date"></p>
                            </div>
                        </template>
                    </div>

                    {{-- X-axis labels --}}
                    <div class="absolute left-14 right-0 bottom-0 h-6 flex justify-between items-center text-xs text-muted-foreground">
                        <span x-text="data[0]?.date"></span>
                        <span x-text="data[Math.floor(data.length / 2)]?.date"></span>
                        <span x-text="data[data.length - 1]?.date"></span>
                    </div>
                </div>
            </div>
        @endif

        {{-- History Timeline --}}
        <div class="bg-card border border-border rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-border flex items-center justify-between">
                <h2 class="text-lg font-semibold text-foreground">Historikk</h2>
                <span class="text-sm text-muted-foreground">{{ $this->entries->count() }} registreringer</span>
            </div>

            <div class="divide-y divide-border overflow-x-auto">
                @foreach($this->entries->take(10) as $index => $entry)
                    @php
                        $prevEntry = $this->entries->get($index + 1);
                        $diff = $prevEntry ? $entry->weight - $prevEntry->weight : null;
                    @endphp
                    <div
                        wire:key="entry-{{ $entry->id }}"
                        class="px-5 py-4 flex items-center gap-4 hover:bg-card-hover/50 transition-colors group min-w-max"
                    >
                        {{-- Date indicator --}}
                        <div class="flex flex-col items-center w-14 shrink-0">
                            <span class="text-2xl font-bold text-foreground">{{ $entry->recorded_at->format('d') }}</span>
                            <span class="text-xs text-muted-foreground uppercase">{{ $entry->recorded_at->translatedFormat('M') }}</span>
                            <span class="text-xs text-muted-foreground">{{ $entry->recorded_at->format('H:i') }}</span>
                        </div>

                        {{-- Trend indicator --}}
                        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0
                            @if($diff !== null && $diff < 0) bg-green-500/10
                            @elseif($diff !== null && $diff > 0) bg-red-500/10
                            @else bg-card-hover @endif
                        ">
                            @if($diff !== null && $diff < 0)
                                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                            @elseif($diff !== null && $diff > 0)
                                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14" />
                                </svg>
                            @endif
                        </div>

                        {{-- Weight and note --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline gap-2">
                                <span class="text-xl font-semibold text-foreground">{{ number_format($entry->weight, 1, ',', ' ') }}</span>
                                <span class="text-sm text-muted-foreground">kg</span>
                                @if($diff !== null)
                                    <span class="text-sm {{ $diff < 0 ? 'text-green-400' : ($diff > 0 ? 'text-red-400' : 'text-muted-foreground') }}">
                                        ({{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 1, ',', ' ') }})
                                    </span>
                                @endif
                            </div>
                            @if($entry->note)
                                <p class="text-sm text-muted-foreground truncate mt-0.5">{{ $entry->note }}</p>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-1 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity shrink-0">
                            <button
                                wire:click="openModal({{ $entry->id }})"
                                class="p-2 text-muted-foreground hover:text-foreground hover:bg-input rounded-lg transition-colors cursor-pointer"
                                title="Rediger"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button
                                wire:click="delete({{ $entry->id }})"
                                wire:confirm="Er du sikker på at du vil slette denne registreringen?"
                                class="p-2 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors cursor-pointer"
                                title="Slett"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($this->entries->count() > 10)
                <div class="px-5 py-3 border-t border-border bg-card-hover/30 text-center">
                    <button
                        wire:click="openModal"
                        class="text-sm text-accent hover:underline cursor-pointer"
                    >
                        Vis alle {{ $this->entries->count() }} registreringer
                    </button>
                </div>
            @endif
        </div>
    @endif

    {{-- Modal --}}
    @if($showModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.closeModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                wire:click="closeModal"
            ></div>

            {{-- Modal Content --}}
            <div class="relative bg-card border border-border rounded-xl shadow-2xl w-full max-w-md mx-4">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </div>
                        <h2 class="text-lg font-semibold text-foreground">
                            {{ $editingId ? 'Rediger registrering' : 'Ny registrering' }}
                        </h2>
                    </div>
                    <button
                        wire:click="closeModal"
                        class="p-2 text-muted-foreground hover:text-foreground hover:bg-input rounded-lg transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-6 py-5 space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Dato</label>
                            <input
                                type="date"
                                wire:model="date"
                                class="w-full bg-input border border-border rounded-lg px-4 py-3 text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent cursor-pointer"
                            >
                            @error('date')
                                <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Tidspunkt</label>
                            <input
                                type="time"
                                wire:model="time"
                                class="w-full bg-input border border-border rounded-lg px-4 py-3 text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent cursor-pointer"
                            >
                            @error('time')
                                <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Vekt</label>
                        <div class="relative">
                            <input
                                type="number"
                                step="0.1"
                                wire:model="weight"
                                class="w-full bg-input border border-border rounded-lg px-4 py-3 pr-12 text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                placeholder="75.5"
                                autofocus
                            >
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-muted-foreground">kg</span>
                        </div>
                        @error('weight')
                            <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Notat <span class="text-muted-foreground font-normal">(valgfritt)</span></label>
                        <textarea
                            wire:model="note"
                            rows="2"
                            class="w-full bg-input border border-border rounded-lg px-4 py-3 text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent resize-none"
                            placeholder="F.eks. etter trening, fastende..."
                        ></textarea>
                        @error('note')
                            <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                    <button
                        wire:click="closeModal"
                        class="px-5 py-2.5 text-sm font-medium text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                    >
                        Avbryt
                    </button>
                    <button
                        wire:click="save"
                        class="px-5 py-2.5 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                    >
                        {{ $editingId ? 'Lagre endringer' : 'Registrer vekt' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
