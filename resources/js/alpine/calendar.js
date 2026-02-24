/**
 * Alpine.js component for BPA Calendar
 *
 * Handles:
 * - API-driven data fetching (shifts, external events, calendar days)
 * - Client-side navigation (no Livewire round-trips)
 * - Drag & drop, resize, and drag-to-create functionality
 * - Swipe navigation (mobile)
 * - Overlap calculation for side-by-side events
 */
export default (config = {}) => ({
    // =========================================================================
    // Calendar data from API
    // =========================================================================
    shifts: [],
    shiftsByDate: {},
    externalEvents: [],
    externalEventsByDate: {},
    assistants: [],
    calendarDays: { weeks: [] },
    remainingHours: null,
    availableYears: [],

    // Current date/view state
    currentYear: config.initialYear || new Date().getFullYear(),
    currentMonth: config.initialMonth || (new Date().getMonth() + 1),
    currentDay: config.initialDay || new Date().getDate(),
    view: config.initialView || 'month',

    // Loading states
    isLoading: true,
    isNavigating: false,

    // Norwegian month and day names
    monthNames: ['Januar', 'Februar', 'Mars', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Desember'],
    monthNamesShort: ['jan', 'feb', 'mar', 'apr', 'mai', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'des'],
    dayNames: ['Man', 'Tir', 'Ons', 'Tor', 'Fre', 'Lør', 'Søn'],
    dayNamesFull: ['Mandag', 'Tirsdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lørdag', 'Søndag'],

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
    dragOverSlot: null,
    dragQuarter: 0,

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
    createSessionId: 0,
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

    // Client-side data cache for prefetched periods
    _dataCache: {},
    _maxCacheEntries: 6,
    _cacheTtlMs: 5 * 60 * 1000, // 5 minutes
    _prefetchInProgress: false,

    // Swipe navigation state (mobile)
    swipeStartX: 0,
    swipeStartY: 0,
    swipeStartTime: 0,
    isSwiping: false,
    swipeOffsetX: 0,
    isAnimatingSwipe: false,

    // =========================================================================
    // Modal state (shift create/edit)
    // =========================================================================
    modal: {
        show: false,
        isEditing: false,
        editingShiftId: null,
        isSubmitting: false,
        errors: {},
        isExistingRecurring: false,
        form: {
            assistant_id: '',
            from_date: '',
            from_time: '08:00',
            to_date: '',
            to_time: '16:00',
            is_unavailable: false,
            is_all_day: false,
            note: '',
            is_recurring: false,
            recurring_interval: 'weekly',
            recurring_end_type: 'count',
            recurring_count: 4,
            recurring_end_date: '',
            _recurring_scope: null,
        },
    },

    // Recurring dialog state
    recurringDialog: {
        show: false,
        action: null,
        shiftId: null,
        scope: null,
        _moveData: null,
    },

    // Quick create state
    quickCreate: {
        show: false,
        date: '',
        time: '',
        endTime: '',
        x: 0,
        y: 0,
    },

    // Backward compatibility for existing code
    get absencePopup() {
        return this.$store.absencePopup;
    },

    get contextMenu() {
        return this.$store.contextMenu;
    },

    // =========================================================================
    // Computed properties
    // =========================================================================

    /**
     * Get the current month name in Norwegian.
     */
    get currentMonthName() {
        return this.monthNames[this.currentMonth - 1];
    },

    /**
     * Get formatted date string for day view header.
     */
    get formattedDate() {
        const date = new Date(this.currentYear, this.currentMonth - 1, this.currentDay);
        const dayIndex = ((date.getDay() + 6) % 7); // Convert to Monday=0
        const dayName = this.dayNamesFull[dayIndex];
        return `${dayName}, ${this.currentDay}. ${this.monthNames[this.currentMonth - 1]} ${this.currentYear}`;
    },

    /**
     * Get formatted date string for day view (short, for mobile).
     */
    get formattedDateShort() {
        const date = new Date(this.currentYear, this.currentMonth - 1, this.currentDay);
        const dayIndex = ((date.getDay() + 6) % 7);
        const dayNameShort = this.dayNames[dayIndex];
        return `${dayNameShort} ${this.currentDay}. ${this.monthNamesShort[this.currentMonth - 1]}`;
    },

    /**
     * Get the current ISO week number.
     */
    get currentWeekNumber() {
        const date = new Date(this.currentYear, this.currentMonth - 1, this.currentDay);
        return this._getISOWeekNumber(date);
    },

    /**
     * Get days for the current week (used in week/day views).
     */
    get currentWeekDays() {
        const date = new Date(this.currentYear, this.currentMonth - 1, this.currentDay);
        const dayOfWeek = (date.getDay() + 6) % 7; // Monday=0
        const monday = new Date(date);
        monday.setDate(date.getDate() - dayOfWeek);

        const today = this._todayString();
        const days = [];

        for (let i = 0; i < 7; i++) {
            const d = new Date(monday);
            d.setDate(monday.getDate() + i);
            const dateStr = this._formatDate(d);
            days.push({
                date: dateStr,
                day: d.getDate(),
                dayName: this.dayNames[i],
                dayNameFull: this.dayNamesFull[i],
                isToday: dateStr === today,
                isWeekend: i >= 5,
                isSelected: d.getDate() === this.currentDay && d.getMonth() + 1 === this.currentMonth,
            });
        }

        return days;
    },

    /**
     * Get week range string for header (e.g., "3 - 9. Februar 2026").
     */
    get weekRange() {
        const date = new Date(this.currentYear, this.currentMonth - 1, this.currentDay);
        const dayOfWeek = (date.getDay() + 6) % 7;
        const monday = new Date(date);
        monday.setDate(date.getDate() - dayOfWeek);
        const sunday = new Date(monday);
        sunday.setDate(monday.getDate() + 6);

        if (monday.getMonth() === sunday.getMonth()) {
            return `${monday.getDate()} - ${sunday.getDate()}. ${this.monthNames[monday.getMonth()]} ${monday.getFullYear()}`;
        }
        return `${monday.getDate()}. ${this.monthNames[monday.getMonth()]} - ${sunday.getDate()}. ${this.monthNames[sunday.getMonth()]} ${sunday.getFullYear()}`;
    },

    /**
     * Get week range string (short version for mobile).
     */
    get weekRangeShort() {
        const date = new Date(this.currentYear, this.currentMonth - 1, this.currentDay);
        const dayOfWeek = (date.getDay() + 6) % 7;
        const monday = new Date(date);
        monday.setDate(date.getDate() - dayOfWeek);
        const sunday = new Date(monday);
        sunday.setDate(monday.getDate() + 6);

        if (monday.getMonth() === sunday.getMonth()) {
            return `${monday.getDate()}-${sunday.getDate()}. ${this.monthNamesShort[monday.getMonth()]}`;
        }
        return `${monday.getDate()}. ${this.monthNamesShort[monday.getMonth()]} - ${sunday.getDate()}. ${this.monthNamesShort[sunday.getMonth()]}`;
    },

    /**
     * Get weeks in the current month (for week selector dropdown).
     */
    get weeksInMonth() {
        const firstOfMonth = new Date(this.currentYear, this.currentMonth - 1, 1);
        const lastOfMonth = new Date(this.currentYear, this.currentMonth, 0);

        const weeks = [];
        const dayOfWeek = (firstOfMonth.getDay() + 6) % 7;
        const currentMonday = new Date(firstOfMonth);
        currentMonday.setDate(firstOfMonth.getDate() - dayOfWeek);
        const selectedWeek = this.currentWeekNumber;

        while (currentMonday <= lastOfMonth) {
            const endOfWeek = new Date(currentMonday);
            endOfWeek.setDate(currentMonday.getDate() + 6);
            const weekNumber = this._getISOWeekNumber(currentMonday);

            let label;
            if (currentMonday.getMonth() === endOfWeek.getMonth()) {
                label = `${currentMonday.getDate()}-${endOfWeek.getDate()}. ${this.monthNamesShort[currentMonday.getMonth()]}`;
            } else {
                label = `${currentMonday.getDate()}. ${this.monthNamesShort[currentMonday.getMonth()]} - ${endOfWeek.getDate()}. ${this.monthNamesShort[endOfWeek.getMonth()]}`;
            }

            weeks.push({
                weekNumber,
                label: `Uke ${weekNumber}: ${label}`,
                labelShort: label,
                date: this._formatDate(currentMonday),
                isSelected: weekNumber === selectedWeek,
            });

            currentMonday.setDate(currentMonday.getDate() + 7);
        }

        return weeks;
    },

    /**
     * Check if today is the currently selected date.
     */
    get isTodaySelected() {
        const today = new Date();
        return this.currentYear === today.getFullYear()
            && this.currentMonth === today.getMonth() + 1
            && this.currentDay === today.getDate();
    },

    /**
     * Get the current time position as percentage for the time indicator line.
     */
    get currentTimePosition() {
        const now = new Date();
        const minutesFromStart = now.getHours() * 60 + now.getMinutes();
        return (minutesFromStart / 1440) * 100;
    },

    /**
     * Check if any day in the current week is today (for time indicator).
     */
    get weekHasToday() {
        return this.currentWeekDays.some(d => d.isToday);
    },

    /**
     * Get remaining hours display data.
     */
    get remainingHoursFormatted() {
        if (!this.remainingHours) return '';
        return this.remainingHours.formatted_remaining;
    },

    get remainingMinutes() {
        if (!this.remainingHours) return 0;
        return this.remainingHours.remaining_minutes;
    },

    /**
     * Generate time slots for week/day views (00:00 - 23:00).
     */
    get timeSlots() {
        const slots = [];
        for (let hour = 0; hour <= 23; hour++) {
            slots.push({
                hour,
                label: String(hour).padStart(2, '0') + ':00',
            });
        }
        return slots;
    },

    /**
     * Get the date string for the currently selected day.
     */
    get currentDateString() {
        return this._formatDate(new Date(this.currentYear, this.currentMonth - 1, this.currentDay));
    },

    /**
     * Get assistant IDs that are unavailable all day on the current date (for day view sidebar).
     */
    get dayViewUnavailableAssistantIds() {
        const dateStr = this.currentDateString;
        const dayShifts = this.shiftsByDate[dateStr] || [];
        return dayShifts
            .filter(s => s.is_unavailable && s.is_all_day)
            .map(s => s.assistant_id)
            .filter((v, i, a) => a.indexOf(v) === i);
    },

    // =========================================================================
    // Initialization
    // =========================================================================

    async init() {
        // Set day view as default on mobile (first visit)
        if (window.innerWidth < 768 && !sessionStorage.getItem('calendar-initialized')) {
            sessionStorage.setItem('calendar-initialized', 'true');
            if (this.view === 'month') {
                this.view = 'day';
            }
        }

        // Fetch all initial data
        await this.fetchAllData();

        // Sync view to Livewire for any remaining server-side needs
        this.$watch('view', () => {
            this._syncToLivewire();
        });

        // Listen for global Livewire events
        document.addEventListener('livewire:navigated', () => {
            this._invalidateCache();
            this.fetchCalendarData();
        });

        // Listen for "go to today" from topbar button (dispatched as browser custom event)
        window.addEventListener('calendar-go-to-today', () => {
            this.goToToday();
        });

        // Listen for context menu / absence popup actions dispatched via custom events
        window.addEventListener('calendar-open-modal', (e) => {
            const d = e.detail || {};
            this.openModal(d.date, d.time, d.assistantId, d.endTime, d.isUnavailable);
        });
        window.addEventListener('calendar-edit-shift', (e) => {
            this.editShift(e.detail.shiftId);
        });
        window.addEventListener('calendar-duplicate-shift', (e) => {
            this.duplicateShiftToModal(e.detail.shiftId);
        });
        window.addEventListener('calendar-delete-shift', (e) => {
            this.deleteShift(e.detail.shiftId);
        });
        window.addEventListener('calendar-archive-shift', (e) => {
            this.archiveShift(e.detail.shiftId);
        });
        window.addEventListener('calendar-create-absence', (e) => {
            const d = e.detail;
            this.createAbsenceFromSelection(d.assistantId, d.fromDate, d.toDate);
        });

        // Handle ?create=1 URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('create')) {
            const today = this._todayString();
            this.openModal(today);
            // Clear URL params
            window.history.replaceState({}, '', window.location.pathname);
        }
    },

    // =========================================================================
    // API Fetch Methods
    // =========================================================================

    /**
     * Generic API fetch with CSRF token and error handling.
     * Supports both GET (default) and mutating requests (POST/PUT/DELETE).
     *
     * @param {string} url - The API endpoint URL.
     * @param {object} [options] - Optional fetch options (method, body, etc.).
     *   For GET requests, pass no options. For mutating requests, pass { method, body }.
     *   When options are provided, the response object is returned directly (not parsed).
     * @returns {Promise<object|Response>} Parsed JSON for GET, raw Response for mutations.
     */
    async apiFetch(url, options = null) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        // Mutating request (POST/PUT/DELETE) - return raw Response for caller to handle
        if (options) {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(options.headers || {}),
                },
                credentials: 'same-origin',
            });
            return response;
        }

        // GET request - parse JSON and throw on error (original behavior)
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            console.error(`API fetch failed: ${url}`, response.status);
            throw new Error(`HTTP ${response.status}`);
        }

        return response.json();
    },

    /**
     * Fetch all data needed for initial load.
     */
    async fetchAllData() {
        this.isLoading = true;
        try {
            await Promise.all([
                this.fetchCalendarData(),
                this.fetchAssistants(),
                this.fetchRemainingHours(),
                this.fetchAvailableYears(),
            ]);
        } catch (err) {
            console.error('Failed to fetch calendar data:', err);
        } finally {
            this.isLoading = false;
        }
    },

    /**
     * Fetch calendar view data (shifts + external events + calendar days).
     * Checks client-side cache first for instant rendering on repeated visits.
     */
    async fetchCalendarData() {
        const cacheKey = this._getCacheKey(this.view, this.currentYear, this.currentMonth, this.currentDay);

        // Cache hit: restore instantly, then prefetch neighbors in background
        if (this._isCacheValid(cacheKey)) {
            this._restoreFromCache(cacheKey);
            this._prefetchNeighbors();
            return;
        }

        // Cache miss: fetch from server
        try {
            const promises = [
                this.fetchShifts(),
                this.fetchExternalEvents(),
            ];

            // Only fetch calendar days structure for month view
            if (this.view === 'month') {
                promises.push(this.fetchCalendarDays());
            }

            await Promise.all(promises);

            // Store freshly fetched data in cache
            this._storeInCache(cacheKey, {
                shifts: this.shifts,
                shiftsByDate: this.shiftsByDate,
                externalEvents: this.externalEvents,
                externalEventsByDate: this.externalEventsByDate,
                calendarDays: this.view === 'month' ? this.calendarDays : null,
            });
        } catch (err) {
            console.error('Failed to fetch calendar data:', err);
        }

        // Prefetch neighbors after loading current view
        this._prefetchNeighbors();
    },

    /**
     * Fetch shifts from API.
     */
    async fetchShifts() {
        const params = new URLSearchParams({
            year: this.currentYear,
            month: this.currentMonth,
            view: this.view,
        });
        if (this.view !== 'month') {
            params.append('day', this.currentDay);
        }

        const data = await this.apiFetch(`/api/bpa/calendar/shifts?${params}`);
        this.shifts = data.shifts;
        this.shiftsByDate = data.shifts_by_date;
        this._clearOverlapCache();
    },

    /**
     * Fetch external events from API.
     */
    async fetchExternalEvents() {
        const params = new URLSearchParams({
            year: this.currentYear,
            month: this.currentMonth,
            view: this.view,
        });
        if (this.view !== 'month') {
            params.append('day', this.currentDay);
        }

        const data = await this.apiFetch(`/api/bpa/calendar/external-events?${params}`);
        this.externalEvents = data.events;
        this.externalEventsByDate = data.events_by_date;
        this._clearOverlapCache();
    },

    /**
     * Fetch assistants (cached - fetched once).
     */
    async fetchAssistants() {
        const data = await this.apiFetch('/api/bpa/calendar/assistants');
        this.assistants = data.assistants;
    },

    /**
     * Fetch remaining hours for the current year.
     */
    async fetchRemainingHours() {
        const data = await this.apiFetch(`/api/bpa/calendar/remaining-hours?year=${this.currentYear}`);
        this.remainingHours = data;
    },

    /**
     * Fetch available years for year selector.
     */
    async fetchAvailableYears() {
        const data = await this.apiFetch('/api/bpa/calendar/available-years');
        this.availableYears = data.years;
    },

    /**
     * Fetch calendar days structure (month grid with weeks).
     */
    async fetchCalendarDays() {
        const data = await this.apiFetch(`/api/bpa/calendar/days?year=${this.currentYear}&month=${this.currentMonth}`);
        this.calendarDays = data;
    },

    // =========================================================================
    // Data Helper Methods
    // =========================================================================

    /**
     * Get shifts for a specific date.
     */
    getShiftsForDate(date) {
        return this.shiftsByDate[date] || [];
    },

    /**
     * Get external events for a specific date.
     */
    getExternalEventsForDate(date) {
        return this.externalEventsByDate[date] || [];
    },

    /**
     * Get all-day shifts for a date.
     */
    getAllDayShiftsForDate(date) {
        return this.getShiftsForDate(date).filter(s => s.is_all_day);
    },

    /**
     * Get all-day external events for a date.
     */
    getAllDayExternalEventsForDate(date) {
        return this.getExternalEventsForDate(date).filter(e => e.is_all_day);
    },

    /**
     * Get timed (non-all-day) shifts that start at a specific hour.
     */
    getTimedShiftsForSlot(date, hour) {
        return this.getShiftsForDate(date).filter(s => {
            if (s.is_all_day) return false;
            const startHour = parseInt(s.start_time.split(':')[0], 10);
            return startHour === hour;
        });
    },

    /**
     * Get timed external events that start at a specific hour.
     */
    getTimedExternalEventsForSlot(date, hour) {
        return this.getExternalEventsForDate(date).filter(e => {
            if (e.is_all_day) return false;
            const startHour = parseInt(e.start_time.split(':')[0], 10);
            return startHour === hour;
        });
    },

    /**
     * Calculate overlap layout for events in a time slot.
     * Returns object with 'width' and 'left' percentages keyed by event ID.
     * Uses greedy column assignment algorithm.
     */
    calculateOverlapLayout(shifts, externalEvents) {
        const events = [];

        for (const shift of shifts) {
            const [sh, sm] = shift.start_time.split(':').map(Number);
            const [eh, em] = shift.end_time.split(':').map(Number);
            events.push({
                id: 'shift_' + shift.id,
                start: sh * 60 + sm,
                end: eh * 60 + em,
            });
        }

        for (const event of externalEvents) {
            const [sh, sm] = event.start_time.split(':').map(Number);
            const [eh, em] = event.end_time.split(':').map(Number);
            events.push({
                id: 'ext_' + event.id,
                start: sh * 60 + sm,
                end: eh * 60 + em,
            });
        }

        if (events.length === 0) return {};

        // Sort by start time
        events.sort((a, b) => a.start - b.start);

        // Greedy column assignment
        const columns = [];
        const layout = {};

        for (const event of events) {
            let column = 0;
            for (let colIndex = 0; colIndex < columns.length; colIndex++) {
                if (event.start >= columns[colIndex]) {
                    column = colIndex;
                    break;
                }
                column = colIndex + 1;
            }
            columns[column] = event.end;
            layout[event.id] = { column };
        }

        const maxColumns = columns.length;
        const width = 100 / maxColumns;

        const result = {};
        for (const [id, data] of Object.entries(layout)) {
            result[id] = {
                width,
                left: data.column * width,
            };
        }

        return result;
    },

    /**
     * Cache for overlap layout calculations to avoid redundant computation
     * within the same render cycle. Keyed by "date|hour".
     * @private
     */
    _overlapLayoutCache: {},

    /**
     * Get the cached overlap layout for a date+hour slot, computing if needed.
     * @private
     */
    _getOverlapLayoutForSlot(date, hour) {
        const key = date + '|' + hour;
        if (!this._overlapLayoutCache[key]) {
            const shifts = this.getTimedShiftsForSlot(date, hour);
            const events = this.getTimedExternalEventsForSlot(date, hour);
            this._overlapLayoutCache[key] = this.calculateOverlapLayout(shifts, events);
        }
        return this._overlapLayoutCache[key];
    },

    /**
     * Clear the overlap layout cache (called after data changes).
     * @private
     */
    _clearOverlapCache() {
        this._overlapLayoutCache = {};
    },

    /**
     * Get overlap layout for a specific shift in a slot.
     * Returns { width, left } percentages.
     */
    getShiftLayout(date, hour, shiftId) {
        const layout = this._getOverlapLayoutForSlot(date, hour);
        return layout['shift_' + shiftId] || { width: 100, left: 0 };
    },

    /**
     * Get overlap layout for a specific external event in a slot.
     * Returns { width, left } percentages.
     */
    getExternalEventLayout(date, hour, eventId) {
        const layout = this._getOverlapLayoutForSlot(date, hour);
        return layout['ext_' + eventId] || { width: 100, left: 0 };
    },

    /**
     * Get the start minute from a time string "HH:MM".
     */
    getStartMinute(timeStr) {
        return parseInt(timeStr.split(':')[1], 10);
    },

    /**
     * Calculate top percentage for a shift/event in a time slot.
     */
    getTopPercent(timeStr) {
        return (this.getStartMinute(timeStr) / 60) * 100;
    },

    /**
     * Calculate height percentage for a shift based on its duration.
     */
    getHeightPercent(durationMinutes) {
        return (durationMinutes / 60) * 100;
    },

    /**
     * Calculate duration in minutes between two time strings.
     */
    getDurationMinutes(startTime, endTime) {
        const [sh, sm] = startTime.split(':').map(Number);
        const [eh, em] = endTime.split(':').map(Number);
        return (eh * 60 + em) - (sh * 60 + sm);
    },

    /**
     * Check if a shift should be displayed on a specific date in month view.
     * Multi-day all-day absences are only shown on the first day or on Monday of new weeks.
     */
    shouldDisplayShift(shift, date) {
        if (!shift.is_unavailable || !shift.is_all_day) return true;

        const startDate = shift.starts_at.split('T')[0];
        const endDate = shift.ends_at.split('T')[0];

        // Single day absence
        if (startDate === endDate) return true;

        // Show on first day
        if (date === startDate) return true;

        // Show on Monday if absence started before this week
        const d = new Date(date);
        const dayOfWeek = (d.getDay() + 6) % 7; // Monday=0
        if (dayOfWeek === 0) {
            // It's a Monday - check if shift started before this week
            const weekStart = new Date(d);
            weekStart.setDate(d.getDate() - dayOfWeek);
            if (new Date(startDate) < weekStart) return true;
        }

        return false;
    },

    /**
     * Calculate how many columns a shift should span in month view.
     */
    getShiftColumnSpan(shift, date) {
        if (!shift.is_unavailable || !shift.is_all_day) return 1;

        const startDate = shift.starts_at.split('T')[0];
        const endDate = shift.ends_at.split('T')[0];

        if (startDate === endDate) return 1;

        const currentDate = new Date(date);
        const dayOfWeek = (currentDate.getDay() + 6) % 7;

        // Get week boundaries
        const weekStart = new Date(currentDate);
        weekStart.setDate(currentDate.getDate() - dayOfWeek);
        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekStart.getDate() + 6);

        const shiftStart = new Date(startDate);
        const shiftEnd = new Date(endDate);

        // Effective start
        const effectiveStart = shiftStart < weekStart ? weekStart : shiftStart;

        if (this._formatDate(currentDate) !== this._formatDate(effectiveStart)) return 1;

        // Effective end capped at week end
        const effectiveEnd = shiftEnd > weekEnd ? weekEnd : shiftEnd;

        const days = Math.round((effectiveEnd - effectiveStart) / (1000 * 60 * 60 * 24)) + 1;
        return Math.max(1, days);
    },

    /**
     * Get multi-day shifts for a specific week with positioning information.
     */
    getMultiDayShiftsForWeek(weekDays) {
        const multiDayShifts = [];
        const processedIds = new Set();

        for (let dayIndex = 0; dayIndex < weekDays.length; dayIndex++) {
            const day = weekDays[dayIndex];
            const dayShifts = this.getShiftsForDate(day.date);

            for (const shift of dayShifts) {
                if (processedIds.has(shift.id)) continue;
                if (!shift.is_unavailable || !shift.is_all_day) continue;

                const startDate = shift.starts_at.split('T')[0];
                const endDate = shift.ends_at.split('T')[0];
                if (startDate === endDate) continue;

                if (!this.shouldDisplayShift(shift, day.date)) continue;

                const columnSpan = this.getShiftColumnSpan(shift, day.date);
                if (columnSpan > 1) {
                    multiDayShifts.push({
                        shift,
                        startColumn: dayIndex + 2, // +2 for week number column
                        columnSpan,
                        startDate: day.date,
                    });
                    processedIds.add(shift.id);
                }
            }
        }

        // Assign rows to prevent overlaps
        for (let i = 0; i < multiDayShifts.length; i++) {
            multiDayShifts[i].row = this._findAvailableRow(multiDayShifts[i], multiDayShifts.slice(0, i));
        }

        return multiDayShifts;
    },

    /**
     * Get the maximum row count for multi-day shifts visible on a specific day index.
     */
    getMultiDayRowCountForDay(multiDayShifts, dayIndex) {
        let maxRow = 0;
        const column = dayIndex + 2;

        for (const ms of multiDayShifts) {
            const start = ms.startColumn;
            const end = start + ms.columnSpan - 1;
            if (column >= start && column <= end) {
                maxRow = Math.max(maxRow, ms.row);
            }
        }

        return maxRow;
    },

    // =========================================================================
    // Navigation
    // =========================================================================

    /**
     * Navigate to previous or next period.
     */
    async navigate(direction) {
        if (direction === 'prev') {
            if (this.view === 'day') {
                this._adjustDate(-1, 'day');
            } else if (this.view === 'week') {
                this._adjustDate(-7, 'day');
            } else {
                this._adjustDate(-1, 'month');
            }
        } else {
            if (this.view === 'day') {
                this._adjustDate(1, 'day');
            } else if (this.view === 'week') {
                this._adjustDate(7, 'day');
            } else {
                this._adjustDate(1, 'month');
            }
        }

        // Sync to Livewire for modal compatibility
        this._syncToLivewire();

        // Only show loading indicator on cache miss
        const cacheKey = this._getCacheKey(this.view, this.currentYear, this.currentMonth, this.currentDay);
        const hasCachedData = this._isCacheValid(cacheKey);
        if (!hasCachedData) {
            this.isNavigating = true;
        }

        try {
            await this.fetchCalendarData();
        } finally {
            this.isNavigating = false;
        }
    },

    /**
     * Go to today.
     */
    async goToToday() {
        const today = new Date();
        this.currentYear = today.getFullYear();
        this.currentMonth = today.getMonth() + 1;
        this.currentDay = today.getDate();

        this._syncToLivewire();
        if (!this._hasCurrentViewCached()) this.isNavigating = true;
        try {
            await this.fetchCalendarData();
        } finally {
            this.isNavigating = false;
        }
    },

    /**
     * Set view and refetch data.
     */
    async setView(newView) {
        this.view = newView;
        this._syncToLivewire();
        if (!this._hasCurrentViewCached()) this.isNavigating = true;
        try {
            await this.fetchCalendarData();
        } finally {
            this.isNavigating = false;
        }
    },

    /**
     * Go to a specific month (from month selector).
     */
    async goToMonth(month) {
        this.currentMonth = month;
        this._syncToLivewire();
        if (!this._hasCurrentViewCached()) this.isNavigating = true;
        try {
            await this.fetchCalendarData();
        } finally {
            this.isNavigating = false;
        }
    },

    /**
     * Go to a specific year (from year selector).
     */
    async goToYear(year) {
        this.currentYear = year;
        this._syncToLivewire();
        if (!this._hasCurrentViewCached()) this.isNavigating = true;
        try {
            await Promise.all([
                this.fetchCalendarData(),
                this.fetchRemainingHours(),
            ]);
        } finally {
            this.isNavigating = false;
        }
    },

    /**
     * Go to a specific day (from week header or month click).
     */
    async goToDay(dateStr) {
        const date = new Date(dateStr);
        this.currentYear = date.getFullYear();
        this.currentMonth = date.getMonth() + 1;
        this.currentDay = date.getDate();
        this.view = 'day';
        this._syncToLivewire();
        if (!this._hasCurrentViewCached()) this.isNavigating = true;
        try {
            await this.fetchCalendarData();
        } finally {
            this.isNavigating = false;
        }
    },

    /**
     * Go to a specific week (from week selector dropdown).
     */
    async goToWeek(dateStr) {
        const date = new Date(dateStr);
        this.currentYear = date.getFullYear();
        this.currentMonth = date.getMonth() + 1;
        this.currentDay = date.getDate();
        this.view = 'week';
        this._syncToLivewire();
        if (!this._hasCurrentViewCached()) this.isNavigating = true;
        try {
            await this.fetchCalendarData();
        } finally {
            this.isNavigating = false;
        }
    },

    // =========================================================================
    // Keyboard Shortcuts
    // =========================================================================

    handleKeydown(e) {
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;
        if (e.key === 'Escape' && this.$store.contextMenu.show) {
            this.$store.contextMenu.hide();
            return;
        }
        if (e.key === 'm' || e.key === 'M') { this.setView('month'); }
        if (e.key === 'u' || e.key === 'U') { this.setView('week'); }
        if (e.key === 'd' || e.key === 'D') { this.setView('day'); }
        if (e.key === 'ArrowLeft') { this.navigate('prev'); }
        if (e.key === 'ArrowRight') { this.navigate('next'); }
        if (e.key === 't' || e.key === 'T') { this.goToToday(); }
    },

    // =========================================================================
    // Swipe Navigation (mobile)
    // =========================================================================

    handleTouchStart(e) {
        if (e.touches.length !== 1) return;
        if (this.isCreatingShift || this.isSelectingDays ||
            this.resizingShift || this.draggedShift) return;

        this.swipeStartX = e.touches[0].clientX;
        this.swipeStartY = e.touches[0].clientY;
        this.swipeStartTime = Date.now();
        this.isSwiping = false;
    },

    handleTouchMove(e) {
        if (!this.swipeStartTime || this.isAnimatingSwipe) return;
        if (this.isCreatingShift || this.isSelectingDays ||
            this.resizingShift || this.draggedShift) {
            this.swipeStartTime = 0;
            return;
        }

        const deltaX = e.touches[0].clientX - this.swipeStartX;
        const deltaY = e.touches[0].clientY - this.swipeStartY;
        const elapsed = Date.now() - this.swipeStartTime;

        if (!this.isSwiping && elapsed < 150 && Math.abs(deltaX) > 30 && Math.abs(deltaX) > Math.abs(deltaY) * 1.5) {
            this.isSwiping = true;
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

        if (this.isSwiping) {
            this.swipeOffsetX = deltaX;
        }
    },

    handleTouchEnd(e) {
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

        const isValidSwipe = Math.abs(deltaX) > 75 &&
                            Math.abs(deltaX) > Math.abs(deltaY) * 1.5 &&
                            elapsed < 400;

        if (isValidSwipe || (this.isSwiping && Math.abs(this.swipeOffsetX) > 50)) {
            e.preventDefault();

            this.isAnimatingSwipe = true;
            const direction = deltaX > 0 ? 1 : -1;
            this.swipeOffsetX = direction * window.innerWidth;

            // Check if the target period is cached for instant rendering
            const targetPeriod = this._getSwipeTargetPeriod(direction);
            const targetCacheKey = targetPeriod
                ? this._getCacheKey(targetPeriod.view, targetPeriod.year, targetPeriod.month, targetPeriod.day)
                : null;
            const hasCachedData = targetCacheKey && this._isCacheValid(targetCacheKey);

            setTimeout(async () => {
                // Only show skeleton if data is not cached
                if (!hasCachedData) {
                    this.$store.swipeLoader.show();
                }
                await new Promise(resolve => requestAnimationFrame(resolve));

                if (direction > 0) {
                    await this.navigate('prev');
                } else {
                    await this.navigate('next');
                }

                this.$store.swipeLoader.hide();

                // Disable transition so we can jump instantly to the enter position
                this.isAnimatingSwipe = false;
                this.swipeOffsetX = -direction * 50;

                // Double rAF ensures the browser paints the jump before we animate
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        this.isAnimatingSwipe = true;
                        this.swipeOffsetX = 0;
                        setTimeout(() => {
                            this.isAnimatingSwipe = false;
                        }, 200);
                    });
                });
            }, hasCachedData ? 50 : 150);
        } else {
            this.swipeOffsetX = 0;
            this.resetSwipeState();
        }
    },

    resetSwipeState() {
        this.swipeStartX = 0;
        this.swipeStartY = 0;
        this.swipeStartTime = 0;
        this.isSwiping = false;
        this.isAnimatingSwipe = false;
    },

    /**
     * Navigate to previous period (used by swipe navigation).
     */
    navigatePrevious() {
        return this.navigate('prev');
    },

    /**
     * Navigate to next period (used by swipe navigation).
     */
    navigateNext() {
        return this.navigate('next');
    },

    /**
     * Navigate previous arrow button (instant UI update).
     */
    navigatePreviousArrow(type) {
        if (type === 'day') this._adjustDate(-1, 'day');
        else if (type === 'week') this._adjustDate(-7, 'day');
        else if (type === 'month') this._adjustDate(-1, 'month');

        this._syncToLivewire();
        if (!this._hasCurrentViewCached()) this.isNavigating = true;
        this.fetchCalendarData().finally(() => { this.isNavigating = false; });
    },

    /**
     * Navigate next arrow button (instant UI update).
     */
    navigateNextArrow(type) {
        if (type === 'day') this._adjustDate(1, 'day');
        else if (type === 'week') this._adjustDate(7, 'day');
        else if (type === 'month') this._adjustDate(1, 'month');

        this._syncToLivewire();
        if (!this._hasCurrentViewCached()) this.isNavigating = true;
        this.fetchCalendarData().finally(() => { this.isNavigating = false; });
    },

    // =========================================================================
    // Context Menu
    // =========================================================================

    showSlotContextMenu(e, date, time) {
        e.preventDefault();
        e.stopPropagation();
        const x = Math.min(e.clientX, window.innerWidth - 200);
        const y = Math.min(e.clientY, window.innerHeight - 250);
        this.$store.contextMenu.showSlot(x, y, date, time);
    },

    showShiftContextMenu(e, shiftId, isUnavailable = false) {
        e.preventDefault();
        e.stopPropagation();
        const x = Math.min(e.clientX, window.innerWidth - 200);
        const y = Math.min(e.clientY, window.innerHeight - 250);
        this.$store.contextMenu.showShift(x, y, shiftId, isUnavailable);
    },

    hideContextMenu() {
        this.$store.contextMenu.hide();
    },

    contextMenuAction(action) {
        const menu = this.contextMenu;
        if (menu.type === 'slot') {
            if (action === 'create') {
                this.openModal(menu.date, menu.time);
            } else if (action === 'unavailable') {
                this.openModal(menu.date, menu.time, null, null, true);
            }
        } else if (menu.type === 'shift') {
            if (action === 'edit') {
                this.editShift(menu.shiftId);
            } else if (action === 'duplicate') {
                this.duplicateShiftToModal(menu.shiftId);
            } else if (action === 'delete') {
                this.deleteShift(menu.shiftId);
            } else if (action === 'archive') {
                this.archiveShift(menu.shiftId);
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
        this.editShift(shiftId);
    },

    openQuickCreate(e, date, time, endTime = null) {
        if (this.createTimeout) {
            clearTimeout(this.createTimeout);
            this.createTimeout = null;
            this.createPending = false;
        }
        const x = Math.min(e.clientX, window.innerWidth - 280);
        const y = Math.min(e.clientY, window.innerHeight - 300);
        this.quickCreate.date = date;
        this.quickCreate.time = time;
        this.quickCreate.endTime = endTime || '';
        this.quickCreate.x = x;
        this.quickCreate.y = y;
        this.quickCreate.show = true;
    },

    closeQuickCreate() {
        this.quickCreate.show = false;
        this.quickCreate.endTime = '';
    },

    // =========================================================================
    // Drag-to-create shift
    // =========================================================================

    startCreate(e, date, time, slotElement) {
        if (e.target.closest('[data-shift]')) return;
        if (e.button !== 0) return;

        if (this.createTimeout) clearTimeout(this.createTimeout);
        if (this._createMoveHandler) document.removeEventListener('mousemove', this._createMoveHandler);
        if (this._createUpHandler) document.removeEventListener('mouseup', this._createUpHandler);

        this.isCreatingShift = false;
        this.createPending = true;
        this.createTimeout = null;
        this.createSessionId++;
        this.createDate = date;
        this.createStartTime = time;
        this.createEndTime = time;
        this.createStartY = e.clientY;

        const slot = slotElement || e.target.closest('[data-slot-height]');
        if (slot) {
            const rect = slot.getBoundingClientRect();
            this.createStartSlotTop = rect.top;
            this.createSlotHeight = rect.height;
        }

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

        const sessionId = this.createSessionId;
        const component = this;
        this.createTimeout = setTimeout(() => {
            if (component.createSessionId === sessionId && component.createPending) {
                component.isCreatingShift = true;
                component.$nextTick(() => {});
            }
            component.createTimeout = null;
        }, 150);
    },

    updateCreate(e) {
        if (!this.isCreatingShift) return;

        const [startH, startM] = this.createStartTime.split(':').map(Number);
        const startMinutes = startH * 60 + startM;
        const minutesPerPixel = 60 / this.createSlotHeight;
        const diffY = e.clientY - this.createStartY;
        const diffMinutes = Math.round((diffY * minutesPerPixel) / 15) * 15;
        const endMinutes = Math.max(startMinutes + 15, startMinutes + diffMinutes);
        const clampedEndMinutes = Math.min(endMinutes, 24 * 60);
        const endH = Math.floor(clampedEndMinutes / 60);
        const endM = clampedEndMinutes % 60;
        this.createEndTime = `${String(endH).padStart(2, '0')}:${String(endM).padStart(2, '0')}`;
    },

    endCreate(e) {
        if (this.createTimeout) {
            clearTimeout(this.createTimeout);
            this.createTimeout = null;
        }

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

        if (slotHour < startH || slotHour > endH) return null;
        if (slotHour === endH && endM === 0 && slotHour !== startH) return null;

        const startMinutes = startH * 60 + startM;
        const endMinutes = endH * 60 + endM;
        const slotStartMinutes = slotHour * 60;

        let topPercent = 0;
        if (slotHour === startH) {
            topPercent = (startM / 60) * 100;
        }

        let heightPercent = 100 - topPercent;
        if (slotHour === endH || (slotHour < endH && endMinutes <= (slotHour + 1) * 60)) {
            const effectiveEndInSlot = Math.min(endMinutes, (slotHour + 1) * 60) - slotStartMinutes;
            heightPercent = (effectiveEndInSlot / 60) * 100 - topPercent;
        }

        const isFirst = slotHour === startH;
        const isLast = slotHour === endH || (slotHour < endH && endMinutes <= (slotHour + 1) * 60);

        return { top: topPercent, height: heightPercent, isFirst, isLast };
    },

    // =========================================================================
    // Day selection (month view) - for creating multi-day absences
    // =========================================================================

    startSelectDays(e, date) {
        if (e.target.closest('[data-shift]') || e.target.closest('[data-event]')) return;
        if (e.button !== 0) return;

        if (this.selectTimeout) clearTimeout(this.selectTimeout);
        if (this._selectMoveHandler) document.removeEventListener('mouseenter', this._selectMoveHandler, true);
        if (this._selectUpHandler) document.removeEventListener('mouseup', this._selectUpHandler);

        this.isSelectingDays = false;
        this.selectPending = true;
        this.selectTimeout = null;
        this.selectSessionId++;
        this.selectStartDate = date;
        this.selectEndDate = date;

        this._selectUpHandler = (upE) => {
            this.endSelectDays(upE);
            document.removeEventListener('mouseup', this._selectUpHandler);
            this._selectUpHandler = null;
        };

        document.addEventListener('mouseup', this._selectUpHandler);

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
        if (this.selectTimeout) {
            clearTimeout(this.selectTimeout);
            this.selectTimeout = null;
        }

        if (!this.isSelectingDays) {
            this.selectPending = false;
            this.selectStartDate = null;
            this.selectEndDate = null;
            return;
        }

        const start = this.selectStartDate;
        const end = this.selectEndDate;
        const [fromDate, toDate] = start <= end ? [start, end] : [end, start];

        const x = Math.min(e.clientX, window.innerWidth - 320);
        const y = Math.min(e.clientY, window.innerHeight - 200);

        this.$store.absencePopup.open(x, y, fromDate, toDate);

        this.isSelectingDays = false;
        this.selectPending = false;
        this.selectStartDate = null;
        this.selectEndDate = null;
    },

    isDateSelected(date) {
        if (!this.isSelectingDays || !this.selectStartDate || !this.selectEndDate) return false;
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

        let data = {};
        try {
            const raw = e.dataTransfer.getData('text/plain');
            if (raw) data = JSON.parse(raw);
        } catch (err) {
            return;
        }

        let preciseTime = time;
        if (time) {
            const slot = e.target.closest('[data-slot-height]');
            if (slot) {
                const rect = slot.getBoundingClientRect();
                const relativeY = e.clientY - rect.top;
                const percentInSlot = relativeY / rect.height;
                const quarterInSlot = Math.floor(percentInSlot * 4);
                const [hour] = time.split(':').map(Number);
                const minutes = Math.min(quarterInSlot, 3) * 15;
                preciseTime = `${String(hour).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
            }
        }

        if (data.type === 'assistant') {
            this.createShiftFromDrag(data.id, date, preciseTime);
        } else if (data.type === 'shift') {
            if (e.ctrlKey) {
                this.duplicateShiftApi(data.id, date);
            } else {
                this.moveShiftApi(data.id, date, preciseTime);
            }
        }

        this.draggedAssistant = null;
        this.draggedShift = null;
    },

    allowDrop(e, time = null, date = null) {
        e.preventDefault();
        if (date) this.dragOverDate = date;

        if (this.draggedShift && time && this.draggedShiftDuration) {
            let preciseTime = time;
            let quarterInSlot = 0;
            const slot = e.target.closest('[data-slot-height]');
            if (slot) {
                const rect = slot.getBoundingClientRect();
                const relativeY = e.clientY - rect.top;
                const percentInSlot = relativeY / rect.height;
                quarterInSlot = Math.min(Math.floor(percentInSlot * 4), 3);
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

            this.dragPreviewX = e.clientX;
            this.dragPreviewY = e.clientY;
            this.dragOverSlot = time;
            this.dragQuarter = quarterInSlot;
        } else if (time) {
            this.dragOverSlot = time;
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
            if (this.resizingShift) {
                const diff = upE.clientY - this.resizeStartY;
                const newMinutes = Math.max(15, Math.round((this.resizeStartHeight + diff * minutesPerPixel) / 15) * 15);
                this.resizeShiftApi(this.resizingShift, newMinutes);
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
        if (!this.modal.form.from_date || !this.modal.form.to_date) return '';
        if (this.modal.form.is_all_day) return 'Hele dagen';

        const [fromH, fromM] = (this.modal.form.from_time || '00:00').split(':').map(Number);
        const [toH, toM] = (this.modal.form.to_time || '00:00').split(':').map(Number);

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

    // =========================================================================
    // Client-side data cache
    // =========================================================================

    /**
     * Generate a cache key for a given view + date.
     */
    _getCacheKey(view, year, month, day) {
        if (view === 'month') return `month-${year}-${month}`;
        if (view === 'week') return `week-${year}-${month}-${day}`;
        return `day-${year}-${month}-${day}`;
    },

    /**
     * Check if a cache entry exists and is still valid (within TTL).
     */
    _isCacheValid(key) {
        const cached = this._dataCache[key];
        if (!cached) return false;
        return (Date.now() - cached.fetchedAt) < this._cacheTtlMs;
    },

    /**
     * Store current view data in the cache.
     */
    _storeInCache(key, data) {
        this._dataCache[key] = {
            ...data,
            fetchedAt: Date.now(),
        };
        this._evictOldEntries();
    },

    /**
     * Restore view data from a cache entry.
     */
    _restoreFromCache(key) {
        const cached = this._dataCache[key];
        if (!cached) return;

        this.shifts = cached.shifts;
        this.shiftsByDate = cached.shiftsByDate;
        this.externalEvents = cached.externalEvents;
        this.externalEventsByDate = cached.externalEventsByDate;
        if (cached.calendarDays) {
            this.calendarDays = cached.calendarDays;
        }
        this._clearOverlapCache();
    },

    /**
     * Evict oldest cache entries when exceeding max size (LRU).
     */
    _evictOldEntries() {
        const keys = Object.keys(this._dataCache);
        if (keys.length <= this._maxCacheEntries) return;

        // Sort by fetchedAt ascending (oldest first)
        keys.sort((a, b) => this._dataCache[a].fetchedAt - this._dataCache[b].fetchedAt);

        // Remove oldest entries until we are within limit
        while (keys.length > this._maxCacheEntries) {
            const oldest = keys.shift();
            delete this._dataCache[oldest];
        }
    },

    /**
     * Check if the current view period has valid cached data.
     */
    _hasCurrentViewCached() {
        const cacheKey = this._getCacheKey(this.view, this.currentYear, this.currentMonth, this.currentDay);
        return this._isCacheValid(cacheKey);
    },

    /**
     * Invalidate all cached data. Called after CRUD operations.
     */
    _invalidateCache() {
        this._dataCache = {};
        this._clearOverlapCache();
    },

    /**
     * Get the neighbor periods (previous + next) for the current view.
     */
    _getNeighborPeriods() {
        const periods = [];

        if (this.view === 'month') {
            let prevMonth = this.currentMonth - 1;
            let prevYear = this.currentYear;
            if (prevMonth < 1) { prevMonth = 12; prevYear--; }
            periods.push({ view: 'month', year: prevYear, month: prevMonth, day: 1 });

            let nextMonth = this.currentMonth + 1;
            let nextYear = this.currentYear;
            if (nextMonth > 12) { nextMonth = 1; nextYear++; }
            periods.push({ view: 'month', year: nextYear, month: nextMonth, day: 1 });
        }

        if (this.view === 'week') {
            const current = new Date(this.currentYear, this.currentMonth - 1, this.currentDay);

            const prevWeek = new Date(current);
            prevWeek.setDate(current.getDate() - 7);
            periods.push({
                view: 'week',
                year: prevWeek.getFullYear(),
                month: prevWeek.getMonth() + 1,
                day: prevWeek.getDate(),
            });

            const nextWeek = new Date(current);
            nextWeek.setDate(current.getDate() + 7);
            periods.push({
                view: 'week',
                year: nextWeek.getFullYear(),
                month: nextWeek.getMonth() + 1,
                day: nextWeek.getDate(),
            });
        }

        if (this.view === 'day') {
            const current = new Date(this.currentYear, this.currentMonth - 1, this.currentDay);

            const prevDay = new Date(current);
            prevDay.setDate(current.getDate() - 1);
            periods.push({
                view: 'day',
                year: prevDay.getFullYear(),
                month: prevDay.getMonth() + 1,
                day: prevDay.getDate(),
            });

            const nextDay = new Date(current);
            nextDay.setDate(current.getDate() + 1);
            periods.push({
                view: 'day',
                year: nextDay.getFullYear(),
                month: nextDay.getMonth() + 1,
                day: nextDay.getDate(),
            });
        }

        return periods;
    },

    /**
     * Prefetch adjacent periods in the background (no loading indicators).
     * Uses setTimeout to avoid blocking the main thread.
     */
    async _prefetchNeighbors() {
        if (this._prefetchInProgress) return;
        this._prefetchInProgress = true;

        try {
            const neighbors = this._getNeighborPeriods();

            for (const neighbor of neighbors) {
                const cacheKey = this._getCacheKey(neighbor.view, neighbor.year, neighbor.month, neighbor.day);
                if (this._isCacheValid(cacheKey)) continue;

                // Yield to the browser before each background fetch
                await new Promise(resolve => setTimeout(resolve, 50));
                await this._fetchAndCache(neighbor);
            }
        } catch (e) {
            // Silently fail prefetch - not critical
        } finally {
            this._prefetchInProgress = false;
        }
    },

    /**
     * Fetch data for a specific period and store in cache (no UI updates).
     */
    async _fetchAndCache(period) {
        try {
            const params = new URLSearchParams({
                year: period.year,
                month: period.month,
                view: period.view,
            });
            if (period.view !== 'month') {
                params.append('day', period.day);
            }

            const [shiftsData, eventsData] = await Promise.all([
                this.apiFetch(`/api/bpa/calendar/shifts?${params}`),
                this.apiFetch(`/api/bpa/calendar/external-events?${params}`),
            ]);

            let calendarDays = null;
            if (period.view === 'month') {
                calendarDays = await this.apiFetch(
                    `/api/bpa/calendar/days?year=${period.year}&month=${period.month}`
                );
            }

            const cacheKey = this._getCacheKey(period.view, period.year, period.month, period.day);
            this._storeInCache(cacheKey, {
                shifts: shiftsData.shifts,
                shiftsByDate: shiftsData.shifts_by_date,
                externalEvents: eventsData.events,
                externalEventsByDate: eventsData.events_by_date,
                calendarDays,
            });
        } catch (e) {
            // Silently fail - prefetch is not critical
        }
    },

    // =========================================================================
    // Private helper methods
    // =========================================================================

    /**
     * Adjust current date by an amount (days or months).
     */
    _adjustDate(amount, unit) {
        const date = new Date(this.currentYear, this.currentMonth - 1, this.currentDay);

        if (unit === 'day') {
            date.setDate(date.getDate() + amount);
        } else if (unit === 'month') {
            date.setDate(1); // Avoid overflow when going to shorter months
            date.setMonth(date.getMonth() + amount);
        }

        this.currentYear = date.getFullYear();
        this.currentMonth = date.getMonth() + 1;
        this.currentDay = date.getDate();
    },

    /**
     * Sync navigation state to Livewire component for server-side rendering.
     */
    _syncToLivewire() {
        if (!this.$wire) return;
        this.$wire.year = this.currentYear;
        this.$wire.month = this.currentMonth;
        this.$wire.day = this.currentDay;
        this.$wire.view = this.view;
    },

    /**
     * Show a toast notification via the global toast event system.
     */
    _showToast(message, type = 'success') {
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { message, type }
        }));
    },

    /**
     * Format a Date object as YYYY-MM-DD string.
     */
    _formatDate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    },

    /**
     * Get today's date as YYYY-MM-DD string.
     */
    _todayString() {
        return this._formatDate(new Date());
    },

    /**
     * Get ISO week number for a date.
     */
    _getISOWeekNumber(date) {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    },

    /**
     * Calculate the target period after a swipe. Direction: 1 = prev, -1 = next.
     */
    _getSwipeTargetPeriod(direction) {
        const date = new Date(this.currentYear, this.currentMonth - 1, this.currentDay);

        if (this.view === 'day') {
            date.setDate(date.getDate() + (direction > 0 ? -1 : 1));
        } else if (this.view === 'week') {
            date.setDate(date.getDate() + (direction > 0 ? -7 : 7));
        } else {
            date.setDate(1);
            date.setMonth(date.getMonth() + (direction > 0 ? -1 : 1));
        }

        return {
            view: this.view,
            year: date.getFullYear(),
            month: date.getMonth() + 1,
            day: date.getDate(),
        };
    },

    /**
     * Find the first available row for a multi-day shift (no overlaps).
     */
    _findAvailableRow(newShift, existingShifts) {
        if (existingShifts.length === 0) return 1;

        const newStart = newShift.startColumn;
        const newEnd = newStart + newShift.columnSpan - 1;

        const occupiedRows = {};
        for (const existing of existingShifts) {
            const existingStart = existing.startColumn;
            const existingEnd = existingStart + existing.columnSpan - 1;
            if (!(newEnd < existingStart || newStart > existingEnd)) {
                occupiedRows[existing.row] = true;
            }
        }

        let row = 1;
        while (occupiedRows[row]) row++;
        return row;
    },

    // =========================================================================
    // Modal Methods (shift create/edit)
    // =========================================================================

    /**
     * Open modal for creating a new shift.
     */
    openModal(date = null, time = null, assistantId = null, endTime = null, isUnavailable = false) {
        this.modal.isEditing = false;
        this.modal.editingShiftId = null;
        this.modal.errors = {};
        this.modal.isSubmitting = false;
        this.modal.isExistingRecurring = false;

        const today = this._todayString();
        this.modal.form = {
            assistant_id: assistantId || '',
            from_date: date || today,
            from_time: time || '08:00',
            to_date: date || today,
            to_time: endTime || (time ? this._addHourToTime(time) : '16:00'),
            is_unavailable: isUnavailable || false,
            is_all_day: false,
            note: '',
            is_recurring: false,
            recurring_interval: 'weekly',
            recurring_end_type: 'count',
            recurring_count: 4,
            recurring_end_date: '',
            _recurring_scope: null,
        };
        this.modal.show = true;
    },

    /**
     * Add 1 hour to a time string "HH:MM", capping at 23:XX.
     */
    _addHourToTime(time) {
        const parts = time.split(':');
        const endHour = Math.min(parseInt(parts[0], 10) + 1, 23);
        return String(endHour).padStart(2, '0') + ':' + (parts[1] || '00');
    },

    /**
     * Open modal for editing an existing shift.
     */
    editShift(shiftId) {
        const shift = this._findShift(shiftId);
        if (!shift) return;

        // Check if recurring - show dialog first
        if (shift.is_recurring) {
            this.recurringDialog.shiftId = shiftId;
            this.recurringDialog.action = 'edit';
            this.recurringDialog.show = true;
            return;
        }

        this._populateFormFromShift(shift);
        this.modal.isEditing = true;
        this.modal.editingShiftId = shiftId;
        this.modal.isExistingRecurring = false;
        this.modal.errors = {};
        this.modal.show = true;
    },

    /**
     * Open modal with shift data pre-filled for duplication (new shift).
     */
    duplicateShiftToModal(shiftId) {
        const shift = this._findShift(shiftId);
        if (!shift) return;

        this._populateFormFromShift(shift);
        this.modal.isEditing = false;
        this.modal.editingShiftId = null;
        this.modal.isExistingRecurring = false;
        this.modal.errors = {};
        this.modal.show = true;
    },

    /**
     * Populate modal form from shift data object.
     */
    _populateFormFromShift(shift) {
        this.modal.form = {
            assistant_id: shift.assistant_id || '',
            from_date: shift.date || (shift.starts_at ? shift.starts_at.split('T')[0] : ''),
            from_time: shift.start_time || '08:00',
            to_date: shift.ends_at ? shift.ends_at.split('T')[0] : (shift.date || ''),
            to_time: shift.end_time || '16:00',
            is_unavailable: shift.is_unavailable || false,
            is_all_day: shift.is_all_day || false,
            note: shift.note || '',
            is_recurring: false,
            recurring_interval: 'weekly',
            recurring_end_type: 'count',
            recurring_count: 4,
            recurring_end_date: '',
            _recurring_scope: null,
        };
    },

    /**
     * Close the modal and reset state.
     */
    closeModal() {
        this.modal.show = false;
        this.modal.isEditing = false;
        this.modal.editingShiftId = null;
        this.modal.errors = {};
        this.modal.isSubmitting = false;
        this.modal.isExistingRecurring = false;
    },

    /**
     * Save shift (create or update) via API.
     */
    async saveShift(createAnother = false) {
        this.modal.isSubmitting = true;
        this.modal.errors = {};

        try {
            const payload = { ...this.modal.form };

            // Add scope for recurring edits
            if (this.modal.isEditing && payload._recurring_scope) {
                payload.scope = payload._recurring_scope;
            }
            delete payload._recurring_scope;

            const url = this.modal.isEditing
                ? `/api/bpa/shifts/${this.modal.editingShiftId}`
                : '/api/bpa/shifts';
            const method = this.modal.isEditing ? 'PUT' : 'POST';

            const response = await this.apiFetch(url, {
                method,
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok) {
                if (response.status === 422) {
                    this.modal.errors = data.errors || {};
                    return;
                }
                this._showToast(data.message || 'Noe gikk galt', 'error');
                return;
            }

            this._showToast(data.message, 'success');
            this._invalidateCache();
            await Promise.all([
                this.fetchCalendarData(),
                this.fetchRemainingHours(),
            ]);

            if (createAnother) {
                const keepDate = this.modal.form.from_date;
                this.openModal(keepDate);
            } else {
                this.closeModal();
            }
        } catch (error) {
            this._showToast('Nettverksfeil', 'error');
        } finally {
            this.modal.isSubmitting = false;
        }
    },

    /**
     * Delete shift - check recurring, then execute.
     */
    async deleteShift(shiftId) {
        const shift = this._findShift(shiftId);
        if (!shift) return;

        if (shift.is_recurring) {
            this.recurringDialog.shiftId = shiftId;
            this.recurringDialog.action = 'delete';
            this.recurringDialog.show = true;
            return;
        }

        if (!confirm('Er du sikker på at du vil slette denne oppføringen permanent?')) return;
        await this._executeDelete(shiftId, 'single', 'delete');
    },

    /**
     * Archive shift (soft delete) - check recurring, then execute.
     */
    async archiveShift(shiftId) {
        const shift = this._findShift(shiftId);
        if (!shift) return;

        if (shift.is_recurring) {
            this.recurringDialog.shiftId = shiftId;
            this.recurringDialog.action = 'archive';
            this.recurringDialog.show = true;
            return;
        }

        if (!confirm('Er du sikker på at du vil arkivere denne oppføringen?')) return;
        await this._executeDelete(shiftId, 'single', 'archive');
    },

    /**
     * Execute delete/archive via API.
     */
    async _executeDelete(shiftId, scope, type) {
        try {
            const response = await this.apiFetch(`/api/bpa/shifts/${shiftId}?scope=${scope}&type=${type}`, {
                method: 'DELETE',
            });
            const data = await response.json();

            if (response.ok) {
                this._showToast(data.message, 'success');
                this._invalidateCache();
                await Promise.all([
                    this.fetchCalendarData(),
                    this.fetchRemainingHours(),
                ]);
                this.closeModal();
            } else {
                this._showToast(data.message || 'Kunne ikke slette', 'error');
            }
        } catch (error) {
            this._showToast('Nettverksfeil', 'error');
        }
    },

    /**
     * Confirm a recurring dialog action (edit/delete/archive/move).
     */
    async confirmRecurring(scope) {
        const { shiftId, action } = this.recurringDialog;
        this.recurringDialog.show = false;

        if (action === 'edit') {
            const shift = this._findShift(shiftId);
            if (shift) {
                this._populateFormFromShift(shift);
                this.modal.isEditing = true;
                this.modal.editingShiftId = shiftId;
                this.modal.isExistingRecurring = true;
                this.modal.form._recurring_scope = scope;
                this.modal.errors = {};
                this.modal.show = true;
            }
        } else if (action === 'delete') {
            await this._executeDelete(shiftId, scope, 'delete');
        } else if (action === 'archive') {
            await this._executeDelete(shiftId, scope, 'archive');
        } else if (action === 'move') {
            const moveData = this.recurringDialog._moveData;
            if (moveData) {
                await this._executeMove(shiftId, moveData.newDate, moveData.newTime, scope);
            }
        }
    },

    /**
     * Duplicate a shift via API.
     */
    async duplicateShiftApi(shiftId, targetDate = null) {
        try {
            const body = targetDate ? { target_date: targetDate } : {};
            const response = await this.apiFetch(`/api/bpa/shifts/${shiftId}/duplicate`, {
                method: 'POST',
                body: JSON.stringify(body),
            });
            const data = await response.json();

            if (response.ok) {
                this._showToast(data.message, 'success');
                this._invalidateCache();
                await Promise.all([
                    this.fetchCalendarData(),
                    this.fetchRemainingHours(),
                ]);
            } else {
                this._showToast(data.message || 'Kunne ikke duplisere', 'error');
            }
        } catch (error) {
            this._showToast('Nettverksfeil', 'error');
        }
    },

    /**
     * Move a shift via API (from drag & drop).
     */
    async moveShiftApi(shiftId, newDate, newTime) {
        const shift = this._findShift(shiftId);

        if (shift && shift.is_recurring) {
            this.recurringDialog.shiftId = shiftId;
            this.recurringDialog.action = 'move';
            this.recurringDialog._moveData = { newDate, newTime };
            this.recurringDialog.show = true;
            return;
        }

        await this._executeMove(shiftId, newDate, newTime, 'single');
    },

    /**
     * Execute move via API.
     */
    async _executeMove(shiftId, newDate, newTime, scope) {
        try {
            const response = await this.apiFetch(`/api/bpa/shifts/${shiftId}/move`, {
                method: 'POST',
                body: JSON.stringify({ new_date: newDate, new_time: newTime, scope }),
            });
            const data = await response.json();

            if (response.ok) {
                this._showToast(data.message, 'success');
                this._invalidateCache();
                await this.fetchCalendarData();
            } else {
                this._showToast(data.message || 'Kunne ikke flytte', 'error');
            }
        } catch (error) {
            this._showToast('Nettverksfeil', 'error');
        }
    },

    /**
     * Resize a shift via API.
     */
    async resizeShiftApi(shiftId, durationMinutes) {
        try {
            const response = await this.apiFetch(`/api/bpa/shifts/${shiftId}/resize`, {
                method: 'POST',
                body: JSON.stringify({ duration_minutes: durationMinutes }),
            });
            const data = await response.json();

            if (response.ok) {
                this._showToast(data.message, 'success');
                this._invalidateCache();
                await Promise.all([
                    this.fetchCalendarData(),
                    this.fetchRemainingHours(),
                ]);
            } else {
                this._showToast(data.message || 'Kunne ikke endre varighet', 'error');
            }
        } catch (error) {
            this._showToast('Nettverksfeil', 'error');
        }
    },

    /**
     * Quick create a shift via API.
     */
    async quickCreateShift(assistantId) {
        try {
            const response = await this.apiFetch('/api/bpa/shifts/quick-create', {
                method: 'POST',
                body: JSON.stringify({
                    assistant_id: assistantId,
                    date: this.quickCreate.date,
                    time: this.quickCreate.time,
                    end_time: this.quickCreate.endTime || null,
                }),
            });
            const data = await response.json();

            if (response.ok) {
                this._showToast(data.message, 'success');
                this.closeQuickCreate();
                this._invalidateCache();
                await Promise.all([
                    this.fetchCalendarData(),
                    this.fetchRemainingHours(),
                ]);
            } else {
                this._showToast(data.message || 'Kunne ikke opprette', 'error');
                this.closeQuickCreate();
            }
        } catch (error) {
            this._showToast('Nettverksfeil', 'error');
            this.closeQuickCreate();
        }
    },

    /**
     * Create shift from sidebar drag & drop - opens modal with pre-filled data.
     */
    createShiftFromDrag(assistantId, date, time = null) {
        const startTime = time || '08:00';
        const endTime = this._addHourToTime(this._addHourToTime(this._addHourToTime(startTime)));
        this.openModal(date, startTime, assistantId, endTime);
    },

    /**
     * Create an all-day absence from multi-day selection in month view.
     */
    async createAbsenceFromSelection(assistantId, fromDate, toDate) {
        try {
            const response = await this.apiFetch('/api/bpa/shifts', {
                method: 'POST',
                body: JSON.stringify({
                    assistant_id: assistantId,
                    from_date: fromDate,
                    to_date: toDate,
                    is_unavailable: true,
                    is_all_day: true,
                }),
            });
            const data = await response.json();

            if (response.ok) {
                this._showToast(data.message, 'success');
                this._invalidateCache();
                await this.fetchCalendarData();
            } else {
                this._showToast(data.message || 'Kunne ikke opprette fravar', 'error');
            }
        } catch (error) {
            this._showToast('Nettverksfeil', 'error');
        }
    },

    /**
     * Find a shift in the current shifts data by ID.
     */
    _findShift(shiftId) {
        return this.shifts.find(s => s.id === shiftId) || null;
    },

    /**
     * Compute recurring preview dates client-side for the modal.
     */
    getRecurringPreviewDates() {
        const form = this.modal.form;
        if (!form.is_recurring || !form.from_date) return [];

        const dates = [];
        let startDate = new Date(form.from_date + 'T00:00:00');

        const maxCount = form.recurring_end_type === 'count'
            ? (parseInt(form.recurring_count, 10) || 4)
            : 52;

        const endDate = (form.recurring_end_type === 'date' && form.recurring_end_date)
            ? new Date(form.recurring_end_date + 'T00:00:00')
            : null;

        let current = new Date(startDate);

        for (let i = 0; i < maxCount; i++) {
            if (endDate && current > endDate) break;
            dates.push(this._formatDate(current));

            if (form.recurring_interval === 'biweekly') {
                current = new Date(current);
                current.setDate(current.getDate() + 14);
            } else if (form.recurring_interval === 'monthly') {
                current = new Date(current);
                current.setMonth(current.getMonth() + 1);
                // Keep original day if possible
                const targetDay = Math.min(startDate.getDate(), new Date(current.getFullYear(), current.getMonth() + 1, 0).getDate());
                current.setDate(targetDay);
            } else {
                // weekly
                current = new Date(current);
                current.setDate(current.getDate() + 7);
            }
        }

        return dates;
    },

    /**
     * Format a date string for Norwegian display (dd.mm).
     */
    formatDateShort(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        return String(d.getDate()).padStart(2, '0') + '.' + String(d.getMonth() + 1).padStart(2, '0');
    },
});
