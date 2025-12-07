import './bootstrap';
import ApexCharts from 'apexcharts';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import sort from '@alpinejs/sort';

// Alpine components
import calendar from './alpine/calendar';
import { monthlyHoursChart, percentageChart } from './alpine/bpa-charts';

// Make ApexCharts available globally
window.ApexCharts = ApexCharts;

// Make BPA chart factories available globally for Blade templates
window.monthlyHoursChart = monthlyHoursChart;
window.percentageChart = percentageChart;

// Register Alpine sort plugin
Alpine.plugin(sort);

// Register Alpine store for context menu (persists across Livewire re-renders)
Alpine.store('contextMenu', {
    show: false,
    x: 0,
    y: 0,
    type: null, // 'slot' or 'shift'
    shiftId: null,
    date: null,
    time: null,
    isUnavailable: false,

    showSlot(x, y, date, time) {
        this.show = true;
        this.x = x;
        this.y = y;
        this.type = 'slot';
        this.shiftId = null;
        this.date = date;
        this.time = time;
        this.isUnavailable = false;
    },

    showShift(x, y, shiftId, isUnavailable = false) {
        this.show = true;
        this.x = x;
        this.y = y;
        this.type = 'shift';
        this.shiftId = shiftId;
        this.date = null;
        this.time = null;
        this.isUnavailable = isUnavailable;
    },

    hide() {
        this.show = false;
    },

    // Execute action and call Livewire component
    action(actionName) {
        const calendarEl = document.querySelector('[x-data^="calendar"]');
        const wireEl = calendarEl?.closest('[wire\\:id]');
        const wireId = wireEl?.getAttribute('wire:id');

        if (!wireId) {
            console.error('Context menu: Could not find Livewire component');
            this.hide();
            return;
        }

        const wire = Livewire.find(wireId);

        if (this.type === 'slot') {
            if (actionName === 'create') {
                wire.call('openModal', this.date, this.time, null, null, false);
            } else if (actionName === 'unavailable') {
                wire.call('openModal', this.date, this.time, null, null, true);
            }
        } else if (this.type === 'shift') {
            if (actionName === 'edit') {
                wire.call('editShift', this.shiftId);
            } else if (actionName === 'duplicate') {
                wire.call('duplicateShiftWithModal', this.shiftId);
            } else if (actionName === 'delete') {
                wire.call('deleteShift', this.shiftId);
            } else if (actionName === 'archive') {
                wire.call('archiveShift', this.shiftId);
            }
        }

        this.hide();
    }
});

// Register Alpine components
Alpine.data('calendar', calendar);

// Start Livewire
Livewire.start();
