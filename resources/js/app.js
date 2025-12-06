import './bootstrap';
import ApexCharts from 'apexcharts';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import sort from '@alpinejs/sort';

// Alpine components
import calendar from './alpine/calendar';

// Make ApexCharts available globally
window.ApexCharts = ApexCharts;

// Register Alpine sort plugin
Alpine.plugin(sort);

// Register Alpine components
Alpine.data('calendar', calendar);

// Start Livewire
Livewire.start();
