{{-- MÅNEDSVISNING --}}
<div class="flex-1 bg-card border border-border rounded-lg overflow-hidden flex flex-col">
    {{-- Ukedager header --}}
    <div class="grid grid-cols-[2rem_repeat(7,1fr)] md:grid-cols-[3rem_repeat(7,1fr)] border-b border-border bg-card">
        {{-- Ukenummer kolonne --}}
        <div class="p-1 md:p-2 text-center text-xs font-medium text-muted-foreground border-r border-border">
            U
        </div>
        {{-- Dager --}}
        @foreach($norwegianDays as $index => $day)
            <div class="p-1 md:p-2 text-center text-xs md:text-sm font-medium text-muted {{ in_array($day, ['Lør', 'Søn']) ? 'text-muted-foreground' : '' }}">
                {{-- Mobil: Kun første bokstav, Desktop: Fullt navn --}}
                <span class="md:hidden">{{ mb_substr($day, 0, 1) }}</span>
                <span class="hidden md:inline">{{ $day }}</span>
            </div>
        @endforeach
    </div>

    {{-- Kalender uker --}}
    <div class="flex-1 grid grid-rows-{{ count($this->weeks) }} divide-y divide-border">
        @foreach($this->weeks as $week)
            <div class="relative grid grid-cols-[2rem_repeat(7,1fr)] md:grid-cols-[3rem_repeat(7,1fr)] divide-x divide-border min-h-12 md:min-h-24">
                {{-- Multi-day events overlay (desktop only) --}}
                @php
                    $multiDayShifts = $this->getMultiDayShiftsForWeek($week);
                @endphp
                @if(count($multiDayShifts) > 0)
                    <div class="hidden md:grid grid-cols-[3rem_repeat(7,1fr)] absolute inset-0 pointer-events-none z-20 content-start">
                        @foreach($multiDayShifts as $multiShift)
                            @php
                                $shift = $multiShift['shift'];
                                $assistantColor = $shift->assistant?->color ?? '#6b7280';
                            @endphp
                            <div
                                class="pointer-events-auto cursor-pointer hover:bg-destructive/30 transition-colors px-1.5 py-0.5 rounded bg-destructive/20 border-l-2 border-destructive mr-1 ml-10 mt-1"
                                style="grid-column: {{ $multiShift['startColumn'] }} / span {{ $multiShift['columnSpan'] }}; grid-row: {{ $multiShift['row'] }};"
                                @click.stop="handleShiftClick({{ $shift->id }})"
                                @contextmenu.stop="showShiftContextMenu($event, {{ $shift->id }}, true)"
                            >
                                <div class="text-xs font-medium text-destructive truncate">{{ $shift->assistant?->name ?? 'Tidligere ansatt' }} - Borte</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Ukenummer --}}
                <div class="p-0.5 md:p-1 text-center text-[10px] md:text-xs text-muted-foreground bg-card flex items-start justify-center pt-1 md:pt-2">
                    {{ $week[0]['weekNumber'] }}
                </div>

                {{-- Dager i uken --}}
                @foreach($week as $day)
                    <div
                        wire:key="day-{{ $day['date'] }}"
                        wire:click="goToDay('{{ $day['date'] }}')"
                        @mousedown="startSelectDays($event, '{{ $day['date'] }}')"
                        @mouseenter="updateSelectDays('{{ $day['date'] }}')"
                        @contextmenu="showSlotContextMenu($event, '{{ $day['date'] }}', null)"
                        @dragover="allowDrop($event, null, '{{ $day['date'] }}')"
                        @dragleave="leaveDrop($event)"
                        @drop.stop="handleDrop($event, '{{ $day['date'] }}')"
                        class="p-0.5 md:p-1 relative group transition-colors cursor-pointer overflow-hidden select-none
                            {{ $day['isCurrentMonth'] ? 'bg-card hover:bg-card-hover' : 'bg-surface hover:bg-card' }}
                            {{ $day['isToday'] ? 'ring-2 ring-inset ring-accent' : '' }}"
                        :class="{
                            'ring-2 ring-inset ring-accent !bg-accent/20': dragOverDate === '{{ $day['date'] }}',
                            'ring-2 ring-inset ring-destructive !bg-destructive/20': isDateSelected('{{ $day['date'] }}')
                        }"
                    >
                        {{-- Dato --}}
                        <div class="flex items-center justify-between relative z-30">
                            @if($day['isToday'])
                                <span class="text-xs md:text-sm font-bold bg-accent text-black rounded-full w-5 h-5 md:w-7 md:h-7 flex items-center justify-center">
                                    {{ $day['day'] }}
                                </span>
                            @else
                                <span class="text-xs md:text-sm font-medium {{ $day['isCurrentMonth'] ? ($day['isWeekend'] ? 'text-muted' : 'text-foreground') : 'text-muted-foreground' }}">
                                    {{ $day['day'] }}
                                </span>
                            @endif
                        </div>

                        {{-- Events: Dots på mobil, full info på desktop --}}
                        @php
                            $dayShifts = $this->getShiftsForDate($day['date']);
                            $dayExternalEvents = $this->getExternalEventsForDate($day['date']);
                            $multiDayRowCount = $this->getMultiDayRowCountForDay($multiDayShifts, $loop->index);
                        @endphp
                        @if(count($dayShifts) > 0 || count($dayExternalEvents) > 0)
                            <div class="mt-0.5 md:mt-1">
                                {{-- MOBIL: Fargede prikker --}}
                                <div class="flex flex-wrap gap-0.5 md:hidden">
                                    @foreach($dayShifts as $shift)
                                        @if($shift->is_unavailable)
                                            <span
                                                @click.stop="handleShiftClick({{ $shift->id }})"
                                                @contextmenu.stop="showShiftContextMenu($event, {{ $shift->id }}, true)"
                                                class="w-2 h-2 rounded-full bg-destructive cursor-pointer"
                                                title="{{ $shift->assistant?->initials ?? '?' }} - Borte"
                                            ></span>
                                        @else
                                            <span
                                                @click.stop="handleShiftClick({{ $shift->id }})"
                                                @contextmenu.stop="showShiftContextMenu($event, {{ $shift->id }}, false)"
                                                class="w-2 h-2 rounded-full cursor-pointer"
                                                style="background-color: {{ $shift->assistant?->color ?? '#6b7280' }}"
                                                title="{{ $shift->assistant?->initials ?? '?' }} {{ $shift->time_range }}"
                                            ></span>
                                        @endif
                                    @endforeach
                                    {{-- Eksterne events (mobil) --}}
                                    @foreach($dayExternalEvents as $event)
                                        <span
                                            class="w-2 h-2 rounded-full opacity-50"
                                            style="background-color: {{ $event->color }}"
                                            title="{{ $event->title }}"
                                        ></span>
                                    @endforeach
                                </div>

                                {{-- DESKTOP: Full event-info --}}
                                <div class="hidden md:block space-y-0.5 min-w-0" @if($multiDayRowCount > 0) style="padding-top: {{ $multiDayRowCount * 22 }}px" @endif>
                                    {{-- Eksterne kalender-events (desktop) - øverst for bedre tooltip-visning --}}
                                    @foreach($dayExternalEvents as $externalEvent)
                                        <div
                                            x-data="{ showTooltip: false }"
                                            @mouseenter="showTooltip = true"
                                            @mouseleave="showTooltip = false"
                                            class="px-1.5 py-0.5 rounded border-l-2 group/ext cursor-default relative"
                                            style="background-color: {{ $externalEvent->color }}15; border-color: {{ $externalEvent->color }}"
                                        >
                                            <div class="text-xs font-medium text-foreground truncate opacity-70 group-hover/ext:opacity-100 transition-opacity">
                                                @if($externalEvent->isManUtd())<span class="mr-0.5">⚽</span>@endif{{ $externalEvent->title }}
                                            </div>
                                            @unless($externalEvent->is_all_day)
                                                <div class="text-[9px] text-foreground truncate opacity-60 group-hover/ext:opacity-100 transition-opacity">{{ $externalEvent->getTimeRange() }}</div>
                                            @endunless

                                            {{-- Tooltip --}}
                                            <div
                                                x-show="showTooltip"
                                                x-cloak
                                                x-transition:enter="transition ease-out duration-150"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-100"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                class="absolute z-50 top-full left-0 mt-1 w-48 p-2 bg-card border border-border rounded-lg shadow-lg"
                                            >
                                                <div class="text-xs font-semibold text-foreground mb-1">{{ $externalEvent->title }}</div>
                                                <div class="text-[10px] text-muted-foreground">
                                                    <span class="inline-block px-1 py-0.5 rounded text-[9px]" style="background-color: {{ $externalEvent->color }}30; color: {{ $externalEvent->color }}">
                                                        {{ $externalEvent->calendar_label }}
                                                    </span>
                                                </div>
                                                @if($externalEvent->is_all_day)
                                                    <div class="text-[10px] text-muted mt-1">Hele dagen</div>
                                                @else
                                                    <div class="text-[10px] text-muted mt-1">{{ $externalEvent->getTimeRange() }}</div>
                                                @endif
                                                @if($externalEvent->location)
                                                    <div class="text-[10px] text-muted mt-0.5">📍 {{ $externalEvent->location }}</div>
                                                @endif
                                                @if($externalEvent->description)
                                                    <div class="text-[10px] text-muted mt-1 line-clamp-2">{{ $externalEvent->description }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach

                                    {{-- Assistentvakter --}}
                                    @foreach($dayShifts as $shift)
                                        @php
                                            $assistantColor = $shift->assistant?->color ?? '#6b7280';
                                            $columnSpan = $this->getShiftColumnSpan($shift, $day['date']);
                                            $isMultiDay = $columnSpan > 1;
                                            // Skip multi-day absences on desktop (they're shown in overlay)
                                            $skipOnDesktop = $isMultiDay && $shift->is_unavailable && $shift->is_all_day;
                                        @endphp

                                        @if($this->shouldDisplayShift($shift, $day['date']) && !$skipOnDesktop)
                                            @if($shift->is_unavailable)
                                                <div
                                                    @mousedown.stop
                                                    @click.stop="handleShiftClick({{ $shift->id }})"
                                                    @contextmenu.stop="showShiftContextMenu($event, {{ $shift->id }}, true)"
                                                    draggable="true"
                                                    @dragstart="startDragShift($event, {{ $shift->id }}, '{{ $shift->starts_at->format('H:i') }}', {{ $shift->duration_minutes }})"
                                                    @dragend="endDrag($event)"
                                                    class="px-1.5 py-0.5 rounded bg-destructive/20 border-l-2 border-destructive cursor-pointer hover:bg-destructive/30 transition-colors"
                                                    :class="draggedShift === {{ $shift->id }} && 'opacity-50'"
                                                >
                                                    <div class="text-xs font-medium text-destructive truncate">{{ $shift->assistant?->name ?? 'Tidligere ansatt' }} - Borte</div>
                                                    @unless($shift->is_all_day)
                                                        <div class="text-[9px] text-muted truncate">{{ $shift->time_range }}</div>
                                                    @endunless
                                                </div>
                                            @else
                                                <div
                                                    @mousedown.stop
                                                    @click.stop="handleShiftClick({{ $shift->id }})"
                                                    @contextmenu.stop="showShiftContextMenu($event, {{ $shift->id }}, false)"
                                                    draggable="true"
                                                    @dragstart="startDragShift($event, {{ $shift->id }}, '{{ $shift->starts_at->format('H:i') }}', {{ $shift->duration_minutes }})"
                                                    @dragend="endDrag($event)"
                                                    class="px-1.5 py-0.5 rounded border-l-2 cursor-pointer hover:opacity-80 transition-opacity"
                                                    :class="draggedShift === {{ $shift->id }} && 'opacity-50'"
                                                    style="background-color: {{ $assistantColor }}20; border-color: {{ $assistantColor }};"
                                                >
                                                    <div class="text-xs font-medium truncate" style="color: {{ $assistantColor }}">{{ $shift->assistant?->name ?? 'Tidligere ansatt' }}</div>
                                                    <div class="text-[9px] text-muted truncate">{{ $shift->time_range }}</div>
                                                </div>
                                            @endif
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Hover: Legg til knapp (kun desktop) --}}
                        <button
                            wire:click.stop="openModal('{{ $day['date'] }}')"
                            class="absolute bottom-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded bg-accent text-black hover:bg-accent-hover cursor-pointer hidden md:block"
                            title="Legg til vakt"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
