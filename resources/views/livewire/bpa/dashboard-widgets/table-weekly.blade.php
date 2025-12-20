<div class="bg-card border border-border rounded-lg overflow-hidden h-full flex flex-col">
    <div class="px-5 py-4 border-b border-border">
        <h3 class="text-sm font-medium text-foreground">Timer i uka</h3>
    </div>
    <div class="overflow-x-auto flex-1">
        <table class="w-full">
            <thead>
                <tr class="border-b border-border">
                    <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Uke</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Totalt</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Gjennomsnitt</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Antall</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @foreach($this->weeklyHours as $row)
                    <tr class="hover:bg-card-hover transition-colors">
                        <td class="px-5 py-3 text-sm text-foreground">{{ $row['week'] }}</td>
                        <td class="px-5 py-3 text-sm text-foreground">{{ $row['total'] }}</td>
                        <td class="px-5 py-3 text-sm text-foreground">{{ $row['average'] }}</td>
                        <td class="px-5 py-3 text-sm text-foreground">{{ $row['count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-border flex items-center justify-between mt-auto">
        <div class="flex items-center gap-2">
            <span class="text-xs text-muted-foreground">Pr. side</span>
            <select wire:model.live="weeklyPerPage" class="bg-input border border-border rounded px-2 py-1 text-xs text-foreground cursor-pointer">
                <option value="3">3</option>
                <option value="5">5</option>
                <option value="10">10</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            @if($weeklyPage > 1)
                <x-button variant="secondary" size="sm" wire:click="prevPage('weekly')">Forrige</x-button>
            @endif
            <span class="text-xs text-muted-foreground">{{ $weeklyPage }} / {{ $this->weeklyTotalPages }}</span>
            @if($weeklyPage < $this->weeklyTotalPages)
                <x-button variant="secondary" size="sm" wire:click="nextPage('weekly')">Neste</x-button>
            @endif
        </div>
    </div>
</div>
