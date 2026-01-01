<x-page-container class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Oppgavelister</h1>
            <p class="text-sm text-muted-foreground mt-1 hidden sm:block">Administrer dine oppgavelister</p>
        </div>
        <button
            wire:click="openListModal"
            class="p-2.5 text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
            title="Ny liste"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
        </button>
    </div>

    {{-- Lists Grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" x-sort="$wire.updateOrder($item, $position)" wire:ignore.self>
        @forelse($this->taskLists as $list)
            <a
                href="{{ route('bpa.tasks.show', $list['slug']) }}"
                wire:key="list-{{ $list['id'] }}"
                x-sort:item="'list-{{ $list['id'] }}'"
                class="bg-card border border-border rounded-lg p-4 hover:bg-card-hover transition-colors cursor-pointer block"
            >
                {{-- Drag handle + Name --}}
                <div class="flex items-start gap-3 mb-3">
                    <svg class="w-4 h-4 text-muted-foreground cursor-grab mt-1 shrink-0 touch-none" x-sort:handle fill="currentColor" viewBox="0 0 24 24" @click.prevent.stop>
                        <circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" />
                        <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                        <circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" />
                    </svg>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-semibold text-foreground truncate">{{ $list['name'] }}</h3>
                            @if($list['is_shared'])
                                <svg class="w-4 h-4 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Delt med alle">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                </svg>
                            @elseif($list['assistant_name'])
                                <span class="text-xs bg-accent/20 text-accent px-1.5 py-0.5 rounded shrink-0" title="Tildelt {{ $list['assistant_name'] }}">
                                    {{ $list['assistant_name'] }}
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-muted-foreground mt-1">
                            {{ $list['task_count'] }} {{ $list['task_count'] === 1 ? 'oppgave' : 'oppgaver' }}
                            @if($list['task_count'] > 0)
                                · {{ $list['completed_count'] }} fullført
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Progress bar --}}
                @if($list['task_count'] > 0)
                    <div class="mb-3">
                        <div class="w-full bg-muted-foreground/10 rounded-full h-2 overflow-hidden">
                            <div
                                class="bg-accent h-full transition-all duration-300"
                                style="width: {{ $list['task_count'] > 0 ? round(($list['completed_count'] / $list['task_count']) * 100) : 0 }}%"
                            ></div>
                        </div>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center justify-end pt-3 border-t border-border">
                    <div class="flex items-center gap-1">
                        <button
                            wire:click="openListModal({{ $list['id'] }})"
                            @click.prevent.stop
                            class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer"
                            title="Rediger"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button
                            wire:click="deleteList({{ $list['id'] }})"
                            wire:confirm="Er du sikker på at du vil slette denne listen og alle oppgaver i den?"
                            @click.prevent.stop
                            class="p-1.5 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded transition-colors cursor-pointer"
                            title="Slett"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full">
                <div class="bg-card border border-border rounded-lg px-4 py-12 text-center text-muted-foreground">
                    <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p>Ingen oppgavelister ennå</p>
                    <p class="text-sm mt-1">Trykk + for å opprette din første liste</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- List Modal --}}
    @if($showListModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            x-data
            x-on:keydown.escape.window="$wire.closeListModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                wire:click="closeListModal"
            ></div>

            {{-- Modal --}}
            <div class="relative bg-card border border-border rounded-lg shadow-xl w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
                {{-- Header --}}
                <div class="px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        {{ $editingListId ? 'Rediger liste' : 'Ny liste' }}
                    </h2>
                    <button
                        wire:click="closeListModal"
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
                            wire:model="listName"
                            class="w-full bg-input border border-border rounded-lg px-3 py-2 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="F.eks. Handletur, Ukesoppgaver..."
                            autofocus
                        >
                        @error('listName')
                            <p class="text-destructive text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{ isShared: @entangle('listIsShared'), assistantId: @entangle('listAssistantId'), allowAssistantAdd: @entangle('listAllowAssistantAdd') }">
                        <x-checkbox
                            x-model="isShared"
                            x-on:change="if(isShared) assistantId = null; else allowAssistantAdd = false"
                            label="Delt liste"
                            description="Alle assistenter kan se denne listen"
                            size="sm"
                        />

                        {{-- Sub-option: Allow assistants to add tasks (only visible when shared) --}}
                        <div x-show="isShared" x-transition class="mt-3">
                            <x-checkbox
                                x-model="allowAssistantAdd"
                                label="Tillat assistenter å legge til"
                                description="Assistenter kan legge til oppgaver i denne listen"
                                size="sm"
                            />
                        </div>
                    </div>

                    <div x-data="{ isShared: @entangle('listIsShared'), assistantId: @entangle('listAssistantId') }" x-show="!isShared" x-transition>
                        <label class="block text-sm font-medium text-foreground mb-1">Tildel til assistent</label>
                        <x-select x-model="assistantId" x-on:change="if(assistantId) isShared = false" :inline="true" placeholder="Ingen (kun admin)">
                            @foreach($this->assistants as $assistant)
                                <option value="{{ $assistant['id'] }}">{{ $assistant['name'] }}</option>
                            @endforeach
                        </x-select>
                        <p class="text-xs text-muted-foreground mt-1">Listen og alle oppgaver tilhører kun denne assistenten</p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-4 sm:px-6 py-4 border-t border-border flex items-center justify-end gap-3">
                    <x-button variant="secondary" wire:click="closeListModal">Avbryt</x-button>
                    <x-button wire:click="saveList" wire:loading.attr="disabled">{{ $editingListId ? 'Lagre' : 'Opprett' }}</x-button>
                </div>
            </div>
        </div>
    @endif
</x-page-container>
