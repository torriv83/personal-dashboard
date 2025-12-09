<div class="py-4 sm:p-6 space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-foreground">Medisinsk</h1>
        <p class="text-sm text-muted-foreground mt-1">Oversikt over utstyr og resepter</p>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @foreach($this->stats as $stat)
            <div class="bg-card border border-border rounded-lg p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">{{ $stat['label'] }}</p>
                        <p class="text-3xl font-bold text-foreground mt-1">{{ $stat['value'] }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-accent/10 flex items-center justify-center">
                        @if($stat['icon'] === 'box')
                            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        @elseif($stat['icon'] === 'folder')
                            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                        @elseif($stat['icon'] === 'document')
                            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Alert Card - Next Expiry --}}
    @if($this->nextExpiry)
        <div class="bg-card border rounded-lg p-4 sm:p-5
            @if($this->nextExpiry->status === 'danger') border-red-500/50 bg-red-500/5
            @elseif($this->nextExpiry->status === 'warning') border-yellow-500/50 bg-yellow-500/5
            @else border-border @endif">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                {{-- Ikon og tekst --}}
                <div class="flex items-center gap-3 sm:gap-4 flex-1">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center shrink-0
                        @if($this->nextExpiry->status === 'danger') bg-red-500/20 text-red-400
                        @elseif($this->nextExpiry->status === 'warning') bg-yellow-500/20 text-yellow-400
                        @else bg-accent/20 text-accent @endif">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs sm:text-sm text-muted-foreground">Neste resept utløper</p>
                        <p class="text-base sm:text-lg font-semibold text-foreground truncate">{{ $this->nextExpiry->name }}</p>
                    </div>
                </div>
                {{-- Dager igjen - "0 dager igjen" på samme linje --}}
                <div class="flex items-baseline justify-center sm:justify-end gap-1.5">
                    <span class="text-xl sm:text-2xl font-bold
                        @if($this->nextExpiry->status === 'danger') text-red-400
                        @elseif($this->nextExpiry->status === 'warning') text-yellow-400
                        @else text-accent @endif">
                        {{ $this->nextExpiry->daysLeft }}
                    </span>
                    <span class="text-xs sm:text-sm text-muted-foreground">dager igjen</span>
                </div>
            </div>
        </div>
    @endif

    {{-- Expiring Prescriptions Table --}}
    <div class="bg-card border border-border rounded-lg overflow-hidden">
        <div class="px-5 py-4 border-b border-border flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-orange-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-foreground">Resepter som utløper</h3>
                    <p class="text-xs text-muted-foreground">Innen 30 dager</p>
                </div>
            </div>
            <a href="{{ route('medical.prescriptions') }}" class="text-sm text-accent hover:underline cursor-pointer flex items-center gap-1">
                Se alle
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-card-hover/50">
                    <tr class="border-b border-border">
                        <th class="px-4 sm:px-5 py-3 text-left text-xs font-semibold text-foreground uppercase tracking-wider whitespace-nowrap">Navn</th>
                        <th class="px-4 sm:px-5 py-3 text-left text-xs font-semibold text-foreground uppercase tracking-wider whitespace-nowrap">Gyldig til</th>
                        <th class="px-4 sm:px-5 py-3 text-right text-xs font-semibold text-foreground uppercase tracking-wider whitespace-nowrap">Dager igjen</th>
                        <th class="px-4 sm:px-5 py-3 text-center text-xs font-semibold text-foreground uppercase tracking-wider whitespace-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($this->expiringPrescriptions as $prescription)
                        <tr class="hover:bg-card-hover transition-colors">
                            <td class="px-4 sm:px-5 py-3 text-sm text-foreground whitespace-nowrap">{{ $prescription->name }}</td>
                            <td class="px-4 sm:px-5 py-3 text-sm text-muted-foreground whitespace-nowrap">
                                {{ $prescription->valid_to->format('d.m.Y') }}
                            </td>
                            <td class="px-4 sm:px-5 py-3 text-sm text-right whitespace-nowrap
                                @if($prescription->status === 'danger') text-red-400
                                @elseif($prescription->status === 'warning') text-yellow-400
                                @else text-foreground @endif">
                                {{ $prescription->daysLeft }}
                            </td>
                            <td class="px-4 sm:px-5 py-3 text-center whitespace-nowrap">
                                @if($prescription->status === 'expired')
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-red-500/20 text-red-400 rounded">
                                        Utløpt
                                    </span>
                                @elseif($prescription->status === 'danger')
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-red-500/20 text-red-400 rounded">
                                        Utløper
                                    </span>
                                @elseif($prescription->status === 'warning')
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-yellow-500/20 text-yellow-400 rounded">
                                        Snart
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-accent/20 text-accent rounded">
                                        OK
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="{{ route('medical.equipment') }}" class="bg-card border border-border rounded-lg p-5 hover:bg-card-hover transition-colors cursor-pointer group">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-foreground group-hover:text-accent transition-colors">Utstyr</p>
                    <p class="text-xs text-muted-foreground">Se og administrer medisinsk utstyr</p>
                </div>
                <svg class="w-5 h-5 text-muted-foreground group-hover:text-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
        </a>

        <a href="{{ route('medical.prescriptions') }}" class="bg-card border border-border rounded-lg p-5 hover:bg-card-hover transition-colors cursor-pointer group">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-purple-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-foreground group-hover:text-accent transition-colors">Resepter</p>
                    <p class="text-xs text-muted-foreground">Se og administrer resepter</p>
                </div>
                <svg class="w-5 h-5 text-muted-foreground group-hover:text-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
        </a>
    </div>
</div>
