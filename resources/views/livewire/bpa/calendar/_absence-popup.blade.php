{{-- Absence Popup (for multi-day selection in month view) --}}
<div x-show="showAbsencePopup" x-cloak>
    {{-- Usynlig backdrop for å lukke --}}
    <div
        @click="closeAbsencePopup()"
        @keydown.escape.window="closeAbsencePopup()"
        class="fixed inset-0 z-40"
    ></div>

    {{-- Popover ved musepekeren --}}
    <div
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed z-50 bg-card border border-border rounded-xl shadow-2xl p-4 min-w-72"
        :style="`left: ${absencePopupX}px; top: ${absencePopupY}px;`"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between mb-3 pb-2 border-b border-border">
            <div>
                <div class="text-sm font-medium text-foreground flex items-center gap-2">
                    <svg class="w-4 h-4 text-destructive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    Opprett fravær
                </div>
                <div class="text-xs text-muted mt-0.5">
                    <span x-text="formatDateRange()"></span>
                    <span class="text-destructive font-medium">
                        (<span x-text="getSelectedDaysCount()"></span> <span x-text="getSelectedDaysCount() === 1 ? 'dag' : 'dager'"></span>)
                    </span>
                </div>
            </div>
            <button
                @click="closeAbsencePopup()"
                class="p-1 rounded text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Velg assistent --}}
        <div class="mb-3">
            <label class="block text-xs font-medium text-muted mb-1.5">Hvem er borte?</label>
            <div class="space-y-0.5 max-h-48 overflow-y-auto">
                @foreach($this->assistants as $assistant)
                    <button
                        @click="absenceAssistantId = {{ $assistant->id }}"
                        class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg transition-colors cursor-pointer text-left"
                        :class="absenceAssistantId === {{ $assistant->id }}
                            ? 'bg-destructive/20 ring-1 ring-destructive'
                            : 'hover:bg-card-hover'"
                    >
                        <span
                            class="w-3 h-3 rounded-full shrink-0"
                            style="background-color: {{ $assistant->color ?? '#3b82f6' }}"
                        ></span>
                        <span class="text-sm text-foreground">{{ $assistant->name }}</span>
                        <svg
                            x-show="absenceAssistantId === {{ $assistant->id }}"
                            x-cloak
                            class="w-4 h-4 ml-auto text-destructive"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Knapper --}}
        <div class="flex items-center gap-2 pt-2 border-t border-border">
            <button
                @click="createAbsence()"
                :disabled="!absenceAssistantId"
                class="flex-1 px-3 py-2 rounded-lg bg-destructive text-white font-medium text-sm transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed hover:bg-destructive/90"
            >
                Opprett fravær
            </button>
            <button
                @click="closeAbsencePopup()"
                class="px-3 py-2 rounded-lg bg-surface hover:bg-card-hover text-muted font-medium text-sm transition-colors cursor-pointer"
            >
                Avbryt
            </button>
        </div>
    </div>
</div>
