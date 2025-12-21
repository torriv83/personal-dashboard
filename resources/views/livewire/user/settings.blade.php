<x-page-container class="max-w-4xl mx-auto space-y-6" x-data="{ activeTab: 'generelt' }">
    <!-- Page header -->
    <div>
        <h1 class="text-2xl font-semibold text-foreground">Innstillinger</h1>
        <p class="mt-1 text-sm text-muted">Konfigurer applikasjonen</p>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-border">
        <nav class="flex gap-1" aria-label="Tabs">
            <button
                type="button"
                @click="activeTab = 'generelt'"
                :class="activeTab === 'generelt' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-foreground hover:border-border'"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Generelt
            </button>
            <button
                type="button"
                @click="activeTab = 'bpa'"
                :class="activeTab === 'bpa' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-foreground hover:border-border'"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                BPA
            </button>
            <button
                type="button"
                @click="activeTab = 'verktoy'"
                :class="activeTab === 'verktoy' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-foreground hover:border-border'"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" />
                </svg>
                Verktøy
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="space-y-6">
        <!-- GENERELT TAB -->
        <div x-show="activeTab === 'generelt'" x-cloak class="space-y-6">
            <!-- Preferences -->
            <x-card>
                <x-slot name="header">
                    <h2 class="text-lg font-medium text-foreground">Preferanser</h2>
                    <p class="mt-1 text-sm text-muted">Systeminnstillinger</p>
                </x-slot>

                <div class="space-y-4">
                    <div>
                        <label for="timezone" class="block text-sm font-medium text-foreground mb-1.5">Tidssone</label>
                        <input type="text" id="timezone" value="Europe/Oslo" disabled
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground opacity-50 cursor-not-allowed" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="date_format" class="block text-sm font-medium text-foreground mb-1.5">Datoformat</label>
                            <input type="text" id="date_format" value="d.m.Y" disabled
                                class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground opacity-50 cursor-not-allowed" />
                        </div>

                        <div>
                            <label for="time_format" class="block text-sm font-medium text-foreground mb-1.5">Tidsformat</label>
                            <input type="text" id="time_format" value="H:i" disabled
                                class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground opacity-50 cursor-not-allowed" />
                        </div>
                    </div>

                    <p class="text-xs text-muted">Disse innstillingene er faste og kan ikke endres.</p>
                </div>
            </x-card>

            <!-- Weather Settings -->
            <x-card>
                <x-slot name="header">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                        </svg>
                        <h2 class="text-lg font-medium text-foreground">Vær</h2>
                    </div>
                    <p class="mt-1 text-sm text-muted">Konfigurer værvisning på kontrollpanelet</p>
                </x-slot>

                <div class="space-y-4">
                    {{-- Enable/Disable toggle --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-foreground">Vis vær på kontrollpanel</p>
                            <p class="text-xs text-muted">Viser værinformasjon fra Met.no (Yr)</p>
                        </div>
                        <button
                            wire:click="toggleWeather"
                            class="relative w-12 h-7 rounded-full transition-colors cursor-pointer {{ $weatherEnabled ? 'bg-accent' : 'bg-border' }}"
                        >
                            <span
                                class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-transform {{ $weatherEnabled ? 'translate-x-5' : '' }}"
                            ></span>
                        </button>
                    </div>

                    @if($weatherEnabled)
                        <div x-data="{ saved: false }"
                            x-on:weather-saved.window="saved = true; setTimeout(() => saved = false, 2000)"
                            class="pt-4 border-t border-border"
                        >
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="weather_search" class="block text-sm font-medium text-foreground">Sted</label>
<x-saved-indicator />
                            </div>
                            <form wire:submit="searchWeatherLocation" class="flex gap-2">
                                <div class="flex-1">
                                    <input
                                        type="text"
                                        id="weather_search"
                                        wire:model="weatherLocationSearch"
                                        class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                        placeholder="Søk etter sted..."
                                    />
                                </div>
                                <button
                                    type="submit"
                                    class="px-4 py-2 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50"
                                >
                                    <span wire:loading.remove wire:target="searchWeatherLocation">Søk</span>
                                    <span wire:loading wire:target="searchWeatherLocation">...</span>
                                </button>
                            </form>
                            @error('weatherLocationSearch')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-xs text-muted">
                                Nåværende sted: <span class="text-foreground">{{ $weatherLocationName }}</span>
                            </p>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Lock Screen -->
            <x-card>
                <x-slot name="header">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <h2 class="text-lg font-medium text-foreground">Låseskjerm</h2>
                    </div>
                    <p class="mt-1 text-sm text-muted">Beskytt appen når du er borte fra PC-en</p>
                </x-slot>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- PIN Status --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            @if($hasPin)
                                <div class="w-12 h-12 rounded-full bg-accent/20 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-base font-medium text-foreground">PIN-kode aktiv</p>
                                    <p class="text-sm text-muted">Låseskjerm er aktivert</p>
                                </div>
                            @else
                                <div class="w-12 h-12 rounded-full bg-muted/20 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-base font-medium text-foreground">Ingen PIN-kode</p>
                                    <p class="text-sm text-muted">Sett opp for å aktivere låseskjerm</p>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @if($hasPin)
                                <button type="button" wire:click="openPinModal"
                                    class="px-4 py-2 text-sm font-medium text-foreground bg-input border border-border rounded-lg hover:bg-card-hover transition-colors cursor-pointer">
                                    Endre PIN
                                </button>
                                <button type="button" wire:click="openRemovePinModal"
                                    class="px-4 py-2 text-sm font-medium text-red-400 bg-red-500/10 border border-red-500/30 rounded-lg hover:bg-red-500/20 transition-colors cursor-pointer">
                                    Fjern PIN
                                </button>
                                <button type="button" x-data x-on:click="Livewire.dispatch('lock')"
                                    class="px-4 py-2 text-sm font-medium text-foreground bg-input border border-border rounded-lg hover:bg-card-hover transition-colors cursor-pointer">
                                    Test låseskjerm
                                </button>
                            @else
                                <button type="button" wire:click="openPinModal"
                                    class="px-4 py-2 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer">
                                    Sett opp PIN-kode
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Timeout Setting --}}
                    <div x-data="{ saved: false }"
                        x-on:timeout-saved.window="saved = true; setTimeout(() => saved = false, 2000)">
                        <div class="flex items-center justify-between mb-2">
                            <label for="lock_timeout" class="block text-sm font-medium text-foreground">Lås etter
                                inaktivitet</label>
