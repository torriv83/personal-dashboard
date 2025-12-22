<x-page-container class="space-y-6" x-data="{
    draggingBookmarkId: null,
    contextMenu: { show: false, x: 0, y: 0, bookmarkId: null, isDeadBookmark: false },
    longPressTimer: null,
    dragEnabledId: null,
    touchStartPos: { x: 0, y: 0 },

    openContextMenu(event, bookmarkId, isDead) {
        this.contextMenu.x = event.clientX;
        this.contextMenu.y = event.clientY;
        this.contextMenu.bookmarkId = bookmarkId;
        this.contextMenu.isDeadBookmark = isDead;
        this.contextMenu.show = true;
    },
    closeContextMenu() {
        this.contextMenu.show = false;
    },

    handleTouchStart(event, bookmarkId) {
        // Store initial touch position
        this.touchStartPos.x = event.touches[0].clientX;
        this.touchStartPos.y = event.touches[0].clientY;

        // Set timer for long-press (600ms)
        this.longPressTimer = setTimeout(() => {
            // Enable drag for this bookmark
            this.dragEnabledId = bookmarkId;

            // Haptic feedback (vibration)
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }

            // Add visual feedback
            event.currentTarget.style.transform = 'scale(1.02)';
            event.currentTarget.style.boxShadow = '0 8px 16px rgba(0, 0, 0, 0.15)';
        }, 600);
    },

    handleTouchMove(event) {
        // If user moves finger more than 10px, cancel long-press and allow scroll
        const deltaX = Math.abs(event.touches[0].clientX - this.touchStartPos.x);
        const deltaY = Math.abs(event.touches[0].clientY - this.touchStartPos.y);

        if (deltaX > 10 || deltaY > 10) {
            this.cancelLongPress();
        }
    },

    handleTouchEnd(event) {
        // Reset visual feedback
        event.currentTarget.style.transform = '';
        event.currentTarget.style.boxShadow = '';

        this.cancelLongPress();
    },

    cancelLongPress() {
        if (this.longPressTimer) {
            clearTimeout(this.longPressTimer);
            this.longPressTimer = null;
        }
    },

    isDragEnabled(bookmarkId) {
        return this.dragEnabledId === bookmarkId;
    }
}">
    <div class="flex gap-6">
        {{-- Sidebar (desktop only) --}}
        <aside class="w-64 shrink-0 hidden lg:block">
            {{-- Sidebar header (matches main content header height) --}}
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-foreground">Mapper</h2>
                <p class="text-sm text-muted-foreground mt-1">Organiser bokmerkene dine</p>
            </div>

            {{-- Folder navigation --}}
            <div class="bg-card border border-border rounded-lg p-4 sticky top-4">
                {{-- All bookmarks (drop target for removing from folder) --}}
                <button
                    wire:click="openFolder(null)"
                    @dragover.prevent="$el.classList.add('ring-2', 'ring-accent')"
                    @dragleave="$el.classList.remove('ring-2', 'ring-accent')"
                    @drop.prevent="if (draggingBookmarkId) { $wire.dropBookmarkToFolder(draggingBookmarkId, null); $el.classList.remove('ring-2', 'ring-accent'); draggingBookmarkId = null; }"
                    @class([
                        'w-full flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors cursor-pointer',
                        'bg-accent text-black font-medium' => $folderId === null,
                        'text-foreground hover:bg-card-hover' => $folderId !== null,
                    ])
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                    <span>Alle bokmerker</span>
                </button>

                {{-- Folder tree --}}
                @if($this->folderTree->count() > 0)
                    <div
                        class="mt-3 pt-3 border-t border-border space-y-1"
                        x-sort="$wire.updateFolderOrder($item, $position)"
                        wire:ignore.self
                    >
                        @foreach($this->folderTree as $folder)
                            @php $isExpanded = in_array($folder->id, $expandedFolders); @endphp
                            <div
                                wire:key="folder-{{ $folder->id }}-{{ $isExpanded ? 'expanded' : 'collapsed' }}"
                                x-sort:item="'folder-{{ $folder->id }}'"
                                x-data="{ expanded: @js($isExpanded) }"
                            >
                                {{-- Parent folder --}}
                                <div class="flex items-center gap-1 cursor-grab active:cursor-grabbing">
                                    @if($folder->children->count() > 0)
                                        <button
                                            @click="expanded = !expanded; $wire.toggleFolderExpanded({{ $folder->id }})"
                                            class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                                        >
                                            <svg
                                                class="w-3 h-3 transition-transform"
                                                :class="{ 'rotate-90': expanded }"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    @else
                                        <div class="w-5"></div>
                                    @endif
                                    <button
                                        wire:click="openFolder({{ $folder->id }})"
                                        @dragover.prevent="$el.classList.add('ring-2', 'ring-accent')"
                                        @dragleave="$el.classList.remove('ring-2', 'ring-accent')"
                                        @drop.prevent="if (draggingBookmarkId) { $wire.dropBookmarkToFolder(draggingBookmarkId, {{ $folder->id }}); $el.classList.remove('ring-2', 'ring-accent'); draggingBookmarkId = null; }"
                                        class="flex-1 flex items-center gap-2 px-2 py-1.5 text-sm rounded-lg transition-colors cursor-pointer text-left {{ $folderId === $folder->id ? 'bg-accent text-black font-medium' : 'text-foreground hover:bg-card-hover' }}"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                        </svg>
                                        <span class="truncate flex-1">{{ $folder->name }}</span>
                                        <span class="text-xs opacity-70 shrink-0">{{ $folder->bookmarks_count + $folder->children->sum('bookmarks_count') }}</span>
                                    </button>
                                </div>

                                {{-- Children (subfolders) --}}
                                @if($folder->children->count() > 0)
                                    <div
                                        x-show="expanded"
                                        x-collapse
                                        class="ml-9 mt-1 space-y-1"
                                        x-sort="$wire.updateFolderOrder($item, $position)"
                                        wire:ignore.self
                                    >
                                        @foreach($folder->children as $child)
                                            <div
                                                wire:key="folder-{{ $child->id }}"
                                                x-sort:item="'folder-{{ $child->id }}'"
                                                class="cursor-grab active:cursor-grabbing"
                                            >
                                                <button
                                                    wire:click="openFolder({{ $child->id }})"
                                                    @dragover.prevent="$el.classList.add('ring-2', 'ring-accent')"
                                                    @dragleave="$el.classList.remove('ring-2', 'ring-accent')"
                                                    @drop.prevent="if (draggingBookmarkId) { $wire.dropBookmarkToFolder(draggingBookmarkId, {{ $child->id }}); $el.classList.remove('ring-2', 'ring-accent'); draggingBookmarkId = null; }"
                                                    class="flex-1 flex items-center gap-2 px-2 py-1.5 text-sm rounded-lg transition-colors cursor-pointer text-left {{ $folderId === $child->id ? 'bg-accent text-black font-medium' : 'text-foreground hover:bg-card-hover' }}"
                                                >
                                                    <svg class="w-3.5 h-3.5 shrink-0 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                    </svg>
                                                    <span class="truncate flex-1">{{ $child->name }}</span>
                                                    <span class="text-xs opacity-70 shrink-0">{{ $child->bookmarks_count }}</span>
                                                </button>
                                            </div>
                                        @endforeach

                                        {{-- Add subfolder button --}}
                                        <button
                                            wire:click="openFolderModal(null, {{ $folder->id }})"
                                            class="w-full flex items-center gap-2 px-2 py-1.5 text-sm text-muted-foreground hover:text-foreground rounded-lg hover:bg-card-hover transition-colors cursor-pointer"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            <span class="text-xs">Ny undermappe</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Add folder button --}}
                <button
                    wire:click="openFolderModal"
                    class="w-full flex items-center gap-2 px-3 py-2 mt-3 text-sm text-muted-foreground hover:text-foreground rounded-lg hover:bg-card-hover transition-colors cursor-pointer border border-dashed border-border"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Ny mappe
                </button>
            </div>
        </aside>

        {{-- Main content --}}
        <main class="flex-1 min-w-0 space-y-6">
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
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold text-foreground flex items-center gap-2">
                            <svg class="w-6 h-6 text-muted-foreground hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            {{ $currentFolder?->name ?? 'Mappe' }}
                        </h1>
                        {{-- Edit folder (desktop only) --}}
                        <button
                            wire:click="openFolderModal({{ $folderId }})"
                            class="hidden sm:flex p-1.5 text-muted-foreground hover:text-foreground rounded-lg hover:bg-card-hover transition-colors cursor-pointer"
                            title="Rediger mappe"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        {{-- Delete folder (desktop only) --}}
                        <button
                            x-data
                            @click="if(confirm('Slette mappen? Bokmerker flyttes til ingen mappe.')) $wire.deleteFolder({{ $folderId }})"
                            class="hidden sm:flex p-1.5 text-muted-foreground hover:text-destructive rounded-lg hover:bg-card-hover transition-colors cursor-pointer"
                            title="Slett mappe"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-muted-foreground mt-1 hidden sm:block">{{ $this->totalBookmarksCount }} bokmerker i mappen</p>
                @else
                    <h1 class="text-2xl font-bold text-foreground">Bokmerker</h1>
                    <p class="text-sm text-muted-foreground mt-1 hidden sm:block">Lagre og organiser lenker</p>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            {{-- Desktop: Show all buttons --}}
            <button
                wire:click="checkDeadLinks"
                class="hidden sm:flex p-2.5 text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                title="Sjekk døde lenker"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
            <button
                wire:click="openTagModal"
                class="hidden sm:flex p-2.5 text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                title="Ny tag"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
            </button>
            <button
                wire:click="openFolderModal"
                class="hidden sm:flex p-2.5 text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                title="Ny mappe"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
            </button>

            {{-- Add bookmark (always visible) --}}
            <button
                wire:click="openBookmarkModal"
                class="p-2.5 text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                title="Legg til bokmerke"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </button>

            {{-- Mobile: Dropdown menu for secondary actions --}}
            <div class="relative sm:hidden" x-data="{ open: false }">
                <button
                    @click="open = !open"
                    class="p-2.5 text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                    title="Flere valg"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                </button>
                <div
                    x-show="open"
                    @click.outside="open = false"
                    x-transition
                    class="absolute right-0 top-full mt-2 w-48 bg-card border border-border rounded-lg shadow-lg py-1 z-50"
                >
                    {{-- Top section: Create/Edit actions --}}
                    @if($folderId)
                        <button
                            wire:click="openFolderModal({{ $folderId }})"
                            @click="open = false"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Rediger mappe
                        </button>
                    @endif
                    <button
                        wire:click="openFolderModal"
                        @click="open = false"
                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                        </svg>
                        Ny mappe
                    </button>
                    <button
                        wire:click="openTagModal"
                        @click="open = false"
                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Ny tag
                    </button>

                    <div class="border-t border-border my-1"></div>

                    {{-- Middle section: Selection & Sorting --}}
                    @if($this->bookmarks->count() > 0 && count($selectedIds) === 0)
                        <button
                            wire:click="$set('selectAll', true)"
                            @click="open = false"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Velg alle ({{ $this->bookmarks->count() }})
                        </button>
                    @endif

                    <div class="px-4 py-2 text-xs font-medium text-muted-foreground uppercase tracking-wider">Sortering</div>
                    <button
                        wire:click="$set('sortBy', 'newest')"
                        @click="open = false"
                        class="w-full flex items-center gap-3 px-4 py-2 text-sm transition-colors cursor-pointer {{ $sortBy === 'newest' ? 'text-accent font-medium bg-accent/10' : 'text-foreground hover:bg-card-hover' }}"
                    >
                        Nyeste først
                    </button>
                    <button
                        wire:click="$set('sortBy', 'oldest')"
                        @click="open = false"
                        class="w-full flex items-center gap-3 px-4 py-2 text-sm transition-colors cursor-pointer {{ $sortBy === 'oldest' ? 'text-accent font-medium bg-accent/10' : 'text-foreground hover:bg-card-hover' }}"
                    >
                        Eldste først
                    </button>
                    <button
                        wire:click="$set('sortBy', 'title_asc')"
                        @click="open = false"
                        class="w-full flex items-center gap-3 px-4 py-2 text-sm transition-colors cursor-pointer {{ $sortBy === 'title_asc' ? 'text-accent font-medium bg-accent/10' : 'text-foreground hover:bg-card-hover' }}"
                    >
                        A-Å
                    </button>
                    <button
                        wire:click="$set('sortBy', 'title_desc')"
                        @click="open = false"
                        class="w-full flex items-center gap-3 px-4 py-2 text-sm transition-colors cursor-pointer {{ $sortBy === 'title_desc' ? 'text-accent font-medium bg-accent/10' : 'text-foreground hover:bg-card-hover' }}"
                    >
                        Å-A
                    </button>

                    <div class="border-t border-border my-1"></div>

                    {{-- Bottom section: Other actions --}}
                    <button
                        wire:click="checkDeadLinks"
                        @click="open = false"
                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Sjekk døde lenker
                    </button>
                    @if($folderId)
                        <button
                            @click="if(confirm('Slette mappen? Bokmerker flyttes til ingen mappe.')) $wire.deleteFolder({{ $folderId }}); open = false"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-destructive hover:bg-card-hover transition-colors cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Slett mappe
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Toolbar: Search and Sort --}}
    <div class="flex flex-col sm:flex-row gap-4">
        {{-- Mobile: Search bar (shown when toggled from FAB) --}}
        @if($showMobileSearch)
            <div class="sm:hidden" x-data x-init="$nextTick(() => $refs.mobileSearchInput.focus())">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        x-ref="mobileSearchInput"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Søk i bokmerker..."
                        @keydown.escape="$wire.closeMobileSearch()"
                        class="w-full bg-input border border-border rounded-lg pl-10 pr-10 py-2.5 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                    >
                    <button
                        wire:click="closeMobileSearch"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        {{-- Desktop: Full toolbar --}}
        <div class="hidden sm:flex sm:flex-row gap-4 flex-1">
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
        {{-- Quick select all button (desktop only - mobile has it in toolbar) --}}
        <div class="hidden sm:flex items-center justify-end">
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

    {{-- Pinned Bookmarks Section (only on main view) --}}
    @if($folderId === null && $search === '' && $tagId === null && $this->pinnedBookmarks->count() > 0)
        <div class="space-y-3">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-accent" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
                <h2 class="text-sm font-medium text-foreground">Festede</h2>
                <span class="text-xs text-muted-foreground">({{ $this->pinnedBookmarks->count() }})</span>
            </div>
            <div
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
                x-sort="$wire.updatePinnedOrder($item, $position)"
            >
                @foreach($this->pinnedBookmarks as $bookmark)
                    <div
                        wire:key="pinned-{{ $bookmark->id }}"
                        x-sort:item="'pinned-{{ $bookmark->id }}'"
                        :draggable="isDragEnabled({{ $bookmark->id }}) ? 'true' : 'false'"
                        @dragstart="draggingBookmarkId = {{ $bookmark->id }}; $event.dataTransfer.effectAllowed = 'move'"
                        @dragend="draggingBookmarkId = null; dragEnabledId = null"
                        @touchstart="handleTouchStart($event, {{ $bookmark->id }})"
                        @touchmove="handleTouchMove($event)"
                        @touchend="handleTouchEnd($event)"
                        @contextmenu.prevent="openContextMenu($event, {{ $bookmark->id }}, {{ $bookmark->is_dead ? 'true' : 'false' }})"
                        class="group relative bg-card border rounded-lg hover:border-accent/50 transition-all duration-200 {{ $bookmark->is_read ? 'opacity-60' : '' }} {{ $bookmark->is_dead ? 'border-destructive/50' : 'border-border' }} ring-2 ring-accent/20"
                        :class="isDragEnabled({{ $bookmark->id }}) ? 'cursor-grabbing' : 'sm:cursor-grab'"
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

                            {{-- Folder indicator --}}
                            @if($bookmark->folder)
                                <div class="flex items-center gap-1 mt-2">
                                    <svg class="w-3 h-3 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                    </svg>
                                    <span class="text-xs text-muted-foreground">{{ $bookmark->folder->name }}</span>
                                </div>
                            @endif
                        </a>

                        {{-- Action buttons (visible on hover) --}}
                        <div class="absolute top-2 right-2 flex items-center gap-1 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
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

                            {{-- Toggle pin (always pinned in this section) --}}
                            <button
                                wire:click="togglePin({{ $bookmark->id }})"
                                class="p-1.5 text-accent bg-card/80 backdrop-blur rounded transition-colors cursor-pointer"
                                title="Fjern fra festede"
                            >
                                <svg class="w-4 h-4" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                </svg>
                            </button>

                            {{-- Preview --}}
                            <button
                                wire:click="openPreview({{ $bookmark->id }})"
                                class="p-1.5 text-muted-foreground hover:text-foreground bg-card/80 backdrop-blur rounded transition-colors cursor-pointer"
                                title="Forhåndsvisning"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
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
                @endforeach
            </div>
        </div>

        {{-- Divider between pinned and regular bookmarks --}}
        <div class="border-t border-border"></div>
    @endif

    {{-- Bookmarks Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        {{-- Child folders (shown first when inside a parent folder) --}}
        @foreach($this->childFolders as $childFolder)
            <div
                wire:key="child-folder-{{ $childFolder->id }}"
                wire:click="openFolder({{ $childFolder->id }})"
                class="group relative bg-card border border-border rounded-lg hover:border-accent/50 transition-colors cursor-pointer"
            >
                <div class="p-4">
                    {{-- Folder icon and name --}}
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-medium text-foreground truncate">{{ $childFolder->name }}</h3>
                            <p class="text-xs text-muted-foreground">{{ $childFolder->bookmarks_count }} bokmerker</p>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        @forelse($this->bookmarks as $bookmark)
            <div
                wire:key="bookmark-{{ $bookmark->id }}"
                :draggable="isDragEnabled({{ $bookmark->id }}) ? 'true' : 'false'"
                @dragstart="draggingBookmarkId = {{ $bookmark->id }}; $event.dataTransfer.effectAllowed = 'move'"
                @dragend="draggingBookmarkId = null; dragEnabledId = null"
                @touchstart="handleTouchStart($event, {{ $bookmark->id }})"
                @touchmove="handleTouchMove($event)"
                @touchend="handleTouchEnd($event)"
                @contextmenu.prevent="openContextMenu($event, {{ $bookmark->id }}, {{ $bookmark->is_dead ? 'true' : 'false' }})"
                class="group relative bg-card border rounded-lg hover:border-accent/50 transition-all duration-200 {{ $bookmark->is_read ? 'opacity-60' : '' }} {{ $bookmark->is_dead ? 'border-destructive/50' : 'border-border' }}"
                :class="isDragEnabled({{ $bookmark->id }}) ? 'cursor-grabbing' : 'sm:cursor-grab'"
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
                <div class="absolute top-2 right-2 flex items-center gap-1 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
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

                    {{-- Toggle pin --}}
                    <button
                        wire:click="togglePin({{ $bookmark->id }})"
                        class="p-1.5 text-muted-foreground hover:text-foreground bg-card/80 backdrop-blur rounded transition-colors cursor-pointer {{ $bookmark->is_pinned ? 'text-accent' : '' }}"
                        title="{{ $bookmark->is_pinned ? 'Fjern fra festede' : 'Fest bokmerke' }}"
                    >
                        <svg class="w-4 h-4" fill="{{ $bookmark->is_pinned ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                    </button>

                    {{-- Preview --}}
                    <button
                        wire:click="openPreview({{ $bookmark->id }})"
                        class="p-1.5 text-muted-foreground hover:text-foreground bg-card/80 backdrop-blur rounded transition-colors cursor-pointer"
                        title="Forhåndsvisning"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
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
            @if($this->childFolders->isEmpty())
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
            @endif
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

                    {{-- Parent folder --}}
                    @if($this->rootFolders->count() > 0)
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Overordnet mappe</label>
                            <select
                                wire:model="folderParentId"
                                class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent cursor-pointer"
                            >
                                <option value="">Ingen (hovedmappe)</option>
                                @foreach($this->rootFolders as $rootFolder)
                                    @if($rootFolder->id !== $editingFolderId)
                                        <option value="{{ $rootFolder->id }}">{{ $rootFolder->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('folderParentId')
                                <p class="text-xs text-destructive mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-muted-foreground mt-1">Velg en mappe for å opprette en undermappe.</p>
                        </div>
                    @endif

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
                        @foreach($this->folderTree as $folder)
                            <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                            @foreach($folder->children as $child)
                                <option value="{{ $child->id }}">&nbsp;&nbsp;&nbsp;&nbsp;↳ {{ $child->name }}</option>
                            @endforeach
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

    {{-- Preview Modal --}}
    @if($showPreviewModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            @keydown.escape.window="$wire.closePreview()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/70"
                wire:click="closePreview"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-6xl h-[85vh] flex flex-col">
                {{-- Header --}}
                <div class="flex items-center justify-between px-4 py-3 border-b border-border shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <h3 class="text-lg font-semibold text-foreground truncate">{{ $previewTitle }}</h3>
                        <a
                            href="{{ $previewUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-xs text-muted-foreground hover:text-accent truncate cursor-pointer"
                        >
                            {{ $previewUrl }}
                        </a>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a
                            href="{{ $previewUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="p-1.5 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                            title="Åpne i ny fane"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                        <button
                            wire:click="closePreview"
                            class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Iframe --}}
                <div class="flex-1 min-h-0 bg-white">
                    <iframe
                        src="{{ $previewUrl }}"
                        class="w-full h-full border-0"
                        sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
                    ></iframe>
                </div>
            </div>
        </div>
    @endif

    {{-- Mobile Folder Sidebar --}}
    @if($showMobileFolderSidebar)
        <div
            class="fixed inset-0 z-50 lg:hidden"
            x-data="{ draggingBookmarkId: null }"
            @keydown.escape.window="$wire.closeMobileFolderSidebar()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closeMobileFolderSidebar"
            ></div>

            {{-- Sidebar --}}
            <div class="absolute inset-y-0 left-0 w-72 bg-card border-r border-border shadow-xl flex flex-col">
                {{-- Header --}}
                <div class="flex items-center justify-between px-4 py-3 border-b border-border">
                    <h2 class="text-lg font-semibold text-foreground">Mapper</h2>
                    <button
                        wire:click="closeMobileFolderSidebar"
                        class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Content --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-2">
                    {{-- All bookmarks --}}
                    <button
                        wire:click="openFolder(null)"
                        @class([
                            'w-full flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors cursor-pointer',
                            'bg-accent text-black font-medium' => $folderId === null,
                            'text-foreground hover:bg-card-hover' => $folderId !== null,
                        ])
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                        <span>Alle bokmerker</span>
                    </button>

                    {{-- Folder tree --}}
                    @if($this->folderTree->count() > 0)
                        <div class="mt-3 pt-3 border-t border-border space-y-1">
                            @foreach($this->folderTree as $folder)
                                @php $isExpanded = in_array($folder->id, $expandedFolders); @endphp
                                <div
                                    wire:key="mobile-folder-{{ $folder->id }}"
                                    x-data="{ expanded: @js($isExpanded) }"
                                >
                                    {{-- Parent folder --}}
                                    <div class="flex items-center gap-1">
                                        @if($folder->children->count() > 0)
                                            <button
                                                @click="expanded = !expanded; $wire.toggleFolderExpanded({{ $folder->id }})"
                                                class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                                            >
                                                <svg
                                                    class="w-3 h-3 transition-transform"
                                                    :class="{ 'rotate-90': expanded }"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        @else
                                            <div class="w-5"></div>
                                        @endif
                                        <button
                                            wire:click="openFolder({{ $folder->id }}); $wire.closeMobileFolderSidebar()"
                                            class="flex-1 flex items-center gap-2 px-2 py-1.5 text-sm rounded-lg transition-colors cursor-pointer text-left {{ $folderId === $folder->id ? 'bg-accent text-black font-medium' : 'text-foreground hover:bg-card-hover' }}"
                                        >
                                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                            </svg>
                                            <span class="truncate flex-1">{{ $folder->name }}</span>
                                            <span class="text-xs opacity-70 shrink-0">{{ $folder->bookmarks_count + $folder->children->sum('bookmarks_count') }}</span>
                                        </button>
                                    </div>

                                    {{-- Children (subfolders) --}}
                                    @if($folder->children->count() > 0)
                                        <div
                                            x-show="expanded"
                                            x-collapse
                                            class="ml-9 mt-1 space-y-1"
                                        >
                                            @foreach($folder->children as $child)
                                                <button
                                                    wire:key="mobile-folder-{{ $child->id }}"
                                                    wire:click="openFolder({{ $child->id }}); $wire.closeMobileFolderSidebar()"
                                                    class="w-full flex items-center gap-2 px-2 py-1.5 text-sm rounded-lg transition-colors cursor-pointer text-left {{ $folderId === $child->id ? 'bg-accent text-black font-medium' : 'text-foreground hover:bg-card-hover' }}"
                                                >
                                                    <svg class="w-3.5 h-3.5 shrink-0 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                    </svg>
                                                    <span class="truncate flex-1">{{ $child->name }}</span>
                                                    <span class="text-xs opacity-70 shrink-0">{{ $child->bookmarks_count }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-4 py-3 border-t border-border">
                    <button
                        wire:click="openFolderModal"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Ny mappe
                    </button>
                </div>
            </div>
        </div>
    @endif
        </main>
    </div>

    {{-- Context Menu (Right-click) --}}
    <div
        x-show="contextMenu.show"
        x-cloak
        @click.away="closeContextMenu()"
        @keydown.escape.window="closeContextMenu()"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        :style="`position: fixed; left: ${contextMenu.x}px; top: ${contextMenu.y}px; z-index: 9999;`"
        class="min-w-48 bg-card border border-border rounded-lg shadow-xl py-1"
    >
        {{-- Rediger --}}
        <button
            @click="$wire.openBookmarkModal(contextMenu.bookmarkId); closeContextMenu();"
            class="w-full px-3 py-2 text-left text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Rediger
        </button>

        {{-- Sjekk lenke --}}
        <button
            @click="$wire.checkSingleDeadLink(contextMenu.bookmarkId); closeContextMenu();"
            class="w-full px-3 py-2 text-left text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Sjekk lenke
        </button>

        {{-- Fjern død-status (only for dead bookmarks) --}}
        <button
            x-show="contextMenu.isDeadBookmark"
            @click="$wire.clearDeadStatus(contextMenu.bookmarkId); closeContextMenu();"
            class="w-full px-3 py-2 text-left text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer flex items-center gap-2"
        >
            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Fjern død-status
        </button>

        {{-- Flytt til ønskeliste --}}
        <button
            @click="$wire.moveToWishlist(contextMenu.bookmarkId); closeContextMenu();"
            class="w-full px-3 py-2 text-left text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer flex items-center gap-2"
        >
            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            Flytt til ønskeliste
        </button>

        <div class="border-t border-border my-1"></div>

        {{-- Slett --}}
        <button
            @click="if (confirm('Er du sikker på at du vil slette dette bokmerket?')) { $wire.deleteBookmark(contextMenu.bookmarkId); } closeContextMenu();"
            class="w-full px-3 py-2 text-left text-sm text-destructive hover:bg-destructive hover:text-white transition-colors cursor-pointer flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Slett
        </button>
    </div>

    {{-- Add to Wishlist Modal (for moveToWishlist action) --}}
    <livewire:bookmarks.add-to-wishlist-modal />
</x-page-container>
