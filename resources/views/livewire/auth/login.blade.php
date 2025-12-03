<div class="w-full max-w-md">
    {{-- Login Card --}}
    <div class="bg-card border border-border rounded-lg p-8 shadow-xl">
        <div class="text-center mb-6">
            <h2 class="text-xl font-semibold text-foreground">Velkommen tilbake</h2>
            <p class="text-muted text-sm mt-1">Logg inn for å fortsette</p>
        </div>

        {{-- Success message (e.g. after password reset) --}}
        @if (session('status'))
            <div class="mb-4 bg-success/10 border border-success/20 rounded-lg p-3 text-center">
                <p class="text-sm text-success">{{ session('status') }}</p>
            </div>
        @endif

        <form wire:submit="login" class="space-y-5">
            {{-- Email --}}
            <x-input
                wire:model="email"
                type="email"
                label="E-postadresse"
                placeholder="din@epost.no"
                required
                autocomplete="email"
            />

            {{-- Password --}}
            <x-input
                wire:model="password"
                type="password"
                label="Passord"
                placeholder="Skriv inn passordet ditt"
                required
                autocomplete="current-password"
            />

            {{-- Remember me & Forgot password --}}
            <div class="flex items-center justify-between">
                <x-checkbox
                    wire:model="remember"
                    label="Husk meg"
                />
                <a href="{{ route('password.request') }}" class="text-sm text-accent hover:text-accent-hover transition-colors cursor-pointer">
                    Glemt passord?
                </a>
            </div>

            {{-- Submit button --}}
            <x-button type="submit" class="w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="login">Logg inn</span>
                <span wire:loading.inline-flex wire:target="login" class="items-center gap-2">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Logger inn...
                </span>
            </x-button>
        </form>
    </div>

    {{-- Decorative element --}}
    <div class="mt-6 flex items-center justify-center gap-2 text-muted-foreground text-xs">
        <div class="h-px w-12 bg-border"></div>
        <span>Sikker innlogging</span>
        <div class="h-px w-12 bg-border"></div>
    </div>
</div>
