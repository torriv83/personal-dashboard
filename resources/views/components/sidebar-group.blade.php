@props(['label', 'icon', 'active' => false])

<div x-data="{ open: @js($active) }" class="space-y-1">
    {{-- Parent button --}}
    <button
        x-on:click="open = !open"
        type="button"
        @class([
            'w-full flex items-center justify-between px-3 py-2.5 rounded-md transition-colors cursor-pointer',
            'bg-accent-dark text-accent' => $active,
            'text-muted hover:text-foreground hover:bg-card-hover' => !$active,
        ])
    >
        <span class="flex items-center gap-3">
            <span class="{{ $active ? 'text-accent' : 'text-current' }}">
                {{ $icon }}
            </span>
            <span>{{ $label }}</span>
        </span>
        <svg
            class="w-4 h-4 transition-transform duration-200"
            x-bind:class="open && 'rotate-180'"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    {{-- Child links --}}
    <div
        x-show="open"
        x-collapse
        x-cloak
        class="ml-4 pl-4 border-l border-border space-y-1"
    >
        {{ $slot }}
    </div>
</div>
