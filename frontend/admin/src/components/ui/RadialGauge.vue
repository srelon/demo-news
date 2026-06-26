<script setup lang="ts">
import { computed } from 'vue'
import type { ApexOptions } from 'apexcharts'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps<{
    title: string
    subtitle?: string
    series: number[]
    badge?: string
    badge_type?: 'success' | 'warning'
    center_text?: string
    stats?: { label: string; value: string | number }[]
}>()

const clamped_series = computed(() => [Math.min(props.series[0] ?? 0, 100)])

const chart_options: ApexOptions = {
    colors: ['#465FFF'],
    chart: {
        fontFamily: 'Outfit, sans-serif',
        sparkline: { enabled: true },
    },
    plotOptions: {
        radialBar: {
            startAngle: -90,
            endAngle: 90,
            hollow: { size: '80%' },
            track: {
                background: '#E4E7EC',
                strokeWidth: '100%',
                margin: 5,
            },
            dataLabels: {
                name: { show: false },
                value: { show: false },
            },
        },
    },
    fill: { type: 'solid', colors: ['#465FFF'] },
    stroke: { lineCap: 'round' },
    labels: [''],
}
</script>

<template>
    <div class="h-full rounded-2xl border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 pt-5 bg-white shadow-default rounded-2xl pb-11 dark:bg-gray-900 sm:px-6 sm:pt-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ title }}</h3>
                <p v-if="subtitle" class="mt-1 text-gray-500 text-theme-sm dark:text-gray-400">{{ subtitle }}</p>
            </div>
            <div class="relative max-h-[195px]">
                <div class="radial-bar-chart">
                    <VueApexCharts type="radialBar" height="330" :options="chart_options" :series="clamped_series" />
                </div>
                <span
                    v-if="badge"
                    class="absolute left-1/2 top-[85%] -translate-x-1/2 -translate-y-[85%] rounded-full px-3 py-1 text-xs font-medium"
                    :class="badge_type === 'success'
                        ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500'
                        : 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400'"
                >
                    {{ badge }}
                </span>
            </div>
            <p v-if="center_text" class="mx-auto mt-1.5 w-full max-w-[380px] text-center text-sm text-gray-500 sm:text-base">
                {{ center_text }}
            </p>
        </div>

        <div v-if="stats?.length" class="flex items-center justify-center gap-5 px-6 py-3.5 sm:gap-8 sm:py-5">
            <template v-for="(stat, i) in stats" :key="i">
                <div v-if="i > 0" class="w-px bg-gray-200 h-7 dark:bg-gray-800" />
                <div>
                    <p class="mb-1 text-center text-gray-500 text-theme-xs dark:text-gray-400 sm:text-sm">{{ stat.label }}</p>
                    <p class="text-base font-semibold text-center text-gray-800 dark:text-white/90 sm:text-lg">{{ stat.value }}</p>
                </div>
            </template>
        </div>
    </div>
</template>

<style scoped>
.radial-bar-chart {
    width: 100%;
    max-width: 330px;
    margin: 0 auto;
}
</style>
