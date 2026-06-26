<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAdminNotificationStore } from '@/stores/notifications'
import type { AdminNotification } from '@/stores/notifications'
import IconBell from '@/components/icons/IconBell.vue'
import IconClose from '@/components/icons/IconClose.vue'
import IconReply from '@/components/icons/IconReply.vue'
import IconThumbUp from '@/components/icons/IconThumbUp.vue'
import IconThumbDown from '@/components/icons/IconThumbDown.vue'
import IconBellOutline from '@/components/icons/IconBellOutline.vue'
import IconAlertTriangle from '@/components/icons/IconAlertTriangle.vue'

const router = useRouter()
const auth = useAuthStore()
const notifStore = useAdminNotificationStore()

const can_see_moderator = computed(() => auth.accesses('moderator', 'view'))
const can_see_debug = computed(() => auth.accesses('debug', 'view'))
const is_visible = computed(() => can_see_moderator.value || can_see_debug.value)

const active_tab = ref<'moderator' | 'debug'>('moderator')
const open = ref(false)
const loading = ref(false)
const wrap = ref<HTMLDivElement | null>(null)

function notifLabel(n: AdminNotification): string {
    if (n.type === 'reply') return `${n.data.from_name} replied to a comment`
    if (n.type === 'like') return `${n.data.from_name} liked a comment`
    if (n.type === 'dislike') return `${n.data.from_name} disliked a comment`
    return n.data.message ?? 'Notification'
}

function toggle() {
    if (open.value) {
        open.value = false
        return
    }

    if (!can_see_moderator.value && can_see_debug.value) {
        active_tab.value = 'debug'
    }

    open.value = true

    if (can_see_moderator.value) {
        loading.value = true
        notifStore.fetchRecent().finally(() => { loading.value = false })
    }
}

function goToNotification(n: AdminNotification) {
    open.value = false
    if (!n.article_id) return
    const query: Record<string, any> = { tab: 'comments' }
    if (n.comment_id) query.comment = n.comment_id
    if (n.parent_id) query.parent_id = n.parent_id
    router.push({ name: 'article', params: { id: n.article_id }, query })
}

function goToAll() {
    open.value = false
    router.push({ name: 'notifications' })
}

function goToDebug() {
    open.value = false
    router.push({ name: 'debug' })
}

function onClickOutside(e: MouseEvent) {
    if (open.value && !wrap.value?.contains(e.target as Node)) {
        open.value = false
    }
}

onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))
</script>

