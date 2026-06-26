<script setup lang="ts">
import { COMMENT_SORTS } from '@/composables/useAdminComments'
import type { CommentSort } from '@/composables/useAdminComments'

const model = defineModel<CommentSort>({
    required: true,
})
</script>

<template>
    <div class="flex gap-1">
        <button
            v-for="s in COMMENT_SORTS"
            :key="s[0]"
            @click="model = s[0]"
            class="rounded-lg px-3 py-1.5 text-sm transition-colors"
            :class="model === s[0]
                ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white'
                : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
        >{{ s[1] }}</button>
    </div>
</template>
