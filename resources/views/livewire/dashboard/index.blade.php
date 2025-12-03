<div class="h-full flex flex-col">
    <!-- Title at top left -->
    <h1 class="text-3xl font-bold text-foreground mb-6">Kontrollpanel</h1>

    <!-- Centered content -->
    <div class="flex-1 flex items-center justify-center">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6 max-w-3xl w-full">
            <!-- BPA Card -->
            <a href="{{ route('bpa.dashboard') }}" wire:navigate class="block bg-card border border-border rounded-lg p-6 hover:bg-card-hover hover:border-accent transition-all group cursor-pointer">
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

            <!-- Medical Card -->
            <a href="{{ route('medical.dashboard') }}" wire:navigate class="block bg-card border border-border rounded-lg p-6 hover:bg-card-hover hover:border-accent transition-all group cursor-pointer">
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

            <!-- Economy Card -->
            <a href="{{ route('economy') }}" wire:navigate class="block bg-card border border-border rounded-lg p-6 hover:bg-card-hover hover:border-accent transition-all group cursor-pointer">
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

            <!-- Wishlist Card -->
            <a href="{{ route('wishlist') }}" wire:navigate class="block bg-card border border-border rounded-lg p-6 hover:bg-card-hover hover:border-accent transition-all group cursor-pointer">
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
        </div>
    </div>
</div>
