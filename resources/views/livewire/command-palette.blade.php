<div
    x-data="{
        selectedIndex: 0,
        get resultsCount() {
            return document.querySelectorAll('[data-result-item]').length;
        },
        selectNext() {
            this.selectedIndex = Math.min(this.selectedIndex + 1, this.resultsCount - 1);
            this.scrollToSelected();
        },
        selectPrev() {
            this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
            this.scrollToSelected();
        },
        scrollToSelected() {
            const selected = document.querySelector(`[data-result-item][data-index='${this.selectedIndex}']`);
            if (selected) {
                selected.scrollIntoView({ block: 'nearest' });
            }
        },
        navigateToSelected() {
            const selected = document.querySelector(`[data-result-item][data-index='${this.selectedIndex}']`);
            if (selected) {
                const url = selected.dataset.url;
                if (url) {
                    window.location.href = url;
                }
            }
        }
    }"
    x-init="
        $watch('$wire.search', () => selectedIndex = 0);
    "
    @keydown.ctrl.k.window.prevent="$wire.open()"
    @keydown.escape.window="$wire.isOpen && $wire.close()"
>
    {{-- Command Palette Modal --}}
    <template x-teleport="body">
        <div
            x-show="$wire.isOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[100] overflow-y-auto"
            style="display: none;"
        >
            {{-- Backdrop --}}
            <div
                class="fixed inset-0 bg-black/70 backdrop-blur-sm"
                @click="$wire.close()"
            ></div>

            {{-- Modal Content --}}
            <div class="fixed inset-0 flex items-start justify-center pt-[15vh] px-4">
                <div
                    x-show="$wire.isOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @click.outside="$wire.close()"
                    class="w-full max-w-xl bg-card border border-border rounded-xl shadow-2xl overflow-hidden"
                >
                    {{-- Search Input --}}
                    <div class="flex items-center gap-3 px-4 py-3 border-b border-border">
                        <svg class="w-5 h-5 text-muted-foreground shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            wire:model.live.debounce.150ms="search"
                            placeholder="Søk etter sider, assistenter, utstyr..."
                            class="flex-1 bg-transparent border-0 text-foreground placeholder-muted-foreground focus:outline-none focus:ring-0 text-base"
                            x-ref="searchInput"
                            x-init="$watch('$wire.isOpen', (value) => { if (value) { setTimeout(() => $refs.searchInput.focus(), 50); } })"
                            @keydown.arrow-down.prevent="selectNext()"
                            @keydown.arrow-up.prevent="selectPrev()"
                            @keydown.enter.prevent="navigateToSelected()"
                        >
                        <kbd class="hidden sm:inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-muted-foreground bg-background rounded border border-border">
                            ESC
                        </kbd>
                    </div>

                    {{-- Results --}}
                    <div class="max-h-80 overflow-y-auto py-2" wire:loading.class="opacity-50">
                        @php
                            $groupedResults = $this->results->groupBy('category');
                            $globalIndex = 0;
                        @endphp

                        @forelse($groupedResults as $category => $items)
                            <div class="px-3 py-2">
                                <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider px-2 mb-1">
                                    {{ $category }}
                                </h3>
                                <ul class="space-y-0.5">
                                    @foreach($items as $item)
                                        <li>
                                            <a
                                                href="{{ $item['url'] }}"
                                                data-result-item
                                                data-index="{{ $globalIndex }}"
                                                data-url="{{ $item['url'] }}"
                                                @mouseenter="selectedIndex = {{ $globalIndex }}"
                                                :class="selectedIndex === {{ $globalIndex }} ? 'bg-accent/10 text-accent' : 'text-foreground hover:bg-card-hover'"
                                                class="flex items-center gap-3 px-2 py-2 rounded-lg transition-colors cursor-pointer"
                                            >
                                                {{-- Icon --}}
                                                <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-background shrink-0">
                                                    @switch($item['icon'])
                                                        @case('plus')
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                            </svg>
                                                            @break
                                                        @case('home')
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                                            </svg>
                                                            @break
                                                        @case('clock')
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            @break
                                                        @case('calendar')
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                            @break
                                                        @case('users')
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                            </svg>
                                                            @break
                                                        @case('user')
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                            </svg>
                                                            @break
                                                        @case('file-text')
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                            @break
                                                        @case('file-plus')
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                            @break
                                                        @case('heart')
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                                            </svg>
                                                            @break
                                                        @case('package')
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                            </svg>
                                                            @break
                                                        @case('dollar-sign')
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            @break
                                                        @case('gift')
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                                            </svg>
                                                            @break
                                                        @case('settings')
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            </svg>
                                                            @break
                                                        @case('tool')
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" />
                                                            </svg>
                                                            @break
                                                        @default
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                            </svg>
                                                    @endswitch
                                                </span>

                                                {{-- Content --}}
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium truncate">
                                                        {{ $item['name'] }}
                                                    </p>
                                                    @if(!empty($item['subtitle']))
                                                        <p class="text-xs text-muted-foreground truncate">
                                                            {{ $item['subtitle'] }}
                                                        </p>
                                                    @endif
                                                </div>

                                                {{-- Arrow indicator for selected --}}
                                                <span
                                                    x-show="selectedIndex === {{ $globalIndex }}"
                                                    class="text-accent"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                    </svg>
                                                </span>
                                            </a>
                                        </li>
                                        @php $globalIndex++; @endphp
                                    @endforeach
                                </ul>
                            </div>
                        @empty
                            <div class="px-4 py-8 text-center text-muted-foreground">
                                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm">Ingen resultater for "{{ $search }}"</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between gap-4 px-4 py-2 border-t border-border bg-background/50 text-xs text-muted-foreground">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center gap-1">
                                <kbd class="px-1.5 py-0.5 rounded border border-border bg-background">↑</kbd>
                                <kbd class="px-1.5 py-0.5 rounded border border-border bg-background">↓</kbd>
                                naviger
                            </span>
                            <span class="flex items-center gap-1">
                                <kbd class="px-1.5 py-0.5 rounded border border-border bg-background">↵</kbd>
                                åpne
                            </span>
                        </div>
                        <span class="flex items-center gap-1">
                            <kbd class="px-1.5 py-0.5 rounded border border-border bg-background">Ctrl</kbd>
                            <kbd class="px-1.5 py-0.5 rounded border border-border bg-background">K</kbd>
                            hurtigsøk
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
