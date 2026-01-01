<x-page-container class="h-full flex flex-col">
    <!-- Title with settings button -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-foreground">Kontrollpanel</h1>
        <button
            wire:click="$toggle('showSettings')"
            class="p-2 text-muted-foreground hover:text-accent transition-colors cursor-pointer"
            title="Tilpass dashboard"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </button>
    </div>

    <!-- Centered content -->
    <div class="flex-1 flex flex-col items-center justify-center gap-8">
        {{-- Welcome Text --}}
        <div class="text-center">
            <h2 class="text-2xl font-medium text-foreground">Velkommen tilbake</h2>
        </div>

        {{-- Weather Widget --}}
        @if($this->weather)
            <div class="w-full max-w-3xl">
                <div
                    wire:click="$set('showForecastModal', true)"
                    class="bg-card border border-border rounded-lg p-6 cursor-pointer hover:border-accent transition-colors"
                >
                    <div class="flex items-start gap-4">
                        {{-- Weather Icon --}}
                        @php
                            $iconType = app(\App\Services\WeatherService::class)->getIconSvg($this->weather['symbol']);
                            $bgColor = match(true) {
                                $iconType === 'clearsky' => 'bg-yellow-500/20',
                                $iconType === 'clearsky-night' => 'bg-blue-900/30',
                                str_contains($iconType, 'night') => 'bg-indigo-500/20',
                                $iconType === 'rain' => 'bg-blue-500/20',
                                $iconType === 'snow' => 'bg-blue-200/30',
                                $iconType === 'sleet' => 'bg-blue-300/20',
                                $iconType === 'thunder' => 'bg-purple-500/20',
                                $iconType === 'fog' => 'bg-gray-500/20',
                                default => 'bg-gray-500/15',
                            };
                        @endphp
                        <div class="flex items-center justify-center w-16 h-16 rounded-lg {{ $bgColor }} shrink-0">
                            @switch($iconType)
                                @case('clearsky')
                                    {{-- Sun --}}
                                    <svg class="w-10 h-10 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z" />
                                    </svg>
                                    @break
                                @case('clearsky-night')
                                    {{-- Moon --}}
                                    <svg class="w-10 h-10 text-blue-300" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.701-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z" clip-rule="evenodd" />
                                    </svg>
                                    @break
                                @case('fair')
                                @case('partlycloudy')
                                    {{-- Sun with cloud --}}
                                    <svg class="w-10 h-10 text-accent" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M4.5 12a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm2.03-4.06a.75.75 0 101.06-1.061L6.53 5.818a.75.75 0 10-1.06 1.06l1.06 1.061zM9 3a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0V3zm5.47 2.818a.75.75 0 10-1.06 1.06l1.06 1.061a.75.75 0 001.06-1.06l-1.06-1.06zM16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                        <path fill-rule="evenodd" d="M6.75 17.25A2.25 2.25 0 019 15h9a3 3 0 100-6h-.35a4.5 4.5 0 00-8.4 1.5H9a2.25 2.25 0 00-2.25 2.25v4.5z" clip-rule="evenodd" />
                                    </svg>
                                    @break
                                @case('fair-night')
                                @case('partlycloudy-night')
                                    {{-- Moon with cloud --}}
                                    <svg class="w-10 h-10 text-blue-300" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a4 4 0 001 2M18 12a3 3 0 11-6 0h-.35a4.5 4.5 0 00-8.4 1.5H3a2.25 2.25 0 00-2.25 2.25v1.5A2.25 2.25 0 003 19.5h12a3 3 0 100-6h-.35z" clip-rule="evenodd" />
                                    </svg>
                                    @break
                                @case('cloudy')
                                    {{-- Cloud --}}
                                    <svg class="w-10 h-10 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M4.5 9.75a6 6 0 0111.573-2.226 3.75 3.75 0 014.133 4.303A4.5 4.5 0 0118 20.25H6.75a5.25 5.25 0 01-2.23-10.004 6.072 6.072 0 01-.02-.496z" clip-rule="evenodd" />
                                    </svg>
                                    @break
                                @case('rain')
                                    {{-- Cloud with rain --}}
                                    <svg class="w-10 h-10 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19.5v2M12 19.5v2M15 19.5v2" />
                                    </svg>
                                    @break
                                @case('snow')
                                    {{-- Snowflake --}}
                                    <svg class="w-10 h-10 text-blue-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m0-18l-3 3m3-3l3 3m-3 15l-3-3m3 3l3-3M3 12h18M3 12l3-3m-3 3l3 3m15-3l-3-3m3 3l-3 3" />
                                    </svg>
                                    @break
                                @case('sleet')
                                    {{-- Sleet --}}
                                    <svg class="w-10 h-10 text-blue-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 19l1 2M12 19v2M16 19l-1 2" />
                                    </svg>
                                    @break
                                @case('thunder')
                                    {{-- Thunder --}}
                                    <svg class="w-10 h-10 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M14.615 1.595a.75.75 0 01.359.852L12.982 9.75h7.268a.75.75 0 01.548 1.262l-10.5 11.25a.75.75 0 01-1.272-.71l1.992-7.302H3.75a.75.75 0 01-.548-1.262l10.5-11.25a.75.75 0 01.913-.143z" clip-rule="evenodd" />
                                    </svg>
                                    @break
                                @case('fog')
                                    {{-- Fog --}}
                                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 15h18M3 12h18M3 9h18" />
                                    </svg>
                                    @break
                                @default
                                    {{-- Default cloud --}}
                                    <svg class="w-10 h-10 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M4.5 9.75a6 6 0 0111.573-2.226 3.75 3.75 0 014.133 4.303A4.5 4.5 0 0118 20.25H6.75a5.25 5.25 0 01-2.23-10.004 6.072 6.072 0 01-.02-.496z" clip-rule="evenodd" />
                                    </svg>
                            @endswitch
                        </div>

                        {{-- Content area --}}
                        <div class="flex-1 min-w-0">
                            {{-- Top row: Temp/description + time/refresh --}}
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-4xl font-bold text-foreground">{{ $this->weather['temperature'] }}°</span>
                                    <span class="text-lg text-muted">{{ $this->weather['description'] }}</span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-xs text-muted">{{ $this->weather['updated_at'] }}</span>
                                    <button
                                        wire:click.stop="refreshWeather"
                                        wire:loading.attr="disabled"
                                        class="p-1.5 text-muted-foreground hover:text-accent transition-colors cursor-pointer disabled:opacity-50"
                                        title="Oppdater værdata"
                                    >
                                        <svg
                                            class="w-5 h-5"
                                            wire:loading.class="animate-spin"
                                            wire:target="refreshWeather"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Bottom row: Location + wind/precipitation --}}
                            <div class="flex items-center justify-between gap-2 mt-1 text-sm text-muted">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ $this->weather['location'] }}
                                </span>
                                <div class="flex items-center gap-3 shrink-0">
                                    @if($this->weather['wind_speed'] > 0)
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                            </svg>
                                            {{ $this->weather['wind_speed'] }} m/s
                                        </span>
                                    @endif
                                    @if($this->weather['precipitation'] > 0)
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                            </svg>
                                            {{ $this->weather['precipitation'] }} mm
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(count($this->visibleWidgets) === 0)
            {{-- Empty state when all widgets are hidden --}}
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-muted-foreground mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <p class="text-muted-foreground mb-4">Alle moduler er skjult</p>
                <button
                    wire:click="$set('showSettings', true)"
                    class="text-accent hover:text-accent/80 transition-colors cursor-pointer"
                >
                    Klikk her for å vise moduler
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6 max-w-3xl w-full">
                @foreach($this->visibleWidgets as $widget)
                    @if($widget['id'] === 'bpa')
                        <!-- BPA Card -->
                        <a
                            wire:key="widget-bpa"
                            href="{{ route('bpa.dashboard') }}"
                            wire:navigate
                            class="block bg-card border border-border rounded-lg p-6 hover:bg-card-hover hover:border-accent transition-all group cursor-pointer"
                        >
                            <div class="flex items-center gap-4 mb-4">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-accent-dark group-hover:bg-accent transition-colors">
                                    <svg class="w-6 h-6 text-accent group-hover:text-black transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h2 class="text-xl font-semibold text-foreground">BPA</h2>
                            </div>
                            @if ($this->nextShift)
                                <p class="text-muted text-sm">
                                    Neste: {{ $this->nextShift->starts_at->translatedFormat('D j. M') }} {{ $this->nextShift->time_range }}
                                </p>
                            @else
                                <p class="text-muted text-sm">Ingen planlagte vakter</p>
                            @endif
                        </a>
                    @elseif($widget['id'] === 'medical')
                        <!-- Medical Card -->
                        <a
                            wire:key="widget-medical"
                            href="{{ route('medical.dashboard') }}"
                            wire:navigate
                            class="block bg-card border border-border rounded-lg p-6 hover:bg-card-hover hover:border-accent transition-all group cursor-pointer"
                        >
                            <div class="flex items-center gap-4 mb-4">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-accent-dark group-hover:bg-accent transition-colors">
                                    <svg class="w-6 h-6 text-accent group-hover:text-black transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </div>
                                <h2 class="text-xl font-semibold text-foreground">Medisinsk</h2>
                            </div>
                            @if ($this->expiredPrescriptionsCount > 0 || $this->expiringPrescriptionsCount > 0)
                                <p class="text-muted text-sm">
                                    @if ($this->expiredPrescriptionsCount > 0)
                                        <span class="text-destructive">{{ $this->expiredPrescriptionsCount }} utgått</span>
                                    @endif
                                    @if ($this->expiredPrescriptionsCount > 0 && $this->expiringPrescriptionsCount > 0)
                                        <span class="text-muted-foreground">,</span>
                                    @endif
                                    @if ($this->expiringPrescriptionsCount > 0)
                                        {{ $this->expiringPrescriptionsCount }} utløper snart
                                    @endif
                                </p>
                            @else
                                <p class="text-muted text-sm">Alle resepter er OK</p>
                            @endif
                        </a>
                    @elseif($widget['id'] === 'economy')
                        <!-- Economy Card -->
                        <a
                            wire:key="widget-economy"
                            href="{{ route('economy') }}"
                            wire:navigate
                            class="block bg-card border border-border rounded-lg p-6 hover:bg-card-hover hover:border-accent transition-all group cursor-pointer"
                        >
                            <div class="flex items-center gap-4 mb-4">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-accent-dark group-hover:bg-accent transition-colors">
                                    <svg class="w-6 h-6 text-accent group-hover:text-black transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h2 class="text-xl font-semibold text-foreground">Økonomi</h2>
                            </div>
                            @if ($this->toBeBudgeted !== null)
                                <p class="text-muted text-sm">
                                    Til budsjett: {{ number_format($this->toBeBudgeted, 0, ',', ' ') }} kr
                                </p>
                            @else
                                <p class="text-muted text-sm">YNAB-integrasjon</p>
                            @endif
                        </a>
                    @elseif($widget['id'] === 'wishlist')
                        <!-- Wishlist Card -->
                        <a
                            wire:key="widget-wishlist"
                            href="{{ route('wishlist') }}"
                            wire:navigate
                            class="block bg-card border border-border rounded-lg p-6 hover:bg-card-hover hover:border-accent transition-all group cursor-pointer"
                        >
                            <div class="flex items-center gap-4 mb-4">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-accent-dark group-hover:bg-accent transition-colors">
                                    <svg class="w-6 h-6 text-accent group-hover:text-black transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                </div>
                                <h2 class="text-xl font-semibold text-foreground">Ønskeliste</h2>
                            </div>
                            <p class="text-muted text-sm">{{ $this->wishlistCount }} {{ $this->wishlistCount === 1 ? 'ønske' : 'ønsker' }} på listen</p>
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Settings Modal --}}
    @if($showSettings)
        <div
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            x-data
            @click.self="$wire.set('showSettings', false)"
            @keydown.escape.window="$wire.set('showSettings', false)"
        >
            <div class="bg-card border border-border rounded-lg w-full max-w-md mx-4 shadow-xl">
                <div class="flex items-center justify-between p-4 border-b border-border">
                    <h2 class="text-lg font-semibold text-foreground">Tilpass dashboard</h2>
                    <button
                        wire:click="$set('showSettings', false)"
                        class="text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-4">
                    <p class="text-sm text-muted-foreground mb-4">Dra for å endre rekkefølge. Klikk bryteren for å vise/skjule.</p>

                    <div
                        class="space-y-2"
                        x-sort="$wire.updateOrder($item, $position)"
                        wire:ignore.self
                    >
                        @foreach($widgets as $widget)
                            <div
                                wire:key="settings-{{ $widget['id'] }}"
                                x-sort:item="'{{ $widget['id'] }}'"
                                class="flex items-center gap-3 p-3 bg-background border border-border rounded-lg"
                            >
                                {{-- Drag handle --}}
                                <svg
                                    class="w-5 h-5 text-muted-foreground cursor-grab shrink-0"
                                    x-sort:handle
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <circle cx="9" cy="6" r="1.5" />
                                    <circle cx="15" cy="6" r="1.5" />
                                    <circle cx="9" cy="12" r="1.5" />
                                    <circle cx="15" cy="12" r="1.5" />
                                    <circle cx="9" cy="18" r="1.5" />
                                    <circle cx="15" cy="18" r="1.5" />
                                </svg>

                                {{-- Widget name --}}
                                <span class="flex-1 text-foreground">{{ $widget['name'] }}</span>

                                {{-- Visibility toggle --}}
                                <button
                                    wire:click="toggleVisibility('{{ $widget['id'] }}')"
                                    class="relative w-10 h-6 rounded-full transition-colors cursor-pointer {{ $widget['visible'] ? 'bg-accent' : 'bg-border' }}"
                                >
                                    <span
                                        class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform {{ $widget['visible'] ? 'translate-x-4' : '' }}"
                                    ></span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 border-t border-border">
                    <button
                        wire:click="resetToDefaults"
                        class="text-sm text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
                    >
                        Tilbakestill til standard
                    </button>
                    <x-button wire:click="$set('showSettings', false)">Ferdig</x-button>
                </div>
            </div>
        </div>
    @endif

    {{-- Weather Forecast Modal --}}
    @if($showForecastModal)
        <div
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            x-data
            @click.self="$wire.set('showForecastModal', false)"
            @keydown.escape.window="$wire.set('showForecastModal', false)"
        >
            <div class="bg-card border border-border rounded-lg w-full max-w-2xl mx-4 shadow-xl">
                <div class="flex items-center justify-between p-4 border-b border-border">
                    <h2 class="text-lg font-semibold text-foreground">Værmelding - {{ $this->weather['location'] ?? '' }}</h2>
                    <button
                        wire:click="$set('showForecastModal', false)"
                        class="text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Tab buttons --}}
                <div class="flex gap-1 p-4 pb-0">
                    <button
                        wire:click="$set('forecastTab', 'hourly')"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors cursor-pointer {{ $forecastTab === 'hourly' ? 'bg-accent text-black' : 'bg-background text-muted-foreground hover:text-foreground' }}"
                    >
                        I dag
                    </button>
                    <button
                        wire:click="$set('forecastTab', 'daily')"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors cursor-pointer {{ $forecastTab === 'daily' ? 'bg-accent text-black' : 'bg-background text-muted-foreground hover:text-foreground' }}"
                    >
                        7 dager
                    </button>
                </div>

                <div class="p-4">
                    @if($forecastTab === 'hourly')
                        {{-- Hourly forecast (today) --}}
                        @if(count($this->hourlyForecast) > 0)
                            <div class="grid grid-cols-1 gap-2 max-h-[28rem] overflow-y-auto">
                                @foreach($this->hourlyForecast as $hour)
                                    @php
                                        $iconType = app(\App\Services\WeatherService::class)->getIconSvg($hour['symbol']);
                                        $bgColor = match(true) {
                                            $iconType === 'clearsky' => 'bg-yellow-500/20',
                                            $iconType === 'clearsky-night' => 'bg-blue-900/30',
                                            str_contains($iconType, 'night') => 'bg-indigo-500/20',
                                            $iconType === 'rain' => 'bg-blue-500/20',
                                            $iconType === 'snow' => 'bg-blue-200/30',
                                            $iconType === 'sleet' => 'bg-blue-300/20',
                                            $iconType === 'thunder' => 'bg-purple-500/20',
                                            $iconType === 'fog' => 'bg-gray-500/20',
                                            default => 'bg-gray-500/15',
                                        };
                                    @endphp
                                    <div class="flex items-center gap-4 p-3 bg-background border border-border rounded-lg">
                                        {{-- Time --}}
                                        <div class="w-14 shrink-0 font-medium text-foreground">
                                            {{ $hour['hour'] }}
                                        </div>

                                        {{-- Weather Icon --}}
                                        <div class="flex items-center justify-center w-8 h-8 rounded-lg {{ $bgColor }} shrink-0">
                                            @include('livewire.dashboard.partials.weather-icon', ['iconType' => $iconType, 'size' => 'w-5 h-5'])
                                        </div>

                                        {{-- Temperature --}}
                                        <div class="w-12 text-center font-medium text-foreground shrink-0">
                                            {{ $hour['temperature'] }}°
                                        </div>

                                        {{-- Description --}}
                                        <div class="flex-1 text-sm text-muted truncate">{{ $hour['description'] }}</div>

                                        {{-- Extra info --}}
                                        <div class="flex items-center gap-3 text-xs text-muted shrink-0">
                                            @if($hour['precipitation'] > 0)
                                                <span class="flex items-center gap-1" title="Nedbør">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                                    </svg>
                                                    {{ $hour['precipitation'] }} mm
                                                </span>
                                            @endif
                                            <span class="flex items-center gap-1" title="Vind">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                </svg>
                                                {{ $hour['wind_speed'] }} m/s
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-center text-muted py-8">Ingen timedata tilgjengelig for i dag</p>
                        @endif
                    @else
                        {{-- Daily forecast (7 days) --}}
                        @if(count($this->forecast) > 0)
                            <div class="grid grid-cols-1 gap-3">
                                @foreach($this->forecast as $day)
                                    @php
                                        $iconType = app(\App\Services\WeatherService::class)->getIconSvg($day['symbol']);
                                        $bgColor = match(true) {
                                            $iconType === 'clearsky' => 'bg-yellow-500/20',
                                            $iconType === 'clearsky-night' => 'bg-blue-900/30',
                                            str_contains($iconType, 'night') => 'bg-indigo-500/20',
                                            $iconType === 'rain' => 'bg-blue-500/20',
                                            $iconType === 'snow' => 'bg-blue-200/30',
                                            $iconType === 'sleet' => 'bg-blue-300/20',
                                            $iconType === 'thunder' => 'bg-purple-500/20',
                                            $iconType === 'fog' => 'bg-gray-500/20',
                                            default => 'bg-gray-500/15',
                                        };
                                    @endphp
                                    <div class="flex items-center gap-3 sm:gap-4 p-3 bg-background border border-border rounded-lg">
                                        {{-- Day name --}}
                                        <div class="w-14 sm:w-24 shrink-0">
                                            <div class="font-medium text-foreground">
                                                <span class="sm:hidden">{{ $day['day_short'] }}</span>
                                                <span class="hidden sm:inline">{{ $day['day_name'] }}</span>
                                            </div>
                                            <div class="text-xs text-muted">{{ \Carbon\Carbon::parse($day['date'])->format('d.m') }}</div>
                                        </div>

                                        {{-- Weather Icon --}}
                                        <div class="flex items-center justify-center w-10 h-10 rounded-lg {{ $bgColor }} shrink-0">
                                            @include('livewire.dashboard.partials.weather-icon', ['iconType' => $iconType, 'size' => 'w-6 h-6'])
                                        </div>

                                        {{-- Description --}}
                                        <div class="flex-1 text-sm text-muted truncate">{{ $day['description'] }}</div>

                                        {{-- Temps --}}
                                        <div class="text-right shrink-0">
                                            <span class="font-medium text-foreground">{{ $day['temp_high'] }}°</span>
                                            <span class="text-muted">/</span>
                                            <span class="text-muted">{{ $day['temp_low'] }}°</span>
                                        </div>

                                        {{-- Extra info --}}
                                        <div class="flex items-center gap-2 sm:gap-3 text-xs text-muted shrink-0">
                                            @if($day['precipitation'] > 0)
                                                <span class="flex items-center gap-1" title="Nedbør">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                                    </svg>
                                                    {{ $day['precipitation'] }}<span class="hidden sm:inline"> mm</span>
                                                </span>
                                            @endif
                                            <span class="flex items-center gap-1" title="Vind">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                </svg>
                                                {{ $day['wind_speed'] }}<span class="hidden sm:inline"> m/s</span>
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-center text-muted py-8">Kunne ikke hente værmelding</p>
                        @endif
                    @endif
                </div>

                <div class="flex items-center justify-end p-4 border-t border-border">
                    <x-button wire:click="$set('showForecastModal', false)">Lukk</x-button>
                </div>
            </div>
        </div>
    @endif
</x-page-container>
