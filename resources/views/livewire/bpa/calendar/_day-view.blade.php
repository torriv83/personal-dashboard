{{-- DAGSVISNING med assistent-sidebar --}}
<div class="flex-1 flex">
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
                <template x-for="assistant in assistants" :key="assistant.id">
                    <div>
                        <template x-if="dayViewUnavailableAssistantIds.includes(assistant.id)">
                            <div
                                class="p-2 rounded opacity-50 cursor-not-allowed"
                                :style="'background-color: ' + (assistant.color || '#3b82f6') + '10; border: 1px solid ' + (assistant.color || '#3b82f6') + '30'"
                                title="Borte hele dagen"
                            >
                                <div class="text-sm font-medium text-muted" x-text="assistant.name"></div>
                                <div class="text-xs text-muted">Borte</div>
                            </div>
                        </template>
                        <template x-if="!dayViewUnavailableAssistantIds.includes(assistant.id)">
                            <div
                                class="p-2 rounded cursor-grab hover:opacity-80 transition-opacity active:cursor-grabbing"
                                :style="'background-color: ' + (assistant.color || '#3b82f6') + '20; border: 1px solid ' + (assistant.color || '#3b82f6') + '50'"
                                draggable="true"
                                @dragstart="startDragAssistant($event, assistant.id)"
                                @dragend="endDrag($event)"
                            >
                                <div class="text-sm font-medium text-foreground" x-text="assistant.name"></div>
                                <div class="text-xs text-muted" x-text="assistant.type_label"></div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Kalender --}}
    <div class="flex-1 bg-card border border-border rounded-lg md:rounded-l-none md:rounded-r-lg overflow-hidden flex flex-col">
        {{-- Dag-header --}}
        <div class="grid grid-cols-[2.5rem_1fr] md:grid-cols-[3rem_1fr] border-b border-border bg-card">
            <div class="p-1 md:p-2 border-r border-border"></div>
            <div class="p-2 md:p-3 text-center">
                <div class="text-sm md:text-base font-medium text-foreground">
                    <span class="md:hidden" x-text="formattedDateShort + ' ' + currentYear"></span>
                    <span class="hidden md:inline" x-text="formattedDate"></span>
                </div>
            </div>
        </div>

        {{-- Hele-dagen events --}}
        <div class="grid grid-cols-[2.5rem_1fr] md:grid-cols-[3rem_1fr] border-b border-border bg-surface/50">
            <div class="p-1 md:p-2 text-right text-[9px] md:text-[10px] text-muted-foreground border-r border-border flex items-center justify-end pr-1 md:pr-2">
                <span class="md:hidden">HD</span>
                <span class="hidden md:inline">Hel dag</span>
            </div>
            <div class="p-1 md:p-2 flex flex-wrap gap-1 md:gap-2">
                <template x-for="shift in getAllDayShiftsForDate(currentDateString)" :key="'dad-' + shift.id">
                    <div>
                        <template x-if="shift.is_unavailable">
                            <div class="bg-destructive/20 border border-destructive/50 rounded px-1.5 md:px-2 py-0.5 md:py-1 cursor-pointer hover:bg-destructive/30 transition-colors"
                                @click.stop="handleShiftClick(shift.id)">
                                <div class="text-[10px] md:text-xs font-medium text-destructive">
                                    <span class="md:hidden" x-text="(shift.assistant_short_name || '?') + ' - Borte'"></span>
                                    <span class="hidden md:inline" x-text="(shift.assistant_name || 'Tidligere ansatt') + ' - Borte hele dagen'"></span>
                                </div>
                            </div>
                        </template>
                        <template x-if="!shift.is_unavailable">
                            <div class="rounded px-1.5 md:px-2 py-0.5 md:py-1 cursor-pointer hover:opacity-80 transition-opacity"
                                :style="'background-color: ' + (shift.assistant_color || '#6b7280') + '20; border: 1px solid ' + (shift.assistant_color || '#6b7280') + '50'"
                                @click.stop="handleShiftClick(shift.id)">
                                <div class="text-[10px] md:text-xs font-medium"
                                    :style="'color: ' + (shift.assistant_color || '#6b7280')">
                                    <span class="md:hidden" x-text="shift.assistant_short_name || '?'"></span>
                                    <span class="hidden md:inline" x-text="shift.assistant_name || 'Tidligere ansatt'"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
                {{-- Eksterne hele-dagen events --}}
                <template x-for="externalEvent in getAllDayExternalEventsForDate(currentDateString)" :key="'dae-' + externalEvent.id">
                    <div
                        x-data="{ showTooltip: false }"
                        @mouseenter="showTooltip = true"
                        @mouseleave="showTooltip = false"
                        class="rounded px-1.5 md:px-2 py-0.5 md:py-1 relative group/ext cursor-default border-l-2"
                        :style="'background-color: ' + externalEvent.color + '15; border-color: ' + externalEvent.color"
                    >
                        <div class="text-[10px] md:text-xs font-medium text-foreground opacity-70 group-hover/ext:opacity-100 transition-opacity"
                            x-text="externalEvent.title"></div>
                        {{-- Tooltip --}}
                        <div
                            x-show="showTooltip"
                            x-cloak
                            class="absolute z-50 top-full left-0 mt-1 w-48 p-2 bg-card border border-border rounded-lg shadow-lg"
                        >
                            <div class="text-xs font-semibold text-foreground" x-text="externalEvent.title"></div>
                            <div class="text-[9px] mt-1 px-1 py-0.5 rounded inline-block"
                                :style="'background-color: ' + externalEvent.color + '30; color: ' + externalEvent.color"
                                x-text="externalEvent.calendar_label"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Tidslinje --}}
        <div class="flex-1 relative flex flex-col">
            {{-- Navarende tid-indikator --}}
            <template x-if="isTodaySelected">
                <div
                    x-data="{
                        position: currentTimePosition,
                        timeText: '',
                        init() {
                            this.update();
                            setInterval(() => this.update(), 30000);
                        },
                        update() {
                            const now = new Date();
                            const h = String(now.getHours()).padStart(2, '0');
                            const m = String(now.getMinutes()).padStart(2, '0');
                            this.timeText = h + ':' + m;
                            const minutesFromStart = now.getHours() * 60 + now.getMinutes();
                            this.position = (minutesFromStart / 1440) * 100;
                        }
                    }"
                    class="absolute left-0 right-0 z-20 pointer-events-none"
                    :style="'top: ' + position + '%'"
                >
                    <div class="flex items-center">
                        <div class="w-10 md:w-12 flex justify-end pr-1">
                            <span class="text-[10px] font-semibold text-white bg-destructive rounded px-1 py-0.5 leading-none" x-text="timeText"></span>
                        </div>
                        <div class="flex-1 h-0.5 bg-destructive"></div>
                    </div>
                </div>
            </template>

            {{-- Time-rader --}}
            <template x-for="slot in timeSlots" :key="'ds-' + slot.hour">
                <div
                    class="grid grid-cols-[2.5rem_1fr] md:grid-cols-[3rem_1fr] border-b border-border flex-1 min-h-12 md:min-h-16 group"
                >
                    {{-- Klokkeslett --}}
                    <div class="text-right text-[10px] md:text-xs text-muted-foreground border-r border-border flex items-start justify-end pr-1 md:pr-2 pt-1"
                        x-text="slot.label"></div>

                    {{-- Innhold for denne timen med 15-min intervaller --}}
                    <div
                        class="relative flex flex-col transition-colors"
                        data-slot-height="64"
                        @dragover="allowDrop($event, slot.label, currentDateString)"
                        @dragleave="leaveDrop($event)"
                        @drop="handleDrop($event, currentDateString, slot.label)"
                    >
                        {{-- 15-minutters linjer med drag-indikator --}}
                        <div class="absolute inset-0 flex flex-col pointer-events-none">
                            <div class="flex-1 border-b border-border/30 transition-colors"
                                :class="(draggedShift || draggedAssistant) && dragOverSlot === slot.label && dragQuarter === 0 && 'bg-accent/40'"></div>
                            <div class="flex-1 border-b border-border/50 transition-colors"
                                :class="(draggedShift || draggedAssistant) && dragOverSlot === slot.label && dragQuarter === 1 && 'bg-accent/40'"></div>
                            <div class="flex-1 border-b border-border/30 transition-colors"
                                :class="(draggedShift || draggedAssistant) && dragOverSlot === slot.label && dragQuarter === 2 && 'bg-accent/40'"></div>
                            <div class="flex-1 transition-colors"
                                :class="(draggedShift || draggedAssistant) && dragOverSlot === slot.label && dragQuarter === 3 && 'bg-accent/40'"></div>
                        </div>

                        {{-- Klikkbare 15-min omrader --}}
                        <div class="relative flex-1 flex flex-col">
                            <template x-for="quarter in [0, 1, 2, 3]" :key="'q-' + slot.hour + '-' + quarter">
                                <div
                                    @mousedown="startCreate($event, currentDateString, String(slot.hour).padStart(2, '0') + ':' + String(quarter * 15).padStart(2, '0'), $el.closest('[data-slot-height]'))"
                                    @dblclick.stop="openQuickCreate($event, currentDateString, String(slot.hour).padStart(2, '0') + ':' + String(quarter * 15).padStart(2, '0'))"
                                    @contextmenu="showSlotContextMenu($event, currentDateString, String(slot.hour).padStart(2, '0') + ':' + String(quarter * 15).padStart(2, '0'))"
                                    class="flex-1 hover:bg-card-hover/50 transition-colors cursor-pointer group/quarter"
                                    :title="'Kl ' + String(slot.hour).padStart(2, '0') + ':' + String(quarter * 15).padStart(2, '0') + ' (dra for a velge tid, dobbeltklikk for 3t)'"
                                >
                                    {{-- Hover: Legg til knapp per kvarter --}}
                                    <button
                                        @click.stop="openModal(currentDateString, String(slot.hour).padStart(2, '0') + ':' + String(quarter * 15).padStart(2, '0'))"
                                        class="absolute right-1 opacity-0 group-hover/quarter:opacity-100 transition-opacity p-0.5 rounded bg-accent text-black hover:bg-accent-hover text-xs cursor-pointer"
                                        :style="'top: ' + (quarter * 25 + 2) + '%'"
                                        :title="'Legg til vakt kl ' + String(slot.hour).padStart(2, '0') + ':' + String(quarter * 15).padStart(2, '0')"
                                        @mousedown.stop
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                </div>
                            </template>

                            {{-- Drag-to-create preview overlay --}}
                            <div
                                x-show="isCreatingShift && createDate === currentDateString && getCreatePreviewStyle(currentDateString, slot.hour)"
                                x-cloak
                                class="absolute left-0.5 right-0.5 md:left-1 md:right-1 bg-accent/30 pointer-events-none z-20 border-x-2 border-accent border-dashed"
                                :class="{
                                    'border-t-2 rounded-t': getCreatePreviewStyle(currentDateString, slot.hour)?.isFirst,
                                    'border-b-2 rounded-b': getCreatePreviewStyle(currentDateString, slot.hour)?.isLast
                                }"
                                :style="'top: ' + (getCreatePreviewStyle(currentDateString, slot.hour)?.top ?? 0) + '%; height: ' + (getCreatePreviewStyle(currentDateString, slot.hour)?.height ?? 0) + '%;'"
                            >
                                <div class="px-1 md:px-2 py-0.5 text-xs font-medium text-accent"
                                    x-show="getCreatePreviewStyle(currentDateString, slot.hour)?.isFirst">
                                    <span x-text="createStartTime + ' - ' + createEndTime"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Vakter i dette time-slottet --}}
                        <template x-for="shift in getTimedShiftsForSlot(currentDateString, slot.hour)" :key="'ds-' + shift.id">
                            <div>
                                <template x-if="shift.is_unavailable">
                                    <div
                                        @click="handleShiftClick(shift.id)"
                                        @contextmenu="showShiftContextMenu($event, shift.id, true)"
                                        :data-shift="shift.id"
                                        draggable="true"
                                        @dragstart="startDragShift($event, shift.id, shift.start_time, shift.duration_minutes)"
                                        @dragend="endDrag($event)"
                                        class="absolute bg-destructive/20 border-l-2 border-destructive rounded px-1 md:px-2 py-0.5 md:py-1 pointer-events-auto cursor-pointer hover:bg-destructive/30 transition-colors z-10 group/shift"
                                        :class="draggedShift === shift.id && '!pointer-events-none opacity-50'"
                                        :style="'top: ' + getTopPercent(shift.start_time) + '%; height: ' + getHeightPercent(shift.duration_minutes) + '%; left: calc(' + getShiftLayout(currentDateString, slot.hour, shift.id).left + '% + 2px); width: calc(' + getShiftLayout(currentDateString, slot.hour, shift.id).width + '% - 4px);'"
                                    >
                                        <div class="text-xs md:text-sm font-medium text-destructive">
                                            <span class="md:hidden" x-text="shift.assistant_short_name || '?'"></span>
                                            <span class="hidden md:inline" x-text="shift.assistant_name || 'Tidligere ansatt'"></span>
                                        </div>
                                        <div class="text-[10px] md:text-xs"
                                            :class="(resizingShift === shift.id || draggedShift === shift.id) ? 'font-bold text-accent' : 'text-muted'">
                                            <span x-show="resizingShift !== shift.id && draggedShift !== shift.id" x-text="'Borte ' + shift.time_range"></span>
                                            <span x-show="resizingShift === shift.id" x-text="resizePreviewEndTime"></span>
                                            <span x-show="draggedShift === shift.id && dragPreviewTime" x-text="'Borte ' + dragPreviewTime"></span>
                                        </div>
                                        {{-- Resize handle --}}
                                        <div
                                            @mousedown="startResize($event, shift.id, shift.duration_minutes, shift.start_time)"
                                            class="absolute bottom-0 left-0 right-0 h-2 cursor-ns-resize opacity-0 group-hover/shift:opacity-100 bg-destructive/50 rounded-b transition-opacity"
                                            @click.stop
                                        ></div>
                                    </div>
                                </template>
                                <template x-if="!shift.is_unavailable">
                                    <div
                                        @click="handleShiftClick(shift.id)"
                                        @contextmenu="showShiftContextMenu($event, shift.id, false)"
                                        :data-shift="shift.id"
                                        draggable="true"
                                        @dragstart="startDragShift($event, shift.id, shift.start_time, shift.duration_minutes)"
                                        @dragend="endDrag($event)"
                                        class="absolute rounded px-1 md:px-2 py-0.5 md:py-1 pointer-events-auto cursor-pointer hover:opacity-80 transition-opacity z-10 border-l-2 group/shift"
                                        :class="draggedShift === shift.id && '!pointer-events-none opacity-50'"
                                        :style="'top: ' + getTopPercent(shift.start_time) + '%; height: ' + getHeightPercent(shift.duration_minutes) + '%; left: calc(' + getShiftLayout(currentDateString, slot.hour, shift.id).left + '% + 2px); width: calc(' + getShiftLayout(currentDateString, slot.hour, shift.id).width + '% - 4px); background-color: ' + (shift.assistant_color || '#6b7280') + '20; border-color: ' + (shift.assistant_color || '#6b7280')"
                                    >
                                        <div class="text-xs md:text-sm font-medium"
                                            :style="'color: ' + (shift.assistant_color || '#6b7280')">
                                            <span class="md:hidden" x-text="shift.assistant_short_name || '?'"></span>
                                            <span class="hidden md:inline" x-text="shift.assistant_name || 'Tidligere ansatt'"></span>
                                        </div>
                                        <div class="text-[10px] md:text-xs"
                                            :class="(resizingShift === shift.id || draggedShift === shift.id) ? 'font-bold text-accent' : 'text-muted'">
                                            <span x-show="resizingShift !== shift.id && draggedShift !== shift.id" x-text="shift.time_range"></span>
                                            <span x-show="resizingShift === shift.id" x-text="resizePreviewEndTime"></span>
                                            <span x-show="draggedShift === shift.id && dragPreviewTime" x-text="dragPreviewTime"></span>
                                        </div>
                                        {{-- Resize handle --}}
                                        <div
                                            @mousedown="startResize($event, shift.id, shift.duration_minutes, shift.start_time)"
                                            class="absolute bottom-0 left-0 right-0 h-2 cursor-ns-resize opacity-0 group-hover/shift:opacity-100 rounded-b transition-opacity"
                                            :style="'background-color: ' + (shift.assistant_color || '#6b7280') + '50'"
                                            @click.stop
                                        ></div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Eksterne kalender-events i dette time-slottet --}}
                        <template x-for="externalEvent in getTimedExternalEventsForSlot(currentDateString, slot.hour)" :key="'de-' + externalEvent.id">
                            <div x-data="{ showTooltip: false }">
                                <div
                                    @mouseenter="showTooltip = true"
                                    @mouseleave="showTooltip = false"
                                    class="absolute rounded px-1 md:px-2 py-0.5 md:py-1 z-5 border-l-2 group/ext cursor-default"
                                    :style="'top: ' + getTopPercent(externalEvent.start_time) + '%; height: ' + getHeightPercent(getDurationMinutes(externalEvent.start_time, externalEvent.end_time)) + '%; left: calc(' + getExternalEventLayout(currentDateString, slot.hour, externalEvent.id).left + '% + 2px); width: calc(' + getExternalEventLayout(currentDateString, slot.hour, externalEvent.id).width + '% - 4px); background-color: ' + externalEvent.color + '15; border-color: ' + externalEvent.color"
                                >
                                    <template x-if="getDurationMinutes(externalEvent.start_time, externalEvent.end_time) < 60">
                                        <div class="text-[10px] md:text-xs font-medium text-foreground truncate opacity-70 group-hover/ext:opacity-100 transition-opacity">
                                            <span x-text="externalEvent.title"></span>
                                            <span class="font-normal text-foreground/70" x-text="' ' + externalEvent.start_time + ' - ' + externalEvent.end_time"></span>
                                        </div>
                                    </template>
                                    <template x-if="getDurationMinutes(externalEvent.start_time, externalEvent.end_time) >= 60">
                                        <div>
                                            <div class="text-xs md:text-sm font-medium text-foreground opacity-70 group-hover/ext:opacity-100 transition-opacity"
                                                x-text="externalEvent.title"></div>
                                            <div class="text-[10px] md:text-xs text-foreground opacity-60 group-hover/ext:opacity-100 transition-opacity"
                                                x-text="externalEvent.start_time + ' - ' + externalEvent.end_time"></div>
                                        </div>
                                    </template>

                                    {{-- Tooltip --}}
                                    <div
                                        x-show="showTooltip"
                                        x-cloak
                                        class="absolute z-50 top-full left-0 mt-1 w-52 p-2 bg-card border border-border rounded-lg shadow-lg"
                                    >
                                        <div class="text-xs font-semibold text-foreground" x-text="externalEvent.title"></div>
                                        <div class="text-[9px] mt-1 px-1 py-0.5 rounded inline-block"
                                            :style="'background-color: ' + externalEvent.color + '30; color: ' + externalEvent.color"
                                            x-text="externalEvent.calendar_label"></div>
                                        <div class="text-[10px] text-muted mt-1"
                                            x-text="externalEvent.start_time + ' - ' + externalEvent.end_time"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
