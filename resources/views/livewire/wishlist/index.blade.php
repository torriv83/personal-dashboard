<x-page-container class="space-y-6" x-data="{ expanded: [], moveDropdownOpen: null, lightboxImage: null }">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Ønskeliste</h1>
            <p class="text-sm text-muted-foreground mt-1 hidden sm:block">Hold oversikt over ting du ønsker deg</p>
        </div>
        <div class="flex items-center gap-2">
            {{-- Image Actions Dropdown --}}
            <div class="relative" x-data="{ open: false }">
                <button
                    @click="open = !open"
                    class="p-2.5 text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                    title="Bildehandlinger"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="sr-only">Bildehandlinger</span>
                </button>
                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-56 bg-card border border-border rounded-lg shadow-lg z-50"
                >
                    <div class="py-1">
                        <button
                            wire:click="fetchMissingImages"
                            @click="open = false"
                            class="w-full px-4 py-2 text-left text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer flex items-center gap-2"
                        >
                            <svg class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Hent manglende bilder
                        </button>
                        <button
                            wire:click="refetchAllImages"
                            wire:confirm="Er du sikker? Dette vil slette alle eksisterende bilder og hente dem på nytt."
                            @click="open = false"
                            class="w-full px-4 py-2 text-left text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer flex items-center gap-2"
                        >
                            <svg class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Hent alle bilder på nytt
                        </button>
                    </div>
                </div>
            </div>
            <button
                wire:click="openGroupModal"
                class="p-2.5 text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                title="Ny gruppe"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
            </button>
            <button
                wire:click="openItemModal"
                class="p-2.5 text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                title="Legg til ønske"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Wishlist Table (Desktop) / Cards (Mobile) --}}
    <div class="bg-card border border-border rounded-lg overflow-hidden">
        {{-- Mobile Card Layout --}}
        <div class="md:hidden divide-y divide-border" x-sort="$wire.updateOrder($item, $position)" wire:ignore.self>
            @forelse($this->wishlists as $wishlist)
                @if($wishlist['is_group'])
                    {{-- Group Card (Alt B: Two-line layout) --}}
                    <div
                        wire:key="mobile-group-{{ $wishlist['id'] }}"
                        x-sort:item="'group-{{ $wishlist['id'] }}'"
                        class="p-4 hover:bg-card-hover transition-colors"
                    >
                        {{-- Line 1: Drag handle + Folder icon + Name + Chevron --}}
                        <div
                            class="flex items-center justify-between cursor-pointer"
                            @click="expanded.includes({{ $wishlist['id'] }}) ? expanded = expanded.filter(x => x !== {{ $wishlist['id'] }}) : expanded.push({{ $wishlist['id'] }})"
                        >
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                <svg class="w-4 h-4 text-muted-foreground cursor-grab shrink-0" x-sort:handle fill="currentColor" viewBox="0 0 24 24" @click.stop>
                                    <circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" />
                                    <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                                    <circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" />
                                </svg>
                                <svg class="w-4 h-4 text-yellow-500 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                                @if($wishlist['is_shared'])
                                    <svg class="w-3.5 h-3.5 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Delt">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                    </svg>
                                @endif
                                <span class="text-sm font-medium text-foreground truncate">{{ $wishlist['navn'] }}</span>
                            </div>
                            <svg
                                class="w-4 h-4 text-muted-foreground transition-transform shrink-0 ml-2"
                                :class="expanded.includes({{ $wishlist['id'] }}) && 'rotate-90'"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        {{-- Line 2: Count + Price + Actions --}}
                        <div class="flex items-center justify-between mt-2 pl-6" wire:click.stop>
                            <span class="text-xs text-muted-foreground">
                                {{ count($wishlist['items']) }} elementer · kr {{ number_format($this->getGroupTotal($wishlist['items']), 0, ',', ' ') }}
                            </span>
                            <div class="flex items-center gap-1">
                                <button wire:click="openShareModal({{ $wishlist['id'] }})" class="p-1.5 text-muted-foreground hover:text-accent hover:bg-accent/10 rounded transition-colors cursor-pointer" title="Del">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" /></svg>
                                </button>
                                <button wire:click="openItemModal(null, {{ $wishlist['id'] }})" class="p-1.5 text-muted-foreground hover:text-accent hover:bg-accent/10 rounded transition-colors cursor-pointer" title="Legg til">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                </button>
                                <button wire:click="openGroupModal({{ $wishlist['id'] }})" class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer" title="Rediger">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                <button wire:click="deleteGroup({{ $wishlist['id'] }})" wire:confirm="Er du sikker på at du vil slette denne gruppen og alle elementer i den?" class="p-1.5 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 rounded transition-colors cursor-pointer" title="Slett">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Expanded Child Items --}}
                        <div x-show="expanded.includes({{ $wishlist['id'] }})" x-collapse class="mt-3 pl-4 border-l-2 border-border space-y-3">
                            @foreach($wishlist['items'] as $item)
                                @php $isCompleted = in_array($item['status'], ['Spart', 'Kjøpt']); @endphp
                                <div class="p-3 bg-card-hover/30 rounded-lg {{ $isCompleted ? 'opacity-60' : '' }}">
                                    {{-- Line 1: Image + Name + Price --}}
                                    <div class="flex items-start gap-3">
                                        @if($item['image_url'])
                                            <img src="{{ $item['image_url'] }}" alt="{{ $item['navn'] }}" @click="lightboxImage = '{{ $item['image_url'] }}'" class="w-12 h-12 object-cover rounded border border-border shrink-0 cursor-pointer hover:opacity-80 transition-opacity">
                                        @else
                                            <div class="w-12 h-12 bg-muted-foreground/10 rounded border border-border shrink-0 flex items-center justify-center">
                                                <svg class="w-6 h-6 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-foreground {{ $isCompleted ? 'line-through' : '' }} truncate">{{ $item['navn'] }}</p>
                                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                <span class="text-xs text-muted-foreground">{{ $item['antall'] }} × kr {{ number_format($item['pris'], 0, ',', ' ') }}</span>
                                                @if($item['url'])
                                                    <a href="{{ $item['url'] }}" target="_blank" class="text-accent hover:underline cursor-pointer text-xs flex items-center gap-0.5" @click.stop>
                                                        Lenke <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="text-sm font-medium text-foreground whitespace-nowrap">kr {{ number_format($item['pris'] * $item['antall'], 0, ',', ' ') }}</span>
                                    </div>
                                    {{-- Line 2: Status + Actions --}}
                                    <div class="flex items-center justify-between mt-2">
                                        <select
                                            wire:change="updateItemStatus({{ $item['id'] }}, $event.target.value)"
                                            @click.stop
                                            class="px-2 py-0.5 text-xs font-medium rounded border-0 cursor-pointer focus:outline-none focus:ring-2 focus:ring-accent {{ $this->getStatusBgColor($item['status']) }} {{ $this->getStatusColor($item['status']) }}"
                                        >
                                            @foreach($this->statusOptions as $value => $label)
                                                <option value="{{ $value }}" {{ $item['status_value'] === $value ? 'selected' : '' }} class="bg-card text-foreground">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <div class="flex items-center gap-1">
                                            <button wire:click.stop="moveItemToGroup({{ $item['id'] }}, null)" class="p-1.5 text-muted-foreground hover:text-accent hover:bg-accent/10 rounded transition-colors cursor-pointer" title="Flytt ut av gruppe">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                            </button>
                                            <button wire:click.stop="openItemModal({{ $item['id'] }}, {{ $wishlist['id'] }})" class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button wire:click.stop="deleteItem({{ $item['id'] }})" wire:confirm="Er du sikker på at du vil slette dette elementet?" class="p-1.5 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 rounded transition-colors cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    {{-- Single Item Card --}}
                    @php $isCompleted = in_array($wishlist['status'], ['Spart', 'Kjøpt']); @endphp
                    <div
                        wire:key="mobile-item-{{ $wishlist['id'] }}"
                        x-sort:item="'item-{{ $wishlist['id'] }}'"
                        class="p-4 hover:bg-card-hover transition-colors {{ $isCompleted ? 'opacity-60' : '' }}"
                    >
                        <div class="flex items-start gap-3">
                            <svg class="w-4 h-4 text-muted-foreground cursor-grab mt-0.5 shrink-0" x-sort:handle fill="currentColor" viewBox="0 0 24 24">
                                <circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" />
                                <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                                <circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" />
                            </svg>
                            @if($wishlist['image_url'])
                                <img src="{{ $wishlist['image_url'] }}" alt="{{ $wishlist['navn'] }}" @click="lightboxImage = '{{ $wishlist['image_url'] }}'" class="w-12 h-12 object-cover rounded border border-border shrink-0 cursor-pointer hover:opacity-80 transition-opacity">
                            @else
                                <div class="w-12 h-12 bg-muted-foreground/10 rounded border border-border shrink-0 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-foreground {{ $isCompleted ? 'line-through' : '' }} truncate">{{ $wishlist['navn'] }}</p>
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    <span class="text-xs text-muted-foreground">{{ $wishlist['antall'] }} × kr {{ number_format($wishlist['pris'], 0, ',', ' ') }}</span>
                                    @if($wishlist['url'])
                                        <a href="{{ $wishlist['url'] }}" target="_blank" class="text-accent hover:underline cursor-pointer text-xs flex items-center gap-0.5">
                                            Lenke <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <span class="text-sm font-medium text-foreground whitespace-nowrap">kr {{ number_format($wishlist['pris'] * $wishlist['antall'], 0, ',', ' ') }}</span>
                        </div>
                        {{-- Actions row with status --}}
                        <div class="flex items-center justify-between mt-2 pl-7">
                            <select
                                wire:change="updateItemStatus({{ $wishlist['id'] }}, $event.target.value)"
                                class="px-2 py-0.5 text-xs font-medium rounded border-0 cursor-pointer focus:outline-none focus:ring-2 focus:ring-accent {{ $this->getStatusBgColor($wishlist['status']) }} {{ $this->getStatusColor($wishlist['status']) }}"
                            >
                                @foreach($this->statusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $wishlist['status_value'] === $value ? 'selected' : '' }} class="bg-card text-foreground">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="flex items-center gap-1">
                                {{-- Move to group dropdown --}}
                                <div class="relative" x-data="{ openUp: true, newGroupName: '' }" x-init="openUp = $el.getBoundingClientRect().top > 200">
                                    <button
                                        @click.stop="openUp = $el.getBoundingClientRect().top > 200; moveDropdownOpen = moveDropdownOpen === 'mobile-{{ $wishlist['id'] }}' ? null : 'mobile-{{ $wishlist['id'] }}'"
                                        class="p-1.5 text-muted-foreground hover:text-yellow-500 hover:bg-yellow-500/10 rounded transition-colors cursor-pointer"
                                        title="Flytt til gruppe"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                                    </button>
                                    <div
                                        x-show="moveDropdownOpen === 'mobile-{{ $wishlist['id'] }}'"
                                        @click.away="moveDropdownOpen = null"
                                        x-transition
                                        class="absolute right-0 w-56 bg-card border border-border rounded-lg shadow-lg z-10 py-1"
                                        :class="openUp ? 'bottom-full mb-1' : 'top-full mt-1'"
                                    >
                                        <div class="px-3 py-1.5 text-xs font-medium text-muted-foreground uppercase tracking-wider">Flytt til gruppe</div>
                                        @foreach($this->groups as $group)
                                            <button
                                                wire:click="moveItemToGroup({{ $wishlist['id'] }}, {{ $group['id'] }})"
                                                @click="moveDropdownOpen = null"
                                                class="w-full px-3 py-2 text-left text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer flex items-center gap-2"
                                            >
                                                <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 24 24"><path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                                                {{ $group['name'] }}
                                            </button>
                                        @endforeach
                                        {{-- New group input --}}
                                        <div class="border-t border-border mt-1 pt-2 px-2 pb-1">
                                            <div class="flex items-center gap-1">
                                                <input
                                                    type="text"
                                                    x-model="newGroupName"
                                                    @keydown.enter.prevent="if(newGroupName.trim()) { $wire.createGroupAndMoveItem({{ $wishlist['id'] }}, newGroupName); moveDropdownOpen = null; newGroupName = ''; }"
                                                    @click.stop
                                                    placeholder="Ny mappe..."
                                                    class="min-w-0 flex-1 bg-input border border-border rounded px-2 py-1.5 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-1 focus:ring-accent"
                                                >
                                                <button
                                                    @click.stop="if(newGroupName.trim()) { $wire.createGroupAndMoveItem({{ $wishlist['id'] }}, newGroupName); moveDropdownOpen = null; newGroupName = ''; }"
                                                    class="shrink-0 p-1.5 text-accent hover:bg-accent/10 rounded transition-colors cursor-pointer"
                                                    title="Opprett mappe"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button wire:click="openItemModal({{ $wishlist['id'] }})" class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                <button wire:click="deleteItem({{ $wishlist['id'] }})" wire:confirm="Er du sikker på at du vil slette dette ønsket?" class="p-1.5 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 rounded transition-colors cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <div class="px-4 py-12 text-center text-muted-foreground">
                    <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <p>Ingen ønsker ennå</p>
                    <p class="text-sm mt-1">Trykk + for å legge til</p>
                </div>
            @endforelse
        </div>

        {{-- Desktop Table Layout --}}
        <div class="hidden md:block overflow-x-auto"
            x-data="{
                colWidths: { drag: 0, bilde: 0, navn: 0, lenke: 0, pris: 0, antall: 0, status: 0, totalt: 0, handlinger: 0 },
                measureColumns() {
                    this.colWidths.drag = this.$refs.thDrag?.offsetWidth || 0;
                    this.colWidths.bilde = this.$refs.thBilde?.offsetWidth || 0;
                    this.colWidths.navn = this.$refs.thNavn?.offsetWidth || 0;
                    this.colWidths.lenke = this.$refs.thLenke?.offsetWidth || 0;
                    this.colWidths.pris = this.$refs.thPris?.offsetWidth || 0;
                    this.colWidths.antall = this.$refs.thAntall?.offsetWidth || 0;
                    this.colWidths.status = this.$refs.thStatus?.offsetWidth || 0;
                    this.colWidths.totalt = this.$refs.thTotalt?.offsetWidth || 0;
                    this.colWidths.handlinger = this.$refs.thHandlinger?.offsetWidth || 0;
                }
            }"
            x-init="$nextTick(() => measureColumns())"
            @resize.window.debounce.100ms="measureColumns()"
        >
            <table class="w-full">
                <thead>
                    <tr class="border-b border-border bg-card-hover/50">
                        <th x-ref="thDrag" class="w-10 px-3 py-3"></th>
                        <th x-ref="thBilde" class="w-16 px-3 py-3"></th>
                        <th x-ref="thNavn" class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Navn</th>
                        <th x-ref="thLenke" class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Lenke</th>
                        <th x-ref="thPris" class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Pris</th>
                        <th x-ref="thAntall" class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase tracking-wider">Antall</th>
                        <th x-ref="thStatus" class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                        <th x-ref="thTotalt" class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Totalt</th>
                        <th x-ref="thHandlinger" class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Handlinger</th>
                    </tr>
                </thead>
                {{-- Sortable tbody - ONLY contains sortable items (groups and standalone items) --}}
                <tbody class="divide-y divide-border" x-sort="$wire.updateOrder($item, $position)" wire:ignore.self>
                    @forelse($this->wishlists as $wishlist)
                        @if($wishlist['is_group'])
                            {{-- Group Row - uses single cell layout to contain children within the sortable item --}}
                            @php $toggleClick = "expanded.includes({$wishlist['id']}) ? expanded = expanded.filter(x => x !== {$wishlist['id']}) : expanded.push({$wishlist['id']})"; @endphp
                            <tr
                                wire:key="group-{{ $wishlist['id'] }}"
                                x-sort:item="'group-{{ $wishlist['id'] }}'"
                                class="align-top"
                            >
                                <td colspan="9" class="p-0">
                                    {{-- Group Header Row --}}
                                    <div class="flex items-center hover:bg-card-hover transition-colors cursor-pointer" @click="{{ $toggleClick }}">
                                        {{-- Drag Handle --}}
                                        <div class="w-10 px-3 py-4 text-muted-foreground shrink-0" @click.stop>
                                            <div class="flex items-center gap-2" x-sort:handle>
                                                <svg class="w-4 h-4 cursor-grab" fill="currentColor" viewBox="0 0 24 24">
                                                    <circle cx="9" cy="6" r="1.5" />
                                                    <circle cx="15" cy="6" r="1.5" />
                                                    <circle cx="9" cy="12" r="1.5" />
                                                    <circle cx="15" cy="12" r="1.5" />
                                                    <circle cx="9" cy="18" r="1.5" />
                                                    <circle cx="15" cy="18" r="1.5" />
                                                </svg>
                                            </div>
                                        </div>
                                        {{-- Name with expand icon --}}
                                        <div class="flex-1 px-4 py-4">
                                            <div class="flex items-center gap-2">
                                                <svg
                                                    class="w-4 h-4 text-muted-foreground transition-transform"
                                                    :class="expanded.includes({{ $wishlist['id'] }}) && 'rotate-90'"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                                <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                </svg>
                                                @if($wishlist['is_shared'])
                                                    <svg class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Delt">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                                    </svg>
                                                @endif
                                                <span class="text-sm font-medium text-foreground">{{ $wishlist['navn'] }}</span>
                                                <span class="text-xs text-muted-foreground">({{ count($wishlist['items']) }} elementer)</span>
                                            </div>
                                        </div>
                                        {{-- Group Total - dynamically sized to match Totalt column --}}
                                        <div class="px-4 py-4 text-right shrink-0 box-content" :style="'width: ' + (colWidths.totalt - 32) + 'px'">
                                            <span class="text-sm font-medium text-foreground">
                                                kr {{ number_format($this->getGroupTotal($wishlist['items']), 0, ',', ' ') }}
                                            </span>
                                        </div>
                                        {{-- Actions - dynamically sized to match Handlinger column --}}
                                        <div class="px-4 py-4 text-right shrink-0 box-content" :style="'width: ' + (colWidths.handlinger - 32) + 'px'" @click.stop>
                                            <div class="flex items-center justify-end gap-1">
                                                <button
                                                    wire:click="openShareModal({{ $wishlist['id'] }})"
                                                    class="p-1.5 text-muted-foreground hover:text-accent hover:bg-accent/10 rounded transition-colors cursor-pointer"
                                                    title="Del gruppe"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                                    </svg>
                                                </button>
                                                <button
                                                    wire:click="openItemModal(null, {{ $wishlist['id'] }})"
                                                    class="p-1.5 text-muted-foreground hover:text-accent hover:bg-accent/10 rounded transition-colors cursor-pointer"
                                                    title="Legg til i gruppe"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                </button>
                                                <button
                                                    wire:click="openGroupModal({{ $wishlist['id'] }})"
                                                    class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer"
                                                    title="Rediger gruppe"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <button
                                                    wire:click="deleteGroup({{ $wishlist['id'] }})"
                                                    wire:confirm="Er du sikker på at du vil slette denne gruppen og alle elementer i den?"
                                                    class="p-1.5 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 rounded transition-colors cursor-pointer"
                                                    title="Slett gruppe"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Expanded Child Items (inside the same TD, not a separate TR) --}}
                                    <div x-show="expanded.includes({{ $wishlist['id'] }})" x-collapse class="border-t border-border bg-card-hover/20">
                                        <table class="w-full">
                                            <colgroup>
                                                <col :style="'width: ' + colWidths.drag + 'px'">
                                                <col :style="'width: ' + colWidths.bilde + 'px'">
                                                <col :style="'width: ' + colWidths.navn + 'px'">
                                                <col :style="'width: ' + colWidths.lenke + 'px'">
                                                <col :style="'width: ' + colWidths.pris + 'px'">
                                                <col :style="'width: ' + colWidths.antall + 'px'">
                                                <col :style="'width: ' + colWidths.status + 'px'">
                                                <col :style="'width: ' + colWidths.totalt + 'px'">
                                                <col :style="'width: ' + colWidths.handlinger + 'px'">
                                            </colgroup>
                                            <tbody class="divide-y divide-border/50">
                                                @foreach($wishlist['items'] as $item)
                                                    @php $isCompleted = in_array($item['status'], ['Spart', 'Kjøpt']); @endphp
                                                    <tr class="hover:bg-card-hover/50 transition-colors {{ $isCompleted ? 'opacity-60' : '' }}">
                                                        {{-- Indent + no drag handle for child items --}}
                                                        <td class="w-10 px-3 py-3">
                                                            <div class="w-4 h-4 ml-2 border-l-2 border-b-2 border-border rounded-bl"></div>
                                                        </td>
                                                        {{-- Image --}}
                                                        <td class="px-3 py-3">
                                                            @if($item['image_url'])
                                                                <img src="{{ $item['image_url'] }}" alt="{{ $item['navn'] }}" @click="lightboxImage = '{{ $item['image_url'] }}'" class="w-12 h-12 object-cover rounded border border-border cursor-pointer hover:opacity-80 transition-opacity">
                                                            @else
                                                                <div class="w-12 h-12 bg-muted-foreground/10 rounded border border-border flex items-center justify-center">
                                                                    <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                    </svg>
                                                                </div>
                                                            @endif
                                                        </td>
                                                        {{-- Name --}}
                                                        <td class="px-4 py-3">
                                                            <span class="text-sm text-foreground {{ $isCompleted ? 'line-through' : '' }}">
                                                                {{ $item['navn'] }}
                                                            </span>
                                                        </td>
                                                        {{-- Link --}}
                                                        <td class="px-4 py-3 text-sm">
                                                            @if($item['url'])
                                                                <a href="{{ $item['url'] }}" target="_blank" class="text-accent hover:underline cursor-pointer flex items-center gap-1">
                                                                    Se her
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                                    </svg>
                                                                </a>
                                                            @else
                                                                <span class="text-muted-foreground">—</span>
                                                            @endif
                                                        </td>
                                                        {{-- Price --}}
                                                        <td class="px-4 py-3 text-sm text-foreground text-right">
                                                            kr {{ number_format($item['pris'], 0, ',', ' ') }}
                                                        </td>
                                                        {{-- Quantity --}}
                                                        <td class="px-4 py-3 text-sm text-foreground text-center">
                                                            {{ $item['antall'] }}
                                                        </td>
                                                        {{-- Status --}}
                                                        <td class="px-4 py-3">
                                                            <select
                                                                wire:change="updateItemStatus({{ $item['id'] }}, $event.target.value)"
                                                                class="px-2 py-1 text-xs font-medium rounded border-0 cursor-pointer focus:outline-none focus:ring-2 focus:ring-accent {{ $this->getStatusBgColor($item['status']) }} {{ $this->getStatusColor($item['status']) }}"
                                                            >
                                                                @foreach($this->statusOptions as $value => $label)
                                                                    <option value="{{ $value }}" {{ $item['status_value'] === $value ? 'selected' : '' }} class="bg-card text-foreground">{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        {{-- Total --}}
                                                        <td class="px-4 py-3 text-right">
                                                            <span class="text-sm text-foreground">
                                                                kr {{ number_format($item['pris'] * $item['antall'], 0, ',', ' ') }}
                                                            </span>
                                                        </td>
                                                        {{-- Actions --}}
                                                        <td class="px-4 py-3 text-right">
                                                            <div class="flex items-center justify-end gap-1">
                                                                <button
                                                                    wire:click="moveItemToGroup({{ $item['id'] }}, null)"
                                                                    class="p-1.5 text-muted-foreground hover:text-accent hover:bg-accent/10 rounded transition-colors cursor-pointer"
                                                                    title="Flytt ut av gruppe"
                                                                >
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                                                    </svg>
                                                                </button>
                                                                <button
                                                                    wire:click="openItemModal({{ $item['id'] }}, {{ $wishlist['id'] }})"
                                                                    class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer"
                                                                    title="Rediger"
                                                                >
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                    </svg>
                                                                </button>
                                                                <button
                                                                    wire:click="deleteItem({{ $item['id'] }})"
                                                                    wire:confirm="Er du sikker på at du vil slette dette elementet?"
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
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @else
                            {{-- Single Item Row --}}
                            @php
                                $isCompleted = in_array($wishlist['status'], ['Spart', 'Kjøpt']);
                            @endphp
                            <tr
                                wire:key="item-{{ $wishlist['id'] }}"
                                x-sort:item="'item-{{ $wishlist['id'] }}'"
                                class="hover:bg-card-hover transition-colors {{ $isCompleted ? 'opacity-60' : '' }}"
                            >
                                {{-- Drag Handle --}}
                                <td class="px-3 py-4 text-muted-foreground" x-sort:handle>
                                    <svg class="w-4 h-4 cursor-grab" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="9" cy="6" r="1.5" />
                                        <circle cx="15" cy="6" r="1.5" />
                                        <circle cx="9" cy="12" r="1.5" />
                                        <circle cx="15" cy="12" r="1.5" />
                                        <circle cx="9" cy="18" r="1.5" />
                                        <circle cx="15" cy="18" r="1.5" />
                                    </svg>
                                </td>
                                {{-- Image --}}
                                <td class="px-3 py-4">
                                    @if($wishlist['image_url'])
                                        <img src="{{ $wishlist['image_url'] }}" alt="{{ $wishlist['navn'] }}" @click="lightboxImage = '{{ $wishlist['image_url'] }}'" class="w-12 h-12 object-cover rounded border border-border cursor-pointer hover:opacity-80 transition-opacity">
                                    @else
                                        <div class="w-12 h-12 bg-muted-foreground/10 rounded border border-border flex items-center justify-center">
                                            <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                </td>
                                {{-- Name --}}
                                <td class="px-4 py-4">
                                    <span class="text-sm font-medium text-foreground {{ $isCompleted ? 'line-through' : '' }}">
                                        {{ $wishlist['navn'] }}
                                    </span>
                                </td>
                                {{-- Link --}}
                                <td class="px-4 py-4 text-sm">
                                    @if($wishlist['url'])
                                        <a href="{{ $wishlist['url'] }}" target="_blank" class="text-accent hover:underline cursor-pointer flex items-center gap-1">
                                            Se her
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </td>
                                {{-- Price --}}
                                <td class="px-4 py-4 text-sm text-foreground text-right">
                                    kr {{ number_format($wishlist['pris'], 0, ',', ' ') }}
                                </td>
                                {{-- Quantity --}}
                                <td class="px-4 py-4 text-sm text-foreground text-center">
                                    {{ $wishlist['antall'] }}
                                </td>
                                {{-- Status --}}
                                <td class="px-4 py-4">
                                    <select
                                        wire:change="updateItemStatus({{ $wishlist['id'] }}, $event.target.value)"
                                        class="px-2 py-1 text-xs font-medium rounded border-0 cursor-pointer focus:outline-none focus:ring-2 focus:ring-accent {{ $this->getStatusBgColor($wishlist['status']) }} {{ $this->getStatusColor($wishlist['status']) }}"
                                    >
                                        @foreach($this->statusOptions as $value => $label)
                                            <option value="{{ $value }}" {{ $wishlist['status_value'] === $value ? 'selected' : '' }} class="bg-card text-foreground">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                {{-- Total --}}
                                <td class="px-4 py-4 text-right">
                                    <span class="text-sm font-medium text-foreground">
                                        kr {{ number_format($wishlist['pris'] * $wishlist['antall'], 0, ',', ' ') }}
                                    </span>
                                </td>
                                {{-- Actions --}}
                                <td class="px-4 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        {{-- Move to group dropdown --}}
                                        <div class="relative" x-data="{ openUp: true, newGroupName: '' }" x-init="openUp = $el.getBoundingClientRect().top > 200">
                                            <button
                                                @click.stop="openUp = $el.getBoundingClientRect().top > 200; moveDropdownOpen = moveDropdownOpen === 'desktop-{{ $wishlist['id'] }}' ? null : 'desktop-{{ $wishlist['id'] }}'"
                                                class="p-1.5 text-muted-foreground hover:text-yellow-500 hover:bg-yellow-500/10 rounded transition-colors cursor-pointer"
                                                title="Flytt til gruppe"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                </svg>
                                            </button>
                                            <div
                                                x-show="moveDropdownOpen === 'desktop-{{ $wishlist['id'] }}'"
                                                @click.away="moveDropdownOpen = null"
                                                x-transition
                                                class="absolute right-0 w-56 bg-card border border-border rounded-lg shadow-lg z-10 py-1"
                                                :class="openUp ? 'bottom-full mb-1' : 'top-full mt-1'"
                                            >
                                                <div class="px-3 py-1.5 text-xs font-medium text-muted-foreground uppercase tracking-wider">Flytt til gruppe</div>
                                                @foreach($this->groups as $group)
                                                    <button
                                                        wire:click="moveItemToGroup({{ $wishlist['id'] }}, {{ $group['id'] }})"
                                                        @click="moveDropdownOpen = null"
                                                        class="w-full px-3 py-2 text-left text-sm text-foreground hover:bg-card-hover transition-colors cursor-pointer flex items-center gap-2"
                                                    >
                                                        <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 24 24"><path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                                                        {{ $group['name'] }}
                                                    </button>
                                                @endforeach
                                                {{-- New group input --}}
                                                <div class="border-t border-border mt-1 pt-2 px-2 pb-1">
                                                    <div class="flex items-center gap-1">
                                                        <input
                                                            type="text"
                                                            x-model="newGroupName"
                                                            @keydown.enter.prevent="if(newGroupName.trim()) { $wire.createGroupAndMoveItem({{ $wishlist['id'] }}, newGroupName); moveDropdownOpen = null; newGroupName = ''; }"
                                                            @click.stop
                                                            placeholder="Ny mappe..."
                                                            class="min-w-0 flex-1 bg-input border border-border rounded px-2 py-1.5 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-1 focus:ring-accent"
                                                        >
                                                        <button
                                                            @click.stop="if(newGroupName.trim()) { $wire.createGroupAndMoveItem({{ $wishlist['id'] }}, newGroupName); moveDropdownOpen = null; newGroupName = ''; }"
                                                            class="shrink-0 p-1.5 text-accent hover:bg-accent/10 rounded transition-colors cursor-pointer"
                                                            title="Opprett mappe"
                                                        >
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            wire:click="openItemModal({{ $wishlist['id'] }})"
                                            class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer"
                                            title="Rediger"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            wire:click="deleteItem({{ $wishlist['id'] }})"
                                            wire:confirm="Er du sikker på at du vil slette dette ønsket?"
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
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center text-muted-foreground">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    <p>Ingen ønsker ennå</p>
                                    <p class="text-sm">Klikk "Legg til ønske" for å komme i gang</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Table Footer with Totals --}}
        <div class="px-4 sm:px-5 py-4 border-t border-border bg-card-hover/30">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-sm text-muted-foreground">
                    {{ count($this->wishlists) }} ønsker totalt
                </p>
                <div class="flex items-center justify-between sm:justify-end gap-4 sm:gap-6">
                    <div class="text-left sm:text-right">
                        <p class="text-xs text-muted-foreground uppercase tracking-wider">Total verdi</p>
                        <p class="text-sm font-medium text-foreground">kr {{ number_format($this->totalAll, 0, ',', ' ') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-muted-foreground uppercase tracking-wider">Gjenstår</p>
                        <p class="text-lg font-bold text-accent">kr {{ number_format($this->totalRemaining, 0, ',', ' ') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Item Modal --}}
    @if($showItemModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.closeItemModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closeItemModal"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                {{-- Header --}}
                <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        @if($editingItemId)
                            Rediger ønske
                        @elseif($editingItemGroupId)
                            Legg til i gruppe
                        @else
                            Nytt ønske
                        @endif
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
                            wire:model="itemNavn"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="Hva ønsker du deg?"
                            autofocus
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">URL / Lenke</label>
                        <input
                            type="url"
                            wire:model="itemUrl"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="https://..."
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1">Bilde-URL</label>
                        <div class="flex items-center gap-2">
                            <input
                                type="url"
                                wire:model="itemImageUrl"
                                class="flex-1 bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                placeholder="https://..."
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
                        </div>
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
                    <x-button variant="secondary" wire:click="closeItemModal">Avbryt</x-button>
                    <x-button wire:click="saveItem">{{ $editingItemId ? 'Lagre' : 'Legg til' }}</x-button>
                </div>
            </div>
        </div>
    @endif

    {{-- Group Modal --}}
    @if($showGroupModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.closeGroupModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closeGroupModal"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
                {{-- Header --}}
                <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        {{ $editingGroupId ? 'Rediger gruppe' : 'Ny gruppe' }}
                    </h2>
                    <button
                        wire:click="closeGroupModal"
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
                        <label class="block text-sm font-medium text-foreground mb-1">Gruppenavn *</label>
                        <input
                            type="text"
                            wire:model="groupNavn"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="F.eks. Oppussing soverom, Hjemmekontor..."
                            autofocus
                        >
                    </div>
                    <p class="text-xs text-muted-foreground">
                        En gruppe lar deg samle relaterte ønsker på ett sted. Du kan legge til elementer i gruppen etterpå.
                    </p>
                </div>

                {{-- Footer --}}
                <div class="px-4 sm:px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                    <x-button variant="secondary" wire:click="closeGroupModal">Avbryt</x-button>
                    <x-button wire:click="saveGroup">{{ $editingGroupId ? 'Lagre' : 'Opprett' }}</x-button>
                </div>
            </div>
        </div>
    @endif

    {{-- Share Modal --}}
    @if($showShareModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data="{ copied: false }"
            x-on:keydown.escape.window="$wire.closeShareModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closeShareModal"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
                {{-- Header --}}
                <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">Del gruppe</h2>
                    <button
                        wire:click="closeShareModal"
                        class="p-1 text-muted-foreground hover:text-foreground rounded transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-4 sm:px-6 py-4 space-y-4">
                    {{-- Toggle --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-foreground">Aktiver deling</p>
                            <p class="text-xs text-muted-foreground">Gjør gruppen tilgjengelig via lenke</p>
                        </div>
                        <button
                            wire:click="toggleSharing"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-card {{ $sharingEnabled ? 'bg-accent' : 'bg-muted-foreground/30' }}"
                            role="switch"
                            aria-checked="{{ $sharingEnabled ? 'true' : 'false' }}"
                        >
                            <span
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $sharingEnabled ? 'translate-x-5' : 'translate-x-0' }}"
                            ></span>
                        </button>
                    </div>

                    {{-- Share URL section --}}
                    @if($sharingEnabled && $shareUrl)
                        <div class="space-y-3 pt-2 border-t border-border">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">Delingslenke</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="text"
                                        value="{{ $shareUrl }}"
                                        readonly
                                        class="flex-1 bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:outline-none"
                                    >
                                    <button
                                        @click="navigator.clipboard.writeText('{{ $shareUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="px-3 py-2 text-sm font-medium rounded-lg transition-colors cursor-pointer"
                                        :class="copied ? 'bg-accent text-black' : 'bg-card-hover text-foreground border border-border hover:bg-input'"
                                    >
                                        <span x-show="!copied">Kopier</span>
                                        <span x-show="copied" x-cloak>Kopiert!</span>
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <a
                                    href="{{ $shareUrl }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex-1 px-3 py-2 text-sm font-medium text-center text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                                >
                                    Forhåndsvis
                                </a>
                                <button
                                    wire:click="regenerateShareToken"
                                    wire:confirm="Er du sikker på at du vil generere en ny lenke? Den gamle lenken vil slutte å virke."
                                    class="flex-1 px-3 py-2 text-sm font-medium text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                                >
                                    Ny lenke
                                </button>
                            </div>
                        </div>
                    @endif

                    <p class="text-xs text-muted-foreground pt-2">
                        Delte lister viser kun navn, pris, lenke og antall. Status-kolonnen er skjult for besøkende.
                    </p>
                </div>

                {{-- Footer --}}
                <div class="px-4 sm:px-6 py-4 border-t border-border flex items-center justify-end">
                    <x-button variant="secondary" wire:click="closeShareModal">Lukk</x-button>
                </div>
            </div>
        </div>
    @endif

    {{-- Image Lightbox --}}
    <div
        x-show="lightboxImage"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="lightboxImage = null"
        @keydown.escape.window="lightboxImage = null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 cursor-pointer"
        style="display: none;"
    >
        <img
            :src="lightboxImage"
            @click.stop
            class="max-w-full max-h-full object-contain rounded-lg shadow-2xl cursor-default"
            alt="Forstørret bilde"
        >
        <button
            @click="lightboxImage = null"
            class="absolute top-4 right-4 p-2 text-white/80 hover:text-white transition-colors cursor-pointer"
            title="Lukk"
        >
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</x-page-container>
