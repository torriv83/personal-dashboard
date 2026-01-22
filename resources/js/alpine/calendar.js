/**
 * Alpine.js component for BPA Calendar
 *
 * Handles drag & drop, resize, and drag-to-create functionality
 */
export default (entangledView) => ({
    // View state (entangled with Livewire via $wire.entangle)
    view: entangledView,

    // Sidebar state
    showAssistants: false,

    // Drag & drop state for assistants
    draggedAssistant: null,

    // Drag & drop state for shifts
    draggedShift: null,
    draggedShiftStart: null,
    draggedShiftDuration: null,
    dragPreviewTime: null,
    dragPreviewX: 0,
    dragPreviewY: 0,
    dragOverDate: null,
    dragOverSlot: null, // The hour slot being hovered (e.g., "08:00")
    dragQuarter: 0, // Which quarter of the hour (0-3)

    // Resize state
    resizingShift: null,
    resizeStartY: 0,
    resizeStartHeight: 0,
    resizeShiftStartTime: null,
    resizePreviewEndTime: null,

    // Click protection flags
    justDragged: false,
    justResized: false,

    // Drag-to-create state
    isCreatingShift: false,
    createPending: false,
    createTimeout: null,
    createSessionId: 0, // Incremented each drag to invalidate stale timeouts
    createDate: null,
    createStartTime: null,
    createEndTime: null,
    createStartY: 0,
    createStartSlotTop: 0,
    createSlotHeight: 0,
    _createMoveHandler: null,
    _createUpHandler: null,

    // Day selection state (month view)
    isSelectingDays: false,
    selectPending: false,
    selectTimeout: null,
    selectSessionId: 0,
    selectStartDate: null,
    selectEndDate: null,
    _selectMoveHandler: null,
    _selectUpHandler: null,

    // Swipe navigation state (mobile)
    swipeStartX: 0,
    swipeStartY: 0,
    swipeStartTime: 0,
    isSwiping: false,
    swipeOffsetX: 0, // Current horizontal offset during swipe
    isAnimatingSwipe: false, // True during slide-out animation

    // Absence popup is now in Alpine.store('absencePopup')
    // This getter provides backward compatibility for existing code
    get absencePopup() {
        return this.$store.absencePopup;
    },

    // Context menu state is now in Alpine.store('contextMenu')
    // This getter provides backward compatibility for existing code
    get contextMenu() {
        return this.$store.contextMenu;
    },

    /**
     * Initialize component - set day view as default on mobile
     */
    init() {
        if (window.innerWidth < 768 && !sessionStorage.getItem('calendar-initialized')) {
            sessionStorage.setItem('calendar-initialized', 'true');
            if (this.view === 'month') {
                this.setView('day');
            }
        }
    },

    /**
     * Handle keyboard shortcuts
     */
    handleKeydown(e) {
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;
        // Close context menu on Escape
        if (e.key === 'Escape' && this.$store.contextMenu.show) {
            this.$store.contextMenu.hide();
            return;
        }
        if (e.key === 'm' || e.key === 'M') { this.setView('month'); this.$wire.$refresh(); }
        if (e.key === 'u' || e.key === 'U') { this.setView('week'); this.$wire.$refresh(); }
        if (e.key === 'd' || e.key === 'D') { this.setView('day'); this.$wire.$refresh(); }
        if (e.key === 'ArrowLeft') { this.navigatePrevious(); }
        if (e.key === 'ArrowRight') { this.navigateNext(); }
    },

    /**
     * Set view (entangled - automatically syncs to Livewire)
     */
    setView(newView) {
        this.view = newView;
    },

    // =========================================================================
    // Swipe Navigation (mobile)
    // =========================================================================

    /**
     * Handle touch start - record starting position and time
     */
    handleTouchStart(e) {
        // Only handle single touch
        if (e.touches.length !== 1) return;

        // Don't start swipe if other operations are active
        if (this.isCreatingShift || this.isSelectingDays ||
            this.resizingShift || this.draggedShift) return;

        this.swipeStartX = e.touches[0].clientX;
        this.swipeStartY = e.touches[0].clientY;
        this.swipeStartTime = Date.now();
        this.isSwiping = false;
    },

    /**
     * Handle touch move - detect if this is a horizontal swipe and update offset
     */
    handleTouchMove(e) {
        // Skip if no start position recorded or animating
        if (!this.swipeStartTime || this.isAnimatingSwipe) return;

        // Don't interfere if other operations started
        if (this.isCreatingShift || this.isSelectingDays ||
            this.resizingShift || this.draggedShift) {
            this.swipeStartTime = 0;
            return;
        }

        const deltaX = e.touches[0].clientX - this.swipeStartX;
        const deltaY = e.touches[0].clientY - this.swipeStartY;
        const elapsed = Date.now() - this.swipeStartTime;

        // Detect horizontal swipe early (within 150ms, before drag-to-create activates)
        // Requires: >30px horizontal, horizontal > 1.5x vertical
        if (!this.isSwiping && elapsed < 150 && Math.abs(deltaX) > 30 && Math.abs(deltaX) > Math.abs(deltaY) * 1.5) {
            this.isSwiping = true;
            // Cancel any pending create/select operations
            if (this.createTimeout) {
                clearTimeout(this.createTimeout);
                this.createTimeout = null;
                this.createPending = false;
            }
            if (this.selectTimeout) {
                clearTimeout(this.selectTimeout);
                this.selectTimeout = null;
                this.selectPending = false;
            }
        }

        // Update visual offset while swiping (follow finger directly)
        if (this.isSwiping) {
            this.swipeOffsetX = deltaX;
        }
    },

    /**
     * Handle touch end - animate and navigate if valid swipe detected
     */
    handleTouchEnd(e) {
        // Skip if no start position or other operations active
        if (!this.swipeStartTime || this.isAnimatingSwipe) return;
        if (this.isCreatingShift || this.isSelectingDays ||
            this.resizingShift || this.draggedShift) {
            this.resetSwipeState();
            return;
        }

        const touch = e.changedTouches[0];
        const deltaX = touch.clientX - this.swipeStartX;
        const deltaY = touch.clientY - this.swipeStartY;
        const elapsed = Date.now() - this.swipeStartTime;

        // Valid swipe: >75px horizontal, horizontal > 1.5x vertical, <400ms
        const isValidSwipe = Math.abs(deltaX) > 75 &&
                            Math.abs(deltaX) > Math.abs(deltaY) * 1.5 &&
                            elapsed < 400;

        if (isValidSwipe || (this.isSwiping && Math.abs(this.swipeOffsetX) > 50)) {
            e.preventDefault();

            // Animate slide-out before navigating
            this.isAnimatingSwipe = true;
            const direction = deltaX > 0 ? 1 : -1;
            this.swipeOffsetX = direction * window.innerWidth;

            // Navigate after animation completes
            setTimeout(async () => {
                // Show skeleton while loading (using global store that persists)
                this.$store.swipeLoader.show();

                // Small delay to let Alpine render the skeleton before Livewire call
                await new Promise(resolve => requestAnimationFrame(resolve));

                // Wait for Livewire to finish updating
                if (direction > 0) {
                    await this.navigatePrevious();
                } else {
                    await this.navigateNext();
                }

                // Livewire is done - hide skeleton and animate in
                this.$store.swipeLoader.hide();
                this.swipeOffsetX = -direction * 50; // Start slightly offset
                requestAnimationFrame(() => {
                    this.swipeOffsetX = 0; // Animate to center
                    setTimeout(() => {
                        this.isAnimatingSwipe = false;
                    }, 200);
                });
            }, 150);
        } else {
            // Snap back to center
            this.swipeOffsetX = 0;
            this.resetSwipeState();
        }
    },

    /**
     * Reset swipe state
     */
    resetSwipeState() {
        this.swipeStartX = 0;
        this.swipeStartY = 0;
        this.swipeStartTime = 0;
        this.isSwiping = false;
        this.isAnimatingSwipe = false;
    },

    /**
     * Navigate to previous period based on current view (for swipe navigation)
     * Returns a promise that resolves when Livewire is done
     */
    navigatePrevious() {
        if (this.view === 'day') {
            return this.$wire.previousDay();
        } else if (this.view === 'week') {
            return this.$wire.previousWeek();
        } else if (this.view === 'month') {
            return this.$wire.previousMonth();
        }
        return Promise.resolve();
    },

    /**
     * Navigate to next period based on current view (for swipe navigation)
     * Returns a promise that resolves when Livewire is done
     */
    navigateNext() {
        if (this.view === 'day') {
            return this.$wire.nextDay();
        } else if (this.view === 'week') {
            return this.$wire.nextWeek();
        } else if (this.view === 'month') {
            return this.$wire.nextMonth();
        }
        return Promise.resolve();
    },

    /**
     * Navigate to previous period for arrow buttons (Alpine + Livewire)
     * Updates properties instantly for fast UI, then refreshes data
     */
    navigatePreviousArrow(type) {
        const year = this.$wire.year;
        const month = this.$wire.month;
        const day = this.$wire.day;

        if (type === 'day') {
            const date = new Date(year, month - 1, day);
            date.setDate(date.getDate() - 1);
            this.$wire.year = date.getFullYear();
            this.$wire.month = date.getMonth() + 1;
            this.$wire.day = date.getDate();
        } else if (type === 'week') {
            const date = new Date(year, month - 1, day);
            date.setDate(date.getDate() - 7);
            this.$wire.year = date.getFullYear();
            this.$wire.month = date.getMonth() + 1;
            this.$wire.day = date.getDate();
        } else if (type === 'month') {
            const date = new Date(year, month - 1, 1);
            date.setMonth(date.getMonth() - 1);
            this.$wire.year = date.getFullYear();
            this.$wire.month = date.getMonth() + 1;
        }

        // Force Livewire refresh to fetch correct data
        this.$wire.$refresh();
    },

    /**
     * Navigate to next period for arrow buttons (Alpine + Livewire)
     * Updates properties instantly for fast UI, then refreshes data
     */
    navigateNextArrow(type) {
        const year = this.$wire.year;
        const month = this.$wire.month;
        const day = this.$wire.day;

        if (type === 'day') {
            const date = new Date(year, month - 1, day);
            date.setDate(date.getDate() + 1);
            this.$wire.year = date.getFullYear();
            this.$wire.month = date.getMonth() + 1;
            this.$wire.day = date.getDate();
        } else if (type === 'week') {
            const date = new Date(year, month - 1, day);
            date.setDate(date.getDate() + 7);
            this.$wire.year = date.getFullYear();
            this.$wire.month = date.getMonth() + 1;
            this.$wire.day = date.getDate();
        } else if (type === 'month') {
            const date = new Date(year, month - 1, 1);
            date.setMonth(date.getMonth() + 1);
            this.$wire.year = date.getFullYear();
            this.$wire.month = date.getMonth() + 1;
        }

        // Force Livewire refresh to fetch correct data
        this.$wire.$refresh();
    },

    // =========================================================================
    // Context Menu
    // =========================================================================

    /**
     * Show context menu for empty slot
     */
    showSlotContextMenu(e, date, time) {
        e.preventDefault();
        e.stopPropagation();

        // Calculate position
        const x = Math.min(e.clientX, window.innerWidth - 200);
        const y = Math.min(e.clientY, window.innerHeight - 250);

        // Use the global store (persists across Livewire re-renders)
        this.$store.contextMenu.showSlot(x, y, date, time);
    },

    /**
     * Show context menu for existing shift
     */
    showShiftContextMenu(e, shiftId, isUnavailable = false) {
        e.preventDefault();
        e.stopPropagation();

        // Calculate position
        const x = Math.min(e.clientX, window.innerWidth - 200);
        const y = Math.min(e.clientY, window.innerHeight - 250);

        // Use the global store (persists across Livewire re-renders)
        this.$store.contextMenu.showShift(x, y, shiftId, isUnavailable);
    },

    /**
     * Hide context menu
     */
    hideContextMenu() {
        this.$store.contextMenu.hide();
    },

    /**
     * Handle context menu action
     */
    contextMenuAction(action) {
        const menu = this.contextMenu;

        if (menu.type === 'slot') {
            // Slot actions - openModal(date, time, assistantId, endTime, isUnavailable)
            if (action === 'create') {
                this.$wire.openModal(menu.date, menu.time, null, null, false);
            } else if (action === 'unavailable') {
                this.$wire.openModal(menu.date, menu.time, null, null, true);
            }
        } else if (menu.type === 'shift') {
            // Shift actions
            if (action === 'edit') {
                this.$wire.editShift(menu.shiftId);
            } else if (action === 'duplicate') {
                this.$wire.duplicateShiftWithModal(menu.shiftId);
            } else if (action === 'delete') {
                this.$wire.deleteShift(menu.shiftId);
            } else if (action === 'archive') {
                this.$wire.archiveShift(menu.shiftId);
            }
        }

        this.hideContextMenu();
    },

    // =========================================================================
    // Drag & Drop: Assistants from sidebar
    // =========================================================================

    startDragAssistant(e, assistantId) {
        this.draggedAssistant = assistantId;
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('text/plain', JSON.stringify({ type: 'assistant', id: assistantId }));
    },

    // =========================================================================
    // Drag & Drop: Existing shifts
    // =========================================================================

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

    endDrag() {
        this.draggedAssistant = null;
        this.draggedShift = null;
        this.draggedShiftStart = null;
        this.draggedShiftDuration = null;
        this.dragPreviewTime = null;
        this.dragPreviewX = 0;
        this.dragPreviewY = 0;
        this.dragOverDate = null;
        this.dragOverSlot = null;
        this.dragQuarter = 0;
        this.justDragged = true;
        setTimeout(() => this.justDragged = false, 200);
    },

    // =========================================================================
    // Shift interactions
    // =========================================================================

    handleShiftClick(shiftId) {
        if (this.justDragged || this.justResized) return;
        if (typeof this.$wire.editShift !== 'function') return;
        this.$wire.editShift(shiftId);
    },

    openQuickCreate(e, date, time, endTime = null) {
        if (typeof this.$wire.openQuickCreate !== 'function') return;
        // Cancel any pending drag-to-create (for double-click)
        if (this.createTimeout) {
            clearTimeout(this.createTimeout);
            this.createTimeout = null;
            this.createPending = false;
        }
        // Calculate position and pass to Livewire (stored server-side to survive re-renders)
        const x = Math.min(e.clientX, window.innerWidth - 280);
        const y = Math.min(e.clientY, window.innerHeight - 300);
        this.$wire.openQuickCreate(date, time, endTime, x, y);
    },

    // =========================================================================
    // Drag-to-create shift
    // =========================================================================

    startCreate(e, date, time, slotElement) {
        // Don't start if clicking on a shift
        if (e.target.closest('[data-shift]')) return;
        // Only left mouse button
        if (e.button !== 0) return;

        // Clean up any existing drag state (may be stale after Livewire re-render)
        if (this.createTimeout) {
            clearTimeout(this.createTimeout);
        }
        if (this._createMoveHandler) {
            document.removeEventListener('mousemove', this._createMoveHandler);
        }
        if (this._createUpHandler) {
            document.removeEventListener('mouseup', this._createUpHandler);
        }

        // Reset all drag-to-create state and increment session ID
        this.isCreatingShift = false;
        this.createPending = true;
        this.createTimeout = null;
        this.createSessionId++;
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

        // Store handlers on component so we can clean them up later
        this._createMoveHandler = (moveE) => this.updateCreate(moveE);
        this._createUpHandler = (upE) => {
            this.endCreate(upE);
            document.removeEventListener('mousemove', this._createMoveHandler);
            document.removeEventListener('mouseup', this._createUpHandler);
            this._createMoveHandler = null;
            this._createUpHandler = null;
        };

        document.addEventListener('mousemove', this._createMoveHandler);
        document.addEventListener('mouseup', this._createUpHandler);

        // Delay before showing visual feedback (allows double-click to cancel)
        // Capture session ID to verify timeout is still valid when it fires
        const sessionId = this.createSessionId;
        const component = this;
        this.createTimeout = setTimeout(() => {
            // Only proceed if this is still the current drag session
            if (component.createSessionId === sessionId && component.createPending) {
                component.isCreatingShift = true;
                // Force Alpine to recognize the state change
                component.$nextTick(() => {});
            }
            component.createTimeout = null;
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

    // =========================================================================
    // Day selection (month view) - for creating multi-day absences
    // =========================================================================

    startSelectDays(e, date) {
        // Don't start if clicking on a shift or event
        if (e.target.closest('[data-shift]') || e.target.closest('[data-event]')) return;
        // Only left mouse button
        if (e.button !== 0) return;

        // Clean up any existing selection state
        if (this.selectTimeout) {
            clearTimeout(this.selectTimeout);
        }
        if (this._selectMoveHandler) {
            document.removeEventListener('mouseenter', this._selectMoveHandler, true);
        }
        if (this._selectUpHandler) {
            document.removeEventListener('mouseup', this._selectUpHandler);
        }

        // Reset state and increment session ID
        this.isSelectingDays = false;
        this.selectPending = true;
        this.selectTimeout = null;
        this.selectSessionId++;
        this.selectStartDate = date;
        this.selectEndDate = date;

        // Store handlers for cleanup
        this._selectUpHandler = (upE) => {
            this.endSelectDays(upE);
            document.removeEventListener('mouseup', this._selectUpHandler);
            this._selectUpHandler = null;
        };

        document.addEventListener('mouseup', this._selectUpHandler);

        // Delay before showing visual feedback (allows click to navigate)
        const sessionId = this.selectSessionId;
        const component = this;
        this.selectTimeout = setTimeout(() => {
            if (component.selectSessionId === sessionId && component.selectPending) {
                component.isSelectingDays = true;
                component.$nextTick(() => {});
            }
            component.selectTimeout = null;
        }, 150);
    },

    updateSelectDays(date) {
        if (!this.isSelectingDays) return;
        this.selectEndDate = date;
    },

    endSelectDays(e) {
        // Clear timeout if still pending
        if (this.selectTimeout) {
            clearTimeout(this.selectTimeout);
            this.selectTimeout = null;
        }

        // If we never started showing the visual (quick click), just clean up
        if (!this.isSelectingDays) {
            this.selectPending = false;
            this.selectStartDate = null;
            this.selectEndDate = null;
            return;
        }

        // Calculate the date range (ensure start <= end)
        const start = this.selectStartDate;
        const end = this.selectEndDate;
        const [fromDate, toDate] = start <= end ? [start, end] : [end, start];

        // Show the absence popup using the global store
        const x = Math.min(e.clientX, window.innerWidth - 320);
        const y = Math.min(e.clientY, window.innerHeight - 200);

        this.$store.absencePopup.open(x, y, fromDate, toDate);

        // Reset selection state
        this.isSelectingDays = false;
        this.selectPending = false;
        this.selectStartDate = null;
        this.selectEndDate = null;
    },

    /**
     * Check if a date is within the current selection range
     */
    isDateSelected(date) {
        if (!this.isSelectingDays || !this.selectStartDate || !this.selectEndDate) {
            return false;
        }
        const start = this.selectStartDate;
        const end = this.selectEndDate;
        const [from, to] = start <= end ? [start, end] : [end, start];
        return date >= from && date <= to;
    },


    // =========================================================================
    // Drop handling
    // =========================================================================

    handleDrop(e, date, time = null) {
        e.preventDefault();

        // Safely parse transfer data
        let data = {};
        try {
            const raw = e.dataTransfer.getData('text/plain');
            if (raw) {
                data = JSON.parse(raw);
            }
        } catch (err) {
            // Not valid JSON (might be from drag-to-create or other source)
            return;
        }

        // Guard: ensure $wire has the expected methods
        if (typeof this.$wire.createShiftFromDrag !== 'function') {
            console.warn('Calendar component not ready');
            return;
        }

        // Calculate precise quarter-hour based on drop position within slot
        let preciseTime = time;
        if (time) {
            const slot = e.target.closest('[data-slot-height]');
            if (slot) {
                const rect = slot.getBoundingClientRect();
                const relativeY = e.clientY - rect.top;
                const percentInSlot = relativeY / rect.height;
                const quarterInSlot = Math.floor(percentInSlot * 4); // 0, 1, 2, or 3
                const [hour] = time.split(':').map(Number);
                const minutes = Math.min(quarterInSlot, 3) * 15; // 0, 15, 30, or 45
                preciseTime = `${String(hour).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
            }
        }

        if (data.type === 'assistant') {
            this.$wire.createShiftFromDrag(data.id, date, preciseTime);
        } else if (data.type === 'shift') {
            // Ctrl+drag = duplicate, normal drag = move
            if (e.ctrlKey) {
                this.$wire.duplicateShift(data.id, date);
            } else {
                this.$wire.moveShift(data.id, date, preciseTime);
            }
        }

        this.draggedAssistant = null;
        this.draggedShift = null;
    },

    allowDrop(e, time = null, date = null) {
        e.preventDefault();
        // Track which date we're hovering over (for month view)
        if (date) {
            this.dragOverDate = date;
        }
        // Calculate preview time for shift drag with quarter-hour precision
        if (this.draggedShift && time && this.draggedShiftDuration) {
            // Calculate precise quarter-hour based on position within slot
            let preciseTime = time;
            let quarterInSlot = 0;
            const slot = e.target.closest('[data-slot-height]');
            if (slot) {
                const rect = slot.getBoundingClientRect();
                const relativeY = e.clientY - rect.top;
                const percentInSlot = relativeY / rect.height;
                quarterInSlot = Math.min(Math.floor(percentInSlot * 4), 3); // 0, 1, 2, or 3
                const [hour] = time.split(':').map(Number);
                const minutes = quarterInSlot * 15;
                preciseTime = `${String(hour).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
            }

            const [h, m] = preciseTime.split(':').map(Number);
            const startMinutes = h * 60 + m;
            const endMinutes = startMinutes + this.draggedShiftDuration;
            const endH = Math.floor(endMinutes / 60);
            const endM = endMinutes % 60;
            this.dragPreviewTime = `${preciseTime} - ${String(endH).padStart(2, '0')}:${String(endM).padStart(2, '0')}`;

            // Track cursor position for floating tooltip
            this.dragPreviewX = e.clientX;
            this.dragPreviewY = e.clientY;

            // Track which slot and quarter for visual indicator
            this.dragOverSlot = time;
            this.dragQuarter = quarterInSlot;
        } else if (time) {
            // Also track for assistant drag (show slot but no time preview)
            this.dragOverSlot = time;
            // Calculate quarter for assistant drag too
            const slot = e.target.closest('[data-slot-height]');
            if (slot) {
                const rect = slot.getBoundingClientRect();
                const relativeY = e.clientY - rect.top;
                const percentInSlot = relativeY / rect.height;
                this.dragQuarter = Math.min(Math.floor(percentInSlot * 4), 3);
            }
        }
    },

    leaveDrop(e) {
        e.currentTarget.classList.remove('bg-accent/20');
        this.dragOverDate = null;
        this.dragOverSlot = null;
        this.dragQuarter = 0;
    },

    // =========================================================================
    // Resize functionality
    // =========================================================================

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

        // Get actual slot height for accurate calculations
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
            if (this.resizingShift && typeof this.$wire.resizeShift === 'function') {
                const diff = upE.clientY - this.resizeStartY;
                const newMinutes = Math.max(15, Math.round((this.resizeStartHeight + diff * minutesPerPixel) / 15) * 15);
                this.$wire.resizeShift(this.resizingShift, newMinutes);
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

    // =========================================================================
    // Computed: Total time display for shift modal
    // =========================================================================

    get totalTime() {
        if (!this.$wire.fromDate || !this.$wire.toDate) return '';
        if (this.$wire.isAllDay) return 'Hele dagen';

        const [fromH, fromM] = this.$wire.fromTime.split(':').map(Number);
        const [toH, toM] = this.$wire.toTime.split(':').map(Number);

        const fromMinutes = fromH * 60 + fromM;
        const toMinutes = toH * 60 + toM;
        const diffMinutes = toMinutes - fromMinutes;

        if (diffMinutes <= 0) return 'Ugyldig tid';

        const hours = Math.floor(diffMinutes / 60);
        const mins = diffMinutes % 60;

        if (hours === 0) return mins + ' min';
        if (mins === 0) return hours + ' t';
        return hours + ' t ' + mins + ' min';
    },
});
