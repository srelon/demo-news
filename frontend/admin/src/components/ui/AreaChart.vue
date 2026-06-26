<script setup lang="ts">
import { computed } from 'vue'
import type { ApexOptions } from 'apexcharts'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps<{
    title: string
    subtitle?: string
    labels: string[]
    series: { name: string; data: number[] }[]
    colors?: string[]
}>()

const chart_options = computed((): ApexOptions => ({
    legend: {
        show: true,
        position: 'top',
        horizontalAlign: 'left',
        fontFamily: 'Outfit',
        markers: {},
    },
    colors: props.colors ?? ['#465FFF', '#17b978', '#f59e0b'],
    chart: {
        fontFamily: 'Outfit, sans-serif',
        type: 'area',
        toolbar: { show: false },
    },
    fill: {
        type: 'gradient',
        gradient: {
            opacityFrom: 0.45,
            opacityTo: 0,
        },
    },
    stroke: {
        curve: 'smooth',
        width: props.series.map(() => 2),
    },
    markers: { size: 0 },
    grid: {
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } },
    },
    dataLabels: { enabled: false },
    xaxis: {
        type: 'category',
        categories: props.labels,
        axisBorder: { show: false },
        axisTicks: { show: false },
        tooltip: { enabled: false },
    },
    yaxis: { title: { style: { fontSize: '0px' } } },
    tooltip: { x: { show: true } },
}))
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ title }}</h3>
            <p v-if="subtitle" class="mt-1 text-gray-500 text-theme-sm dark:text-gray-400">{{ subtitle }}</p>
        </div>
        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <div class="-ml-4 min-w-[650px] xl:min-w-full pl-2">
                <VueApexCharts type="area" height="310" :options="chart_options" :series="series" />
            </div>
        </div>
    </div>
</template>
