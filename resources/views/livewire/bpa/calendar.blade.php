<div class="w-full h-full flex flex-col"
    @keydown.window="
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName)) return;
        if ($event.key === 'm' || $event.key === 'M') $wire.setView('month');
        if ($event.key === 'u' || $event.key === 'U') $wire.setView('week');
        if ($event.key === 'd' || $event.key === 'D') $wire.setView('day');
    "
    x-data="{
    showAssistants: false,
    draggedAssistant: null,
    draggedShift: null,
    draggedShiftStart: null,
    draggedShiftDuration: null,
    dragPreviewTime: null,
    dragOverDate: null,
    resizingShift: null,
    resizeStartY: 0,
    resizeStartHeight: 0,
    resizeShiftStartTime: null,
    resizePreviewEndTime: null,
    justDragged: false,
    justResized: false,
    quickCreateX: 0,
    quickCreateY: 0,
    // Drag-to-create state
    isCreatingShift: false,
    createPending: false,
    createTimeout: null,
    createDate: null,
    createStartTime: null,
    createEndTime: null,
    createStartY: 0,
    createStartSlotTop: 0,
    createSlotHeight: 0,
    init() {
        // Sett dagvisning som standard på mobil (< 768px) - kun ved første lasting
        if (window.innerWidth < 768 && !sessionStorage.getItem('calendar-initialized')) {
            sessionStorage.setItem('calendar-initialized', 'true');
            if ('{{ $view }}' === 'month') {
                $wire.setView('day');
            }
        }
    },
    // Drag & drop for assistants from sidebar
    startDragAssistant(e, assistantId) {
        this.draggedAssistant = assistantId;
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('text/plain', JSON.stringify({ type: 'assistant', id: assistantId }));
    },
    // Drag & drop for existing shifts
    startDragShift(e, shiftId, startTime, durationMinutes) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', JSON.stringify({ type: 'shift', id: shiftId }));
        this.draggedShiftStart = startTime;
        this.draggedShiftDuration = durationMinutes;
        // Delay applying pointer-events-none so drag can initiate first
        setTimeout(() => {
            this.draggedShift = shiftId;
        }, 0);
    },
    endDrag(e) {
        this.draggedAssistant = null;
        this.draggedShift = null;
        this.draggedShiftStart = null;
        this.draggedShiftDuration = null;
        this.dragPreviewTime = null;
        this.dragOverDate = null;
        this.justDragged = true;
        setTimeout(() => this.justDragged = false, 200);
    },
    handleShiftClick(shiftId) {
        if (this.justDragged || this.justResized) return;
        if (typeof $wire.editShift !== 'function') return;
        $wire.editShift(shiftId);
    },
    openQuickCreate(e, date, time, endTime = null) {
        if (typeof $wire.openQuickCreate !== 'function') return;
        // Cancel any pending drag-to-create (for double-click)
        if (this.createTimeout) {
            clearTimeout(this.createTimeout);
            this.createTimeout = null;
            this.createPending = false;
        }
        // Capture position and call Livewire
        this.quickCreateX = Math.min(e.clientX, window.innerWidth - 280);
        this.quickCreateY = Math.min(e.clientY, window.innerHeight - 300);
        $wire.openQuickCreate(date, time, endTime);
    },
    // Drag-to-create methods
    startCreate(e, date, time, slotElement) {
        // Don't start if clicking on a shift
        if (e.target.closest('[data-shift]')) return;
        // Only left mouse button
        if (e.button !== 0) return;

        // Store initial data
        this.createPending = true;
        this.createDate = date;
        this.createStartTime = time;
        this.createEndTime = time;
        this.createStartY = e.clientY;

        // Get slot dimensions for calculating time
        const slot = slotElement || e.target.closest('[data-slot-height]');
        if (slot) {
            const rect = slot.getBoundingClientRect();
            this.createStartSlotTop = rect.top;
            this.createSlotHeight = rect.height;
        }

        // Add document-level listeners
        const moveHandler = (moveE) => this.updateCreate(moveE);
        const upHandler = (upE) => {
            this.endCreate(upE);
            document.removeEventListener('mousemove', moveHandler);
            document.removeEventListener('mouseup', upHandler);
        };

        document.addEventListener('mousemove', moveHandler);
        document.addEventListener('mouseup', upHandler);

        // Delay before showing visual feedback (allows double-click to cancel)
        this.createTimeout = setTimeout(() => {
            if (this.createPending) {
                this.isCreatingShift = true;
            }
            this.createTimeout = null;
        }, 150);
    },
    updateCreate(e) {
        if (!this.isCreatingShift) return;

        // Calculate new end time based on mouse position
        const [startH, startM] = this.createStartTime.split(':').map(Number);
        const startMinutes = startH * 60 + startM;

        // Calculate minutes per pixel based on slot height (1 hour per slot)
        const minutesPerPixel = 60 / this.createSlotHeight;
        const diffY = e.clientY - this.createStartY;
        const diffMinutes = Math.round((diffY * minutesPerPixel) / 15) * 15; // Snap to 15 min

        // Calculate end time (minimum 15 minutes)
        const endMinutes = Math.max(startMinutes + 15, startMinutes + diffMinutes);
        const clampedEndMinutes = Math.min(endMinutes, 24 * 60); // Cap at midnight

        const endH = Math.floor(clampedEndMinutes / 60);
        const endM = clampedEndMinutes % 60;
        this.createEndTime = `${String(endH).padStart(2, '0')}:${String(endM).padStart(2, '0')}`;
    },
    endCreate(e) {
        // Clear timeout if still pending
        if (this.createTimeout) {
            clearTimeout(this.createTimeout);
            this.createTimeout = null;
        }

        // If we never started showing the visual (quick click), just clean up
        if (!this.isCreatingShift) {
            this.createPending = false;
            this.createDate = null;
            this.createStartTime = null;
            this.createEndTime = null;
            return;
        }

        const [startH, startM] = this.createStartTime.split(':').map(Number);
        const [endH, endM] = this.createEndTime.split(':').map(Number);
        const startMinutes = startH * 60 + startM;
        const endMinutes = endH * 60 + endM;

        // Only create if dragged at least 15 minutes
        if (endMinutes > startMinutes) {
            this.openQuickCreate(e, this.createDate, this.createStartTime, this.createEndTime);
        }

        this.isCreatingShift = false;
        this.createPending = false;
        this.createDate = null;
        this.createStartTime = null;
        this.createEndTime = null;
    },
    getCreatePreviewStyle(date, slotHour) {
        if (!this.isCreatingShift || this.createDate !== date) return null;

        const [startH, startM] = this.createStartTime.split(':').map(Number);
        const [endH, endM] = this.createEndTime.split(':').map(Number);

        // Only show in slots within the time range
        if (slotHour < startH || slotHour > endH) return null;
        if (slotHour === endH && endM === 0 && slotHour !== startH) return null;

        const startMinutes = startH * 60 + startM;
        const endMinutes = endH * 60 + endM;
        const slotStartMinutes = slotHour * 60;

        // Calculate top and height relative to this slot
        let topPercent = 0;
        if (slotHour === startH) {
            topPercent = (startM / 60) * 100;
        }

        let heightPercent = 100 - topPercent;
        if (slotHour === endH || (slotHour < endH && endMinutes <= (slotHour + 1) * 60)) {
            const effectiveEndInSlot = Math.min(endMinutes, (slotHour + 1) * 60) - slotStartMinutes;
            heightPercent = (effectiveEndInSlot / 60) * 100 - topPercent;
        }

        // isFirst: only true for the starting slot
        const isFirst = slotHour === startH;
        // isLast: true for the ending slot
        const isLast = slotHour === endH || (slotHour < endH && endMinutes <= (slotHour + 1) * 60);

        return { top: topPercent, height: heightPercent, isFirst, isLast };
    },
    handleDrop(e, date, time = null) {
        e.preventDefault();
        const data = JSON.parse(e.dataTransfer.getData('text/plain') || '{}');

        // Guard: ensure $wire has the expected methods (prevents wrong component errors)
        if (typeof $wire.createShiftFromDrag !== 'function') {
            console.warn('Calendar component not ready');
            return;
        }

        if (data.type === 'assistant') {
            $wire.createShiftFromDrag(data.id, date, time);
        } else if (data.type === 'shift') {
            // Ctrl+drag = duplicate, normal drag = move
            if (e.ctrlKey) {
                $wire.duplicateShift(data.id, date);
            } else {
                $wire.moveShift(data.id, date, time);
            }
        }

        this.draggedAssistant = null;
        this.draggedShift = null;
    },
    allowDrop(e, time = null, date = null) {
        e.preventDefault();
        e.currentTarget.classList.add('bg-accent/20');
        // Track which date we're hovering over (for month view)
        if (date) {
            this.dragOverDate = date;
        }
        // Calculate preview time for shift drag
        if (this.draggedShift && time && this.draggedShiftDuration) {
            const [h, m] = time.split(':').map(Number);
            const startMinutes = h * 60 + m;
            const endMinutes = startMinutes + this.draggedShiftDuration;
            const endH = Math.floor(endMinutes / 60);
            const endM = endMinutes % 60;
            this.dragPreviewTime = `${time} - ${String(endH).padStart(2, '0')}:${String(endM).padStart(2, '0')}`;
        }
    },
    leaveDrop(e) {
        e.currentTarget.classList.remove('bg-accent/20');
        this.dragOverDate = null;
    },
    // Resize functionality
    startResize(e, shiftId, currentDuration, startTime) {
        e.preventDefault();
        e.stopPropagation();
        this.resizingShift = shiftId;
        this.resizeStartY = e.clientY;
        this.resizeStartHeight = currentDuration;
        this.resizeShiftStartTime = startTime;

        const calcEndTime = (startTime, durationMinutes) => {
            const [h, m] = startTime.split(':').map(Number);
            const startMins = h * 60 + m;
            const endMins = startMins + durationMinutes;
            const endH = Math.floor(endMins / 60);
            const endM = endMins % 60;
            return `${String(endH).padStart(2, '0')}:${String(endM).padStart(2, '0')}`;
        };

        // Get actual slot height (not hardcoded) for accurate calculations
        const slot = e.target.closest('[data-slot-height]');
        const slotHeight = slot ? slot.getBoundingClientRect().height : 64;
        const minutesPerPixel = 60 / slotHeight;

        const moveHandler = (moveE) => {
            if (!this.resizingShift) return;
            const diff = moveE.clientY - this.resizeStartY;
            const newMinutes = Math.max(15, Math.round((this.resizeStartHeight + diff * minutesPerPixel) / 15) * 15);
            e.target.closest('[data-shift]').style.height = `${(newMinutes / 60) * 100}%`;
            this.resizePreviewEndTime = `${this.resizeShiftStartTime} - ${calcEndTime(this.resizeShiftStartTime, newMinutes)}`;
        };

        const upHandler = (upE) => {
            if (this.resizingShift && typeof $wire.resizeShift === 'function') {
                const diff = upE.clientY - this.resizeStartY;
                const newMinutes = Math.max(15, Math.round((this.resizeStartHeight + diff * minutesPerPixel) / 15) * 15);
                $wire.resizeShift(this.resizingShift, newMinutes);
            }
            this.resizingShift = null;
            this.resizePreviewEndTime = null;
            this.justResized = true;
            setTimeout(() => this.justResized = false, 200);
            document.removeEventListener('mousemove', moveHandler);
            document.removeEventListener('mouseup', upHandler);
        };

        document.addEventListener('mousemove', moveHandler);
        document.addEventListener('mouseup', upHandler);
    },
    get totalTime() {
        if (!$wire.fromDate || !$wire.toDate) return '';
        if ($wire.isAllDay) return 'Hele dagen';

        const [fromH, fromM] = $wire.fromTime.split(':').map(Number);
        const [toH, toM] = $wire.toTime.split(':').map(Number);

        const fromMinutes = fromH * 60 + fromM;
        const toMinutes = toH * 60 + toM;
        const diffMinutes = toMinutes - fromMinutes;

        if (diffMinutes <= 0) return 'Ugyldig tid';

        const hours = Math.floor(diffMinutes / 60);
        const mins = diffMinutes % 60;

        if (hours === 0) return mins + ' min';
        if (mins === 0) return hours + ' t';
        return hours + ' t ' + mins + ' min';
    }
}">
    {{-- Header: Tittel + Navigasjon --}}
    @include('livewire.bpa.calendar._header')

    {{-- Kalendervisninger --}}
    @if($view === 'week')
        @include('livewire.bpa.calendar._week-view')
    @elseif($view === 'day')
        @include('livewire.bpa.calendar._day-view')
    @else
        @include('livewire.bpa.calendar._month-view')
    @endif

    {{-- Modal: Opprett/Rediger vakt --}}
    @include('livewire.bpa.calendar._shift-modal')

    {{-- Quick Create Popover --}}
    @include('livewire.bpa.calendar._quick-create')
</div>
