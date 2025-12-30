<div class="bg-card border border-border rounded-lg overflow-hidden h-full">
    <div class="px-5 py-4 border-b border-border">
        <h3 class="text-sm font-medium text-foreground">Ansatte kan ikke jobbe</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-border">
                    <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Navn</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Fra</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Til</th>
                    <th class="px-5 py-3 w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($this->unavailableEmployees as $unavailable)
                    <tr wire:key="unavailable-{{ $unavailable['id'] }}" class="hover:bg-card-hover transition-colors group">
                        <td class="px-5 py-3 text-sm text-foreground whitespace-nowrap">
                                <span class="sm:hidden">{{ Str::before($unavailable['name'], ' ') }}</span>
                                <span class="hidden sm:inline">{{ $unavailable['name'] }}</span>
                            </td>
                        <td class="px-5 py-3 text-sm text-foreground whitespace-nowrap">{{ $unavailable['from'] }}</td>
                        <td class="px-5 py-3 text-sm text-foreground whitespace-nowrap">{{ $unavailable['to'] }}</td>
                        <td class="px-5 py-3 text-right">
                            <button
                                wire:click="deleteUnavailable({{ $unavailable['id'] }})"
                                wire:confirm="Er du sikker på at du vil slette dette fraværet?"
                                type="button"
                                class="p-1.5 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded transition-colors sm:opacity-0 sm:group-hover:opacity-100 cursor-pointer"
                                title="Slett"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-6">
                            @if($showQuickAddForm)
                                {{-- Quick Add Form --}}
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div>
                                            <label class="block text-xs text-muted-foreground mb-1">Ansatt</label>
                                            <select
                                                wire:model="quickAddAssistantId"
                                                class="w-full bg-input border border-border rounded px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent cursor-pointer"
                                            >
                                                <option value="">Velg ansatt...</option>
                                                @foreach($this->allAssistants as $assistant)
                                                    <option value="{{ $assistant->id }}">{{ $assistant->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('quickAddAssistantId')
                                                <span class="text-xs text-destructive">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs text-muted-foreground mb-1">Fra dato</label>
                                            <input
                                                type="date"
                                                wire:model="quickAddFromDate"
                                                class="w-full bg-input border border-border rounded px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent cursor-pointer"
                                            >
                                            @error('quickAddFromDate')
                                                <span class="text-xs text-destructive">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs text-muted-foreground mb-1">Til dato</label>
                                            <input
                                                type="date"
                                                wire:model="quickAddToDate"
                                                class="w-full bg-input border border-border rounded px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent cursor-pointer"
                                            >
                                            @error('quickAddToDate')
                                                <span class="text-xs text-destructive">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            wire:click="closeQuickAddForm"
                                            type="button"
                                            class="px-3 py-1.5 text-xs font-medium text-foreground bg-card-hover border border-border rounded hover:bg-input transition-colors cursor-pointer"
                                        >
                                            Avbryt
                                        </button>
                                        <button
                                            wire:click="saveQuickAdd"
                                            type="button"
                                            class="px-3 py-1.5 text-xs font-medium text-background bg-accent rounded hover:bg-accent/90 transition-colors cursor-pointer"
                                        >
                                            Lagre
                                        </button>
                                    </div>
                                </div>
                            @else
                                {{-- Empty State with Add Button --}}
                                <div class="text-center">
                                    <p class="text-sm text-accent mb-3">Alle kan jobbe!</p>
                                    <button
                                        wire:click="openQuickAddForm"
                                        type="button"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-foreground bg-card-hover border border-border rounded hover:bg-input hover:border-accent/50 transition-colors cursor-pointer"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Legg til fravær
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
