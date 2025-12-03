<div class="w-full max-w-md">
    {{-- Forgot Password Card --}}
    <div class="bg-card border border-border rounded-lg p-8 shadow-xl">
        <div class="text-center mb-6">
            <div class="mx-auto w-12 h-12 bg-accent/10 rounded-full flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-foreground">Glemt passord?</h2>
            <p class="text-muted text-sm mt-1">Skriv inn e-postadressen din, så sender vi deg en lenke for å tilbakestille passordet.</p>
        </div>

        @if($submitted)
            {{-- Success message --}}
            <div class="bg-success/10 border border-success/20 rounded-lg p-4 text-center">
                <svg class="w-8 h-8 text-success mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-foreground font-medium">E-post sendt!</p>
                <p class="text-muted text-sm mt-1">Sjekk innboksen din for en lenke til å tilbakestille passordet.</p>
            </div>

            <div class="mt-6">
                <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 text-sm text-accent hover:text-accent-hover transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Tilbake til innlogging
                </a>
            </div>
        @else
            <form wire:submit="sendResetLink" class="space-y-5">
                {{-- Email --}}
                <x-input
                    wire:model="email"
                    type="email"
                    label="E-postadresse"
                    placeholder="din@epost.no"
                    required
                    autocomplete="email"
                />

                {{-- Submit button --}}
                <x-button type="submit" class="w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="sendResetLink">Send tilbakestillingslenke</span>
                    <span wire:loading.inline-flex wire:target="sendResetLink" class="items-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Sender...
                    </span>
                </x-button>
            </form>

            {{-- Back to login --}}
            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-sm text-muted hover:text-foreground transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Tilbake til innlogging
                </a>
            </div>
        @endif
    </div>
</div>
