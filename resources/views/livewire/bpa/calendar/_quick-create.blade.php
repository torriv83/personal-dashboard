{{-- Quick Create Popover (Pure Alpine.js) --}}
<template x-if="quickCreate.show">
    <div>
        {{-- Usynlig backdrop for a lukke --}}
        <div
            @click="closeQuickCreate()"
            class="fixed inset-0 z-40"
            @keydown.escape.window="closeQuickCreate()"
        ></div>

        {{-- Popover ved musepekeren --}}
        <div
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed z-50 bg-card border border-border rounded-xl shadow-2xl p-3 min-w-56"
            :style="`left: ${quickCreate.x}px; top: ${quickCreate.y}px;`"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between mb-2 pb-2 border-b border-border">
                <div>
                    <div class="text-sm font-medium text-foreground">Hurtigopprett</div>
                    <div class="text-xs text-muted"
                        x-text="(() => {
                            const startTime = quickCreate.time;
                            const endTime = quickCreate.endTime || (() => {
                                const [h, m] = startTime.split(':').map(Number);
                                const endH = Math.min(h + 3, 23);
                                return String(endH).padStart(2, '0') + ':' + String(m).padStart(2, '0');
                            })();
                            const date = new Date(quickCreate.date + 'T00:00:00');
                            const dateStr = date.toLocaleDateString('nb-NO', { day: '2-digit', month: '2-digit' });
                            const [sh, sm] = startTime.split(':').map(Number);
                            const [eh, em] = endTime.split(':').map(Number);
                            const durationMinutes = (eh * 60 + em) - (sh * 60 + sm);
                            const hours = Math.floor(durationMinutes / 60);
                            const mins = durationMinutes % 60;
                            const durationText = hours > 0 ? (mins > 0 ? hours + 't ' + mins + 'min' : hours + 't') : mins + 'min';
                            return dateStr + ' kl ' + startTime + ' - ' + endTime + ' (' + durationText + ')';
                        })()"
                    ></div>
                </div>
                <button
                    @click="closeQuickCreate()"
                    class="p-1 rounded text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Assistenter --}}
            <div class="space-y-0.5">
                <template x-for="assistant in assistants" :key="'qc-' + assistant.id">
                    <button
                        @click="quickCreateShift(assistant.id)"
                        class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-card-hover transition-colors cursor-pointer text-left"
                    >
                        <span
                            class="w-3 h-3 rounded-full shrink-0"
                            :style="'background-color: ' + (assistant.color || '#3b82f6')"
                        ></span>
                        <span class="text-sm text-foreground" x-text="assistant.name"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>
