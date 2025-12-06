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

// Register Alpine components
Alpine.data('calendar', calendar);

// Start Livewire
Livewire.start();
