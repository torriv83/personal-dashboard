<div class="p-4 sm:p-6 space-y-6" x-data="{ expanded: [], moveDropdownOpen: null }">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Ønskeliste</h1>
            <p class="text-sm text-muted-foreground mt-1 hidden sm:block">Hold oversikt over ting du ønsker deg</p>
        </div>
        <div class="flex items-center gap-2">
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
                                    {{-- Line 1: Name + Price --}}
                                    <div class="flex items-start justify-between gap-2">
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
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                <svg class="w-4 h-4 text-muted-foreground cursor-grab mt-0.5 shrink-0" x-sort:handle fill="currentColor" viewBox="0 0 24 24">
                                    <circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" />
                                    <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                                    <circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" />
                                </svg>
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
                                @if(count($this->groups) > 0)
                                    <div class="relative" x-data="{ openUp: true }" x-init="openUp = $el.getBoundingClientRect().top > 200">
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
                                            class="absolute right-0 w-48 bg-card border border-border rounded-lg shadow-lg z-10 py-1"
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
                                        </div>
                                    </div>
                                @endif
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
                colWidths: { drag: 0, navn: 0, lenke: 0, pris: 0, antall: 0, status: 0, totalt: 0, handlinger: 0 },
                measureColumns() {
                    this.colWidths.drag = this.$refs.thDrag?.offsetWidth || 0;
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
                                <td colspan="8" class="p-0">
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
                                        @if(count($this->groups) > 0)
                                            <div class="relative" x-data="{ openUp: true }" x-init="openUp = $el.getBoundingClientRect().top > 200">
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
                                                    class="absolute right-0 w-48 bg-card border border-border rounded-lg shadow-lg z-10 py-1"
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
                                                </div>
                                            </div>
                                        @endif
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
                            <td colspan="8" class="px-5 py-12 text-center text-muted-foreground">
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
                    <button
                        wire:click="closeGroupModal"
                        class="px-4 py-2 text-sm font-medium text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer"
                    >
                        Avbryt
                    </button>
                    <button
                        wire:click="saveGroup"
                        class="px-4 py-2 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                    >
                        {{ $editingGroupId ? 'Lagre' : 'Opprett' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