<template>
    <div v-if="is_visible" ref="wrap" class="relative">
        <button
            class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
            @click="toggle"
        >
            <span
                v-if="notifStore.total_unread > 0"
                class="absolute -right-1 -top-1 z-10 flex h-5 min-w-5 items-center justify-center rounded-full bg-orange-500 px-1 text-xs font-bold text-white"
            >
                {{ notifStore.total_unread > 99 ? '99+' : notifStore.total_unread }}
            </span>
            <IconBell width="20" height="20" />
        </button>

        <div
            v-if="open"
            class="absolute -right-[240px] mt-[17px] flex h-[480px] w-[350px] flex-col rounded-2xl border border-gray-200 bg-white shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark sm:w-[361px] lg:right-0"
        >
            <!-- Tabs header -->
            <div class="flex items-center justify-between px-4 pt-4 pb-0">
                <div class="flex gap-1">
                    <button
                        v-if="can_see_moderator"
                        :class="active_tab === 'moderator'
                            ? 'rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-semibold text-gray-800 dark:bg-white/[0.05] dark:text-white'
                            : 'rounded-lg px-3 py-1.5 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                        @click="active_tab = 'moderator'"
                    >
                        Moderator
                        <span
                            v-if="notifStore.unread_count > 0"
                            class="ml-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-orange-500 px-1 text-xs font-bold text-white"
                        >{{ notifStore.unread_count > 99 ? '99+' : notifStore.unread_count }}</span>
                    </button>
                    <button
                        v-if="can_see_debug"
                        :class="active_tab === 'debug'
                            ? 'rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-semibold text-gray-800 dark:bg-white/[0.05] dark:text-white'
                            : 'rounded-lg px-3 py-1.5 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                        @click="active_tab = 'debug'"
                    >
                        Debug
                        <span
                            v-if="notifStore.debug_unread_count > 0"
                            class="ml-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-xs font-bold text-white"
                        >{{ notifStore.debug_unread_count > 99 ? '99+' : notifStore.debug_unread_count }}</span>
                    </button>
                </div>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <IconClose width="18" height="18" />
                </button>
            </div>

            <div class="mx-4 mt-3 h-px bg-gray-100 dark:bg-gray-800"></div>

            <!-- Moderator tab content -->
            <template v-if="active_tab === 'moderator'">
                <div v-if="loading" class="flex flex-1 items-center justify-center">
                    <span class="h-6 w-6 animate-spin rounded-full border-2 border-gray-200 border-t-orange-500"></span>
                </div>
                <div v-else-if="!notifStore.notifications.length" class="flex flex-1 items-center justify-center text-sm text-gray-400 dark:text-gray-500">
                    No notifications
                </div>
                <ul v-else class="flex flex-1 flex-col overflow-y-auto">
                    <li
                        v-for="n in notifStore.notifications"
                        :key="n.id"
                        class="cursor-pointer border-b border-gray-100 px-4 py-3 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.03]"
                        :class="{ 'bg-orange-50 dark:bg-orange-900/10': !n.read_at }"
                        @click="goToNotification(n)"
                    >
                        <div class="flex items-start gap-3">
                            <span
                                class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs"
                                :class="{
                                    'bg-blue-100 text-blue-600 dark:bg-blue-900/30': n.type === 'reply',
                                    'bg-green-100 text-green-600 dark:bg-green-900/30': n.type === 'like',
                                    'bg-red-100 text-red-600 dark:bg-red-900/30': n.type === 'dislike',
                                    'bg-gray-100 text-gray-600 dark:bg-gray-700': !['reply','like','dislike'].includes(n.type),
                                }"
                            >
                                <IconReply v-if="n.type === 'reply'" class="h-4 w-4" />
                                <IconThumbUp v-else-if="n.type === 'like'" class="h-4 w-4" />
                                <IconThumbDown v-else-if="n.type === 'dislike'" class="h-4 w-4" />
                                <IconBellOutline v-else class="h-4 w-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-800 dark:text-white/90">{{ notifLabel(n) }}</p>
                                <div class="mt-0.5 flex items-center gap-1.5 text-xs text-gray-400">
                                    <span v-if="n.moderator_user">as {{ n.moderator_user.name }}</span>
                                    <span v-if="n.moderator_user">·</span>
                                    <span>{{ new Date(n.created_at).toLocaleDateString('en', { day: 'numeric', month: 'short' }) }}</span>
                                </div>
                            </div>
                            <span v-if="!n.read_at" class="mt-2 h-2 w-2 shrink-0 rounded-full bg-orange-500"></span>
                        </div>
                    </li>
                </ul>
                <div class="border-t border-gray-100 p-3 dark:border-gray-800">
                    <button
                        class="w-full rounded-lg border border-gray-200 bg-white py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
                        @click="goToAll"
                    >
                        View all notifications
                    </button>
                </div>
            </template>

            <!-- Debug tab content -->
            <template v-else-if="active_tab === 'debug'">
                <div class="flex flex-1 flex-col items-center justify-center gap-4 px-6 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20">
                        <IconAlertTriangle class="h-7 w-7 text-red-500" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <template v-if="notifStore.debug_unread_count > 0">
                                {{ notifStore.debug_unread_count }} new error{{ notifStore.debug_unread_count > 1 ? 's' : '' }} in logs
                            </template>
                            <template v-else>
                                No new errors
                            </template>
                        </p>
                        <p class="mt-1 text-xs text-gray-400">Open the debug page to view log details</p>
                    </div>
                    <button
                        class="rounded-lg bg-red-500 px-5 py-2 text-sm font-medium text-white hover:bg-red-600"
                        @click="goToDebug"
                    >
                        Open Debug
                    </button>
                </div>
            </template>
        </div>
    </div>
</template>
