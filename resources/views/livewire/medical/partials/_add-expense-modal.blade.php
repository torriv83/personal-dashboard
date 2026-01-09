@if($showAddExpenseModal)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center"
        x-on:keydown.escape.window="$wire.closeAddExpenseModal()"
    >
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black/50"
            wire:click="closeAddExpenseModal"
        ></div>

        {{-- Modal --}}
        <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-md mx-4">
            {{-- Header --}}
            <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                <h2 class="text-lg font-semibold text-foreground">
                    {{ $editingExpenseId ? 'Rediger betaling' : 'Legg til betaling' }}
                </h2>
                <button
                    wire:click="closeAddExpenseModal"
                    class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <form wire:submit="saveExpense">
                <div class="px-4 sm:px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Beløp (kr) *</label>
                        <input
                            type="number"
                            wire:model="expenseAmount"
                            step="0.01"
                            min="0"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="0,00"
                            autofocus
                        >
                        @error('expenseAmount') <span class="text-destructive text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Dato *</label>
                        <input
                            type="date"
                            wire:model="expenseDate"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent cursor-pointer"
                        >
                        @error('expenseDate') <span class="text-destructive text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Notis</label>
                        <input
                            type="text"
                            wire:model="expenseNote"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="F.eks. Apotek 1, Fastlege..."
                            maxlength="500"
                        >
                        @error('expenseNote') <span class="text-destructive text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-4 sm:px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                    <x-button type="button" variant="secondary" wire:click="closeAddExpenseModal">Avbryt</x-button>
                    <x-button type="submit">{{ $editingExpenseId ? 'Lagre' : 'Legg til' }}</x-button>
                </div>
            </form>
        </div>
    </div>
@endif
