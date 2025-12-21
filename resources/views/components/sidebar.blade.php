<div class="flex flex-col h-full">
    <!-- Logo/Header -->
    <div class="flex items-center gap-3 px-6 py-6 border-b border-border">
        <img src="/icons/icon-192x192.png" alt="Dashboard" class="w-10 h-10 rounded-lg">
        <div>
            <h2 class="text-lg font-semibold text-foreground">Personlig</h2>
            <p class="text-xs text-muted">Dashboard</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto">
        {{-- Kontrollpanel - no submenu --}}
        <x-sidebar-link
            href="{{ route('dashboard') }}"
            :active="request()->routeIs('dashboard')"
        >
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </x-slot>
            Kontrollpanel
        </x-sidebar-link>

        {{-- BPA - with submenu --}}
        <x-sidebar-group
            label="BPA"
            :active="request()->routeIs('bpa.*')"
        >
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>

            <x-sidebar-sublink href="{{ route('bpa.dashboard') }}" :active="request()->routeIs('bpa.dashboard')">
                Dashbord
            </x-sidebar-sublink>
            <x-sidebar-sublink href="{{ route('bpa.calendar') }}" :active="request()->routeIs('bpa.calendar')">
                Kalender
            </x-sidebar-sublink>
            <x-sidebar-sublink href="{{ route('bpa.timesheets') }}" :active="request()->routeIs('bpa.timesheets')">
                Timelister
            </x-sidebar-sublink>
            <x-sidebar-sublink href="{{ route('bpa.assistants') }}" :active="request()->routeIs('bpa.assistants*')">
                Assistenter
            </x-sidebar-sublink>
            <x-sidebar-sublink href="{{ route('bpa.tasks.index') }}" :active="request()->routeIs('bpa.tasks.*')">
                Oppgaver
            </x-sidebar-sublink>
        </x-sidebar-group>

        {{-- Medisinsk - with submenu --}}
        <x-sidebar-group
            label="Medisinsk"
            :active="request()->routeIs('medical.*')"
        >
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </x-slot>

            <x-sidebar-sublink href="{{ route('medical.dashboard') }}" :active="request()->routeIs('medical.dashboard')">
                Dashbord
            </x-sidebar-sublink>
            <x-sidebar-sublink href="{{ route('medical.equipment') }}" :active="request()->routeIs('medical.equipment')">
                Utstyr
            </x-sidebar-sublink>
            <x-sidebar-sublink href="{{ route('medical.prescriptions') }}" :active="request()->routeIs('medical.prescriptions')">
                Resepter
            </x-sidebar-sublink>
            <x-sidebar-sublink href="{{ route('medical.weight') }}" :active="request()->routeIs('medical.weight')">
                Vekt
            </x-sidebar-sublink>
            <x-sidebar-sublink href="{{ route('medical.hjelpemidler') }}" :active="request()->routeIs('medical.hjelpemidler')">
                Hjelpemidler
            </x-sidebar-sublink>
        </x-sidebar-group>

        {{-- Økonomi - no submenu --}}
        <x-sidebar-link
            href="{{ route('economy') }}"
            :active="request()->routeIs('economy*')"
        >
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
            Økonomi
        </x-sidebar-link>

        {{-- Ønskeliste - no submenu --}}
        <x-sidebar-link
            href="{{ route('wishlist') }}"
            :active="request()->routeIs('wishlist*')"
        >
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
            </x-slot>
            Ønskeliste
        </x-sidebar-link>

        {{-- Spill - with submenu --}}
        <x-sidebar-group
            label="Spill"
            :active="request()->routeIs('games.*')"
        >
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </x-slot>

            <x-sidebar-sublink href="{{ route('games.rommers') }}" :active="request()->routeIs('games.rommers')">
                Rømmers
            </x-sidebar-sublink>
        </x-sidebar-group>

        {{-- Verktøy - with submenu --}}
        <x-sidebar-group
            label="Verktøy"
            :active="request()->routeIs('tools.*')"
        >
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </x-slot>

            <x-sidebar-sublink href="{{ route('tools.bookmarks') }}" :active="request()->routeIs('tools.bookmarks')">
                Bokmerker
            </x-sidebar-sublink>
            <x-sidebar-sublink href="{{ route('tools.mileage-calculator') }}" :active="request()->routeIs('tools.mileage-calculator')">
                Kjøregodtgjørelse
            </x-sidebar-sublink>
            <x-sidebar-sublink :disabled="true">
                Pdf til Markdown (kommer snart)
            </x-sidebar-sublink>
            <x-sidebar-sublink href="{{ route('tools.port-generator') }}" :active="request()->routeIs('tools.port-generator')">
                Portvelger
            </x-sidebar-sublink>
        </x-sidebar-group>
    </nav>

    <!-- Footer (user dropdown) -->
    <div class="px-3 py-4 border-t border-border">
        <livewire:user-dropdown />
    </div>
</div>
