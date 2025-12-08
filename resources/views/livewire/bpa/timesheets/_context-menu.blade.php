{{-- Context Menu for Timesheets - Uses Alpine global store to survive Livewire re-renders --}}
<template x-teleport="body">
    <div
        x-data
        x-cloak
        x-show="$store.timesheetMenu.show"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.outside="$store.timesheetMenu.hide()"
        @contextmenu.outside="$store.timesheetMenu.hide()"
        @keydown.escape.window="$store.timesheetMenu.hide()"
        class="fixed z-[100] bg-card border border-border rounded-xl shadow-2xl py-1.5 min-w-48"
        :style="`left: ${$store.timesheetMenu.x}px; top: ${$store.timesheetMenu.y}px;`"
    >
        {{-- Non-archived shift menu --}}
        <div x-show="!$store.timesheetMenu.isArchived">
            <button
                @click="$store.timesheetMenu.action('edit')"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer text-left"
            >
                <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <span>Rediger</span>
            </button>

            <div class="my-1.5 border-t border-border"></div>

            <button
                @click="$store.timesheetMenu.action('toggleAway')"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer text-left"
            >
                <svg class="w-4 h-4" :class="$store.timesheetMenu.isUnavailable ? 'text-warning' : 'text-muted'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
                <span x-text="$store.timesheetMenu.isUnavailable ? 'Fjern borte-status' : 'Merk som borte'"></span>
            </button>
            <button
                @click="$store.timesheetMenu.action('toggleFullDay')"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer text-left"
            >
                <svg class="w-4 h-4" :class="$store.timesheetMenu.isAllDay ? 'text-accent' : 'text-muted'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span x-text="$store.timesheetMenu.isAllDay ? 'Fjern hel dag' : 'Merk som hel dag'"></span>
            </button>

            <div class="my-1.5 border-t border-border"></div>

            <button
                @click="$store.timesheetMenu.action('archive')"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-warning hover:bg-card-hover transition-colors cursor-pointer text-left"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
                <span>Arkiver</span>
            </button>
        </div>

        {{-- Archived shift menu --}}
        <div x-show="$store.timesheetMenu.isArchived">
            <button
                @click="$store.timesheetMenu.action('restore')"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer text-left"
            >
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Gjenopprett</span>
            </button>

            <div class="my-1.5 border-t border-border"></div>

            <button
                @click="$store.timesheetMenu.action('forceDelete')"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-destructive hover:bg-card-hover transition-colors cursor-pointer text-left"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Slett permanent</span>
            </button>
        </div>
    </div>
</template>
