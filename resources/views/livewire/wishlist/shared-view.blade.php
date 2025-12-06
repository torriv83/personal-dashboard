<div class="max-w-4xl mx-auto p-4 sm:p-6 space-y-6">
    {{-- Header --}}
    <div class="text-center">
        <p class="text-sm text-muted-foreground mb-1">Ønskeliste fra Tor</p>
        <h1 class="text-2xl sm:text-3xl font-bold text-foreground">{{ $group->name }}</h1>
        <p class="text-sm text-muted-foreground mt-2">{{ count($this->items) }} {{ count($this->items) === 1 ? 'ønske' : 'ønsker' }}</p>
    </div>

    {{-- Items --}}
    <div class="bg-card border border-border rounded-lg overflow-hidden">
        {{-- Mobile Card Layout --}}
        <div class="md:hidden divide-y divide-border">
            @forelse($this->items as $item)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-foreground">{{ $item->name }}</p>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                @if($item->quantity > 1)
                                    <span class="text-xs text-muted-foreground">{{ $item->quantity }} stk</span>
                                @endif
                                @if($item->url)
                                    <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer" class="text-accent hover:underline cursor-pointer text-xs flex items-center gap-0.5">
                                        Se produkt
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-sm font-medium text-foreground">kr {{ number_format($item->price * $item->quantity, 0, ',', ' ') }}</span>
                            @if($item->quantity > 1)
                                <p class="text-xs text-muted-foreground">à kr {{ number_format($item->price, 0, ',', ' ') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <svg class="w-12 h-12 text-muted-foreground mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <p class="text-muted-foreground">Ingen ønsker i denne listen ennå</p>
                </div>
            @endforelse
        </div>

        {{-- Desktop Table Layout --}}
        <div class="hidden md:block">
            <table class="w-full">
                <thead class="bg-card-hover border-b border-border">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Ønske</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Lenke</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Pris</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase tracking-wider">Antall</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Totalt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($this->items as $item)
                        <tr class="hover:bg-card-hover transition-colors">
                            <td class="px-4 py-4 text-sm font-medium text-foreground">{{ $item->name }}</td>
                            <td class="px-4 py-4">
                                @if($item->url)
                                    <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer" class="text-accent hover:underline cursor-pointer text-sm flex items-center gap-1">
                                        Se produkt
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                @else
                                    <span class="text-muted-foreground text-sm">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm text-foreground text-right whitespace-nowrap">kr {{ number_format($item->price, 0, ',', ' ') }}</td>
                            <td class="px-4 py-4 text-sm text-foreground text-center">{{ $item->quantity }}</td>
                            <td class="px-4 py-4 text-sm font-medium text-foreground text-right whitespace-nowrap">kr {{ number_format($item->price * $item->quantity, 0, ',', ' ') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center">
                                <svg class="w-12 h-12 text-muted-foreground mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                                <p class="text-muted-foreground">Ingen ønsker i denne listen ennå</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer with total --}}
        @if(count($this->items) > 0)
            <div class="px-4 py-3 bg-card-hover border-t border-border flex items-center justify-between">
                <span class="text-sm text-muted-foreground">Totalt</span>
                <span class="text-lg font-semibold text-accent">kr {{ number_format($this->total, 0, ',', ' ') }}</span>
            </div>
        @endif
    </div>
</div>
