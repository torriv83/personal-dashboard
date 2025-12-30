<div>
    @if($showModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data="{
                handlePaste(event) {
                    const items = event.clipboardData?.items;
                    if (!items) return;

                    for (const item of items) {
                        if (item.type.startsWith('image/')) {
                            event.preventDefault();
                            const file = item.getAsFile();
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                $wire.handlePastedImage(e.target.result);
                            };
                            reader.readAsDataURL(file);
                            break;
                        }
                    }
                }
            }"
            x-on:paste.window="handlePaste($event)"
            x-on:keydown.escape.window="$wire.closeModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closeModal"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                {{-- Header --}}
                <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        Legg til i ønskeliste
                    </h2>
                    <button
                        wire:click="closeModal"
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
                            wire:model="itemNavn"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="Hva ønsker du deg?"
                            autofocus
                        >
                        @error('itemNavn') <span class="text-destructive text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">URL / Lenke</label>
                        <input
                            type="url"
                            wire:model="itemUrl"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="https://..."
                        >
                        @error('itemUrl') <span class="text-destructive text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Bilde</label>
                        <div class="flex items-center gap-2">
                            <input
                                type="url"
                                wire:model="itemImageUrl"
                                class="flex-1 bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                placeholder="https://... eller lim inn bilde (Ctrl+V)"
                            >
                            <button
                                type="button"
                                wire:click="fetchImageFromUrl"
                                wire:loading.attr="disabled"
                                wire:target="fetchImageFromUrl"
                                class="px-3 py-2 text-sm font-medium text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                title="Hent bilde fra URL"
                            >
                                <span wire:loading.remove wire:target="fetchImageFromUrl">Hent bilde</span>
                                <span wire:loading wire:target="fetchImageFromUrl">Henter...</span>
                            </button>
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">Høyreklikk på et bilde fra nettet, velg "Kopier bilde", og lim inn her (Ctrl+V)</p>
                        @if($itemImageUrl)
                            <div class="mt-2">
                                <img src="{{ $itemImageUrl }}" alt="Forhåndsvisning" class="h-20 w-20 object-cover rounded border border-border">
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Pris (kr) *</label>
                            <input
                                type="number"
                                wire:model="itemPris"
                                class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                placeholder="0"
                                min="0"
                            >
                            @error('itemPris') <span class="text-destructive text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Antall *</label>
                            <input
                                type="number"
                                wire:model="itemAntall"
                                class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                placeholder="1"
                                min="1"
                            >
                            @error('itemAntall') <span class="text-destructive text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Gruppe</label>
                        <select
                            wire:model="groupId"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent cursor-pointer"
                        >
                            <option value="">Ingen gruppe (frittstående)</option>
                            @foreach($this->groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Status</label>
                        <select
                            wire:model="itemStatus"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent cursor-pointer"
                        >
                            @foreach($this->statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-4 sm:px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                    <x-button variant="secondary" wire:click="closeModal">Avbryt</x-button>
                    <x-button wire:click="save">Legg til</x-button>
                </div>
            </div>
        </div>
    @endif
</div>
