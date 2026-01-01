/**
 * Economy Page Chart - Lazy-loaded ApexCharts component
 *
 * Creates an expandable chart with fullscreen modal support.
 * Uses dynamic import to lazy-load ApexCharts only when needed.
 */

import { loadApexCharts } from './bpa-charts.js';

/**
 * Creates an economy chart Alpine component with lazy-loaded ApexCharts
 * @param {Object} chartConfig - ApexCharts configuration object
 * @returns {Object} Alpine component data object
 */
export function economyChart(chartConfig) {
    return {
        expanded: false,
        loading: false,
        chart: null,
        fullChart: null,
        chartConfig,

        async renderChart() {
            if (this.loading) return; // Prevent concurrent calls
            if (this.chart) this.chart.destroy();
            this.loading = true;
            try {
                const ApexCharts = await loadApexCharts();
                const isMobile = window.innerWidth < 640;
                this.chart = new ApexCharts(
                    this.$refs.chart,
                    { ...this.chartConfig, chart: { ...this.chartConfig.chart, height: isMobile ? 280 : 320 } }
                );
                this.chart.render();
            } finally {
                this.loading = false;
            }
        },

        getFullChartDimensions() {
            const height = Math.max(400, Math.floor(window.innerHeight * 0.90) - 140);
            const width = Math.max(600, Math.floor(window.innerWidth * 0.90) - 80);
            return { height, width };
        },

        async renderFullChart() {
            if (this.fullChart) this.fullChart.destroy();
            this.$nextTick(async () => {
                const ApexCharts = await loadApexCharts();
                const { height, width } = this.getFullChartDimensions();
                this.fullChart = new ApexCharts(
                    this.$refs.fullChart,
                    { ...this.chartConfig, chart: { ...this.chartConfig.chart, height, width } }
                );
                this.fullChart.render();
            });
        },

        openExpanded() {
            this.expanded = true;
            this.renderFullChart();
            document.body.style.overflow = 'hidden';
        },

        closeExpanded() {
            this.expanded = false;
            if (this.fullChart) this.fullChart.destroy();
            document.body.style.overflow = '';
        },

        handleResize() {
            if (this.expanded) this.renderFullChart();
        },

        init() {
            this.$nextTick(async () => await this.renderChart());
        }
    };
}

export default { economyChart };
