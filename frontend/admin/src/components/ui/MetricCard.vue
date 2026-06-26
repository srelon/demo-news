<script setup lang="ts">
defineProps<{
    label: string
    value: string | number
    growth?: number
}>()

function growth_class(val: number) {
    return val >= 0
        ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500'
        : 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500'
}

function growth_label(val: number) {
    return (val >= 0 ? '+' : '') + val + '%'
}
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <div class="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-xl dark:bg-gray-800">
            <slot name="icon" />
        </div>
        <div class="flex items-end justify-between mt-5">
            <div>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ label }}</span>
                <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">
                    {{ typeof value === 'number' ? value.toLocaleString() : value }}
                </h4>
            </div>
            <span
                v-if="growth !== undefined"
                class="flex items-center gap-1 rounded-full py-0.5 pl-2 pr-2.5 text-sm font-medium"
                :class="growth_class(growth)"
            >
                {{ growth_label(growth) }}
            </span>
        </div>
    </div>
</template>
