<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import BasePagination from '@/components/ui/base/BasePagination.vue'
import { useAdminNotificationStore } from '@/stores/notifications'
import type { AdminNotification } from '@/stores/notifications'

const router = useRouter()

const notifStore = useAdminNotificationStore()

const notifications = ref<AdminNotification[]>([])
const pagination = ref<any>(null)
const is_loading = ref(true)

function notifLabel(n: AdminNotification): string {
    if (n.type === 'reply') return `${n.data.from_name} replied to a comment`
    if (n.type === 'like') return `${n.data.from_name} liked a comment`
    if (n.type === 'dislike') return `${n.data.from_name} disliked a comment`
    return n.data.message ?? 'Notification'
}

function load(page = 1) {
    is_loading.value = true
    notifStore.fetchAll(page).then((res: any) => {
        const data = res.data.data
        notifications.value = data.notifications
        pagination.value = data.pagination
    }).finally(() => { is_loading.value = false })
}

function goTo(n: AdminNotification) {
    if (!n.article_id) return
    const query: Record<string, any> = { tab: 'comments' }
    if (n.comment_id) query.comment = n.comment_id
    if (n.parent_id) query.parent_id = n.parent_id
    router.push({ name: 'article', params: { id: n.article_id }, query })
}

watch(
    () => notifStore.notifications,
    (store_items) => {
        store_items.forEach((store_item) => {
            const local = notifications.value.find((n) => n.id === store_item.id)
            if (local) {
                local.read_at = store_item.read_at
            } else if (pagination.value?.current_page === 1) {
                notifications.value.unshift({ ...store_item })
            }
        })
    },
    { deep: true },
)

onMounted(() => {
    notifStore.unread_count = 0
    load()
})
</script>

<template>
    <AdminLayout>
        <PageBreadcrumb page-title="Notifications" />

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Moderator Notifications</h3>
            </div>

            <div v-if="is_loading" class="py-16">
                <BaseLoading />
            </div>

            <div v-else-if="!notifications.length" class="py-16 text-center text-sm text-gray-400">
                No notifications yet
            </div>

            <div v-else>
                <div
                    v-for="n in notifications"
                    :key="n.id"
                    class="flex cursor-pointer items-start gap-4 border-b border-gray-100 px-6 py-4 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]"
                    :class="{ 'bg-orange-50 dark:bg-orange-900/10': !n.read_at }"
                    @click="goTo(n)"
                >
                    <span
                        class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                        :class="{
                            'bg-blue-100 text-blue-600 dark:bg-blue-900/30': n.type === 'reply',
                            'bg-green-100 text-green-600 dark:bg-green-900/30': n.type === 'like',
                            'bg-red-100 text-red-600 dark:bg-red-900/30': n.type === 'dislike',
                            'bg-gray-100 text-gray-600 dark:bg-gray-700': !['reply','like','dislike'].includes(n.type),
                        }"
                    >
                        <svg v-if="n.type === 'reply'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        <svg v-else-if="n.type === 'like'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg>
                        <svg v-else-if="n.type === 'dislike'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 011.789 1.106L17 4m-7 10v2a2 2 0 002 2h.095c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"/></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </span>

                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ notifLabel(n) }}</p>
                        <div class="mt-1 flex items-center gap-2 text-xs text-gray-400">
                            <span v-if="n.moderator_user">as {{ n.moderator_user.name }}</span>
                            <span v-if="n.moderator_user">·</span>
                            <span>{{ new Date(n.created_at).toLocaleDateString('en', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
                        </div>
                    </div>

                    <span v-if="!n.read_at" class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full bg-orange-500"></span>
                </div>

                <div v-if="pagination?.last_page > 1" class="px-6 py-4">
                    <BasePagination :pagination="pagination" @change="load" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
