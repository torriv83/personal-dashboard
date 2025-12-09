{{-- UKEVISNING med assistent-sidebar --}}
<div class="flex-1 flex overflow-hidden">
    {{-- Assistent-sidebar (kun desktop) --}}
    <div class="hidden md:flex shrink-0">
        {{-- Kollapset: Kun ikon --}}
        <button
            x-show="!showAssistants"
            @click="showAssistants = true"
            class="w-10 bg-card border border-border rounded-l-lg flex flex-col items-center justify-center hover:bg-card-hover transition-colors cursor-pointer"
            title="Vis assistenter"
        >
            <svg class="w-5 h-5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
        </button>

        {{-- Utvidet: Full sidebar --}}
        <div
            x-show="showAssistants"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 w-0"
            x-transition:enter-end="opacity-100 w-48"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 w-48"
            x-transition:leave-end="opacity-0 w-0"
            x-cloak
            class="w-48 bg-card border border-border rounded-l-lg flex flex-col overflow-hidden"
        >
            <div class="p-3 border-b border-border flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-foreground">Assistenter</h3>
                    <p class="text-xs text-muted mt-0.5">Dra til kalenderen</p>
                </div>
                <button
                    @click="showAssistants = false"
                    class="p-1 rounded text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                    title="Skjul"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            </div>
        <div class="flex-1 overflow-y-auto p-2 space-y-2">
            @foreach($this->assistants as $assistant)
                <div
                    class="p-2 rounded cursor-grab hover:opacity-80 transition-opacity active:cursor-grabbing"
                    style="background-color: {{ $assistant->color ?? '#3b82f6' }}20; border: 1px solid {{ $assistant->color ?? '#3b82f6' }}50"
                    draggable="true"
                    @dragstart="startDragAssistant($event, {{ $assistant->id }})"
                    @dragend="endDrag($event)"
                >
                    <div class="text-sm font-medium text-foreground">{{ $assistant->name }}</div>
                    <div class="text-xs text-muted">{{ $assistant->type_label }}</div>
                </div>
            @endforeach
        </div>
        </div>
    </div>

    {{-- Kalender (horisontal scroll på mobil) --}}
    <div class="flex-1 bg-card border border-border rounded-lg md:rounded-l-none md:rounded-r-lg overflow-hidden flex flex-col">
        {{-- Ukedager header --}}
        <div class="grid grid-cols-[2rem_repeat(7,minmax(2.5rem,1fr))] md:grid-cols-[3rem_repeat(7,1fr)] border-b border-border bg-card overflow-x-auto">
            {{-- Ukenummer i tid-kolonnen --}}
            <div class="p-1 md:p-2 text-center text-xs font-medium text-muted-foreground border-r border-border flex items-center justify-center">
                <span class="md:hidden">U{{ $this->currentWeekNumber }}</span>
                <span class="hidden md:inline">Uke {{ $this->currentWeekNumber }}</span>
            </div>
            {{-- Dager --}}
            @foreach($this->currentWeekDays as $weekDay)
                <div
                    wire:click="goToDay('{{ $weekDay['date'] }}')"
                    class="p-1 md:p-2 text-center border-r border-border last:border-r-0 cursor-pointer hover:bg-card-hover transition-colors
                        {{ $weekDay['isToday'] ? 'bg-accent/10' : '' }}"
                >
                    <div class="text-[10px] md:text-xs text-muted-foreground {{ $weekDay['isWeekend'] ? 'text-muted-foreground/60' : '' }}">
                        <span class="md:hidden">{{ mb_substr($weekDay['dayName'], 0, 1) }}</span>
                        <span class="hidden md:inline">{{ $weekDay['dayName'] }}</span>
                    </div>
                    <div class="text-sm md:text-lg font-medium {{ $weekDay['isToday'] ? 'text-accent' : ($weekDay['isWeekend'] ? 'text-muted' : 'text-foreground') }}">
                        {{ $weekDay['day'] }}
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Hele-dagen events --}}
        <div class="grid grid-cols-[2rem_repeat(7,minmax(2.5rem,1fr))] md:grid-cols-[3rem_repeat(7,1fr)] border-b border-border bg-surface/50 overflow-x-auto">
            <div class="p-0.5 md:p-1 text-right text-[9px] md:text-[10px] text-muted-foreground border-r border-border flex items-center justify-end pr-1 md:pr-2">
                <span class="md:hidden">HD</span>
                <span class="hidden md:inline">Hel dag</span>
            </div>
            @foreach($this->currentWeekDays as $dayIndex => $weekDay)
                @php
                    $allDayShifts = collect($this->getShiftsForDate($weekDay['date']))->where('is_all_day', true);
                    $allDayExternalEvents = collect($this->getExternalEventsForDate($weekDay['date']))->where('is_all_day', true);
                @endphp
                <div class="p-0.5 md:p-1 border-r border-border last:border-r-0 min-h-6 md:min-h-8 {{ $weekDay['isToday'] ? 'bg-accent/5' : '' }} space-y-0.5">
                    @foreach($allDayShifts as $shift)
                        @php $assistantColor = $shift->assistant?->color ?? '#6b7280'; @endphp
                        @if($shift->is_unavailable)
                            {{-- Mobil: Farget prikk --}}
                            <div class="md:hidden flex justify-center">
                                <span class="w-2 h-2 rounded-full bg-destructive" title="{{ $shift->assistant?->name ?? 'Tidligere ansatt' }} - Borte"></span>
                            </div>
                            {{-- Desktop: Full info --}}
                            <div class="hidden md:block bg-destructive/20 border border-destructive/50 rounded px-1.5 py-0.5 cursor-pointer hover:bg-destructive/30 transition-colors">
                                <div class="text-[10px] font-medium text-destructive truncate">{{ $shift->assistant?->name ?? 'Tidligere ansatt' }} - Borte</div>
                            </div>
                        @else
                            {{-- Mobil: Farget prikk --}}
                            <div class="md:hidden flex justify-center">
                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $assistantColor }}" title="{{ $shift->assistant?->name ?? 'Tidligere ansatt' }}"></span>
                            </div>
                            {{-- Desktop: Full info --}}
                            <div class="hidden md:block rounded px-1.5 py-0.5 cursor-pointer hover:opacity-80 transition-opacity" style="background-color: {{ $assistantColor }}20; border: 1px solid {{ $assistantColor }}50">
                                <div class="text-[10px] font-medium truncate" style="color: {{ $assistantColor }}">{{ $shift->assistant?->name ?? 'Tidligere ansatt' }}</div>
                            </div>
                        @endif
                    @endforeach
                    {{-- Eksterne hele-dagen events --}}
                    @foreach($allDayExternalEvents as $externalEvent)
                        {{-- Mobil: Farget prikk --}}
                        <div class="md:hidden flex justify-center">
                            <span class="w-2 h-2 rounded-full opacity-50" style="background-color: {{ $externalEvent->color }}" title="{{ $externalEvent->title }}"></span>
                        </div>
                        {{-- Desktop: Full info --}}
                        <div
                            x-data="{ showTooltip: false }"
                            @mouseenter="showTooltip = true"
                            @mouseleave="showTooltip = false"
                            class="hidden md:block rounded px-1.5 py-0.5 opacity-50 hover:opacity-70 transition-opacity relative"
                            style="background-color: {{ $externalEvent->color }}15; border: 1px solid {{ $externalEvent->color }}30"
                        >
                            <div class="text-[10px] font-medium truncate text-muted-foreground">
                                @if($externalEvent->isManUtd())⚽@endif
                                {{ $externalEvent->title }}
                            </div>
                            {{-- Tooltip --}}
                            <div
                                x-show="showTooltip"
                                x-cloak
                                class="absolute z-50 top-full left-0 mt-1 w-40 p-2 bg-card border border-border rounded-lg shadow-lg"
                            >
                                <div class="text-xs font-semibold text-foreground">{{ $externalEvent->title }}</div>
                                <div class="text-[9px] mt-1 px-1 py-0.5 rounded inline-block" style="background-color: {{ $externalEvent->color }}30; color: {{ $externalEvent->color }}">{{ $externalEvent->calendar_label }}</div>
                                @if($externalEvent->location)
                                    <div class="text-[10px] text-muted mt-1">📍 {{ $externalEvent->location }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        {{-- Tidslinje med 7 kolonner --}}
        <div class="flex-1 overflow-auto relative flex flex-col">
            {{-- Time-rader --}}
            @foreach($this->timeSlots as $slot)
                <div
                    wire:key="week-slot-{{ $slot['hour'] }}"
                    class="grid grid-cols-[2rem_repeat(7,minmax(2.5rem,1fr))] md:grid-cols-[3rem_repeat(7,1fr)] border-b border-border flex-1 min-h-10 md:min-h-12"
                >
                    {{-- Klokkeslett --}}
                    <div class="text-right text-[10px] md:text-xs text-muted-foreground border-r border-border flex items-start justify-end pr-0.5 md:pr-2 pt-1">
                        {{ $slot['label'] }}
                    </div>

                    {{-- 7 dager --}}
                    @foreach($this->currentWeekDays as $dayIndex => $weekDay)
                        <div
                            class="relative border-r border-border last:border-r-0 {{ $weekDay['isToday'] ? 'bg-accent/5' : '' }} transition-colors"
                            data-slot-height="48"
                            @dragover="allowDrop($event, '{{ $slot['label'] }}', '{{ $weekDay['date'] }}')"
                            @dragleave="leaveDrop($event)"
                            @drop="handleDrop($event, '{{ $weekDay['date'] }}', '{{ $slot['label'] }}')"
                        >
                            {{-- 15-minutters linjer med drag-indikator --}}
                            <div class="absolute inset-0 flex flex-col pointer-events-none">
                                <div class="flex-1 border-b border-border/20 transition-colors"
                                    :class="(draggedShift || draggedAssistant) && dragOverSlot === '{{ $slot['label'] }}' && dragOverDate === '{{ $weekDay['date'] }}' && dragQuarter === 0 && 'bg-accent/40'"></div>
                                <div class="flex-1 border-b border-border/40 transition-colors"
                                    :class="(draggedShift || draggedAssistant) && dragOverSlot === '{{ $slot['label'] }}' && dragOverDate === '{{ $weekDay['date'] }}' && dragQuarter === 1 && 'bg-accent/40'"></div>
                                <div class="flex-1 border-b border-border/20 transition-colors"
                                    :class="(draggedShift || draggedAssistant) && dragOverSlot === '{{ $slot['label'] }}' && dragOverDate === '{{ $weekDay['date'] }}' && dragQuarter === 2 && 'bg-accent/40'"></div>
                                <div class="flex-1 transition-colors"
                                    :class="(draggedShift || draggedAssistant) && dragOverSlot === '{{ $slot['label'] }}' && dragOverDate === '{{ $weekDay['date'] }}' && dragQuarter === 3 && 'bg-accent/40'"></div>
                            </div>

                            {{-- Klikkbart område --}}
                            <div
                                @mousedown="startCreate($event, '{{ $weekDay['date'] }}', '{{ $slot['label'] }}', $el.closest('[data-slot-height]'))"
                                @dblclick.stop="openQuickCreate($event, '{{ $weekDay['date'] }}', '{{ $slot['label'] }}')"
                                @contextmenu="showSlotContextMenu($event, '{{ $weekDay['date'] }}', '{{ $slot['label'] }}')"
                                class="absolute inset-0 hover:bg-card-hover/30 transition-colors cursor-pointer group"
                                title="Dra for å velge tid, dobbeltklikk for 3t"
                            >
                                <button
                                    wire:click="openModal('{{ $weekDay['date'] }}', '{{ $slot['label'] }}')"
                                    class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity p-0.5 rounded bg-accent text-black hover:bg-accent-hover cursor-pointer hidden md:block"
                                    title="Legg til vakt {{ $weekDay['dayName'] }} kl {{ $slot['label'] }}"
                                    @mousedown.stop
                                >
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Drag-to-create preview overlay --}}
                            <div
                                x-show="isCreatingShift && createDate === '{{ $weekDay['date'] }}' && getCreatePreviewStyle('{{ $weekDay['date'] }}', {{ $slot['hour'] }})"
                                x-cloak
                                class="absolute left-0 right-0 bg-accent/30 pointer-events-none z-20 border-x-2 border-accent border-dashed"
                                :class="{
                                    'border-t-2 rounded-t': getCreatePreviewStyle('{{ $weekDay['date'] }}', {{ $slot['hour'] }})?.isFirst,
                                    'border-b-2 rounded-b': getCreatePreviewStyle('{{ $weekDay['date'] }}', {{ $slot['hour'] }})?.isLast
                                }"
                                :style="`top: ${getCreatePreviewStyle('{{ $weekDay['date'] }}', {{ $slot['hour'] }})?.top ?? 0}%; height: ${getCreatePreviewStyle('{{ $weekDay['date'] }}', {{ $slot['hour'] }})?.height ?? 0}%;`"
                            >
                                <div class="px-0.5 text-[9px] font-medium text-accent truncate hidden md:block" x-show="getCreatePreviewStyle('{{ $weekDay['date'] }}', {{ $slot['hour'] }})?.isFirst">
                                    <span x-text="createStartTime + '-' + createEndTime"></span>
                                </div>
                            </div>

                            {{-- Vakter i dette time-slottet --}}
                            @php
                                $dayShifts = collect($this->getShiftsForDate($weekDay['date']))
                                    ->reject(fn($s) => $s->is_all_day)
                                    ->filter(fn($s) => $s->starts_at->hour === $slot['hour']);
                                $timedExternalEvents = collect($this->getExternalEventsForDate($weekDay['date']))
                                    ->reject(fn($e) => $e->is_all_day)
                                    ->filter(fn($e) => $e->starts_at->hour === $slot['hour']);
                            @endphp
                            @foreach($dayShifts as $shift)
                                @php
                                    // Calculate position and height
                                    $startMinute = $shift->starts_at->minute;
                                    $topPercent = ($startMinute / 60) * 100;
                                    $durationHours = $shift->duration_minutes / 60;
                                    $heightPercent = $durationHours * 100;
                                    $assistantColor = $shift->assistant?->color ?? '#6b7280';
                                @endphp
                                @if($shift->is_unavailable)
                                    {{-- Mobil: Farget blokk uten tekst --}}
                                    <div
                                        @click="handleShiftClick({{ $shift->id }})"
                                        @contextmenu="showShiftContextMenu($event, {{ $shift->id }}, true)"
                                        class="md:hidden absolute left-0 right-0 bg-destructive/30 border-l-2 border-destructive pointer-events-auto cursor-pointer z-10"
                                        :class="draggedShift === {{ $shift->id }} && '!pointer-events-none opacity-50'"
                                        style="top: {{ $topPercent }}%; height: {{ $heightPercent }}%;"
                                    ></div>
                                    {{-- Desktop: Full info --}}
                                    <div
                                        @click="handleShiftClick({{ $shift->id }})"
                                        @contextmenu="showShiftContextMenu($event, {{ $shift->id }}, true)"
                                        data-shift="{{ $shift->id }}"
                                        draggable="true"
                                        @dragstart="startDragShift($event, {{ $shift->id }}, '{{ $shift->starts_at->format('H:i') }}', {{ $shift->duration_minutes }})"
                                        @dragend="endDrag($event)"
                                        class="hidden md:block absolute left-0.5 right-0.5 bg-destructive/20 border-l-2 border-destructive rounded px-1 py-0.5 pointer-events-auto cursor-pointer hover:bg-destructive/30 transition-colors z-10 group/shift"
                                        :class="draggedShift === {{ $shift->id }} && '!pointer-events-none opacity-50'"
                                        style="top: {{ $topPercent }}%; height: {{ $heightPercent }}%;"
                                    >
                                        <div class="text-xs font-medium text-destructive truncate">{{ $shift->assistant?->name ?? 'Tidligere ansatt' }}</div>
                                        <div class="text-[10px] truncate" :class="(resizingShift === {{ $shift->id }} || draggedShift === {{ $shift->id }}) ? 'font-bold text-accent' : 'text-muted'">
                                            <span x-show="resizingShift !== {{ $shift->id }} && draggedShift !== {{ $shift->id }}">Borte {{ $shift->time_range }}</span>
                                            <span x-show="resizingShift === {{ $shift->id }}" x-text="resizePreviewEndTime"></span>
                                            <span x-show="draggedShift === {{ $shift->id }} && dragPreviewTime" x-text="'Borte ' + dragPreviewTime"></span>
                                        </div>
                                        {{-- Resize handle --}}
                                        <div
                                            @mousedown="startResize($event, {{ $shift->id }}, {{ $shift->duration_minutes }}, '{{ $shift->starts_at->format('H:i') }}')"
                                            class="absolute bottom-0 left-0 right-0 h-2 cursor-ns-resize opacity-0 group-hover/shift:opacity-100 bg-destructive/50 rounded-b transition-opacity"
                                            @click.stop
                                        ></div>
                                    </div>
                                @else
                                    {{-- Mobil: Farget blokk uten tekst --}}
                                    <div
                                        @click="handleShiftClick({{ $shift->id }})"
                                        @contextmenu="showShiftContextMenu($event, {{ $shift->id }}, false)"
                                        class="md:hidden absolute left-0 right-0 border-l-2 pointer-events-auto cursor-pointer z-10"
                                        :class="draggedShift === {{ $shift->id }} && '!pointer-events-none opacity-50'"
                                        style="top: {{ $topPercent }}%; height: {{ $heightPercent }}%; background-color: {{ $assistantColor }}30; border-color: {{ $assistantColor }}"
                                    ></div>
                                    {{-- Desktop: Full info --}}
                                    <div
                                        @click="handleShiftClick({{ $shift->id }})"
                                        @contextmenu="showShiftContextMenu($event, {{ $shift->id }}, false)"
                                        data-shift="{{ $shift->id }}"
                                        draggable="true"
                                        @dragstart="startDragShift($event, {{ $shift->id }}, '{{ $shift->starts_at->format('H:i') }}', {{ $shift->duration_minutes }})"
                                        @dragend="endDrag($event)"
                                        class="hidden md:block absolute left-0.5 right-0.5 rounded px-1 py-0.5 pointer-events-auto cursor-pointer hover:opacity-80 transition-opacity z-10 border-l-2 group/shift"
                                        :class="draggedShift === {{ $shift->id }} && '!pointer-events-none opacity-50'"
                                        style="top: {{ $topPercent }}%; height: {{ $heightPercent }}%; background-color: {{ $assistantColor }}20; border-color: {{ $assistantColor }}"
                                    >
                                        <div class="text-xs font-medium truncate" style="color: {{ $assistantColor }}">{{ $shift->assistant?->name ?? 'Tidligere ansatt' }}</div>
                                        <div class="text-[10px] truncate" :class="(resizingShift === {{ $shift->id }} || draggedShift === {{ $shift->id }}) ? 'font-bold text-accent' : 'text-muted'">
                                            <span x-show="resizingShift !== {{ $shift->id }} && draggedShift !== {{ $shift->id }}">{{ $shift->time_range }}</span>
                                            <span x-show="resizingShift === {{ $shift->id }}" x-text="resizePreviewEndTime"></span>
                                            <span x-show="draggedShift === {{ $shift->id }} && dragPreviewTime" x-text="dragPreviewTime"></span>
                                        </div>
                                        {{-- Resize handle --}}
                                        <div
                                            @mousedown="startResize($event, {{ $shift->id }}, {{ $shift->duration_minutes }}, '{{ $shift->starts_at->format('H:i') }}')"
                                            class="absolute bottom-0 left-0 right-0 h-2 cursor-ns-resize opacity-0 group-hover/shift:opacity-100 rounded-b transition-opacity"
                                            style="background-color: {{ $assistantColor }}50"
                                            @click.stop
                                        ></div>
                                    </div>
                                @endif
                            @endforeach

                            {{-- Eksterne kalender-events i dette time-slottet --}}
                            @foreach($timedExternalEvents as $externalEvent)
                                @php
                                    $startMinute = $externalEvent->starts_at->minute;
                                    $topPercent = ($startMinute / 60) * 100;
                                    $durationMinutes = $externalEvent->getDurationMinutes();
                                    $durationHours = $durationMinutes / 60;
                                    $heightPercent = $durationHours * 100;
                                @endphp
                                {{-- Mobil: Farget blokk uten tekst --}}
                                <div
                                    class="md:hidden absolute left-0 right-0 border-l-2 opacity-40 z-5"
                                    style="top: {{ $topPercent }}%; height: {{ $heightPercent }}%; background-color: {{ $externalEvent->color }}20; border-color: {{ $externalEvent->color }}"
                                ></div>
                                {{-- Desktop: Full info med tooltip --}}
                                <div
                                    x-data="{ showTooltip: false }"
                                    @mouseenter="showTooltip = true"
                                    @mouseleave="showTooltip = false"
                                    class="hidden md:block absolute left-0.5 right-0.5 rounded px-1 py-0.5 z-5 border-l-2 opacity-50 hover:opacity-70 transition-opacity"
                                    style="top: {{ $topPercent }}%; height: {{ $heightPercent }}%; background-color: {{ $externalEvent->color }}15; border-color: {{ $externalEvent->color }}"
                                >
                                    <div class="text-xs font-medium truncate text-muted-foreground">
                                        @if($externalEvent->isManUtd())⚽@endif
                                        {{ $externalEvent->title }}
                                    </div>
                                    <div class="text-[10px] text-muted truncate">{{ $externalEvent->getTimeRange() }}</div>

                                    {{-- Tooltip --}}
                                    <div
                                        x-show="showTooltip"
                                        x-cloak
                                        class="absolute z-50 top-full left-0 mt-1 w-44 p-2 bg-card border border-border rounded-lg shadow-lg"
                                    >
                                        <div class="text-xs font-semibold text-foreground">{{ $externalEvent->title }}</div>
                                        <div class="text-[9px] mt-1 px-1 py-0.5 rounded inline-block" style="background-color: {{ $externalEvent->color }}30; color: {{ $externalEvent->color }}">{{ $externalEvent->calendar_label }}</div>
                                        <div class="text-[10px] text-muted mt-1">{{ $externalEvent->getTimeRange() }}</div>
                                        @if($externalEvent->location)
                                            <div class="text-[10px] text-muted mt-0.5">📍 {{ $externalEvent->location }}</div>
                                        @endif
                                        @if($externalEvent->description)
                                            <div class="text-[10px] text-muted mt-1 line-clamp-2">{{ $externalEvent->description }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>
