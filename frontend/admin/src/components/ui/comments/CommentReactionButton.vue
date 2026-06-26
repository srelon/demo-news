<script setup lang="ts">
import { computed } from 'vue'
import ThumbDownIcon from '@/icons/ThumbDownIcon.vue'
import ThumbUpIcon from '@/icons/ThumbUpIcon.vue'

const props = defineProps<{
    type: 'like' | 'dislike'
    count: number
    active?: boolean
    disabled?: boolean
    readonly?: boolean
}>()

const emit = defineEmits<{
    (e: 'click'): void
}>()

const icon = computed(() => props.type === 'like' ? ThumbUpIcon : ThumbDownIcon)

const color_class = computed(() => {
    if (props.readonly) {
        return 'border-gray-200 text-gray-500 dark:border-gray-700'
    }

    if (props.active) {
        return props.type === 'like'
            ? 'border-brand-400 bg-brand-50 text-brand-500 dark:border-brand-600 dark:bg-brand-500/10'
            : 'border-red-400 bg-red-50 text-red-500 dark:border-red-600 dark:bg-red-500/10'
    }

    return props.type === 'like'
        ? 'border-gray-200 text-gray-500 hover:border-brand-300 hover:text-brand-500 dark:border-gray-700 disabled:opacity-50 disabled:cursor-default'
        : 'border-gray-200 text-gray-500 hover:border-red-300 hover:text-red-500 dark:border-gray-700 disabled:opacity-50 disabled:cursor-default'
})
</script>

<template>
    <component
        :is="readonly ? 'span' : 'button'"
        :disabled="readonly ? undefined : disabled"
        class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs transition-colors"
        :class="color_class"
        @click="!readonly && emit('click')"
    >
        <component :is="icon" class="w-3.5 h-3.5" />
        {{ count }}
    </component>
</template>
