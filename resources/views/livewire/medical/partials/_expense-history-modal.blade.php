@if($showExpenseHistoryModal)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center"
        x-on:keydown.escape.window="$wire.closeExpenseHistoryModal()"
    >
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black/50"
            wire:click="closeExpenseHistoryModal"
        ></div>

        {{-- Modal --}}
        <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-3xl mx-4 max-h-[90vh] flex flex-col">
            {{-- Header --}}
            <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-foreground">Betalingshistorikk {{ now()->year }}</h2>
                    <p class="text-xs text-muted-foreground mt-0.5">Totalt: {{ number_format($this->frikortTotal, 0, ',', ' ') }} kr</p>
                </div>
                <button
                    wire:click="closeExpenseHistoryModal"
                    class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="flex-1 overflow-y-auto">
                @if($this->expenses->isEmpty())
                    <div class="px-4 sm:px-6 py-12 text-center">
                        <svg class="w-12 h-12 mx-auto text-muted-foreground mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm text-muted-foreground">Ingen betalinger registrert i år</p>
                    </div>
                @else
                    <table class="w-full">
                        <thead class="bg-card-hover/50 sticky top-0">
                            <tr class="border-b border-border">
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-foreground uppercase tracking-wider">Dato</th>
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-foreground uppercase tracking-wider">Notis</th>
                                <th class="px-4 sm:px-6 py-3 text-right text-xs font-semibold text-foreground uppercase tracking-wider">Beløp</th>
                                <th class="px-4 sm:px-6 py-3 text-right text-xs font-semibold text-foreground uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach($this->expenses as $expense)
                                <tr class="hover:bg-card-hover transition-colors">
                                    <td class="px-4 sm:px-6 py-3 text-sm text-foreground whitespace-nowrap">
                                        {{ $expense->expense_date->format('d.m.Y') }}
                                    </td>
                                    <td class="px-4 sm:px-6 py-3 text-sm text-muted-foreground">
                                        {{ $expense->note ?? '-' }}
                                    </td>
                                    <td class="px-4 sm:px-6 py-3 text-sm text-foreground text-right whitespace-nowrap font-medium">
                                        {{ number_format($expense->amount, 0, ',', ' ') }} kr
                                    </td>
                                    <td class="px-4 sm:px-6 py-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            <button
                                                wire:click="editExpense({{ $expense->id }})"
                                                class="p-1 text-muted-foreground hover:text-accent rounded transition-colors cursor-pointer"
                                                title="Rediger"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button
                                                wire:click="deleteExpense({{ $expense->id }})"
                                                wire:confirm="Er du sikker på at du vil slette denne betalingen?"
                                                class="p-1 text-muted-foreground hover:text-destructive rounded transition-colors cursor-pointer"
                                                title="Slett"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Footer --}}
            <div class="px-4 sm:px-6 py-4 border-t border-border flex items-center justify-end">
                <x-button variant="secondary" wire:click="closeExpenseHistoryModal">Lukk</x-button>
            </div>
        </div>
    </div>
@endif
