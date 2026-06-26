<script setup lang="ts">
import { formatCommentDate } from '@/composables/useAdminComments'
import type { AdminCommentUser } from './types'

defineProps<{
    user: AdminCommentUser | null
    created_at: string
    deleted?: boolean
    small?: boolean
    reply?: boolean
}>()

const base_url = import.meta.env.VITE_API_BASE_URL
</script>

<template>
    <div class="flex items-center gap-2.5 min-w-0" :class="{ 'opacity-50': deleted }">
        <div
            class="rounded-full overflow-hidden bg-gray-100 dark:bg-gray-800 shrink-0"
            :class="small ? 'w-7 h-7' : 'w-9 h-9'"
        >
            <img v-if="user?.img" :src="base_url + user.img" :alt="user.name" class="w-full h-full object-cover" />
        </div>
        <div class="min-w-0">
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-sm font-semibold text-gray-800 dark:text-white">{{ user?.name ?? 'Deleted user' }}</span>
                <span v-if="user?.username" class="text-xs text-brand-500">@{{ user.username }}</span>
                <span v-if="user?.is_moderator" class="rounded bg-green-100 px-1.5 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">moderator</span>
                <span v-if="reply" class="rounded bg-blue-100 px-1.5 py-0.5 text-xs font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">reply</span>
                <span v-if="deleted" class="rounded bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-600 dark:bg-red-900/30 dark:text-red-400">deleted</span>
            </div>
            <span class="text-xs text-gray-400">{{ formatCommentDate(created_at) }}</span>
        </div>
    </div>
</template>