<x-saved-indicator />
                        </div>
                        <select id="lock_timeout" wire:model="lockTimeoutMinutes" wire:change="updateLockTimeout"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                            @if(!$hasPin) disabled @endif>
                            <option value="0">Aldri (deaktivert)</option>
                            <option value="5">5 minutter</option>
                            <option value="15">15 minutter</option>
                            <option value="30">30 minutter</option>
                            <option value="60">1 time</option>
                            <option value="120">2 timer</option>
                            <option value="480">8 timer</option>
                        </select>
                        @if(!$hasPin)
                            <p class="mt-2 text-xs text-muted">Sett opp PIN-kode først for å aktivere låseskjerm</p>
                        @else
                            <p class="mt-2 text-xs text-muted">Appen låses automatisk etter valgt tid med inaktivitet</p>
                        @endif
                    </div>
                </div>
            </x-card>

            {{-- Push Notifications --}}
            <x-card>
                <x-slot name="header">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <h2 class="text-lg font-medium text-foreground">Push-varsler</h2>
                    </div>
                    <p class="mt-1 text-sm text-muted">Motta varsler på mobil og desktop</p>
                </x-slot>

                <div class="space-y-6"
                    x-data="pushNotifications('{{ $vapidPublicKey }}')"
                    x-init="init()"
                >
                    {{-- Status indicator --}}
                    <div class="flex items-center gap-2 text-xs text-muted">
                        <template x-if="!supported">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                Ikke støttet i denne nettleseren
                            </span>
                        </template>
                        <template x-if="supported && !subscribed">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                                <span x-text="statusText"></span>
                            </span>
                        </template>
                        <template x-if="supported && subscribed">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                <span x-text="statusText"></span>
                            </span>
                        </template>
                    </div>

                    {{-- Prescription Alerts --}}
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-foreground">Resept-varsler</p>
                                <p class="text-xs text-muted">Varsle når resepter utløper (14, 7, 3 dager før)</p>
                            </div>
                            <button
                                @click="toggleWithSubscription(() => $wire.togglePrescriptionAlerts())"
                                :disabled="loading || !supported"
                                class="relative w-12 h-7 rounded-full transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed {{ $pushPrescriptionEnabled ? 'bg-accent' : 'bg-border' }}"
                            >
                                <span
                                    class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-transform {{ $pushPrescriptionEnabled ? 'translate-x-5' : '' }}"
                                ></span>
                            </button>
                        </div>

                        @if($pushPrescriptionEnabled)
                            <div x-data="{ saved: false }"
                                x-on:prescription-time-saved.window="saved = true; setTimeout(() => saved = false, 2000)"
                                class="pl-4 border-l-2 border-border"
                            >
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="prescription_time" class="block text-sm text-foreground">Varslingstidspunkt</label>
<x-saved-indicator />
                                </div>
                                <select
                                    id="prescription_time"
                                    wire:model="pushPrescriptionTime"
                                    wire:change="savePrescriptionTime"
                                    class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent cursor-pointer"
                                >
                                    @for($h = 6; $h <= 22; $h++)
                                        <option value="{{ sprintf('%02d:00', $h) }}">{{ sprintf('%02d:00', $h) }}</option>
                                    @endfor
                                </select>
                                <p class="mt-1 text-xs text-muted">Klokkeslett for daglige resept-varsler</p>
                            </div>
                        @endif
                    </div>

                    {{-- Shift Reminders --}}
                    <div class="pt-4 border-t border-border space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-foreground">Vakt-påminnelser</p>
                                <p class="text-xs text-muted">Påminnelse før kommende vakter</p>
                            </div>
                            <button
                                @click="toggleWithSubscription(() => $wire.toggleShiftReminders())"
                                :disabled="loading || !supported"
                                class="relative w-12 h-7 rounded-full transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed {{ $pushShiftEnabled ? 'bg-accent' : 'bg-border' }}"
                            >
                                <span
                                    class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-transform {{ $pushShiftEnabled ? 'translate-x-5' : '' }}"
                                ></span>
                            </button>
                        </div>

                        @if($pushShiftEnabled)
                            <div class="space-y-4 pl-4 border-l-2 border-border">
                                {{-- Day before toggle --}}
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-foreground">Dagen før</p>
                                        <p class="text-xs text-muted">Varsle ved samme tidspunkt dagen før vaktstart</p>
                                    </div>
                                    <button
                                        wire:click="toggleShiftDayBefore"
                                        class="relative w-10 h-6 rounded-full transition-colors cursor-pointer {{ $pushShiftDayBefore ? 'bg-accent' : 'bg-border' }}"
                                    >
                                        <span
                                            class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform {{ $pushShiftDayBefore ? 'translate-x-4' : '' }}"
                                        ></span>
                                    </button>
                                </div>

                                {{-- Hours before select --}}
                                <div x-data="{ saved: false }"
                                    x-on:shift-hours-saved.window="saved = true; setTimeout(() => saved = false, 2000)"
                                >
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label for="shift_hours" class="block text-sm text-foreground">Timer før vaktstart</label>
<x-saved-indicator />
                                    </div>
                                    <select
                                        id="shift_hours"
                                        wire:model="pushShiftHoursBefore"
                                        wire:change="saveShiftHoursBefore"
                                        class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent cursor-pointer"
                                    >
                                        <option value="">Deaktivert</option>
                                        <option value="1">1 time før</option>
                                        <option value="2">2 timer før</option>
                                        <option value="3">3 timer før</option>
                                        <option value="4">4 timer før</option>
                                        <option value="6">6 timer før</option>
                                        <option value="8">8 timer før</option>
                                        <option value="12">12 timer før</option>
                                    </select>
                                    <p class="mt-1 text-xs text-muted">Varsel X timer før vaktstart</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </x-card>
        </div>

        <!-- BPA TAB -->
        <div x-show="activeTab === 'bpa'" x-cloak class="space-y-6">
            <x-card>
                <x-slot name="header">
                    <h2 class="text-lg font-medium text-foreground">BPA-innstillinger</h2>
                    <p class="mt-1 text-sm text-muted">Konfigurer timesberegninger</p>
                </x-slot>

                <div class="space-y-4">
                    <div x-data="{ saved: false }"
                        x-on:bpa-saved.window="saved = true; setTimeout(() => saved = false, 2000)">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="bpa_hours_per_week" class="block text-sm font-medium text-foreground">Timer per uke</label>
