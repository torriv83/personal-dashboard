{{-- Context Menu for Assistants - Uses Alpine global store to survive Livewire re-renders --}}
<template x-teleport="body">
    <div
        x-data
        x-cloak
        x-show="$store.assistantMenu.show"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.outside="$store.assistantMenu.hide()"
        @contextmenu.outside="$store.assistantMenu.hide()"
        @keydown.escape.window="$store.assistantMenu.hide()"
        class="fixed z-[100] bg-card border border-border rounded-xl shadow-2xl py-1.5 min-w-44"
        :style="`left: ${$store.assistantMenu.x}px; top: ${$store.assistantMenu.y}px;`"
    >
        {{-- Active assistant menu --}}
        <div x-show="!$store.assistantMenu.isDeleted">
            <button
                @click="$store.assistantMenu.action('view')"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer text-left"
            >
                <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <span>Vis detaljer</span>
            </button>
            <button
                @click="$store.assistantMenu.action('copyTasksLink')"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer text-left"
            >
                <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
                <span>Kopier oppgaveliste-link</span>
            </button>
            <button
                @click="$store.assistantMenu.action('edit')"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer text-left"
            >
                <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <span>Rediger</span>
            </button>

            <div class="my-1.5 border-t border-border"></div>

            <button
                @click="$store.assistantMenu.action('delete')"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-destructive hover:bg-card-hover transition-colors cursor-pointer text-left"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Avslutt arbeidsforhold</span>
            </button>
        </div>

        {{-- Deleted assistant menu --}}
        <div x-show="$store.assistantMenu.isDeleted">
            <button
                @click="$store.assistantMenu.action('restore')"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer text-left"
            >
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Gjenaktiver</span>
            </button>

            <div class="my-1.5 border-t border-border"></div>

            <button
                @click="$store.assistantMenu.action('forceDelete')"
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
