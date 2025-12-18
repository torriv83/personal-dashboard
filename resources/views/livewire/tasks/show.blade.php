<x-page-container class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('bpa.tasks.index') }}"
                class="p-2 text-muted-foreground hover:text-foreground hover:bg-card-hover rounded-lg transition-colors cursor-pointer"
                title="Tilbake"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-foreground">{{ $taskList->name }}</h1>
                    @if($taskList->is_shared)
                        <span class="px-2 py-0.5 text-xs font-medium text-accent bg-accent/10 rounded">
                            Delt
                        </span>
                    @elseif($taskList->assistant)
                        <span class="px-2 py-0.5 text-xs font-medium text-accent bg-accent/10 rounded">
                            {{ $taskList->assistant->name }}
                        </span>
                    @endif
                </div>
                <p class="text-sm text-muted-foreground mt-1 hidden sm:block">
                    {{ $this->pendingTasks->count() }} av {{ $this->tasks->count() }} oppgaver gjenstår
                </p>
            </div>
        </div>
    </div>

    {{-- Quick Add Task Form --}}
    <div class="bg-card border border-border rounded-lg p-4">
        <form wire:submit="addTask" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <x-input
                    wire:model="newTaskTitle"
                    type="text"
                    placeholder="Legg til ny oppgave..."
                    class="w-full"
                />
            </div>
            <div class="flex gap-2">
                <x-select wire:model="newTaskPriority" class="w-32">
                    @foreach($this->priorityOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>

                @unless($this->listHasAssistant)
                    <x-select wire:model="newTaskAssistantId" class="w-48">
                        <option value="">Ingen assistent</option>
                        @foreach($this->assistants as $assistant)
                            <option value="{{ $assistant->id }}">{{ $assistant->name }}</option>
                        @endforeach
                    </x-select>
                @endunless

                <button
                    type="submit"
                    class="px-4 py-2 text-black bg-accent rounded-md hover:bg-accent-hover transition-colors cursor-pointer whitespace-nowrap"
                >
                    Legg til
                </button>
            </div>
        </form>
        @error('newTaskTitle')
            <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
        @enderror
    </div>

    {{-- Pending Tasks List --}}
    @if($this->pendingTasks->isNotEmpty())
        <div class="bg-card border border-border rounded-lg overflow-hidden">
            <div class="divide-y divide-border" x-sort="$wire.updateOrder($item, $position)" wire:ignore.self>
                @foreach($this->pendingTasks as $task)
                    <div
                        wire:key="task-{{ $task->id }}"
                        x-sort:item="'task-{{ $task->id }}'"
                        class="flex items-center gap-3 p-4 hover:bg-card-hover transition-colors"
                    >
                        {{-- Drag Handle --}}
                        <svg
                            x-sort:handle
                            class="w-4 h-4 text-muted-foreground cursor-grab shrink-0"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                            @click.stop
                        >
                            <circle cx="9" cy="6" r="1.5" />
                            <circle cx="15" cy="6" r="1.5" />
                            <circle cx="9" cy="12" r="1.5" />
                            <circle cx="15" cy="12" r="1.5" />
                            <circle cx="9" cy="18" r="1.5" />
                            <circle cx="15" cy="18" r="1.5" />
                        </svg>

                        {{-- Checkbox --}}
                        <button
                            wire:click="toggleTaskStatus({{ $task->id }})"
                            class="shrink-0 cursor-pointer"
                            title="Marker som fullført"
                        >
                            <svg class="w-6 h-6 text-muted-foreground hover:text-foreground transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="4" y="4" width="16" height="16" rx="3" stroke-width="2"/>
                            </svg>
                        </button>

                        {{-- Task Content --}}
                        <div
                            class="flex-1 min-w-0"
                            x-data="{ editing: false, title: '{{ addslashes($task->title) }}' }"
                            @click.stop
                        >
                            {{-- View Mode --}}
                            <div x-show="!editing" class="flex items-center gap-2 flex-wrap">
                                {{-- Priority Indicator (click to cycle) --}}
                                <button
                                    wire:click="cycleTaskPriority({{ $task->id }})"
                                    class="shrink-0 cursor-pointer"
                                    title="Klikk for å endre prioritet"
                                >
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded {{ $task->priority->bgColor() }} {{ $task->priority->color() }} hover:opacity-80 transition-opacity">
                                        {{ $task->priority->label() }}
                                    </span>
                                </button>

                                {{-- Title (click to edit) --}}
                                <span
                                    @click="editing = true; $nextTick(() => $refs.input{{ $task->id }}.focus())"
                                    class="text-foreground cursor-pointer hover:text-accent transition-colors"
                                    title="Klikk for å redigere"
                                >
                                    {{ $task->title }}
                                </span>

                                {{-- Assistant Badge --}}
                                @if($task->assistant)
                                    <span class="px-2 py-0.5 text-xs text-muted-foreground bg-muted-foreground/10 rounded">
                                        {{ $task->assistant->name }}
                                    </span>
                                @endif
                            </div>

                            {{-- Edit Mode --}}
                            <div x-show="editing" x-cloak class="flex items-center gap-2">
                                <input
                                    type="text"
                                    x-model="title"
                                    x-ref="input{{ $task->id }}"
                                    @keydown.enter="$wire.updateTaskTitle({{ $task->id }}, title); editing = false"
                                    @keydown.escape="title = '{{ addslashes($task->title) }}'; editing = false"
                                    @click.away="if (title !== '{{ addslashes($task->title) }}') { $wire.updateTaskTitle({{ $task->id }}, title) }; editing = false"
                                    class="flex-1 px-2 py-1 text-foreground bg-input border border-border rounded focus:outline-none focus:ring-1 focus:ring-accent"
                                />
                                <button
                                    @click="$wire.updateTaskTitle({{ $task->id }}, title); editing = false"
                                    class="p-1 text-accent hover:text-accent-hover cursor-pointer"
                                    title="Lagre"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                                <button
                                    @click="title = '{{ addslashes($task->title) }}'; editing = false"
                                    class="p-1 text-muted-foreground hover:text-foreground cursor-pointer"
                                    title="Avbryt"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-1 shrink-0">
                            <button
                                wire:click="deleteTask({{ $task->id }})"
                                wire:confirm="Er du sikker på at du vil slette denne oppgaven?"
                                class="p-1.5 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 rounded transition-colors cursor-pointer"
                                title="Slett"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @elseif($this->completedTasks->isEmpty())
        <div class="bg-card border border-border rounded-lg overflow-hidden">
            <div class="p-8 text-center text-muted-foreground">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p>Ingen oppgaver ennå</p>
                <p class="text-sm mt-1">Legg til din første oppgave ovenfor</p>
            </div>
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
    @if($this->completedTasks->isNotEmpty())
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
                    <span class="text-xs text-muted-foreground">({{ $this->completedTasks->count() }})</span>
                </div>
                <svg class="w-4 h-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="expanded" x-collapse class="border-t border-border">
                <div class="divide-y divide-border">
                    @foreach($this->completedTasks as $task)
                        <div
                            wire:key="completed-task-{{ $task->id }}"
                            class="flex items-center gap-3 p-4 hover:bg-card-hover transition-colors opacity-60"
                        >
                            {{-- Spacer for drag handle --}}
                            <div class="w-4 shrink-0"></div>

                            {{-- Checkbox --}}
                            <button
                                wire:click="toggleTaskStatus({{ $task->id }})"
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

                                    {{-- Assistant Badge --}}
                                    @if($task->assistant)
                                        <span class="px-2 py-0.5 text-xs text-muted-foreground bg-muted-foreground/10 rounded">
                                            {{ $task->assistant->name }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    wire:click="openEditModal({{ $task->id }})"
                                    class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-input rounded transition-colors cursor-pointer"
                                    title="Rediger"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button
                                    wire:click="deleteTask({{ $task->id }})"
                                    wire:confirm="Er du sikker på at du vil slette denne oppgaven?"
                                    class="p-1.5 text-muted-foreground hover:text-red-400 hover:bg-red-500/10 rounded transition-colors cursor-pointer"
                                    title="Slett"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</x-page-container>
