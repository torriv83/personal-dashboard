/**
 * BPA Dashboard Charts - Expandable ApexCharts component
 *
 * Creates an expandable chart with fullscreen modal support.
 * Reusable for both monthly hours area chart and percentage bar chart.
 */

/**
 * Cached promise for ApexCharts module to avoid multiple imports
 * @type {Promise<typeof import('apexcharts').default>|null}
 */
let apexChartsPromise = null;

/**
 * Dynamically imports ApexCharts and caches the result.
 * Subsequent calls return the cached promise to avoid multiple imports.
 * @returns {Promise<typeof import('apexcharts').default>} The ApexCharts constructor
 */
export async function loadApexCharts() {
    if (!apexChartsPromise) {
        apexChartsPromise = import('apexcharts').then(module => module.default);
    }
    return apexChartsPromise;
}

/**
 * Creates a monthly hours area chart configuration
 * @param {number[]} currentYearData - Current year monthly values
 * @param {number[]} previousYearData - Previous year monthly values
 * @param {number} currentYear - Current year number
 * @param {number} previousYear - Previous year number
 */
export function monthlyHoursChart(currentYearData, previousYearData, currentYear, previousYear) {
    return expandableChart({
        chart: {
            type: 'area',
            background: 'transparent',
            toolbar: { show: false },
            zoom: { enabled: false },
        },
        series: [
            { name: String(currentYear), data: currentYearData },
            { name: String(previousYear), data: previousYearData }
        ],
        colors: ['#c8ff00', '#f97316'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.2,
            }
        },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: {
            categories: ['jan', 'feb', 'mar', 'apr', 'mai', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'des'],
            labels: { style: { colors: '#a3a3a3', fontSize: '12px' } },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            labels: { style: { colors: '#a3a3a3', fontSize: '12px' } },
        },
        grid: {
            borderColor: '#333',
            strokeDashArray: 3,
        },
        legend: {
            position: 'bottom',
            labels: { colors: '#a3a3a3' },
            markers: { radius: 2 },
        },
        tooltip: {
            theme: 'dark',
            x: { show: true },
        },
        dataLabels: { enabled: false },
    });
}

/**
 * Creates a percentage bar chart configuration
 * @param {number[]} prevYearPercent - Previous year percentage values
 * @param {number[]} currYearPercent - Current year percentage values
 * @param {number[]} remainingPercent - Remaining percentage values
 * @param {number} currentYear - Current year number
 * @param {number} previousYear - Previous year number
 */
export function percentageChart(prevYearPercent, currYearPercent, remainingPercent, currentYear, previousYear) {
    return expandableChart({
        chart: {
            type: 'bar',
            background: 'transparent',
            toolbar: { show: false },
            zoom: { enabled: false },
        },
        series: [
            { name: String(previousYear), type: 'bar', data: prevYearPercent },
            { name: String(currentYear), type: 'bar', data: currYearPercent },
            { name: 'Gjenstår', type: 'line', data: remainingPercent }
        ],
        colors: ['#666666', '#3b82f6', '#c8ff00'],
        plotOptions: {
            bar: {
                columnWidth: '70%',
                borderRadius: 2,
            }
        },
        stroke: {
            width: [0, 0, 2],
            curve: 'smooth',
        },
        xaxis: {
            categories: ['jan', 'feb', 'mar', 'apr', 'mai', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'des'],
            labels: { style: { colors: '#a3a3a3', fontSize: '12px' } },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            max: 100,
            labels: {
                style: { colors: '#a3a3a3', fontSize: '12px' },
                formatter: (val) => val + '%'
            },
        },
        grid: {
            borderColor: '#333',
            strokeDashArray: 3,
        },
        legend: {
            position: 'bottom',
            labels: { colors: '#a3a3a3' },
            markers: { radius: 2 },
        },
        tooltip: {
            shared: true,
            intersect: false,
            theme: 'dark',
            y: { formatter: (val) => val + '%' }
        },
        dataLabels: { enabled: false },
    });
}

/**
 * Base expandable chart component
 * @param {Object} chartConfig - ApexCharts configuration
 */
export function expandableChart(chartConfig) {
    return {
        expanded: false,
        loading: false,
        chart: null,
        fullChart: null,
        chartConfig,

        async renderChart() {
            if (this.chart) this.chart.destroy();
            this.loading = true;
            try {
                const ApexCharts = await loadApexCharts();
                this.chart = new ApexCharts(
                    this.$refs.chart,
                    { ...this.chartConfig, chart: { ...this.chartConfig.chart, height: 200 } }
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
            this.$nextTick(() => this.renderChart());
        }
    };
}

export default {
    loadApexCharts,
    monthlyHoursChart,
    percentageChart,
    expandableChart
};
