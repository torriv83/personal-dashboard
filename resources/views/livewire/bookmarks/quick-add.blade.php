<div class="w-full max-w-md">
    <x-card>
        @if($isSaved)
            {{-- Success state --}}
            <div class="text-center py-8">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-primary/20 flex items-center justify-center">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-foreground mb-2">Bokmerke lagret!</h2>
                <p class="text-muted mb-6">Bokmerket er lagt til i samlingen din.</p>

                <div class="flex flex-col gap-3">
                    <button
                        wire:click="addAnother"
                        class="w-full px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors cursor-pointer"
                    >
                        Legg til nytt bokmerke
                    </button>
                    <a
                        href="{{ route('tools.bookmarks') }}"
                        class="w-full px-4 py-2 bg-surface-alt text-foreground rounded-lg hover:bg-surface-alt/80 transition-colors text-center"
                    >
                        Gå til bokmerker
                    </a>
                    <button
                        onclick="window.close()"
                        class="text-muted hover:text-foreground transition-colors text-sm cursor-pointer"
                    >
                        Lukk vindu
                    </button>
                </div>
            </div>
        @else
            {{-- Form --}}
            <div class="space-y-4">
                <h2 class="text-xl font-semibold text-foreground text-center">Legg til bokmerke</h2>

                @if($duplicateUrl)
                    <div class="p-3 rounded-lg bg-error/10 border border-error/20">
                        <p class="text-sm text-error">
                            Denne URLen finnes allerede i bokmerkesamlingen din.
                        </p>
                    </div>
                @endif

                <form wire:submit="save" class="space-y-4">
                    {{-- URL --}}
                    <div>
                        <label for="url" class="block text-sm font-medium text-muted mb-1.5">URL</label>
                        <div class="flex gap-2">
                            <input
                                type="url"
                                id="url"
                                wire:model="url"
                                placeholder="https://example.com"
                                class="flex-1 px-3 py-2 bg-surface-alt border border-border rounded-lg text-foreground placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                            />
                            <button
                                type="button"
                                wire:click="fetchMetadata"
                                wire:loading.attr="disabled"
                                class="px-3 py-2 bg-surface-alt border border-border rounded-lg text-muted hover:text-foreground hover:bg-surface-alt/80 transition-colors cursor-pointer disabled:opacity-50"
                                title="Hent metadata"
                            >
                                <svg wire:loading.remove wire:target="fetchMetadata" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <svg wire:loading wire:target="fetchMetadata" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </button>
                        </div>
                        @error('url')
                            <p class="mt-1 text-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Title --}}
                    <div>
                        <label for="title" class="block text-sm font-medium text-muted mb-1.5">Tittel</label>
                        <input
                            type="text"
                            id="title"
                            wire:model="title"
                            placeholder="Sidetittel"
                            class="w-full px-3 py-2 bg-surface-alt border border-border rounded-lg text-foreground placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                        />
                        @error('title')
                            <p class="mt-1 text-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="description" class="block text-sm font-medium text-muted mb-1.5">Beskrivelse (valgfritt)</label>
                        <textarea
                            id="description"
                            wire:model="description"
                            rows="2"
                            placeholder="En kort beskrivelse..."
                            class="w-full px-3 py-2 bg-surface-alt border border-border rounded-lg text-foreground placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary resize-none"
                        ></textarea>
                    </div>

                    {{-- Folder --}}
                    <div>
                        <label for="folder" class="block text-sm font-medium text-muted mb-1.5">Mappe (valgfritt)</label>

                        {{-- Search and New Folder Button --}}
                        <div class="flex gap-2 mb-2">
                            <input
                                type="text"
                                wire:model.live="searchFolder"
                                placeholder="Søk i mapper..."
                                class="flex-1 px-3 py-2 bg-surface-alt border border-border rounded-lg text-foreground placeholder:text-muted text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                            />
                            <button
                                type="button"
                                wire:click="openFolderModal"
                                class="px-3 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors cursor-pointer text-sm whitespace-nowrap"
                                title="Ny mappe"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>

                        {{-- Folder Dropdown --}}
                        <select
                            id="folder"
                            wire:model="folderId"
                            class="w-full px-3 py-2 bg-surface-alt border border-border rounded-lg text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary cursor-pointer"
                        >
                            <option value="">Ingen mappe</option>
                            @foreach($this->folderTree as $folder)
                                <option value="{{ $folder->id }}">
                                    {{ $folder->name }}@if($folder->is_default) (standard)@endif
                                </option>
                                @foreach($folder->children as $child)
                                    <option value="{{ $child->id }}">
                                        &nbsp;&nbsp;&nbsp;&nbsp;↳ {{ $child->name }}@if($child->is_default) (standard)@endif
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>

                    {{-- Submit --}}
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="w-full px-4 py-2.5 bg-primary text-primary-foreground font-medium rounded-lg hover:bg-primary/90 transition-colors cursor-pointer disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="save">Lagre bokmerke</span>
                        <span wire:loading wire:target="save">Lagrer...</span>
                    </button>
                </form>
            </div>
        @endif
    </x-card>

    {{-- Folder Modal --}}
    @if($showFolderModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.showFolderModal = false"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="showFolderModal = false"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
                {{-- Header --}}
                <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        {{ $editingFolderId ? 'Rediger mappe' : 'Ny mappe' }}
                    </h2>
                    <button
                        wire:click="showFolderModal = false"
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
                        <label class="block text-sm font-medium text-foreground mb-1">Mappenavn *</label>
                        <input
                            type="text"
                            wire:model="folderName"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                            placeholder="F.eks. TV-research, Jobbrelatert..."
                            autofocus
                        >
                        @error('folderName')
                            <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Parent folder --}}
                    @if($this->folderTree->count() > 0)
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Overordnet mappe</label>
                            <select
                                wire:model="folderParentId"
                                class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary cursor-pointer"
                            >
                                <option value="">Ingen (hovedmappe)</option>
                                @foreach($this->folderTree as $rootFolder)
                                    @if($rootFolder->id !== $editingFolderId)
                                        <option value="{{ $rootFolder->id }}">{{ $rootFolder->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('folderParentId')
                                <p class="text-xs text-error mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-muted mt-1">Velg en mappe for å opprette en undermappe.</p>
                        </div>
                    @endif

                    <div class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            wire:model="folderIsDefault"
                            id="folderIsDefault"
                            class="w-4 h-4 rounded border-border bg-input text-primary focus:ring-primary cursor-pointer"
                        >
                        <label for="folderIsDefault" class="text-sm text-foreground cursor-pointer">
                            Bruk som standard-mappe for nye bokmerker
                        </label>
                    </div>

                    <p class="text-xs text-muted">
                        Mapper lar deg organisere bokmerker i kategorier. Standard-mappen velges automatisk når du legger til nye bokmerker.
                    </p>
                </div>

                {{-- Footer --}}
                <div class="px-4 sm:px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                    <button
                        wire:click="showFolderModal = false"
                        class="px-4 py-2 bg-surface-alt text-foreground rounded-lg hover:bg-surface-alt/80 transition-colors cursor-pointer"
                    >
                        Avbryt
                    </button>
                    <button
                        wire:click="saveFolder"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors cursor-pointer"
                    >
                        {{ $editingFolderId ? 'Lagre' : 'Opprett' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
