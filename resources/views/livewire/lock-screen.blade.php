<div
    x-data="{
        isLocked: false,
        lastActivity: Date.now(),
        timeoutMs: {{ $timeoutMinutes * 60 * 1000 }},
        isEnabled: {{ $isEnabled ? 'true' : 'false' }},
        checkInterval: null,

        init() {
            if (!this.isEnabled || this.timeoutMs === 0) return;

            // Reset activity timer on user interaction
            const resetTimer = () => {
                this.lastActivity = Date.now();
            };

            document.addEventListener('mousemove', resetTimer);
            document.addEventListener('keydown', resetTimer);
            document.addEventListener('click', resetTimer);
            document.addEventListener('scroll', resetTimer);
            document.addEventListener('touchstart', resetTimer);

            // Check for inactivity every 10 seconds
            this.checkInterval = setInterval(() => {
                if (!this.isLocked && Date.now() - this.lastActivity > this.timeoutMs) {
                    this.lock();
                }
            }, 10000);

            // Listen for unlock event from Livewire
            Livewire.on('unlocked', () => {
                this.isLocked = false;
                this.lastActivity = Date.now();
            });

            // Listen for lock event from Livewire (for manual lock trigger)
            Livewire.on('lock', () => {
                this.lock();
            });
        },

        lock() {
            this.isLocked = true;
            $wire.lock();
        },

        // Handle keyboard input for PIN
        handleKeydown(e) {
            if (!this.isLocked) return;

            if (e.key >= '0' && e.key <= '9') {
                $wire.addDigit(e.key);
            } else if (e.key === 'Backspace') {
                $wire.removeDigit();
            } else if (e.key === 'Escape') {
                $wire.clearPin();
            }
        }
    }"
    x-on:keydown.window="handleKeydown"
    x-show="isLocked"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center bg-background"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
>
    <div class="w-full max-w-sm mx-4 text-center">
        {{-- Lock Icon --}}
        <div class="mb-6">
            <div class="w-20 h-20 mx-auto rounded-full bg-accent/20 flex items-center justify-center">
                <svg class="w-10 h-10 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
        </div>

        {{-- User Name --}}
        <h2 class="text-xl font-semibold text-foreground mb-2">{{ $userName }}</h2>
        <p class="text-sm text-muted mb-6">
            @if($showPasswordFallback)
                Skriv inn passordet ditt
            @else
                Skriv inn PIN-koden din
            @endif
        </p>

        @if($showPasswordFallback)
            {{-- Password Input --}}
            <form wire:submit="verifyPassword" class="space-y-4">
                <div>
                    <input
                        type="password"
                        wire:model="password"
                        class="w-full bg-input border border-border rounded-lg px-4 py-3 text-foreground text-center placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent {{ $hasError ? 'border-red-500 animate-shake' : '' }}"
                        placeholder="Passord"
                        autofocus
                    >
                </div>

                @if($errorMessage)
                    <p class="text-sm text-red-400">{{ $errorMessage }}</p>
                @endif

                <button
                    type="submit"
                    class="w-full py-3 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer"
                >
                    Lås opp
                </button>

                <button
                    type="button"
                    wire:click="switchToPin"
                    class="text-sm text-muted hover:text-foreground transition-colors cursor-pointer"
                >
                    Bruk PIN i stedet
                </button>
            </form>
        @else
            {{-- PIN Dots --}}
            <div class="flex justify-center gap-3 mb-6">
                @for($i = 0; $i < 6; $i++)
                    <div class="w-4 h-4 rounded-full transition-all duration-150 {{ $i < strlen($pin) ? 'bg-accent scale-110' : 'bg-border' }} {{ $hasError ? 'bg-red-500' : '' }}"></div>
                @endfor
            </div>

            {{-- Error Message --}}
            @if($errorMessage)
                <p class="text-sm text-red-400 mb-4" x-data x-init="$el.classList.add('animate-shake'); setTimeout(() => $el.classList.remove('animate-shake'), 500)">
                    {{ $errorMessage }}
                </p>
            @endif

            {{-- PIN Pad --}}
            <div class="grid grid-cols-3 gap-3 max-w-xs mx-auto">
                @foreach(['1', '2', '3', '4', '5', '6', '7', '8', '9'] as $digit)
                    <button
                        type="button"
                        wire:click="addDigit('{{ $digit }}')"
                        class="w-16 h-16 mx-auto rounded-full bg-card-hover border border-border text-xl font-medium text-foreground hover:bg-input hover:border-accent transition-all duration-150 cursor-pointer active:scale-95"
                    >
                        {{ $digit }}
                    </button>
                @endforeach

                {{-- Empty / Clear --}}
                <button
                    type="button"
                    wire:click="clearPin"
                    class="w-16 h-16 mx-auto rounded-full bg-card-hover border border-border text-sm font-medium text-muted-foreground hover:bg-input hover:text-foreground transition-all duration-150 cursor-pointer"
                >
                    Tøm
                </button>

                {{-- 0 --}}
                <button
                    type="button"
                    wire:click="addDigit('0')"
                    class="w-16 h-16 mx-auto rounded-full bg-card-hover border border-border text-xl font-medium text-foreground hover:bg-input hover:border-accent transition-all duration-150 cursor-pointer active:scale-95"
                >
                    0
                </button>

                {{-- Backspace --}}
                <button
                    type="button"
                    wire:click="removeDigit"
                    class="w-16 h-16 mx-auto rounded-full bg-card-hover border border-border text-muted-foreground hover:bg-input hover:text-foreground transition-all duration-150 cursor-pointer flex items-center justify-center"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z" />
                    </svg>
                </button>
            </div>

            {{-- Fallback link --}}
            <p class="mt-6 text-xs text-muted">
                <button
                    type="button"
                    wire:click="$set('showPasswordFallback', true)"
                    class="hover:text-foreground transition-colors cursor-pointer"
                >
                    Glemt PIN? Bruk passord
                </button>
            </p>
        @endif
    </div>

    <style>
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
            20%, 40%, 60%, 80% { transform: translateX(4px); }
        }
        .animate-shake {
            animation: shake 0.5s ease-in-out;
        }
    </style>
</div>
