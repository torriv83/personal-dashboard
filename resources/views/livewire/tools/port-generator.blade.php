<x-page-container class="h-full flex flex-col">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-foreground">Portvelger</h1>
        <p class="text-sm text-muted-foreground mt-1">Generer tilfeldige porter som ikke kolliderer med andre programmer</p>
    </div>

    {{-- Centered Card Container --}}
    <div class="flex-1 flex items-center justify-center">
        {{-- Port Display Card --}}
        <div class="bg-card border border-border rounded-lg p-6 sm:p-8 w-full max-w-md">
            <div class="text-center space-y-6">
                {{-- Port Number Display --}}
                <div class="space-y-2">
                    <p class="text-xs text-muted-foreground uppercase tracking-wider">Portnummer</p>
                    <div class="text-5xl sm:text-6xl font-mono font-bold text-accent">
                        {{ $port }}
                    </div>
                    <p class="text-xs text-muted-foreground">Område: 49152 - 65535</p>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    {{-- Copy Button --}}
                    <button
                        x-data="{ copied: false }"
                        x-on:click="
                            navigator.clipboard.writeText('{{ $port }}');
                            copied = true;
                            setTimeout(() => copied = false, 2000);
                        "
                        class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer flex items-center justify-center gap-2"
                    >
                        <template x-if="!copied">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </template>
                        <template x-if="copied">
                            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </template>
                        <span x-text="copied ? 'Kopiert!' : 'Kopier'"></span>
                    </button>

                    {{-- Generate New Button --}}
                    <button
                        wire:click="generatePort"
                        class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Generer ny
                    </button>
                </div>

                {{-- Info Section --}}
                <div class="pt-4 border-t border-border text-left">
                    <h2 class="text-sm font-medium text-foreground mb-2">Om porter</h2>
                    <ul class="text-xs text-muted-foreground space-y-1">
                        <li><span class="text-foreground font-medium">0-1023:</span> Velkjente porter (reservert)</li>
                        <li><span class="text-foreground font-medium">1024-49151:</span> Registrerte porter</li>
                        <li class="text-accent"><span class="font-medium">49152-65535:</span> Dynamiske/private porter ✓</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-page-container>
