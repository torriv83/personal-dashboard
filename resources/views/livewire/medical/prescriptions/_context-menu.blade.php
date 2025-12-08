{{-- Context Menu for Prescriptions --}}
<template x-teleport="body">
    <div
        x-data
        x-cloak
        x-show="$store.prescriptionMenu.show"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.outside="$store.prescriptionMenu.hide()"
        @contextmenu.outside="$store.prescriptionMenu.hide()"
        @keydown.escape.window="$store.prescriptionMenu.hide()"
        class="fixed z-[100] bg-card border border-border rounded-xl shadow-2xl py-1.5 min-w-44"
        :style="`left: ${$store.prescriptionMenu.x}px; top: ${$store.prescriptionMenu.y}px;`"
    >
        <button
            @click="$store.prescriptionMenu.action('edit')"
            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer text-left"
        >
            <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <span>Rediger</span>
        </button>

        <div class="my-1.5 border-t border-border"></div>

        <button
            @click="$store.prescriptionMenu.action('delete')"
            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-destructive hover:bg-card-hover transition-colors cursor-pointer text-left"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span>Slett</span>
        </button>
    </div>
</template>
