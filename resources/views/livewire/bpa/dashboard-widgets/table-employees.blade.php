<div class="bg-card border border-border rounded-lg overflow-hidden h-full flex flex-col">
    <div class="px-5 py-4 border-b border-border">
        <h3 class="text-sm font-medium text-foreground">Ansatte</h3>
    </div>
    <div class="overflow-x-auto flex-1">
        <table class="w-full">
            <thead>
                <tr class="border-b border-border">
                    <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Navn</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Stilling</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">E-post</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">Telefon</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-muted-foreground">
                        <button
                            wire:click="sortEmployeesByHours"
                            class="flex items-center gap-1 hover:text-foreground transition-colors cursor-pointer"
                        >
                            Jobbet i år
                            @if($employeesSortDirection === 'desc')
                                <svg class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            @else
                                <svg class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                </svg>
                            @endif
                        </button>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @foreach($this->employees as $employee)
                    <tr class="hover:bg-card-hover transition-colors">
                        <td class="px-5 py-3 text-sm text-foreground whitespace-nowrap">
                                <span class="sm:hidden">{{ Str::before($employee['name'], ' ') }}</span>
                                <span class="hidden sm:inline">{{ $employee['name'] }}</span>
                            </td>
                        <td class="px-5 py-3 whitespace-nowrap">
                            @if($employee['positionColor'] === 'accent')
                                <span class="px-2 py-1 text-xs font-medium bg-accent/20 text-accent rounded whitespace-nowrap">
                                    {{ $employee['position'] }}
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-blue-500/20 text-blue-400 rounded whitespace-nowrap">
                                    {{ $employee['position'] }}
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-sm text-muted-foreground whitespace-nowrap">{{ $employee['email'] }}</td>
                        <td class="px-5 py-3 text-sm text-foreground whitespace-nowrap">{{ $employee['phone'] }}</td>
                        <td class="px-5 py-3 text-sm text-foreground whitespace-nowrap">{{ $employee['hoursThisYear'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-border flex items-center justify-between mt-auto">
        <div class="flex items-center gap-2">
            <span class="text-xs text-muted-foreground">Pr. side</span>
            <select wire:model.live="employeesPerPage" class="bg-input border border-border rounded px-2 py-1 text-xs text-foreground cursor-pointer">
                <option value="3">3</option>
                <option value="5">5</option>
                <option value="10">10</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            @if($employeesPage > 1)
                <button wire:click="prevPage('employees')" class="px-3 py-1.5 text-xs font-medium text-foreground bg-card-hover border border-border rounded hover:bg-input transition-colors cursor-pointer">
                    Forrige
                </button>
            @endif
            <span class="text-xs text-muted-foreground">{{ $employeesPage }} / {{ $this->employeesTotalPages }}</span>
            @if($employeesPage < $this->employeesTotalPages)
                <button wire:click="nextPage('employees')" class="px-3 py-1.5 text-xs font-medium text-foreground bg-card-hover border border-border rounded hover:bg-input transition-colors cursor-pointer">
                    Neste
                </button>
            @endif
        </div>
    </div>
</div>
