<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/plugins/axios'
import { useNotificationStore } from '@/stores/notification'
import type { AppNotification } from '@/stores/notification'
import BasePagination from '@/components/ui/base/BasePagination.vue'
import { useNotificationActions } from '@/composables/useNotificationActions'

const route = useRoute()
const notifStore = useNotificationStore()
const { notifLabel, goTo, onHoverNotif } = useNotificationActions()

const notifications = ref<AppNotification[]>([])
const pagination = ref<any>(null)
const is_loading = ref(true)

function load(page = 1) {
    is_loading.value = true
    axios.get('notifications/all', { params: { page } }).then((res) => {
        const data = res.data.data
        notifications.value = data.notifications
        pagination.value = data.pagination
    }).finally(() => { is_loading.value = false })
}

// Sync WS-driven updates (new notifications, read status) into the local list
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
    load(Number(route.query.page) || 1)
})
</script>

<template>
    <section class="bg0 p-b-100 p-t-30">
        <div class="container">
            <h3 class="f1-l-4 cl3 p-b-30">Notifications</h3>

            <div v-if="is_loading" class="np-loading">
                <span class="np-spinner"></span>
            </div>

            <div v-else-if="!notifications.length" class="np-empty">
                No notifications yet
            </div>

            <div v-else>
                <div
                    v-for="n in notifications"
                    :key="n.id"
                    class="np-item"
                    :class="{ 'np-item--unread': !n.read_at }"
                    @click="goTo(n)"
                    @mouseenter="onHoverNotif(n)"
                >
                    <div class="np-icon" :class="`np-icon--${n.type}`">
                        <i class="fa" :class="{
                            'fa-reply': n.type === 'reply',
                            'fa-thumbs-up': n.type === 'like',
                            'fa-thumbs-down': n.type === 'dislike',
                            'fa-info-circle': n.type === 'system',
                        }"></i>
                    </div>
                    <div class="np-body">
                        <p class="np-text">{{ notifLabel(n) }}</p>
                        <span class="np-date">{{ new Date(n.created_at).toLocaleDateString('en', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
                    </div>
                    <div v-if="!n.read_at" class="np-unread-dot"></div>
                </div>

                <div v-if="pagination?.last_page > 1" class="p-t-20">
                    <BasePagination
                        :current_page="pagination.current_page"
                        :last_page="pagination.last_page"
                        @page-change="load"
                    />
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.np-loading, .np-empty {
    padding: 60px 0;
    text-align: center;
    color: #aaa;
    font-size: 15px;
}

.np-spinner {
    width: 28px;
    height: 28px;
    border: 3px solid #e0e0e0;
    border-top-color: #17b978;
    border-radius: 50%;
    animation: np-spin 0.7s linear infinite;
    display: inline-block;
}

@keyframes np-spin { to { transform: rotate(360deg); } }

.np-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 8px;
    cursor: pointer;
    border: 1px solid #f0f0f0;
    background: #fff;
    transition: box-shadow 0.15s;
}

.np-item:hover { box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
.np-item--unread { background: #f0fbf7; border-color: #c8edd9; }

.np-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    background: #eee;
    color: #888;
}

.np-icon--reply { background: #e8f5e9; color: #17b978; }
.np-icon--like { background: #e3f2fd; color: #2196f3; }
.np-icon--dislike { background: #fce4ec; color: #e74c3c; }
.np-icon--system { background: #fff8e1; color: #ff9800; }

.np-body { flex: 1; }

.np-text {
    font-size: 14px;
    color: #333;
    margin: 0 0 4px;
    font-weight: 500;
}

.np-date { font-size: 12px; color: #aaa; }

.np-unread-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #17b978;
    flex-shrink: 0;
}
</style>
