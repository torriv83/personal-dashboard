<div class="h-full flex flex-col">
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
    <div class="flex-1 flex items-center justify-center">
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
            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6 max-w-3xl w-full"
                x-sort="$wire.updateOrder($item, $position)"
                wire:ignore.self
            >
                @foreach($this->visibleWidgets as $widget)
                    @if($widget['id'] === 'bpa')
                        <!-- BPA Card -->
                        <a
                            wire:key="widget-bpa"
                            x-sort:item="'bpa'"
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
                            x-sort:item="'medical'"
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
                            @if ($this->expiringPrescriptionsCount > 0)
                                <p class="text-muted text-sm">
                                    {{ $this->expiringPrescriptionsCount }} {{ $this->expiringPrescriptionsCount === 1 ? 'resept' : 'resepter' }} utløper snart
                                </p>
                            @else
                                <p class="text-muted text-sm">Alle resepter er OK</p>
                            @endif
                        </a>
                    @elseif($widget['id'] === 'economy')
                        <!-- Economy Card -->
                        <a
                            wire:key="widget-economy"
                            x-sort:item="'economy'"
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
                            x-sort:item="'wishlist'"
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
                    <button
                        wire:click="$set('showSettings', false)"
                        class="px-4 py-2 bg-accent text-black font-medium rounded-lg hover:bg-accent/90 transition-colors cursor-pointer"
                    >
                        Ferdig
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
