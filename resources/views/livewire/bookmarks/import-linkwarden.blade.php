<x-page-container>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Importer fra Linkwarden</h1>
            <p class="text-sm text-muted-foreground mt-1">Last opp en Linkwarden backup.json-fil</p>
        </div>
        <a
            href="{{ route('tools.bookmarks') }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-foreground bg-card border border-border rounded-lg hover:bg-card-hover transition-colors"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Tilbake
        </a>
    </div>

    @if($errorMessage)
        <div class="mb-6 p-4 bg-destructive/10 border border-destructive/30 rounded-lg">
            <p class="text-sm text-destructive">{{ $errorMessage }}</p>
        </div>
    @endif

    @if($isImported)
        {{-- Success state --}}
        <div class="bg-card border border-border rounded-lg p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-accent/10 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-foreground mb-2">Import fullført!</h2>
            <div class="text-muted-foreground mb-6 space-y-1">
                <p>{{ $stats['folders_created'] ?? 0 }} mapper opprettet</p>
                <p>{{ $stats['imported'] ?? 0 }} bokmerker importert</p>
                <p>{{ $stats['duplicates'] ?? 0 }} duplikater hoppet over</p>
            </div>
            <a
                href="{{ route('tools.bookmarks') }}"
                class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium text-background bg-accent rounded-lg hover:bg-accent/90 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
                Gå til bokmerker
            </a>
        </div>
    @elseif($isParsed)
        {{-- Preview state --}}
        <div class="bg-card border border-border rounded-lg overflow-hidden">
            <div class="p-6 border-b border-border">
                <h2 class="text-lg font-semibold text-foreground mb-4">Forhåndsvisning</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-card-hover rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-foreground">{{ $stats['collections'] ?? 0 }}</div>
                        <div class="text-sm text-muted-foreground">Mapper</div>
                    </div>
                    <div class="bg-card-hover rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-foreground">{{ $stats['bookmarks'] ?? 0 }}</div>
                        <div class="text-sm text-muted-foreground">Bokmerker</div>
                    </div>
                    <div class="bg-card-hover rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-foreground">{{ $stats['pinned'] ?? 0 }}</div>
                        <div class="text-sm text-muted-foreground">Pinned</div>
                    </div>
                    <div class="bg-card-hover rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-muted-foreground">{{ $stats['skipped'] ?? 0 }}</div>
                        <div class="text-sm text-muted-foreground">Hoppes over</div>
                    </div>
                </div>
            </div>

            @if(count($previewFolders) > 0)
                <div class="p-6 border-b border-border">
                    <h3 class="text-sm font-medium text-foreground mb-3">Mapper som importeres</h3>
                    <div class="max-h-48 overflow-y-auto space-y-1">
                        @foreach($previewFolders as $folder)
                            <div class="flex items-center justify-between text-sm py-1">
                                <span class="text-muted-foreground">{{ $folder['name'] }}</span>
                                <span class="text-foreground font-medium">{{ $folder['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="p-6 bg-card-hover flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    "Privat > Ønskeliste" hoppes over ({{ $stats['skipped'] ?? 0 }} bokmerker)
                </p>
                <button
                    wire:click="import"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-background bg-accent rounded-lg hover:bg-accent/90 transition-colors disabled:opacity-50 cursor-pointer"
                >
                    <span wire:loading.remove wire:target="import">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </span>
                    <span wire:loading wire:target="import">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="import">Importer</span>
                    <span wire:loading wire:target="import">Importerer...</span>
                </button>
            </div>
        </div>
    @else
        {{-- Upload state --}}
        <div class="bg-card border border-border rounded-lg p-8">
            <div
                x-data="{ isDragging: false }"
                x-on:dragover.prevent="isDragging = true"
                x-on:dragleave.prevent="isDragging = false"
                x-on:drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                :class="isDragging ? 'border-accent bg-accent/5' : 'border-border'"
                class="border-2 border-dashed rounded-lg p-12 text-center transition-colors"
            >
                <input
                    type="file"
                    wire:model="file"
                    accept=".json,application/json"
                    class="hidden"
                    x-ref="fileInput"
                    id="file-upload"
                >
                <label for="file-upload" class="cursor-pointer">
                    <div class="w-16 h-16 mx-auto mb-4 bg-card-hover rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>
                    <p class="text-foreground font-medium mb-1">Klikk for å velge fil eller dra og slipp</p>
                    <p class="text-sm text-muted-foreground">backup.json fra Linkwarden (maks 10MB)</p>
                </label>

                <div wire:loading wire:target="file" class="mt-4">
                    <svg class="w-6 h-6 mx-auto animate-spin text-accent" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-sm text-muted-foreground mt-2">Analyserer fil...</p>
                </div>
            </div>

            @error('file')
                <p class="mt-3 text-sm text-destructive">{{ $message }}</p>
            @enderror

            <div class="mt-6 p-4 bg-card-hover rounded-lg">
                <h3 class="text-sm font-medium text-foreground mb-2">Hvordan eksportere fra Linkwarden</h3>
                <ol class="text-sm text-muted-foreground space-y-1 list-decimal list-inside">
                    <li>Gå til Linkwarden Settings</li>
                    <li>Klikk på "Data" i menyen</li>
                    <li>Klikk "Export Data" for å laste ned backup.json</li>
                </ol>
            </div>
        </div>
    @endif
</x-page-container>