<x-saved-indicator />
                        </div>
                        <input type="number" id="bpa_hours_per_week" wire:model="bpaHoursPerWeek"
                            wire:change="saveBpaHoursPerWeek" step="0.5" min="0" max="168"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="7" />
                        @error('bpaHoursPerWeek')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-muted">Antall BPA-timer du har per uke</p>
                    </div>

                    <div x-data="{ saved: false }"
                        x-on:hourly-rate-saved.window="saved = true; setTimeout(() => saved = false, 2000)">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="bpa_hourly_rate" class="block text-sm font-medium text-foreground">Timesats (kr)</label>
<x-saved-indicator />
                        </div>
                        <input type="number" id="bpa_hourly_rate" wire:model="bpaHourlyRate"
                            wire:change="saveBpaHourlyRate" step="0.01" min="0" max="1000"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="225.40" />
                        @error('bpaHourlyRate')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-muted">Grunnlønn per time for BPA-assistenter</p>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- VERKTØY TAB -->
        <div x-show="activeTab === 'verktoy'" x-cloak class="space-y-6">
            <x-card>
                <x-slot name="header">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        <h2 class="text-lg font-medium text-foreground">Kjøregodtgjørelse</h2>
                    </div>
                    <p class="mt-1 text-sm text-muted">Innstillinger for avstandsberegning</p>
                </x-slot>

                <div x-data="{ saved: false }"
                    x-on:mileage-home-saved.window="saved = true; setTimeout(() => saved = false, 2000)">
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="mileage_home_address" class="block text-sm font-medium text-foreground">Hjemmeadresse</label>
<x-saved-indicator />
                    </div>
                    <input
                        type="text"
                        id="mileage_home_address"
                        wire:model="mileageHomeAddress"
                        wire:change="saveMileageHomeAddress"
                        class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                        placeholder="F.eks. Storgata 1, 0001 Oslo"
                    />
                    @error('mileageHomeAddress')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-muted">Startpunkt for alle avstandsberegninger</p>
                </div>
            </x-card>

            {{-- Bookmarklet --}}
            <x-card>
                <x-slot name="header">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                        <h2 class="text-lg font-medium text-foreground">Bokmerker</h2>
                    </div>
                    <p class="mt-1 text-sm text-muted">Legg enkelt til bokmerker fra andre nettsider</p>
                </x-slot>

                <div class="space-y-6">
                    {{-- Bookmarklet --}}
                    <div>
                        <h3 class="text-sm font-medium text-foreground mb-2">Bookmarklet</h3>
                        <p class="text-xs text-muted mb-3">
                            Dra lenken under til bokmerkelinjen i nettleseren. Klikk på den når du er på en side du vil lagre.
                        </p>
                        <a
                            href="{{ $this->getBookmarkletCode() }}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-accent text-black font-medium rounded-lg hover:bg-accent-hover transition-colors cursor-grab"
                            onclick="event.preventDefault(); alert('Dra denne lenken til bokmerkelinjen i nettleseren din!');"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Legg til bokmerke
                        </a>
                    </div>

                    {{-- Token --}}
                    <div class="pt-4 border-t border-border">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-foreground">Token</h3>
                            <button
                                wire:click="toggleBookmarkToken"
                                class="text-xs text-muted hover:text-foreground transition-colors cursor-pointer"
                            >
                                {{ $showBookmarkToken ? 'Skjul' : 'Vis' }}
                            </button>
                        </div>
                        <p class="text-xs text-muted mb-3">
                            Token brukes for å autentisere bookmarklet-forespørsler. Hold denne hemmelig.
                        </p>

                        @if($showBookmarkToken)
                            <div class="flex items-center gap-2 mb-3">
                                <code class="flex-1 px-3 py-2 bg-input border border-border rounded-lg text-xs font-mono text-foreground break-all">
                                    {{ $bookmarkToken }}
                                </code>
                                <button
                                    type="button"
                                    onclick="navigator.clipboard.writeText('{{ $bookmarkToken }}'); this.innerText = 'Kopiert!'; setTimeout(() => this.innerText = 'Kopier', 2000);"
                                    class="px-3 py-2 text-xs font-medium text-foreground bg-input border border-border rounded-lg hover:bg-card-hover transition-colors cursor-pointer whitespace-nowrap"
                                >
                                    Kopier
                                </button>
                            </div>
                        @endif

                        <button
                            wire:click="regenerateBookmarkToken"
                            wire:confirm="Er du sikker? Eksisterende bookmarklet vil slutte å virke og må oppdateres."
                            class="px-3 py-2 text-xs font-medium text-red-400 bg-red-500/10 border border-red-500/30 rounded-lg hover:bg-red-500/20 transition-colors cursor-pointer"
                        >
                            Regenerer token
                        </button>
                        <p class="mt-2 text-xs text-muted">
                            Obs! Regenerering gjør at eksisterende bookmarklet slutter å virke.
                        </p>
                    </div>

                    {{-- Quick Add URL --}}
                    <div class="pt-4 border-t border-border">
                        <h3 class="text-sm font-medium text-foreground mb-2">Direktelenke</h3>
                        <p class="text-xs text-muted mb-3">
                            Du kan også bruke denne URL-en direkte for å legge til bokmerker.
                        </p>
                        <div class="flex items-center gap-2">
                            <code class="flex-1 px-3 py-2 bg-input border border-border rounded-lg text-xs font-mono text-foreground break-all">
                                {{ $this->getBookmarkletUrl() }}
                            </code>
                            <button
                                type="button"
                                onclick="navigator.clipboard.writeText('{{ $this->getBookmarkletUrl() }}'); this.innerText = 'Kopiert!'; setTimeout(() => this.innerText = 'Kopier', 2000);"
                                class="px-3 py-2 text-xs font-medium text-foreground bg-input border border-border rounded-lg hover:bg-card-hover transition-colors cursor-pointer whitespace-nowrap"
                            >
                                Kopier
                            </button>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    {{-- PIN Setup/Change Modal --}}
    @if($showPinModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" x-data
            x-on:keydown.escape.window="$wire.closePinModal()">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50" wire:click="closePinModal"></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-sm mx-4">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <h2 class="text-lg font-semibold text-foreground">
                            {{ $hasPin ? 'Endre PIN-kode' : 'Sett opp PIN-kode' }}
                        </h2>
                    </div>
                    <button wire:click="closePinModal"
                        class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <form wire:submit="savePin" class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Ny PIN-kode (4-6 siffer)</label>
                        <input type="password" inputmode="numeric" pattern="[0-9]*" maxlength="6" wire:model="newPin"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground text-center tracking-[0.5em] font-mono placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="••••" autofocus>
                        @error('newPin')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Bekreft PIN-kode</label>
                        <input type="password" inputmode="numeric" pattern="[0-9]*" maxlength="6" wire:model="confirmPin"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground text-center tracking-[0.5em] font-mono placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="••••">
                        @error('confirmPin')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2">
                        <label class="block text-sm font-medium text-foreground mb-1.5">Bekreft med passord</label>
                        <input type="password" wire:model="currentPassword"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="Ditt passord">
                        @error('currentPassword')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-end gap-3 pt-4">
                        <x-button type="button" variant="secondary" wire:click="closePinModal">Avbryt</x-button>
                        <x-button type="submit">Lagre PIN</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Remove PIN Modal --}}
    @if($showRemovePinModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" x-data
            x-on:keydown.escape.window="$wire.closeRemovePinModal()">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50" wire:click="closeRemovePinModal"></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-sm mx-4">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <h2 class="text-lg font-semibold text-foreground">Fjern PIN-kode</h2>
                    </div>
                    <button wire:click="closeRemovePinModal"
                        class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <form wire:submit="removePin" class="px-6 py-4 space-y-4">
                    <p class="text-sm text-muted">
                        Er du sikker på at du vil fjerne PIN-koden? Låseskjermen vil bli deaktivert.
                    </p>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">Bekreft med passord</label>
                        <input type="password" wire:model="currentPassword"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="Ditt passord" autofocus>
                        @error('currentPassword')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-end gap-3 pt-4">
                        <x-button type="button" variant="secondary" wire:click="closeRemovePinModal">Avbryt</x-button>
                        <x-button type="submit" variant="danger">Fjern PIN</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-page-container>
