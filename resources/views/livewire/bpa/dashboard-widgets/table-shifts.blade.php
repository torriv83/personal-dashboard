<div class="bg-card border border-border rounded-lg overflow-hidden h-full flex flex-col">
    <div class="px-5 py-4 border-b border-border">
        <h3 class="text-sm font-medium text-foreground">De neste arbeidstidene</h3>
    </div>
    <div class="overflow-x-auto flex-1">
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
    <div class="px-5 py-3 border-t border-border flex items-center justify-between mt-auto">
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
