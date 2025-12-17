<div>
    {{-- Sticky Header with dropdown --}}
    <header class="border-b border-white/10 bg-card/50 backdrop-blur-sm sticky top-0 z-10">
        <div class="max-w-4xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-accent/20 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <span class="text-sm text-muted">Oppgaver</span>
                </div>

                {{-- List selector dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    <button
                        @click="open = !open"
                        @click.outside="open = false"
                        class="flex items-center gap-2 px-3 py-2 bg-card border border-border rounded-lg hover:bg-card-hover transition-colors cursor-pointer"
                    >
                        <span class="text-sm text-foreground">
                            @if($currentListId === null)
                                Dine oppgaver
                            @else
                                {{ $this->currentList?->name }}
                            @endif
                        </span>
                        <svg class="w-4 h-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-64 sm:w-72 bg-card border border-border rounded-lg shadow-lg z-50 overflow-hidden"
                    >
                        {{-- Dine oppgaver option --}}
                        <button
                            wire:click="selectList(null)"
                            @click="open = false"
                            class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-card-hover transition-colors cursor-pointer {{ $currentListId === null ? 'bg-accent/10' : '' }}"
                        >
                            <svg class="w-5 h-5 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-foreground">Dine oppgaver</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ $this->assignedTasks->where('status', App\Enums\TaskStatus::Pending)->count() }} gjenstår
                                </div>
                            </div>
                            @if($currentListId === null)
                                <svg class="w-4 h-4 text-accent shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                </svg>
                            @endif
                        </button>

                        @if($this->sharedLists->isNotEmpty())
                            <div class="border-t border-border">
                                <div class="px-4 py-2 text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    Felles lister
                                </div>
                            </div>

                            @foreach($this->sharedLists as $list)
                                <button
                                    wire:key="menu-shared-list-{{ $list->id }}"
                                    wire:click="selectList({{ $list->id }})"
                                    @click="open = false"
                                    class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-card-hover transition-colors cursor-pointer {{ $currentListId === $list->id ? 'bg-accent/10' : '' }}"
                                >
                                    <svg class="w-5 h-5 text-muted-foreground shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-foreground truncate">{{ $list->name }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ $list->tasks->where('status', App\Enums\TaskStatus::Pending)->count() }} av {{ $list->tasks->count() }} gjenstår
                                        </div>
                                    </div>
                                    @if($currentListId === $list->id)
                                        <svg class="w-4 h-4 text-accent shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                        </svg>
                                    @endif
                                </button>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1">
        <div class="max-w-4xl mx-auto px-4 py-6 space-y-6">
            {{-- Greeting --}}
            <div>
                <h1 class="text-2xl font-bold text-foreground">Hei, {{ $assistant->name }}!</h1>
                <p class="text-muted-foreground mt-1">
                    @if($currentListId === null)
                        Her er dine oppgaver
                    @else
                        {{ $this->currentList?->name }}
                    @endif
                </p>
            </div>

            {{-- Content based on selection --}}
            @if($currentListId === null)
                {{-- Assigned Tasks View --}}
                @php
                    $pendingTasks = $this->assignedTasks->where('status', App\Enums\TaskStatus::Pending);
                    $completedTasks = $this->assignedTasks->where('status', App\Enums\TaskStatus::Completed);
                    $assistantList = $this->assistantLists->first();
                @endphp

                {{-- Quick Add Task Form (only if assistant has their own list) --}}
                @if($assistantList)
                    <div class="bg-card border border-border rounded-lg p-4">
                        <form wire:submit="addTaskToOwnList" class="flex gap-3">
                            <input
                                wire:model="newTaskTitle"
                                type="text"
                                placeholder="Legg til ny oppgave..."
                                class="flex-1 px-3 py-2 bg-input border border-border rounded-md text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            />
                            <button
                                type="submit"
                                class="px-4 py-2 text-black bg-accent rounded-md hover:bg-accent-hover transition-colors cursor-pointer whitespace-nowrap"
                            >
                                Legg til
                            </button>
                        </form>
                        @error('newTaskTitle')
                            <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                @if($pendingTasks->isNotEmpty())
                    <div class="bg-card border border-border rounded-lg overflow-hidden">
                        <div class="divide-y divide-border">
                            @foreach($pendingTasks as $task)
                                <div
                                    wire:key="assigned-task-{{ $task->id }}"
                                    class="flex items-center gap-3 p-4"
                                >
                                    {{-- Checkbox --}}
                                    <button
                                        wire:click="toggleTask({{ $task->id }})"
                                        class="shrink-0 cursor-pointer"
                                        title="Marker som fullført"
                                    >
                                        <svg class="w-6 h-6 text-muted-foreground hover:text-foreground transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <rect x="4" y="4" width="16" height="16" rx="3" stroke-width="2"/>
                                        </svg>
                                    </button>

                                    {{-- Task Content --}}
                                    <div class="flex-1 min-w-0">
                                        <span class="text-foreground">
                                            {{ $task->title }}
                                        </span>
                                    </div>

                                    {{-- Priority dot (mobile only) --}}
                                    <div class="w-2.5 h-2.5 rounded-full shrink-0 sm:hidden {{ $task->priority->dotColor() }}" title="{{ $task->priority->label() }}"></div>

                                    {{-- Priority badge (desktop only) --}}
                                    <span class="hidden sm:inline-flex items-center px-2 py-0.5 text-xs font-medium rounded shrink-0 {{ $task->priority->bgColor() }} {{ $task->priority->color() }}">
                                        {{ $task->priority->label() }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @elseif($completedTasks->isEmpty())
                    <div class="bg-card border border-border rounded-lg p-8 text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-muted-foreground opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <h3 class="text-lg font-medium text-foreground mb-1">Ingen oppgaver</h3>
                        <p class="text-muted-foreground">Du har ingen oppgaver tildelt akkurat nå.</p>
                    </div>
                @else
                    <div class="bg-card border border-border rounded-lg p-8 text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-accent opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-medium text-foreground mb-1">Alt fullført!</h3>
                        <p class="text-muted-foreground">Du har fullført alle dine oppgaver.</p>
                    </div>
                @endif

                {{-- Completed Tasks Section --}}
                @if($completedTasks->isNotEmpty())
                    <div x-data="{ expanded: false }" class="bg-card border border-border rounded-lg overflow-hidden">
                        <button
                            @click="expanded = !expanded"
                            class="w-full flex items-center justify-between p-4 hover:bg-card-hover transition-colors cursor-pointer"
                        >
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm font-medium text-foreground">Fullførte oppgaver</span>
                                <span class="text-xs text-muted-foreground">({{ $completedTasks->count() }})</span>
                            </div>
                            <svg class="w-4 h-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="expanded" x-collapse class="border-t border-border">
                            <div class="divide-y divide-border">
                                @foreach($completedTasks as $task)
                                    <div
                                        wire:key="assigned-completed-{{ $task->id }}"
                                        class="flex items-center gap-3 p-4 opacity-60"
                                    >
                                        {{-- Checkbox --}}
                                        <button
                                            wire:click="toggleTask({{ $task->id }})"
                                            class="shrink-0 cursor-pointer"
                                            title="Marker som ikke fullført"
                                        >
                                            <svg class="w-6 h-6 text-accent" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                                <rect x="3" y="3" width="18" height="18" rx="3" fill="none" stroke="currentColor" stroke-width="2"/>
                                            </svg>
                                        </button>

                                        {{-- Task Content --}}
                                        <div class="flex-1 min-w-0">
                                            <span class="text-foreground line-through">
                                                {{ $task->title }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            @else
                {{-- Shared List View --}}
                @if($this->currentList)
                    @php
                        $pendingTasks = $this->currentList->tasks->where('status', App\Enums\TaskStatus::Pending);
                        $completedTasks = $this->currentList->tasks->where('status', App\Enums\TaskStatus::Completed);
                    @endphp

                    {{-- Quick Add Task Form (only if list allows assistant add) --}}
                    @if($this->currentList->allow_assistant_add)
                        <div class="bg-card border border-border rounded-lg p-4">
                            <form wire:submit="addTaskToSharedList" class="flex gap-3">
                                <input
                                    wire:model="newTaskTitle"
                                    type="text"
                                    placeholder="Legg til ny oppgave..."
                                    class="flex-1 px-3 py-2 bg-input border border-border rounded-md text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                />
                                <button
                                    type="submit"
                                    class="px-4 py-2 text-black bg-accent rounded-md hover:bg-accent-hover transition-colors cursor-pointer whitespace-nowrap"
                                >
                                    Legg til
                                </button>
                            </form>
                            @error('newTaskTitle')
                                <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    @if($pendingTasks->isNotEmpty())
                        <div class="bg-card border border-border rounded-lg overflow-hidden">
                            <div class="divide-y divide-border">
                                @foreach($pendingTasks as $task)
                                    <div
                                        wire:key="list-task-{{ $task->id }}"
                                        class="flex items-center gap-3 p-4"
                                    >
                                        {{-- Checkbox --}}
                                        <button
                                            wire:click="toggleTask({{ $task->id }})"
                                            class="shrink-0 cursor-pointer"
                                            title="Marker som fullført"
                                        >
                                            <svg class="w-6 h-6 text-muted-foreground hover:text-foreground transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <rect x="4" y="4" width="16" height="16" rx="3" stroke-width="2"/>
                                            </svg>
                                        </button>

                                        {{-- Task Content --}}
                                        <div class="flex-1 min-w-0">
                                            <span class="text-foreground">
                                                {{ $task->title }}
                                            </span>
                                        </div>

                                        {{-- Priority dot (mobile only) --}}
                                        <div class="w-2.5 h-2.5 rounded-full shrink-0 sm:hidden {{ $task->priority->dotColor() }}" title="{{ $task->priority->label() }}"></div>

                                        {{-- Priority badge (desktop only) --}}
                                        <span class="hidden sm:inline-flex items-center px-2 py-0.5 text-xs font-medium rounded shrink-0 {{ $task->priority->bgColor() }} {{ $task->priority->color() }}">
                                            {{ $task->priority->label() }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif($completedTasks->isEmpty())
                        <div class="bg-card border border-border rounded-lg p-8 text-center">
                            <svg class="w-16 h-16 mx-auto mb-4 text-muted-foreground opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <h3 class="text-lg font-medium text-foreground mb-1">Ingen oppgaver</h3>
                            <p class="text-muted-foreground">Denne listen har ingen oppgaver ennå.</p>
                        </div>
                    @else
                        <div class="bg-card border border-border rounded-lg p-8 text-center">
                            <svg class="w-16 h-16 mx-auto mb-4 text-accent opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-lg font-medium text-foreground mb-1">Alt fullført!</h3>
                            <p class="text-muted-foreground">Alle oppgaver på denne listen er fullført.</p>
                        </div>
                    @endif

                    {{-- Completed Tasks Section --}}
                    @if($completedTasks->isNotEmpty())
                        <div x-data="{ expanded: false }" class="bg-card border border-border rounded-lg overflow-hidden">
                            <button
                                @click="expanded = !expanded"
                                class="w-full flex items-center justify-between p-4 hover:bg-card-hover transition-colors cursor-pointer"
                            >
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm font-medium text-foreground">Fullførte oppgaver</span>
                                    <span class="text-xs text-muted-foreground">({{ $completedTasks->count() }})</span>
                                </div>
                                <svg class="w-4 h-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div x-show="expanded" x-collapse class="border-t border-border">
                                <div class="divide-y divide-border">
                                    @foreach($completedTasks as $task)
                                        <div
                                            wire:key="list-completed-{{ $task->id }}"
                                            class="flex items-center gap-3 p-4 opacity-60"
                                        >
                                            {{-- Checkbox --}}
                                            <button
                                                wire:click="toggleTask({{ $task->id }})"
                                                class="shrink-0 cursor-pointer"
                                                title="Marker som ikke fullført"
                                            >
                                                <svg class="w-6 h-6 text-accent" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                                    <rect x="3" y="3" width="18" height="18" rx="3" fill="none" stroke="currentColor" stroke-width="2"/>
                                                </svg>
                                            </button>

                                            {{-- Task Content --}}
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    {{-- Title --}}
                                                    <span class="text-foreground line-through">
                                                        {{ $task->title }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            @endif
        </div>
    </main>
</div>
