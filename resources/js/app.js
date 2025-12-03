import './bootstrap';
import ApexCharts from 'apexcharts';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import sort from '@alpinejs/sort';

// Make ApexCharts available globally
window.ApexCharts = ApexCharts;

// Register Alpine sort plugin
Alpine.plugin(sort);

// Start Livewire
Livewire.start();
