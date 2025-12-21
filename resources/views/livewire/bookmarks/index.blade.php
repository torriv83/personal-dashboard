<x-page-container class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col xs:flex-row xs:items-center xs:justify-between gap-4">
        <div class="flex items-center gap-3">
            @if($folderId)
                {{-- Back button when inside a folder --}}
                <button
                    wire:click="goBack"
                    class="p-2 text-muted-foreground hover:text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                    title="Tilbake"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </button>
            @endif
            <div>
                @if($folderId)
                    @php $currentFolder = $this->getCurrentFolder(); @endphp
                    <h1 class="text-2xl font-bold text-foreground flex items-center gap-2">
                        <svg class="w-6 h-6 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        {{ $currentFolder?->name ?? 'Mappe' }}
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1 hidden sm:block">{{ $this->totalBookmarksCount }} bokmerker i mappen</p>
                @else
                    <h1 class="text-2xl font-bold text-foreground">Bokmerker</h1>
                    <p class="text-sm text-muted-foreground mt-1 hidden sm:block">Lagre og organiser lenker</p>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a
                href="{{ route('tools.bookmarks.import') }}"
                class="p-2.5 text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                title="Importer fra Linkwarden"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
            </a>
            <button
                wire:click="checkDeadLinks"
                class="p-2.5 text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                title="Sjekk døde lenker"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
            <button
                wire:click="openTagModal"
                class="p-2.5 text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                title="Ny tag"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
            </button>
            <button
                wire:click="openFolderModal"
                class="p-2.5 text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                title="Ny mappe"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
            </button>
            <button
                wire:click="openBookmarkModal"
                class="p-2.5 text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                title="Legg til bokmerke"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Toolbar: Search and Sort --}}
    <div class="flex flex-col sm:flex-row gap-4">
        {{-- Search --}}
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Søk i bokmerker..."
                class="w-full bg-input border border-border rounded-lg pl-10 pr-10 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
            @if($search)
                <button
                    wire:click="clearSearch"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            @endif
        </div>

        {{-- Sort --}}
        <select
            wire:model.live="sortBy"
            class="bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent cursor-pointer"
        >
            <option value="newest">Nyeste først</option>
            <option value="oldest">Eldste først</option>
            <option value="title_asc">A-Å</option>
            <option value="title_desc">Å-A</option>
        </select>
    </div>

    {{-- Bulk Actions --}}
    @if(count($selectedIds) > 0)
        <div class="flex items-center gap-4 p-3 bg-card border border-border rounded-lg">
            <span class="text-sm text-foreground">{{ count($selectedIds) }} valgt</span>
            <div class="flex items-center gap-2">
                <button
                    wire:click="openMoveModal"
                    class="px-3 py-1.5 text-sm text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                >
                    Flytt til mappe
                </button>
                <button
                    wire:click="bulkDelete"
                    wire:confirm="Er du sikker på at du vil slette {{ count($selectedIds) }} bokmerker?"
                    class="px-3 py-1.5 text-sm text-destructive bg-card-hover border border-border rounded-lg hover:bg-destructive hover:text-white transition-colors cursor-pointer"
                >
                    Slett
                </button>
            </div>
            <div class="ml-auto flex items-center gap-3">
                @if(count($selectedIds) < $this->bookmarks->count())
                    <button
                        wire:click="$set('selectAll', true)"
                        class="text-sm text-muted-foreground hover:text-foreground cursor-pointer"
                    >
                        Velg alle ({{ $this->bookmarks->count() }})
                    </button>
                @endif
                <button
                    wire:click="$set('selectedIds', [])"
                    class="text-sm text-muted-foreground hover:text-foreground cursor-pointer"
                >
                    Avbryt valg
                </button>
            </div>
        </div>
    @elseif($this->bookmarks->count() > 0)
        {{-- Quick select all button when inside a folder --}}
        <div class="flex items-center justify-end">
            <button
                wire:click="$set('selectAll', true)"
                class="text-sm text-muted-foreground hover:text-foreground cursor-pointer"
            >
                Velg alle ({{ $this->bookmarks->count() }})
            </button>
        </div>
    @endif

    {{-- Tag tabs (filter across all folders) --}}
    @if($this->tags->count() > 0)
        <div class="flex flex-wrap gap-2">
            <button
                wire:click="setTagFilter(null)"
                class="px-3 py-1.5 text-sm rounded-lg transition-colors cursor-pointer {{ $tagId === null ? 'bg-accent text-black' : 'bg-card-hover text-foreground border border-border hover:bg-input' }}"
            >
                Alle tags
            </button>
            @foreach($this->tags as $tag)
                <button
                    wire:click="setTagFilter({{ $tag->id }})"
                    class="px-3 py-1.5 text-sm rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 {{ $tagId === $tag->id ? 'text-black' : 'text-foreground border border-border hover:bg-input' }}"
                    style="{{ $tagId === $tag->id ? 'background-color: ' . $tag->color : '' }}"
                >
                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $tag->color }}"></span>
                    {{ $tag->name }}
                    <span class="text-xs opacity-70">({{ $tag->bookmarks_count }})</span>
                </button>
            @endforeach
        </div>
    @endif

    {{-- Bookmarks Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($this->bookmarks as $bookmark)
            <div
                wire:key="bookmark-{{ $bookmark->id }}"
                class="group relative bg-card border rounded-lg overflow-hidden hover:border-accent/50 transition-colors {{ $bookmark->is_read ? 'opacity-60' : '' }} {{ $bookmark->is_dead ? 'border-destructive/50' : 'border-border' }}"
            >
                {{-- Top row: Checkbox + Domain --}}
                <div class="flex items-center gap-2 p-4 pb-0">
                    <input
                        type="checkbox"
                        wire:model.live="selectedIds"
                        value="{{ $bookmark->id }}"
                        class="w-4 h-4 rounded border-border bg-input text-accent focus:ring-accent cursor-pointer shrink-0"
                    >
                    <img
                        src="https://www.google.com/s2/favicons?domain={{ $bookmark->getDomain() }}&sz=32"
                        alt=""
                        class="w-4 h-4 shrink-0"
                        loading="lazy"
                    >
                    <span class="text-xs text-muted-foreground truncate">{{ $bookmark->getDomain() }}</span>
                    @if($bookmark->is_dead)
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-xs font-medium text-destructive bg-destructive/10 rounded shrink-0" title="Død lenke">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Død
                        </span>
                    @endif
                </div>

                {{-- Card content (clickable to open bookmark) --}}
                <a
                    href="{{ $bookmark->url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="block p-4 pt-2 cursor-pointer"
                >

                    {{-- Title --}}
                    <h3 class="text-sm font-medium text-foreground line-clamp-2 mb-1">
                        {{ $bookmark->title }}
                    </h3>

                    {{-- Description --}}
                    @if($bookmark->description)
                        <p class="text-xs text-muted-foreground line-clamp-2 mb-2">
                            {{ $bookmark->description }}
                        </p>
                    @endif

                    {{-- Tags --}}
                    @if($bookmark->tags->count() > 0)
                        <div class="flex flex-wrap gap-1 mt-2">
                            @foreach($bookmark->tags as $tag)
                                <span
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 text-xs rounded"
                                    style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $tag->color }}"></span>
                                    {{ $tag->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Folder indicator (when viewing main list with search) --}}
                    @if($bookmark->folder && !$folderId && $search)
                        <div class="flex items-center gap-1 mt-2">
                            <svg class="w-3 h-3 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            <span class="text-xs text-muted-foreground">{{ $bookmark->folder->name }}</span>
                        </div>
                    @endif
                </a>

                {{-- Action buttons (visible on hover) --}}
                <div class="absolute top-2 right-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    {{-- Quick tag dropdown --}}
                    @if($this->tags->count() > 0)
                        <div class="relative" x-data="{ open: false }">
                            <button
                                @click="open = !open"
                                class="p-1.5 text-muted-foreground hover:text-foreground bg-card/80 backdrop-blur rounded transition-colors cursor-pointer {{ $bookmark->tags->count() > 0 ? 'text-accent' : '' }}"
                                title="Tags"
                            >
                                <svg class="w-4 h-4" fill="{{ $bookmark->tags->count() > 0 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                            </button>
                            <div
                                x-show="open"
                                @click.away="open = false"
                                x-transition
                                class="absolute right-0 mt-1 w-48 bg-card border border-border rounded-lg shadow-lg z-50 py-1"
                            >
                                @foreach($this->tags as $tag)
                                    @php $hasTag = $bookmark->tags->contains('id', $tag->id); @endphp
                                    <button
                                        wire:click="toggleBookmarkTag({{ $bookmark->id }}, {{ $tag->id }})"
                                        class="w-full px-3 py-1.5 text-left text-sm hover:bg-card-hover transition-colors cursor-pointer flex items-center gap-2"
                                    >
                                        <span class="w-3 h-3 rounded-full shrink-0 border-2" style="background-color: {{ $hasTag ? $tag->color : 'transparent' }}; border-color: {{ $tag->color }}"></span>
                                        <span class="text-foreground flex-1">{{ $tag->name }}</span>
                                        @if($hasTag)
                                            <svg class="w-4 h-4 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Toggle read --}}
                    <button
                        wire:click="toggleRead({{ $bookmark->id }})"
                        class="p-1.5 text-muted-foreground hover:text-foreground bg-card/80 backdrop-blur rounded transition-colors cursor-pointer"
                        title="{{ $bookmark->is_read ? 'Marker som ulest' : 'Marker som lest' }}"
                    >
                        <svg class="w-4 h-4" fill="{{ $bookmark->is_read ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>

                    {{-- Edit --}}
                    <button
                        wire:click="openBookmarkModal({{ $bookmark->id }})"
                        class="p-1.5 text-muted-foreground hover:text-foreground bg-card/80 backdrop-blur rounded transition-colors cursor-pointer"
                        title="Rediger"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>

                    {{-- More actions dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button
                            @click="open = !open"
                            class="p-1.5 text-muted-foreground hover:text-foreground bg-card/80 backdrop-blur rounded transition-colors cursor-pointer"
                            title="Flere valg"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                        </button>
                        <div
                            x-show="open"
                            @click.away="open = false"
                            x-transition
                            class="absolute right-0 mt-1 w-48 bg-card border border-border rounded-lg shadow-lg z-50"
                        >
                            {{-- Move to wishlist --}}
                            <button
                                wire:click="moveToWishlist({{ $bookmark->id }})"
                                @click="open = false"
                                class="w-full px-3 py-2 text-left text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer flex items-center gap-2"
                            >
                                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                                Flytt til ønskeliste
                            </button>

                            {{-- Check dead link --}}
                            <button
                                wire:click="checkSingleDeadLink({{ $bookmark->id }})"
                                @click="open = false"
                                class="w-full px-3 py-2 text-left text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Sjekk lenke
                            </button>

                            @if($bookmark->is_dead)
                                {{-- Clear dead status --}}
                                <button
                                    wire:click="clearDeadStatus({{ $bookmark->id }})"
                                    @click="open = false"
                                    class="w-full px-3 py-2 text-left text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer flex items-center gap-2"
                                >
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Fjern død-status
                                </button>
                            @endif

                            <div class="border-t border-border"></div>

                            {{-- Delete --}}
                            <button
                                wire:click="deleteBookmark({{ $bookmark->id }})"
                                wire:confirm="Er du sikker på at du vil slette dette bokmerket?"
                                @click="open = false"
                                class="w-full px-3 py-2 text-left text-sm text-destructive hover:bg-destructive hover:text-white transition-colors cursor-pointer flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Slett
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <svg class="w-12 h-12 mx-auto text-muted-foreground mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
                <p class="text-muted-foreground">
                    @if($search)
                        Ingen bokmerker funnet for "{{ $search }}"
                    @elseif($folderId)
                        Ingen bokmerker i denne mappen
                    @else
                        Ingen bokmerker ennå. Legg til ditt første bokmerke!
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    {{-- Load More / Count --}}
    @if($this->bookmarks->count() > 0)
        <div class="flex flex-col items-center gap-3 mt-6">
            <p class="text-sm text-muted-foreground">
                Viser {{ $this->bookmarks->count() }} av {{ $this->totalBookmarksCount }} bokmerker
            </p>
            @if($this->hasMoreBookmarks())
                <button
                    wire:click="loadMore"
                    wire:loading.attr="disabled"
                    wire:target="loadMore"
                    class="px-6 py-2.5 text-sm font-medium text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="loadMore">Last inn flere</span>
                    <span wire:loading wire:target="loadMore">Laster...</span>
                </button>
            @endif
        </div>
    @endif

    {{-- Folder Section (only on main view, not inside a folder) --}}
    @if($this->folders->count() > 0 && !$folderId)
        <div class="mt-8">
            <h2 class="text-lg font-semibold text-foreground mb-4">Mapper</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($this->folders as $folder)
                    <div class="group bg-card border border-border rounded-lg overflow-hidden hover:border-accent/50 transition-colors">
                        {{-- Clickable folder content --}}
                        <button
                            wire:click="openFolder({{ $folder->id }})"
                            class="w-full p-4 flex items-center gap-3 text-left cursor-pointer"
                        >
                            <div class="p-2 bg-card-hover rounded-lg">
                                <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-foreground truncate">{{ $folder->name }}</span>
                                    @if($folder->is_default)
                                        <span class="shrink-0 text-xs text-accent">(standard)</span>
                                    @endif
                                </div>
                                <span class="text-sm text-muted-foreground">{{ $folder->bookmarks_count }} bokmerker</span>
                            </div>
                            <svg class="w-5 h-5 text-muted-foreground group-hover:text-foreground transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        {{-- Action buttons --}}
                        <div class="px-4 pb-3 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button
                                wire:click="openFolderModal({{ $folder->id }})"
                                class="p-1.5 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                                title="Rediger"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <div class="relative" x-data="{ open: false }">
                                <button
                                    @click.stop="open = !open"
                                    class="p-1.5 text-muted-foreground hover:text-destructive rounded transition-colors cursor-pointer"
                                    title="Slett"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                                <div
                                    x-show="open"
                                    @click.away="open = false"
                                    x-transition
                                    class="absolute left-0 bottom-full mb-2 w-48 bg-card border border-border rounded-lg shadow-lg z-50"
                                >
                                    <button
                                        wire:click="deleteFolder({{ $folder->id }})"
                                        @click="open = false"
                                        class="w-full px-4 py-2 text-left text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                                    >
                                        Slett mappe, behold bokmerker
                                    </button>
                                    <button
                                        wire:click="deleteFolderWithBookmarks({{ $folder->id }})"
                                        wire:confirm="Er du sikker? Dette sletter mappen og alle {{ $folder->bookmarks_count }} bokmerker i den."
                                        @click="open = false"
                                        class="w-full px-4 py-2 text-left text-sm text-destructive hover:bg-destructive hover:text-white transition-colors cursor-pointer"
                                    >
                                        Slett mappe med innhold
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Bookmark Modal --}}
    @if($showBookmarkModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.closeBookmarkModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closeBookmarkModal"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                {{-- Header --}}
                <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        {{ $editingBookmarkId ? 'Rediger bokmerke' : 'Legg til bokmerke' }}
                    </h2>
                    <button
                        wire:click="closeBookmarkModal"
                        class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-4 sm:px-6 py-4 space-y-4">
                    {{-- URL --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">URL *</label>
                        <div class="flex gap-2">
                            <input
                                type="url"
                                wire:model="bookmarkUrl"
                                class="flex-1 bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                placeholder="https://example.com"
                                autofocus
                            >
                            <button
                                wire:click="fetchMetadata"
                                wire:loading.attr="disabled"
                                wire:target="fetchMetadata"
                                class="px-3 py-2 text-sm text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer disabled:opacity-50"
                            >
                                <span wire:loading.remove wire:target="fetchMetadata">Hent info</span>
                                <span wire:loading wire:target="fetchMetadata">Henter...</span>
                            </button>
                        </div>
                        @error('bookmarkUrl')
                            <p class="text-xs text-destructive mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Title --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Tittel *</label>
                        <input
                            type="text"
                            wire:model="bookmarkTitle"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="Sidens tittel"
                        >
                        @error('bookmarkTitle')
                            <p class="text-xs text-destructive mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Beskrivelse</label>
                        <textarea
                            wire:model="bookmarkDescription"
                            rows="2"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent resize-none"
                            placeholder="Valgfri beskrivelse..."
                        ></textarea>
                    </div>

                    {{-- Folder --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Mappe</label>
                        <select
                            wire:model="bookmarkFolderId"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent cursor-pointer"
                        >
                            <option value="">Ingen mappe</option>
                            @foreach($this->folders as $folder)
                                <option value="{{ $folder->id }}">
                                    {{ $folder->name }}
                                    @if($folder->is_default) (standard) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tags --}}
                    @if($this->tags->count() > 0)
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Tags</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach($this->tags as $tag)
                                    <label
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg cursor-pointer transition-colors border {{ in_array($tag->id, $bookmarkTagIds) ? 'border-transparent' : 'border-border bg-card-hover hover:bg-input' }}"
                                        style="{{ in_array($tag->id, $bookmarkTagIds) ? 'background-color: ' . $tag->color . '30; border-color: ' . $tag->color : '' }}"
                                    >
                                        <input
                                            type="checkbox"
                                            wire:model="bookmarkTagIds"
                                            value="{{ $tag->id }}"
                                            class="sr-only"
                                        >
                                        <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $tag->color }}"></span>
                                        <span class="text-sm text-foreground">{{ $tag->name }}</span>
                                        @if(in_array($tag->id, $bookmarkTagIds))
                                            <svg class="w-3.5 h-3.5 text-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-4 sm:px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                    <x-button variant="secondary" wire:click="closeBookmarkModal">Avbryt</x-button>
                    <x-button wire:click="saveBookmark">{{ $editingBookmarkId ? 'Lagre' : 'Legg til' }}</x-button>
                </div>
            </div>
        </div>
    @endif

    {{-- Folder Modal --}}
    @if($showFolderModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.closeFolderModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closeFolderModal"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
                {{-- Header --}}
                <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        {{ $editingFolderId ? 'Rediger mappe' : 'Ny mappe' }}
                    </h2>
                    <button
                        wire:click="closeFolderModal"
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
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="F.eks. TV-research, Jobbrelatert..."
                            autofocus
                        >
                        @error('folderName')
                            <p class="text-xs text-destructive mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            wire:model="folderIsDefault"
                            id="folderIsDefault"
                            class="w-4 h-4 rounded border-border bg-input text-accent focus:ring-accent cursor-pointer"
                        >
                        <label for="folderIsDefault" class="text-sm text-foreground cursor-pointer">
                            Bruk som standard-mappe for nye bokmerker
                        </label>
                    </div>

                    <p class="text-xs text-muted-foreground">
                        Mapper lar deg organisere bokmerker i kategorier. Standard-mappen velges automatisk når du legger til nye bokmerker.
                    </p>
                </div>

                {{-- Footer --}}
                <div class="px-4 sm:px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                    <x-button variant="secondary" wire:click="closeFolderModal">Avbryt</x-button>
                    <x-button wire:click="saveFolder">{{ $editingFolderId ? 'Lagre' : 'Opprett' }}</x-button>
                </div>
            </div>
        </div>
    @endif

    {{-- Tag Modal --}}
    @if($showTagModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.closeTagModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closeTagModal"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
                {{-- Header --}}
                <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        {{ $editingTagId ? 'Rediger tag' : 'Ny tag' }}
                    </h2>
                    <button
                        wire:click="closeTagModal"
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
                        <label class="block text-sm font-medium text-foreground mb-1">Tagnavn *</label>
                        <input
                            type="text"
                            wire:model="tagName"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="F.eks. Les senere, Jobb, Inspirasjon..."
                            autofocus
                        >
                        @error('tagName')
                            <p class="text-xs text-destructive mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Farge</label>
                        <div class="flex flex-wrap gap-2">
                            @php
                                $colors = [
                                    '#ef4444' => 'Rød',
                                    '#f97316' => 'Oransje',
                                    '#eab308' => 'Gul',
                                    '#22c55e' => 'Grønn',
                                    '#14b8a6' => 'Turkis',
                                    '#3b82f6' => 'Blå',
                                    '#6366f1' => 'Indigo',
                                    '#8b5cf6' => 'Lilla',
                                    '#ec4899' => 'Rosa',
                                    '#64748b' => 'Grå',
                                ];
                            @endphp
                            @foreach($colors as $hex => $name)
                                <button
                                    type="button"
                                    wire:click="$set('tagColor', '{{ $hex }}')"
                                    class="w-8 h-8 rounded-lg transition-transform cursor-pointer {{ $tagColor === $hex ? 'ring-2 ring-offset-2 ring-offset-card ring-foreground scale-110' : 'hover:scale-110' }}"
                                    style="background-color: {{ $hex }}"
                                    title="{{ $name }}"
                                ></button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Preview --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Forhåndsvisning</label>
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-sm rounded-lg"
                                style="background-color: {{ $tagColor }}30; color: {{ $tagColor }}"
                            >
                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $tagColor }}"></span>
                                {{ $tagName ?: 'Tagnavn' }}
                            </span>
                        </div>
                    </div>

                    <p class="text-xs text-muted-foreground">
                        Tags lar deg merke bokmerker på tvers av mapper. Du kan filtrere etter tags i hovedvisningen.
                    </p>
                </div>

                {{-- Footer --}}
                <div class="px-4 sm:px-6 py-4 border-t border-border flex items-center justify-between">
                    @if($editingTagId)
                        <button
                            wire:click="deleteTag({{ $editingTagId }})"
                            wire:confirm="Er du sikker på at du vil slette denne taggen?"
                            class="text-sm text-destructive hover:underline cursor-pointer"
                        >
                            Slett tag
                        </button>
                    @else
                        <div></div>
                    @endif
                    <div class="flex items-center gap-3">
                        <x-button variant="secondary" wire:click="closeTagModal">Avbryt</x-button>
                        <x-button wire:click="saveTag">{{ $editingTagId ? 'Lagre' : 'Opprett' }}</x-button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Move to Folder Modal --}}
    @if($showMoveModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.closeMoveModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closeMoveModal"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-md mx-4">
                {{-- Header --}}
                <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        Flytt {{ count($selectedIds) }} bokmerker
                    </h2>
                    <button
                        wire:click="closeMoveModal"
                        class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-4 sm:px-6 py-4">
                    <label class="block text-sm font-medium text-foreground mb-2">Velg mappe</label>
                    <select
                        wire:model="moveToFolderId"
                        class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent cursor-pointer"
                    >
                        <option value="">Fjern fra mappe</option>
                        @foreach($this->folders as $folder)
                            <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Footer --}}
                <div class="px-4 sm:px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                    <x-button variant="secondary" wire:click="closeMoveModal">Avbryt</x-button>
                    <x-button wire:click="bulkMove">Flytt</x-button>
                </div>
            </div>
        </div>
    @endif
</x-page-container>
