<div class="w-full max-w-md">
    {{-- Reset Password Card --}}
    <div class="bg-card border border-border rounded-lg p-8 shadow-xl">
        <div class="text-center mb-6">
            <div class="mx-auto w-12 h-12 bg-accent/10 rounded-full flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-foreground">Tilbakestill passord</h2>
            <p class="text-muted text-sm mt-1">Skriv inn ditt nye passord nedenfor.</p>
        </div>

        <form wire:submit="resetPassword" class="space-y-5">
            {{-- Email (readonly) --}}
            <x-input
                wire:model="email"
                type="email"
                label="E-postadresse"
                readonly
                class="bg-input/50"
            />

            {{-- New Password --}}
            <x-input
                wire:model="password"
                type="password"
                label="Nytt passord"
                placeholder="Minst 8 tegn"
                required
                autocomplete="new-password"
            />

            {{-- Confirm Password --}}
            <x-input
                wire:model="password_confirmation"
                type="password"
                label="Bekreft passord"
                placeholder="Skriv passordet igjen"
                required
                autocomplete="new-password"
            />

            {{-- Submit button --}}
            <x-button type="submit" class="w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="resetPassword">Tilbakestill passord</span>
                <span wire:loading.inline-flex wire:target="resetPassword" class="items-center gap-2">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Tilbakestiller...
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
    </div>
</div>
