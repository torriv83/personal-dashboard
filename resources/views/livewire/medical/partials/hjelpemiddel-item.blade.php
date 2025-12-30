@php
    $hasChildren = $item->children && $item->children->count() > 0;
    $paddingLeft = $depth > 0 ? 'pl-' . ($depth * 6) : '';
@endphp

<div
    wire:key="item-{{ $item->id }}"
    x-sort:item="'item-{{ $item->id }}'"
    class="{{ $depth > 0 ? 'border-l-2 border-border/50 ml-6' : '' }}"
    @if($hasChildren)
        x-data="{ childrenOpen: true }"
    @endif
>
    <div class="px-4 py-3 hover:bg-card-hover/50 transition-colors {{ $depth > 0 ? 'pl-4' : '' }}">
        {{-- Header row: drag, expand, name, url, actions --}}
        <div class="flex items-center gap-3">
            {{-- Drag handle --}}
            <svg class="w-4 h-4 text-muted-foreground cursor-grab shrink-0" x-sort:handle fill="currentColor" viewBox="0 0 24 24">
                <circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" />
                <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                <circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" />
            </svg>

            {{-- Expand/collapse for items with children --}}
            @if($hasChildren)
                <button
                    @click="childrenOpen = !childrenOpen"
                    class="p-0.5 text-muted-foreground hover:text-foreground cursor-pointer shrink-0 -ml-1"
                >
                    <svg
                        class="w-4 h-4 transition-transform"
                        :class="childrenOpen && 'rotate-90'"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            @endif

            {{-- Name and URL --}}
            <div class="flex-1 min-w-0 flex items-center gap-2">
                @if($hasChildren)
                    <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                @endif
                <span class="text-sm font-medium text-foreground truncate">{{ $item->name }}</span>
                @if($hasChildren)
                    <span class="text-xs text-muted-foreground shrink-0">({{ $item->children->count() }})</span>
                @endif
                @if($item->url)
                    <a
                        href="{{ $item->url }}"
                        target="_blank"
                        class="text-accent hover:underline cursor-pointer flex items-center gap-0.5 shrink-0"
                        @click.stop
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-1 shrink-0">
                {{-- Add child item --}}
                <button
                    wire:click="openItemModal(null, {{ $kategoriId }}, {{ $item->id }})"
                    class="p-1.5 text-muted-foreground hover:text-accent hover:bg-accent/10 rounded transition-colors cursor-pointer"
                    title="Legg til under-element"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
                <button
                    wire:click="openItemModal({{ $item->id }})"
                    class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer"
                    title="Rediger"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>
                <button
                    wire:click="deleteItem({{ $item->id }})"
                    wire:confirm="Er du sikker på at du vil slette dette hjelpemiddelet{{ $hasChildren ? ' og alle under-elementer' : '' }}?"
                    class="p-1.5 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded transition-colors cursor-pointer"
                    title="Slett"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Custom fields - full width on mobile --}}
        @if($item->custom_fields && count($item->custom_fields) > 0)
            <div class="mt-1.5 pl-7 flex flex-wrap gap-x-4 gap-y-1">
                @foreach($item->custom_fields as $field)
                    <span
                        class="text-xs text-muted-foreground inline-flex items-center gap-1"
                        x-data="{ copied: false }"
                    >
                        <span class="text-foreground/70">{{ $field['key'] }}:</span>
                        <span>{{ $field['value'] }}</span>
                        <button
                            @click.stop="navigator.clipboard.writeText('{{ addslashes($field['value']) }}'); copied = true; setTimeout(() => copied = false, 1500)"
                            class="p-0.5 text-muted-foreground/50 hover:text-accent rounded transition-colors cursor-pointer"
                            :class="copied && 'text-accent'"
                            :title="copied ? 'Kopiert!' : 'Kopier'"
                        >
                            <svg x-show="!copied" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <svg x-show="copied" x-cloak class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Children --}}
    @if($hasChildren)
        <div
            x-show="childrenOpen"
            x-collapse
            x-sort="$wire.updateItemOrder({{ $kategoriId }}, $item, $position, {{ $item->id }})"
        >
            @foreach($item->children as $child)
                @include('livewire.medical.partials.hjelpemiddel-item', ['item' => $child, 'kategoriId' => $kategoriId, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
