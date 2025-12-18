<x-page-container class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Hjelpemidler</h1>
            <p class="text-sm text-muted-foreground mt-1 hidden sm:block">Oversikt over hjelpemidler fra NAV</p>
        </div>
        <div class="flex items-center gap-2">
            <button
                wire:click="openKategoriModal"
                class="p-2.5 text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                title="Ny kategori"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Brukerpass --}}
    <div class="bg-card border border-border rounded-lg px-4 py-3 flex items-center gap-3 w-fit">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
            </svg>
            <span class="text-sm text-muted-foreground">Brukerpass:</span>
            <span class="text-sm font-medium text-foreground">{{ $this->brukerpass ?: 'Ikke satt' }}</span>
        </div>
        <button
            wire:click="openBrukerpassModal"
            class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer"
            title="Rediger brukerpass"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
        </button>
    </div>

    {{-- Kategorier --}}
    <div class="space-y-4" x-sort="$wire.updateKategoriOrder($item, $position)" wire:ignore.self>
        @forelse($this->kategorier as $kategori)
            <div
                wire:key="kategori-{{ $kategori->id }}"
                x-sort:item="'kategori-{{ $kategori->id }}'"
                class="bg-card border border-border rounded-lg overflow-hidden"
                x-data="{ open: true }"
            >
                {{-- Kategori Header --}}
                <div
                    class="px-4 py-3 flex items-center justify-between hover:bg-card-hover transition-colors cursor-pointer"
                    @click="open = !open"
                >
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-muted-foreground cursor-grab shrink-0" x-sort:handle fill="currentColor" viewBox="0 0 24 24" @click.stop>
                            <circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" />
                            <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                            <circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" />
                        </svg>
                        <svg
                            class="w-4 h-4 text-muted-foreground transition-transform"
                            :class="open && 'rotate-90'"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <span class="text-sm font-medium text-foreground">{{ $kategori->name }}</span>
                        <span class="text-xs text-muted-foreground">({{ $kategori->hjelpemidler->count() }})</span>
                    </div>
                    <div class="flex items-center gap-1" @click.stop>
                        <button
                            wire:click="openItemModal(null, {{ $kategori->id }})"
                            class="p-1.5 text-muted-foreground hover:text-accent hover:bg-accent/10 rounded transition-colors cursor-pointer"
                            title="Legg til hjelpemiddel"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                        <button
                            wire:click="openKategoriModal({{ $kategori->id }})"
                            class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer"
                            title="Rediger kategori"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button
                            wire:click="deleteKategori({{ $kategori->id }})"
                            wire:confirm="Er du sikker på at du vil slette denne kategorien og alle hjelpemidler i den?"
                            class="p-1.5 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 rounded transition-colors cursor-pointer"
                            title="Slett kategori"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Hjelpemidler i kategorien --}}
                <div x-show="open" x-collapse class="border-t border-border">
                    @if($kategori->hjelpemidler->isEmpty())
                        <div class="px-4 py-8 text-center text-muted-foreground text-sm">
                            Ingen hjelpemidler i denne kategorien
                        </div>
                    @else
                        <div
                            class="divide-y divide-border"
                            x-sort="$wire.updateItemOrder({{ $kategori->id }}, $item, $position)"
                            wire:ignore.self
                        >
                            @foreach($kategori->hjelpemidler as $item)
                                <div
                                    wire:key="item-{{ $item->id }}"
                                    x-sort:item="'item-{{ $item->id }}'"
                                    class="px-4 py-3 hover:bg-card-hover/50 transition-colors"
                                >
                                    <div class="flex items-start gap-3">
                                        {{-- Drag handle --}}
                                        <svg class="w-4 h-4 text-muted-foreground cursor-grab mt-0.5 shrink-0" x-sort:handle fill="currentColor" viewBox="0 0 24 24">
                                            <circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" />
                                            <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                                            <circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" />
                                        </svg>

                                        {{-- Content --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-foreground">{{ $item->name }}</span>
                                                @if($item->url)
                                                    <a
                                                        href="{{ $item->url }}"
                                                        target="_blank"
                                                        class="text-accent hover:underline cursor-pointer flex items-center gap-0.5"
                                                        @click.stop
                                                    >
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                        </svg>
                                                    </a>
                                                @endif
                                            </div>

                                            {{-- Custom fields --}}
                                            @if($item->custom_fields && count($item->custom_fields) > 0)
                                                <div class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1">
                                                    @foreach($item->custom_fields as $field)
                                                        <span class="text-xs text-muted-foreground">
                                                            <span class="text-foreground/70">{{ $field['key'] }}:</span>
                                                            {{ $field['value'] }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Actions --}}
                                        <div class="flex items-center gap-1 shrink-0">
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
                                                wire:confirm="Er du sikker på at du vil slette dette hjelpemiddelet?"
                                                class="p-1.5 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 rounded transition-colors cursor-pointer"
                                                title="Slett"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-card border border-border rounded-lg px-4 py-12 text-center text-muted-foreground">
                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                <p>Ingen kategorier ennå</p>
                <p class="text-sm mt-1">Opprett en kategori for å komme i gang</p>
            </div>
        @endforelse
    </div>

    {{-- Hjelpemiddel Modal --}}
    @if($showItemModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.closeItemModal()"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50" wire:click="closeItemModal"></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                {{-- Header --}}
                <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        {{ $editingItemId ? 'Rediger hjelpemiddel' : 'Nytt hjelpemiddel' }}
                    </h2>
                    <button
                        wire:click="closeItemModal"
                        class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-4 sm:px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Navn *</label>
                        <input
                            type="text"
                            wire:model="itemName"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="F.eks. Panthera X"
                            autofocus
                        >
                        @error('itemName') <span class="text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Kategori *</label>
                        <select
                            wire:model="editingItemKategoriId"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent cursor-pointer"
                        >
                            <option value="">Velg kategori...</option>
                            @foreach($this->kategorier as $kat)
                                <option value="{{ $kat->id }}">{{ $kat->name }}</option>
                            @endforeach
                        </select>
                        @error('editingItemKategoriId') <span class="text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Nettside / URL</label>
                        <input
                            type="url"
                            wire:model="itemUrl"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="https://..."
                        >
                        @error('itemUrl') <span class="text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Custom Fields --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-foreground">Egendefinerte felter</label>
                            <button
                                type="button"
                                wire:click="addCustomField"
                                class="text-xs text-accent hover:underline cursor-pointer flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Legg til felt
                            </button>
                        </div>

                        @if(count($itemCustomFields) > 0)
                            <div class="space-y-2">
                                @foreach($itemCustomFields as $index => $field)
                                    <div class="flex items-start gap-2" wire:key="field-{{ $index }}">
                                        <input
                                            type="text"
                                            wire:model="itemCustomFields.{{ $index }}.key"
                                            class="w-1/3 bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                            placeholder="Feltnavn"
                                        >
                                        <input
                                            type="text"
                                            wire:model="itemCustomFields.{{ $index }}.value"
                                            class="flex-1 bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                            placeholder="Verdi"
                                        >
                                        <button
                                            type="button"
                                            wire:click="removeCustomField({{ $index }})"
                                            class="p-2 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 rounded transition-colors cursor-pointer shrink-0"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-muted-foreground">Ingen felter lagt til ennå</p>
                        @endif
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-4 sm:px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                    <button
                        wire:click="closeItemModal"
                        class="px-4 py-2 text-sm font-medium text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                    >
                        Avbryt
                    </button>
                    <button
                        wire:click="saveItem"
                        class="px-4 py-2 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                    >
                        {{ $editingItemId ? 'Lagre' : 'Legg til' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Kategori Modal --}}
    @if($showKategoriModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.closeKategoriModal()"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50" wire:click="closeKategoriModal"></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-md mx-4">
                {{-- Header --}}
                <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        {{ $editingKategoriId ? 'Rediger kategori' : 'Ny kategori' }}
                    </h2>
                    <button
                        wire:click="closeKategoriModal"
                        class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-4 sm:px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Kategorinavn *</label>
                        <input
                            type="text"
                            wire:model="kategoriName"
                            wire:keydown.enter="saveKategori"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="F.eks. Rullestol, Kjelker, Tilbehør..."
                            autofocus
                        >
                        @error('kategoriName') <span class="text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-4 sm:px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                    <button
                        wire:click="closeKategoriModal"
                        class="px-4 py-2 text-sm font-medium text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                    >
                        Avbryt
                    </button>
                    <button
                        wire:click="saveKategori"
                        class="px-4 py-2 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                    >
                        {{ $editingKategoriId ? 'Lagre' : 'Opprett' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Brukerpass Modal --}}
    @if($showBrukerpassModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.closeBrukerpassModal()"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50" wire:click="closeBrukerpassModal"></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-md mx-4">
                {{-- Header --}}
                <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">Brukerpass</h2>
                    <button
                        wire:click="closeBrukerpassModal"
                        class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-4 sm:px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Brukerpass-nummer</label>
                        <input
                            type="text"
                            wire:model="brukerpassValue"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="F.eks. 815784"
                            autofocus
                        >
                        <p class="text-xs text-muted-foreground mt-1">Dette er nummeret på brukerpasset ditt fra NAV Hjelpemiddelsentral.</p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-4 sm:px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                    <button
                        wire:click="closeBrukerpassModal"
                        class="px-4 py-2 text-sm font-medium text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                    >
                        Avbryt
                    </button>
                    <button
                        wire:click="saveBrukerpass"
                        class="px-4 py-2 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                    >
                        Lagre
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-page-container>
