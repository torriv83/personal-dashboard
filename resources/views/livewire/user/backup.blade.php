<x-page-container class="max-w-4xl mx-auto space-y-8" wire:init="loadBackups">
    {{-- Page header --}}
    <div>
        <h1 class="text-2xl font-semibold text-foreground">Backup</h1>
        <p class="mt-1 text-sm text-muted">Administrer backups av applikasjonen</p>
        <a href="{{ route('settings') }}"
            class="mt-2 text-sm text-accent hover:underline inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Tilbake til innstillinger
        </a>
    </div>

    {{-- Backup Actions --}}
    <x-card>
        <x-slot name="header">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-medium text-foreground">Opprett Backup</h2>
                    <p class="mt-1 text-sm text-muted">Lag en manuell backup av database og filer</p>
                </div>
                <button type="button" wire:click="createBackup" wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-medium text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <svg wire:loading.remove wire:target="createBackup" class="w-5 h-5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <svg wire:loading wire:target="createBackup" class="w-5 h-5 animate-spin" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span wire:loading.remove wire:target="createBackup">Opprett Backup</span>
                    <span wire:loading wire:target="createBackup">Oppretter...</span>
                </button>
            </div>
        </x-slot>

        <div class="space-y-4">
            <div class="flex items-start gap-3 p-4 bg-input/50 rounded-lg border border-border">
                <svg class="w-5 h-5 text-accent shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-foreground">
                    <p class="font-medium">Automatiske backups</p>
                    <p class="mt-1 text-muted">Backups kjøres automatisk ukentlig og lagres på NAS via SFTP.</p>
                </div>
            </div>
        </div>
    </x-card>

    {{-- Backup List --}}
    <x-card>
        <x-slot name="header">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-medium text-foreground">Eksisterende Backups</h2>
                    <p class="mt-1 text-sm text-muted">{{ count($backups) }} backup(s) funnet</p>
                </div>
                <button type="button" wire:click="loadBackups"
                    class="p-2 text-muted-foreground hover:text-foreground rounded-lg hover:bg-input transition-colors cursor-pointer"
                    title="Oppdater liste">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
        </x-slot>

        @if($isLoadingBackups)
            <div class="text-center py-12">
                <svg class="w-10 h-10 mx-auto text-accent animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <p class="mt-4 text-sm text-muted">Henter backups fra NAS...</p>
            </div>
        @elseif(count($backups) > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Filnavn
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Dato
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                                Størrelse</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted uppercase tracking-wider">
                                Handlinger</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($backups as $backup)
                            <tr wire:key="backup-{{ $backup['path'] }}" class="hover:bg-input/30 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-accent shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="text-sm font-mono text-foreground">{{ $backup['name'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted">
                                    {{ \Carbon\Carbon::createFromTimestamp($backup['date'])->format('d.m.Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-muted">
                                    {{ $this->formatBytes($backup['size']) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" wire:click="downloadBackup('{{ $backup['path'] }}')"
                                            class="p-2 text-accent hover:bg-accent/10 rounded-lg transition-colors cursor-pointer"
                                            title="Last ned">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                        </button>
                                        <button type="button" wire:click="deleteBackup('{{ $backup['path'] }}')"
                                            wire:confirm="Er du sikker på at du vil slette denne backupen?"
                                            class="p-2 text-red-400 hover:bg-red-500/10 rounded-lg transition-colors cursor-pointer"
                                            title="Slett">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <svg class="w-16 h-16 mx-auto text-muted/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p class="mt-4 text-sm text-muted">Ingen backups funnet</p>
                <p class="mt-1 text-xs text-muted">Opprett din første backup ved å klikke på knappen over</p>
            </div>
        @endif
    </x-card>
</x-page-container>