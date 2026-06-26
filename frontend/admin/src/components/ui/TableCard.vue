<script setup lang="ts">
defineProps<{
    title: string
    action_label?: string
}>()

const emit = defineEmits<{
    action: []
}>()
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ title }}</h3>
            <button
                v-if="action_label"
                class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
                @click="emit('action')"
            >
                {{ action_label }}
            </button>
        </div>
        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <slot />
        </div>
    </div>
</template>
