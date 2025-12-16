<x-page-container class="h-full flex flex-col">
    <!-- Page header -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-foreground">Profil</h1>
        <p class="mt-1 text-sm text-muted">Administrer din profilinformasjon</p>
    </div>

    <!-- Centered content -->
    <div class="flex-1 flex items-center justify-center">
        <div class="w-full max-w-4xl space-y-6">
            <!-- Account stats -->
            <div class="grid grid-cols-2 gap-4">
                <x-card class="text-center">
                    <p class="text-sm text-muted mb-1">Registrert</p>
                    <p class="text-lg font-semibold text-foreground">{{ Auth::user()?->created_at?->format('d.m.Y') ?? '01.01.2024' }}</p>
                </x-card>
                <x-card class="text-center">
                    <p class="text-sm text-muted mb-1">Siste innlogging</p>
                    <p class="text-lg font-semibold text-foreground">{{ Auth::user()?->last_login_at?->format('d.m.Y H:i') ?? 'Nå' }}</p>
                </x-card>
            </div>

            <!-- Profile info and password cards side by side -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Profile info card -->
                <x-card class="flex flex-col">
                    <x-slot name="header">
                        <h2 class="text-lg font-medium text-foreground">Profilinformasjon</h2>
                        <p class="mt-1 text-sm text-muted">Oppdater navn og e-postadresse</p>
                    </x-slot>

                    <form wire:submit="updateProfile" class="flex flex-col flex-1">
                        <div class="space-y-4 flex-1">
                            <div>
                                <label for="name" class="block text-sm font-medium text-foreground mb-1.5">Navn</label>
                                <x-input
                                    wire:model="name"
                                    type="text"
                                    id="name"
                                    placeholder="Ditt navn"
                                />
                                @error('name')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-foreground mb-1.5">E-post</label>
                                <x-input
                                    wire:model="email"
                                    type="email"
                                    id="email"
                                    placeholder="din@epost.no"
                                />
                                @error('email')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex justify-end pt-6 mt-auto">
                            <x-button type="submit" class="cursor-pointer">
                                <span wire:loading.remove wire:target="updateProfile">Lagre endringer</span>
                                <span wire:loading wire:target="updateProfile">Lagrer...</span>
                            </x-button>
                        </div>
                    </form>
                </x-card>

                <!-- Password card -->
                <x-card class="flex flex-col">
                    <x-slot name="header">
                        <h2 class="text-lg font-medium text-foreground">Oppdater passord</h2>
                        <p class="mt-1 text-sm text-muted">Bruk et sterkt, unikt passord</p>
                    </x-slot>

                    <form wire:submit="updatePassword" class="flex flex-col flex-1">
                        <div class="space-y-4 flex-1">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-foreground mb-1.5">Nåværende passord</label>
                                <x-input
                                    wire:model="current_password"
                                    type="password"
                                    id="current_password"
                                    placeholder="Nåværende passord"
                                />
                                @error('current_password')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-foreground mb-1.5">Nytt passord</label>
                                <x-input
                                    wire:model="password"
                                    type="password"
                                    id="password"
                                    placeholder="Nytt passord"
                                />
                                @error('password')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-foreground mb-1.5">Bekreft passord</label>
                                <x-input
                                    wire:model="password_confirmation"
                                    type="password"
                                    id="password_confirmation"
                                    placeholder="Bekreft nytt passord"
                                />
                            </div>
                        </div>

                        <div class="flex justify-end pt-6 mt-auto">
                            <x-button type="submit" class="cursor-pointer">
                                <span wire:loading.remove wire:target="updatePassword">Oppdater passord</span>
                                <span wire:loading wire:target="updatePassword">Oppdaterer...</span>
                            </x-button>
                        </div>
                    </form>
                </x-card>
            </div>
        </div>
    </div>
</x-page-container>
