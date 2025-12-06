<div
    class="p-4 md:p-6 space-y-6"
    x-data="{
        orderList: [],
        showOrderPanel: false,

        addToOrder(id, name, articleNumber) {
            const existing = this.orderList.find(item => item.id === id);
            if (existing) {
                existing.quantity++;
            } else {
                this.orderList.push({ id, name, articleNumber, quantity: 1 });
            }
        },

        removeFromOrder(id) {
            this.orderList = this.orderList.filter(item => item.id !== id);
        },

        updateQuantity(id, delta) {
            const item = this.orderList.find(i => i.id === id);
            if (item) {
                item.quantity = Math.max(1, item.quantity + delta);
            }
        },

        formatOrder() {
            return this.orderList.map(item => {
                const artNr = item.articleNumber ? `Art. nr: ${item.articleNumber}, ` : '';
                return `- ${item.name}, ${artNr}${item.quantity} stk.`;
            }).join('\n');
        },

        async copyOrder() {
            await navigator.clipboard.writeText(this.formatOrder());
        },

        clearOrder() {
            this.orderList = [];
            this.showOrderPanel = false;
        },

        get orderCount() {
            return this.orderList.reduce((sum, item) => sum + item.quantity, 0);
        }
    }"
>
    {{-- Header --}}
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Utstyr</h1>
            <p class="text-sm text-muted-foreground mt-1">Oversikt over medisinsk utstyr</p>
        </div>
        <button
            wire:click="openEquipmentModal"
            class="text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer flex items-center justify-center gap-2 p-2.5 sm:px-4 sm:py-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="hidden sm:inline">Opprett utstyr</span>
        </button>
    </div>

    {{-- Floating Order Cart Button --}}
    <button
        x-show="orderList.length > 0"
        x-on:click="showOrderPanel = true"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="fixed bottom-6 right-6 z-40 flex items-center gap-2 px-4 py-3 bg-accent text-black font-medium rounded-full shadow-lg hover:bg-accent-hover transition-colors cursor-pointer"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
        </svg>
        <span>Bestillingsliste</span>
        <span class="flex items-center justify-center w-6 h-6 bg-black/20 rounded-full text-sm" x-text="orderCount"></span>
    </button>

    {{-- Category Filter with Search --}}
    <div class="bg-card border border-border rounded-lg p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            {{-- Search (vises først på mobil) --}}
            <div class="relative order-first md:order-last">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Søk..."
                    class="w-full md:w-64 bg-input border border-border rounded-lg pl-10 pr-4 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                <svg class="w-4 h-4 text-muted-foreground absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            {{-- Category Pills --}}
            <div class="flex items-center gap-2 flex-wrap">
                <button
                    wire:click="selectCategory(null)"
                    class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors cursor-pointer
                        {{ $selectedCategory === null ? 'bg-accent text-black' : 'bg-card-hover text-foreground hover:bg-input' }}"
                >
                    Alle
                </button>
                @foreach($this->categories as $category)
                    <button
                        wire:click="selectCategory('{{ $category->id }}')"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors cursor-pointer
                            {{ $selectedCategory == $category->id ? 'bg-accent text-black' : 'bg-card-hover text-foreground hover:bg-input' }}"
                    >
                        {{ $category->name }}
                    </button>
                @endforeach

                {{-- Category Management Button --}}
                <button
                    wire:click="openCategoryModal"
                    class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded-lg transition-colors cursor-pointer"
                    title="Administrer kategorier"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Equipment Cards (Mobile) --}}
    <div class="md:hidden space-y-3">
        @forelse($this->equipment as $item)
            <div wire:key="equipment-mobile-{{ $item->id }}" class="bg-card border border-border rounded-lg p-4">
                {{-- Header: Navn + Actions --}}
                <div class="flex items-start justify-between gap-2 mb-1">
                    <div class="flex items-center gap-1.5" x-data="{ copied: false, copyText(text) { const ta = document.createElement('textarea'); ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); this.copied = true; setTimeout(() => this.copied = false, 1500); } }">
                        <h3 class="font-medium text-foreground">{{ $item->name }}</h3>
                        <button
                            type="button"
                            x-on:click="copyText('{{ addslashes($item->name) }}')"
                            class="p-1 text-muted-foreground hover:text-accent rounded transition-colors cursor-pointer"
                            x-bind:title="copied ? 'Kopiert!' : 'Kopier'"
                        >
                            <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <svg x-show="copied" x-cloak class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <button
                            x-on:click="addToOrder({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ $item->article_number }}')"
                            class="p-1.5 text-muted-foreground hover:text-accent hover:bg-accent/10 rounded transition-colors cursor-pointer"
                            title="Legg til i bestilling"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                        <button
                            wire:click="openEquipmentModal({{ $item->id }})"
                            class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer"
                            title="Rediger"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button
                            wire:click="deleteEquipment({{ $item->id }})"
                            wire:confirm="Er du sikker på at du vil slette dette utstyret?"
                            class="p-1.5 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 rounded transition-colors cursor-pointer"
                            title="Slett"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Hva --}}
                <p class="text-sm text-muted-foreground">{{ $item->type }}</p>

                {{-- Artikkelnummer --}}
                @if($item->article_number)
                    <div class="flex items-center gap-1.5 mt-2" x-data="{ copied: false, copyText(text) { const ta = document.createElement('textarea'); ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); this.copied = true; setTimeout(() => this.copied = false, 1500); } }">
                        <span class="text-sm text-muted-foreground">Art.nr:</span>
                        <span class="text-sm font-mono text-accent">{{ $item->article_number }}</span>
                        <button
                            type="button"
                            x-on:click="copyText('{{ $item->article_number }}')"
                            class="p-1 text-muted-foreground hover:text-accent rounded transition-colors cursor-pointer"
                            x-bind:title="copied ? 'Kopiert!' : 'Kopier'"
                        >
                            <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <svg x-show="copied" x-cloak class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    </div>
                @endif

                {{-- Kategori & Link --}}
                <div class="flex items-center flex-wrap gap-3 mt-3">
                    <span class="inline-flex px-2 py-1 text-xs font-medium bg-blue-500/10 text-blue-400 rounded">
                        {{ $item->category->name }}
                    </span>
                    @if($item->link)
                        <a href="{{ $item->link }}" target="_blank" class="text-sm text-accent hover:underline cursor-pointer flex items-center gap-1">
                            Se her
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-card border border-border rounded-lg p-8 text-center text-muted-foreground">
                <div class="flex flex-col items-center gap-2">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p>Ingen utstyr funnet</p>
                </div>
            </div>
        @endforelse

        {{-- Mobile Footer --}}
        <div class="text-sm text-muted-foreground text-center py-2">
            Viser {{ count($this->equipment) }} utstyr
        </div>
    </div>

    {{-- Equipment Table (Desktop) --}}
    <div class="hidden md:block bg-card border border-border rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-border bg-card-hover/50">
                        <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Hva</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Navn</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Artikkelnummer</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Kategori</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Link</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Handlinger</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($this->equipment as $item)
                        <tr wire:key="equipment-{{ $item->id }}" class="hover:bg-card-hover transition-colors">
                            <td class="px-5 py-4 text-sm text-foreground">{{ $item->type }}</td>
                            <td class="px-5 py-4 text-sm text-foreground font-medium">
                                <div class="flex items-center gap-1.5" x-data="{ copied: false, copyText(text) { const ta = document.createElement('textarea'); ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); this.copied = true; setTimeout(() => this.copied = false, 1500); } }">
                                    <span>{{ $item->name }}</span>
                                    <button
                                        type="button"
                                        x-on:click="copyText('{{ addslashes($item->name) }}')"
                                        class="p-1 text-muted-foreground hover:text-accent rounded transition-colors cursor-pointer"
                                        x-bind:title="copied ? 'Kopiert!' : 'Kopier'"
                                    >
                                        <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <svg x-show="copied" x-cloak class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm">
                                @if($item->article_number)
                                    <div class="flex items-center gap-1.5" x-data="{ copied: false, copyText(text) { const ta = document.createElement('textarea'); ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); this.copied = true; setTimeout(() => this.copied = false, 1500); } }">
                                        <span class="font-mono text-accent">{{ $item->article_number }}</span>
                                        <button
                                            type="button"
                                            x-on:click="copyText('{{ $item->article_number }}')"
                                            class="p-1 text-muted-foreground hover:text-accent rounded transition-colors cursor-pointer"
                                            x-bind:title="copied ? 'Kopiert!' : 'Kopier'"
                                        >
                                            <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <svg x-show="copied" x-cloak class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex px-2 py-1 text-xs font-medium bg-blue-500/10 text-blue-400 rounded">
                                    {{ $item->category->name }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-sm">
                                @if($item->link)
                                    <a href="{{ $item->link }}" target="_blank" class="text-accent hover:underline cursor-pointer flex items-center gap-1">
                                        Se her
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                @else
                                    <span class="text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        x-on:click="addToOrder({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ $item->article_number }}')"
                                        class="p-1.5 text-muted-foreground hover:text-accent hover:bg-accent/10 rounded transition-colors cursor-pointer"
                                        title="Legg til i bestilling"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                    <button
                                        wire:click="openEquipmentModal({{ $item->id }})"
                                        class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer"
                                        title="Rediger"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        wire:click="deleteEquipment({{ $item->id }})"
                                        wire:confirm="Er du sikker på at du vil slette dette utstyret?"
                                        class="p-1.5 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 rounded transition-colors cursor-pointer"
                                        title="Slett"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-muted-foreground">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p>Ingen utstyr funnet</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Table Footer --}}
        <div class="px-5 py-3 border-t border-border flex items-center justify-between">
            <p class="text-sm text-muted-foreground">
                Viser {{ count($this->equipment) }} utstyr
            </p>
        </div>
    </div>

    {{-- Equipment Modal --}}
    @if($showEquipmentModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.closeEquipmentModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closeEquipmentModal"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-lg mx-4">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        {{ $editingEquipmentId ? 'Rediger utstyr' : 'Opprett utstyr' }}
                    </h2>
                    <button
                        wire:click="closeEquipmentModal"
                        class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Hva *</label>
                        <input
                            type="text"
                            wire:model="equipmentType"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="F.eks. Tape, Kleberfjerner..."
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Navn *</label>
                        <input
                            type="text"
                            wire:model="equipmentName"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="Produktnavn"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Artikkelnummer</label>
                        <input
                            type="text"
                            wire:model="equipmentArticleNumber"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground font-mono placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="F.eks. 120700"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Kategori *</label>
                        <select
                            wire:model="equipmentCategoryId"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent cursor-pointer"
                        >
                            <option value="">Velg kategori...</option>
                            @foreach($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Link</label>
                        <input
                            type="url"
                            wire:model="equipmentLink"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="https://..."
                        >
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                    <button
                        wire:click="closeEquipmentModal"
                        class="px-4 py-2 text-sm font-medium text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                    >
                        Avbryt
                    </button>
                    <button
                        wire:click="saveEquipment"
                        class="px-4 py-2 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                    >
                        {{ $editingEquipmentId ? 'Lagre' : 'Opprett' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Category Modal --}}
    @if($showCategoryModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.closeCategoryModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closeCategoryModal"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-md mx-4">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">Administrer kategorier</h2>
                    <button
                        wire:click="closeCategoryModal"
                        class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-6 py-4 space-y-4">
                    {{-- Category List --}}
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach($this->categories as $category)
                            <div wire:key="category-{{ $category->id }}" class="flex items-center gap-2 p-2 rounded-lg hover:bg-card-hover transition-colors group">
                                @if($editingCategoryId === $category->id)
                                    <input
                                        type="text"
                                        wire:model="categoryName"
                                        wire:keydown.enter="saveCategory"
                                        wire:keydown.escape="$set('editingCategoryId', null)"
                                        class="flex-1 bg-input border border-accent rounded px-2 py-1 text-sm text-foreground focus:outline-none"
                                        autofocus
                                    >
                                    <button
                                        wire:click="saveCategory"
                                        class="p-1 text-accent hover:bg-accent/10 rounded transition-colors cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <button
                                        wire:click="$set('editingCategoryId', null)"
                                        class="p-1 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                @else
                                    <span class="flex-1 text-sm text-foreground">{{ $category->name }}</span>
                                    <button
                                        wire:click="editCategory({{ $category->id }})"
                                        class="p-1 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors opacity-0 group-hover:opacity-100 cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        wire:click="deleteCategory({{ $category->id }})"
                                        wire:confirm="Er du sikker på at du vil slette denne kategorien?"
                                        class="p-1 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 rounded transition-colors opacity-0 group-hover:opacity-100 cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Add New Category --}}
                    <div class="pt-4 border-t border-border">
                        <div class="flex items-center gap-2">
                            <input
                                type="text"
                                wire:model="categoryName"
                                wire:keydown.enter="saveCategory"
                                placeholder="Ny kategori..."
                                class="flex-1 bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                @if($editingCategoryId) disabled @endif
                            >
                            <button
                                wire:click="saveCategory"
                                class="p-2.5 sm:px-4 sm:py-2 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                                @if($editingCategoryId) disabled @endif
                            >
                                <svg class="w-4 h-4 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="hidden sm:inline">Legg til</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-border flex items-center justify-end">
                    <button
                        wire:click="closeCategoryModal"
                        class="px-4 py-2 text-sm font-medium text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                    >
                        Lukk
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Order Panel Modal --}}
    <div
        x-show="showOrderPanel"
        x-on:keydown.escape.window="showOrderPanel = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center"
        x-cloak
    >
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black/50"
            x-on:click="showOrderPanel = false"
        ></div>

        {{-- Modal --}}
        <div
            x-show="showOrderPanel"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[80vh] flex flex-col"
        >
            {{-- Header --}}
            <div class="px-6 py-4 border-b border-border flex items-center justify-between shrink-0">
                <h2 class="text-lg font-semibold text-foreground">Bestillingsliste</h2>
                <button
                    x-on:click="showOrderPanel = false"
                    class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-4 space-y-3 overflow-y-auto flex-1">
                <template x-for="item in orderList" :key="item.id">
                    <div class="flex items-center gap-3 p-3 bg-card-hover rounded-lg">
                        {{-- Item info --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-foreground truncate" x-text="item.name"></p>
                            <p class="text-xs text-muted-foreground" x-show="item.articleNumber">
                                Art. nr: <span x-text="item.articleNumber" class="font-mono text-accent"></span>
                            </p>
                        </div>

                        {{-- Quantity controls --}}
                        <div class="flex items-center gap-1 shrink-0">
                            <button
                                x-on:click="updateQuantity(item.id, -1)"
                                class="w-7 h-7 flex items-center justify-center text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                </svg>
                            </button>
                            <span class="w-8 text-center text-sm font-medium text-foreground" x-text="item.quantity"></span>
                            <button
                                x-on:click="updateQuantity(item.id, 1)"
                                class="w-7 h-7 flex items-center justify-center text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>

                        {{-- Remove button --}}
                        <button
                            x-on:click="removeFromOrder(item.id)"
                            class="p-1.5 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 rounded transition-colors cursor-pointer shrink-0"
                            title="Fjern"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </template>

                {{-- Empty state --}}
                <div x-show="orderList.length === 0" class="text-center py-8 text-muted-foreground">
                    <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="text-sm">Ingen elementer i bestillingslisten</p>
                </div>
            </div>

            {{-- Preview --}}
            <div x-show="orderList.length > 0" class="px-6 py-3 border-t border-border bg-card-hover/50 shrink-0">
                <p class="text-xs text-muted-foreground mb-2">Forhåndsvisning:</p>
                <pre class="text-xs text-foreground font-mono whitespace-pre-wrap bg-input rounded p-2 max-h-24 overflow-y-auto" x-text="formatOrder()"></pre>
            </div>

            {{-- Footer --}}
            <div x-show="orderList.length > 0" class="px-6 py-4 border-t border-border flex items-center justify-end gap-3 shrink-0">
                <button
                    x-data="{ copied: false }"
                    x-on:click="copyOrder(); copied = true; setTimeout(() => copied = false, 2000)"
                    class="px-4 py-2 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer flex items-center gap-2"
                >
                    <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <svg x-show="copied" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span x-text="copied ? 'Kopiert!' : 'Kopier'"></span>
                </button>
                <button
                    x-on:click="clearOrder()"
                    class="px-4 py-2 text-sm font-medium text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                >
                    Ferdig
                </button>
            </div>
        </div>
    </div>
</div>
