<div class="p-4 sm:p-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Rommers</h1>
            <p class="text-sm text-muted-foreground mt-1 hidden sm:block">Poengføring for kortspillet Rommers</p>
        </div>
        <div class="flex items-center gap-2">
            <button
                class="px-4 py-2 text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="hidden sm:inline">Nytt spill</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Venstre kolonne: Aktivt spill (placeholder) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Ingen aktive spill --}}
            <div class="bg-card border border-border rounded-lg p-8 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-card-hover rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-foreground mb-2">Ingen aktive spill</h3>
                <p class="text-sm text-muted-foreground mb-4">Start et nytt spill for å begynne å føre poeng</p>
                <button class="px-4 py-2 text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer">
                    Start nytt spill
                </button>
            </div>

            {{-- Tidligere spill (placeholder) --}}
            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-border">
                    <h2 class="text-sm font-medium text-foreground">Tidligere spill</h2>
                </div>
                <div class="p-4 text-center text-muted-foreground text-sm">
                    Ingen tidligere spill funnet
                </div>
            </div>
        </div>

        {{-- Høyre kolonne: Nivåoversikt --}}
        <div class="space-y-6">
            {{-- Nivåkrav --}}
            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-border">
                    <h2 class="text-sm font-medium text-foreground">Nivåkrav</h2>
                </div>
                <div class="divide-y divide-border">
                    @foreach($levels as $level => $requirement)
                        <div class="px-4 py-3 flex items-center gap-3 hover:bg-card-hover transition-colors">
                            <span class="w-7 h-7 flex items-center justify-center bg-accent/10 text-accent text-sm font-bold rounded-full shrink-0">
                                {{ $level }}
                            </span>
                            <span class="text-sm text-foreground">{{ $requirement }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Poengberegning --}}
            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-border">
                    <h2 class="text-sm font-medium text-foreground">Poengberegning</h2>
                </div>
                <div class="p-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">3-7</span>
                        <span class="text-foreground font-medium">5 poeng</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">8-K</span>
                        <span class="text-foreground font-medium">10 poeng</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">2, A (Ess)</span>
                        <span class="text-foreground font-medium">20 poeng</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
