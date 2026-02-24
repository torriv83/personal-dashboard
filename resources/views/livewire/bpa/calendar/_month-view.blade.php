{{-- MANEDSVISNING --}}
<div class="flex-1 bg-card border border-border rounded-lg overflow-hidden flex flex-col">
    {{-- Ukedager header --}}
    <div class="grid grid-cols-[2rem_repeat(7,1fr)] md:grid-cols-[3rem_repeat(7,1fr)] border-b border-border bg-card">
        {{-- Ukenummer kolonne --}}
        <div class="p-1 md:p-2 text-center text-xs font-medium text-muted-foreground border-r border-border">
            U
        </div>
        {{-- Dager --}}
        <template x-for="(dayName, idx) in dayNames" :key="idx">
            <div class="p-1 md:p-2 text-center text-xs md:text-sm font-medium text-muted"
                :class="idx >= 5 ? 'text-muted-foreground' : ''">
                <span class="md:hidden" x-text="dayName.charAt(0)"></span>
                <span class="hidden md:inline" x-text="dayName"></span>
            </div>
        </template>
    </div>

    {{-- Kalender uker --}}
    <div class="flex-1 grid divide-y divide-border" :style="'grid-template-rows: repeat(' + (calendarDays.weeks?.length || 5) + ', 1fr)'">
        <template x-for="week in calendarDays.weeks" :key="week.weekNumber">
            <div x-data="{
                get multiDayShifts() { return $data.getMultiDayShiftsForWeek(week.days); }
            }" class="relative grid grid-cols-[2rem_repeat(7,1fr)] md:grid-cols-[3rem_repeat(7,1fr)] divide-x divide-border min-h-12 md:min-h-24">
                {{-- Multi-day events overlay (desktop only) --}}
                <template x-if="multiDayShifts.length > 0">
                    <div class="hidden md:grid grid-cols-[3rem_repeat(7,1fr)] absolute inset-0 pointer-events-none z-20 content-start">
                        <template x-for="ms in multiDayShifts" :key="ms.shift.id + '-' + ms.startDate">
                            <div
                                class="pointer-events-auto cursor-pointer hover:bg-destructive/30 transition-colors px-1.5 py-0.5 rounded bg-destructive/20 border-l-2 border-destructive mr-1 ml-10 mt-1"
                                :style="'grid-column: ' + ms.startColumn + ' / span ' + ms.columnSpan + '; grid-row: ' + ms.row + ';'"
                                @click.stop="handleShiftClick(ms.shift.id)"
                                @contextmenu.stop="showShiftContextMenu($event, ms.shift.id, true)"
                            >
                                <div class="text-xs font-medium text-destructive truncate"
                                    x-text="(ms.shift.assistant_name || 'Tidligere ansatt') + ' - Borte'"></div>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Ukenummer --}}
                <div class="p-0.5 md:p-1 text-center text-[10px] md:text-xs text-muted-foreground bg-card flex items-start justify-center pt-1 md:pt-2"
                    x-text="week.weekNumber"></div>

                {{-- Dager i uken --}}
                <template x-for="(day, dayIdx) in week.days" :key="day.date">
                    <div
                        @click="goToDay(day.date)"
                        @mousedown="startSelectDays($event, day.date)"
                        @mouseenter="updateSelectDays(day.date)"
                        @contextmenu="showSlotContextMenu($event, day.date, null)"
                        @dragover="allowDrop($event, null, day.date)"
                        @dragleave="leaveDrop($event)"
                        @drop.stop="handleDrop($event, day.date)"
                        class="p-0.5 md:p-1 relative group transition-colors cursor-pointer overflow-hidden select-none"
                        :class="{
                            'bg-card hover:bg-card-hover': day.isCurrentMonth,
                            'bg-surface hover:bg-card': !day.isCurrentMonth,
                            'ring-2 ring-inset ring-accent': day.isToday,
                            'ring-2 ring-inset ring-accent !bg-accent/20': dragOverDate === day.date,
                            'ring-2 ring-inset ring-destructive !bg-destructive/20': isDateSelected(day.date)
                        }"
                    >
                        {{-- Dato --}}
                        <div class="flex items-center justify-between relative z-30">
                            <template x-if="day.isToday">
                                <span class="text-xs md:text-sm font-bold bg-accent text-black rounded-full w-5 h-5 md:w-7 md:h-7 flex items-center justify-center"
                                    x-text="day.dayOfMonth"></span>
                            </template>
                            <template x-if="!day.isToday">
                                <span class="text-xs md:text-sm font-medium"
                                    :class="{
                                        'text-foreground': day.isCurrentMonth && !day.isWeekend,
                                        'text-muted': day.isCurrentMonth && day.isWeekend,
                                        'text-muted-foreground': !day.isCurrentMonth
                                    }"
                                    x-text="day.dayOfMonth"></span>
                            </template>
                        </div>

                        {{-- Events: Dots pa mobil, full info pa desktop --}}
                        <template x-if="getShiftsForDate(day.date).length > 0 || getExternalEventsForDate(day.date).length > 0">
                            <div class="mt-0.5 md:mt-1">
                                {{-- MOBIL: Fargede prikker --}}
                                <div class="flex flex-wrap gap-0.5 md:hidden">
                                    <template x-for="shift in getShiftsForDate(day.date)" :key="shift.id">
                                        <span
                                            @click.stop="handleShiftClick(shift.id)"
                                            @contextmenu.stop="showShiftContextMenu($event, shift.id, shift.is_unavailable)"
                                            class="w-2 h-2 rounded-full cursor-pointer"
                                            :class="shift.is_unavailable ? 'bg-destructive' : ''"
                                            :style="!shift.is_unavailable ? 'background-color: ' + (shift.assistant_color || '#6b7280') : ''"
                                            :title="shift.is_unavailable ? (shift.assistant_initials || '?') + ' - Borte' : (shift.assistant_initials || '?') + ' ' + shift.time_range"
                                        ></span>
                                    </template>
                                    {{-- Eksterne events (mobil) --}}
                                    <template x-for="event in getExternalEventsForDate(day.date)" :key="event.id">
                                        <span
                                            class="w-2 h-2 rounded-full opacity-50"
                                            :style="'background-color: ' + event.color"
                                            :title="event.title"
                                        ></span>
                                    </template>
                                </div>

                                {{-- DESKTOP: Full event-info --}}
                                <div class="hidden md:block space-y-0.5 min-w-0"
                                    :style="getMultiDayRowCountForDay(multiDayShifts, dayIdx) > 0 ? 'padding-top: ' + (getMultiDayRowCountForDay(multiDayShifts, dayIdx) * 22) + 'px' : ''">
                                    {{-- Eksterne kalender-events (desktop) --}}
                                    <template x-for="externalEvent in getExternalEventsForDate(day.date)" :key="externalEvent.id">
                                        <div
                                            x-data="{ showTooltip: false }"
                                            @mouseenter="showTooltip = true"
                                            @mouseleave="showTooltip = false"
                                            class="px-1.5 py-0.5 rounded border-l-2 group/ext cursor-default relative"
                                            :style="'background-color: ' + externalEvent.color + '15; border-color: ' + externalEvent.color"
                                        >
                                            <div class="text-xs font-medium text-foreground truncate opacity-70 group-hover/ext:opacity-100 transition-opacity"
                                                x-text="externalEvent.title"></div>
                                            <template x-if="!externalEvent.is_all_day">
                                                <div class="text-[9px] text-foreground truncate opacity-60 group-hover/ext:opacity-100 transition-opacity"
                                                    x-text="externalEvent.start_time + ' - ' + externalEvent.end_time"></div>
                                            </template>

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
                                                <div class="text-xs font-semibold text-foreground mb-1" x-text="externalEvent.title"></div>
                                                <div class="text-[10px] text-muted-foreground">
                                                    <span class="inline-block px-1 py-0.5 rounded text-[9px]"
                                                        :style="'background-color: ' + externalEvent.color + '30; color: ' + externalEvent.color"
                                                        x-text="externalEvent.calendar_label"></span>
                                                </div>
                                                <template x-if="externalEvent.is_all_day">
                                                    <div class="text-[10px] text-muted mt-1">Hele dagen</div>
                                                </template>
                                                <template x-if="!externalEvent.is_all_day">
                                                    <div class="text-[10px] text-muted mt-1" x-text="externalEvent.start_time + ' - ' + externalEvent.end_time"></div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Assistentvakter --}}
                                    <template x-for="shift in getShiftsForDate(day.date)" :key="shift.id">
                                        <template x-if="shouldDisplayShift(shift, day.date) && !(getShiftColumnSpan(shift, day.date) > 1 && shift.is_unavailable && shift.is_all_day)">
                                            <div>
                                                <template x-if="shift.is_unavailable">
                                                    <div
                                                        @mousedown.stop
                                                        @click.stop="handleShiftClick(shift.id)"
                                                        @contextmenu.stop="showShiftContextMenu($event, shift.id, true)"
                                                        draggable="true"
                                                        @dragstart="startDragShift($event, shift.id, shift.start_time, shift.duration_minutes)"
                                                        @dragend="endDrag($event)"
                                                        class="px-1.5 py-0.5 rounded bg-destructive/20 border-l-2 border-destructive cursor-pointer hover:bg-destructive/30 transition-colors"
                                                        :class="draggedShift === shift.id && 'opacity-50'"
                                                    >
                                                        <div class="text-xs font-medium text-destructive truncate"
                                                            x-text="(shift.assistant_name || 'Tidligere ansatt') + ' - Borte'"></div>
                                                        <template x-if="!shift.is_all_day">
                                                            <div class="text-[9px] text-muted truncate" x-text="shift.time_range"></div>
                                                        </template>
                                                    </div>
                                                </template>
                                                <template x-if="!shift.is_unavailable">
                                                    <div
                                                        @mousedown.stop
                                                        @click.stop="handleShiftClick(shift.id)"
                                                        @contextmenu.stop="showShiftContextMenu($event, shift.id, false)"
                                                        draggable="true"
                                                        @dragstart="startDragShift($event, shift.id, shift.start_time, shift.duration_minutes)"
                                                        @dragend="endDrag($event)"
                                                        class="px-1.5 py-0.5 rounded border-l-2 cursor-pointer hover:opacity-80 transition-opacity"
                                                        :class="draggedShift === shift.id && 'opacity-50'"
                                                        :style="'background-color: ' + (shift.assistant_color || '#6b7280') + '20; border-color: ' + (shift.assistant_color || '#6b7280') + ';'"
                                                    >
                                                        <div class="text-xs font-medium truncate"
                                                            :style="'color: ' + (shift.assistant_color || '#6b7280')"
                                                            x-text="shift.assistant_name || 'Tidligere ansatt'"></div>
                                                        <div class="text-[9px] text-muted truncate" x-text="shift.time_range"></div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Hover: Legg til knapp (kun desktop) --}}
                        <button
                            @click.stop="openModal(day.date)"
                            class="absolute bottom-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded bg-accent text-black hover:bg-accent-hover cursor-pointer hidden md:block"
                            title="Legg til vakt"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>
