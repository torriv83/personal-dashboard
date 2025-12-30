<div
    x-data="{
        selectedIndex: 0,
        favorites: $persist([]).as('command-palette-favorites'),
        get resultsCount() {
            return document.querySelectorAll('[data-result-item]').length;
        },
        isFavorite(url) {
            return this.favorites.includes(url);
        },
        toggleFavorite(url, event) {
            event.preventDefault();
            event.stopPropagation();
            if (this.isFavorite(url)) {
                this.favorites = this.favorites.filter(f => f !== url);
            } else {
                this.favorites = [...this.favorites, url];
            }
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
            const container = this.$refs.resultsContainer;
            if (selected && container) {
                const selectedRect = selected.getBoundingClientRect();
                const containerRect = container.getBoundingClientRect();

                if (selectedRect.bottom > containerRect.bottom) {
                    container.scrollTop += selectedRect.bottom - containerRect.bottom + 8;
                } else if (selectedRect.top < containerRect.top) {
                    container.scrollTop -= containerRect.top - selectedRect.top + 8;
                }
            }
        },
        navigateToSelected() {
            const selected = document.querySelector(`[data-result-item][data-index='${this.selectedIndex}']`);
            if (selected) {
                const action = selected.dataset.action;
                if (action) {
                    selected.click();
                } else {
                    const url = selected.dataset.url;
                    if (url) {
                        window.location.href = url;
                    }
                }
            }
        },
        sortResults() {
            const list = this.$refs.resultsList;
            if (!list) return;

            const items = Array.from(list.querySelectorAll('li'));
            items.sort((a, b) => {
                const aKey = a.querySelector('[data-favorite-key]')?.dataset.favoriteKey;
                const bKey = b.querySelector('[data-favorite-key]')?.dataset.favoriteKey;
                const aFav = this.isFavorite(aKey);
                const bFav = this.isFavorite(bKey);

                if (aFav && !bFav) return -1;
                if (!aFav && bFav) return 1;
                return 0;
            });

            // Reorder DOM and update indexes
            items.forEach((item, index) => {
                list.appendChild(item);
                const link = item.querySelector('[data-result-item]');
                if (link) {
                    link.dataset.index = index;
                }
            });

            if (this.$refs.resultsContainer) {
                this.$refs.resultsContainer.scrollTop = 0;
            }

            // Force Alpine to re-evaluate by toggling selectedIndex
            this.selectedIndex = -1;
            this.$nextTick(() => {
                this.selectedIndex = 0;
            });
        }
    }"
    x-init="
        $watch('$wire.search', () => { selectedIndex = 0; setTimeout(() => sortResults(), 50); });
        $watch('$wire.isOpen', (value) => { if (value) setTimeout(() => sortResults(), 50); });
        $watch('favorites', () => $nextTick(() => sortResults()));
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

                    {{-- Weight Registration Mode --}}
                    @if($actionMode === 'weight')
                        <div class="p-4">
                            <div class="flex items-center gap-3 mb-4">
                                <button
                                    wire:click="cancelAction"
                                    class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-card-hover rounded-lg transition-colors cursor-pointer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                        </svg>
                                    </div>
                                    <span class="font-medium text-foreground">Registrer vekt</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="relative flex-1">
                                    <input
                                        type="number"
                                        step="0.1"
                                        wire:model="weightInput"
                                        wire:keydown.enter="saveWeight"
                                        wire:keydown.escape="cancelAction"
                                        x-init="$nextTick(() => $el.focus())"
                                        class="w-full bg-input border border-border rounded-lg px-4 py-3 pr-12 text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent text-lg"
                                        placeholder="75.5"
                                        autofocus
                                    >
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-muted-foreground">kg</span>
                                </div>
                                <button
                                    wire:click="saveWeight"
                                    class="px-5 py-3 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer flex items-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Lagre
                                </button>
                            </div>

                            @error('weightInput')
                                <p class="text-destructive text-sm mt-2">{{ $message }}</p>
                            @enderror

                            <p class="text-xs text-muted-foreground mt-3">
                                Trykk <kbd class="px-1.5 py-0.5 rounded border border-border bg-background">Enter</kbd> for å lagre
                            </p>
                        </div>
                    @else
                        {{-- Results --}}
                        <div class="max-h-80 overflow-y-auto py-2" wire:loading.class="opacity-50" x-ref="resultsContainer">
                            @php
                                $allResults = $this->results->values()->all();
                                $globalIndex = 0;
                            @endphp

                            @if(count($allResults) > 0)
                            <div class="px-3 py-2">
                                <ul class="space-y-0.5" x-ref="resultsList">
                                    @foreach($allResults as $item)
                                        <li>
                                            @php
                                                $favoriteKey = isset($item['action']) ? 'action:'.$item['action'] : $item['url'];
                                            @endphp
                                            @if(isset($item['action']))
                                                <button
                                                    type="button"
                                                    wire:click="{{ $item['action'] === 'weight' ? 'startWeightRegistration' : '' }}"
                                                    data-result-item
                                                    data-index="{{ $globalIndex }}"
                                                    data-action="{{ $item['action'] }}"
                                                    data-favorite-key="{{ $favoriteKey }}"
                                                    @mouseenter="selectedIndex = parseInt($el.dataset.index)"
                                                    :class="selectedIndex === parseInt($el.dataset.index) ? 'bg-accent/10 text-accent' : 'text-foreground hover:bg-card-hover'"
                                                    class="w-full flex items-center gap-3 px-2 py-2 rounded-lg transition-colors cursor-pointer text-left"
                                                >
                                            @else
                                                <a
                                                    href="{{ $item['url'] }}"
                                                    data-result-item
                                                    data-index="{{ $globalIndex }}"
                                                    data-url="{{ $item['url'] }}"
                                                    data-favorite-key="{{ $favoriteKey }}"
                                                    @mouseenter="selectedIndex = parseInt($el.dataset.index)"
                                                    :class="selectedIndex === parseInt($el.dataset.index) ? 'bg-accent/10 text-accent' : 'text-foreground hover:bg-card-hover'"
                                                    class="flex items-center gap-3 px-2 py-2 rounded-lg transition-colors cursor-pointer"
                                                >
                                            @endif
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
                                                        @case('scale')
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                                            </svg>
                                                            @break
                                                        @case('dice')
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <rect x="3" y="3" width="18" height="18" rx="2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                                                                <circle cx="8" cy="8" r="1" fill="currentColor" />
                                                                <circle cx="16" cy="8" r="1" fill="currentColor" />
                                                                <circle cx="12" cy="12" r="1" fill="currentColor" />
                                                                <circle cx="8" cy="16" r="1" fill="currentColor" />
                                                                <circle cx="16" cy="16" r="1" fill="currentColor" />
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

                                                {{-- Favorite star --}}
                                                <span
                                                    role="button"
                                                    tabindex="0"
                                                    @click="toggleFavorite('{{ $favoriteKey }}', $event)"
                                                    @keydown.enter="toggleFavorite('{{ $favoriteKey }}', $event)"
                                                    class="p-1 rounded hover:bg-background transition-colors cursor-pointer shrink-0"
                                                    :class="isFavorite('{{ $favoriteKey }}') ? 'text-yellow-400' : 'text-muted-foreground/50 hover:text-muted-foreground'"
                                                >
                                                    <svg
                                                        class="w-4 h-4"
                                                        :fill="isFavorite('{{ $favoriteKey }}') ? 'currentColor' : 'none'"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                                    </svg>
                                                </span>

                                                {{-- Arrow indicator for selected --}}
                                                <span
                                                    x-show="selectedIndex === parseInt($el.closest('[data-result-item]').dataset.index)"
                                                    class="text-accent"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                    </svg>
                                                </span>
                                            @if(isset($item['action']))
                                                </button>
                                            @else
                                                </a>
                                            @endif
                                        </li>
                                        @php $globalIndex++; @endphp
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <div class="px-4 py-8 text-center text-muted-foreground">
                                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm">Ingen resultater for "{{ $search }}"</p>
                            </div>
                        @endif
                        </div>
                    @endif

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
