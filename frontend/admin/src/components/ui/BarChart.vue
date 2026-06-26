<script setup lang="ts">
import { computed } from 'vue'
import type { ApexOptions } from 'apexcharts'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps<{
    title: string
    labels: (string | number)[]
    series: number[]
    tooltip_suffix?: string
}>()

const chart_series = computed(() => [{ name: props.title, data: props.series }])

const chart_options = computed((): ApexOptions => ({
    colors: ['#465fff'],
    chart: {
        fontFamily: 'Outfit, sans-serif',
        type: 'bar',
        toolbar: { show: false },
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '39%',
            borderRadius: 4,
            borderRadiusApplication: 'end',
        },
    },
    dataLabels: { enabled: false },
    stroke: { show: true, width: 4, colors: ['transparent'] },
    xaxis: {
        categories: props.labels,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { fontSize: '11px' } },
    },
    yaxis: { title: { text: '' } },
    legend: { show: false },
    grid: { yaxis: { lines: { show: true } } },
    fill: { opacity: 1 },
    tooltip: {
        y: { formatter: (val: number) => val + (props.tooltip_suffix ?? '') },
    },
}))
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ title }}</h3>
        </div>
        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <div class="-ml-5 min-w-[650px] xl:min-w-full pl-2">
                <VueApexCharts type="bar" height="180" :options="chart_options" :series="chart_series" />
            </div>
        </div>
    </div>
</template>
