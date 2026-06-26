<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notification'
import type { AppNotification } from '@/stores/notification'
import { useNotificationActions } from '@/composables/useNotificationActions'

const router = useRouter()
const notifStore = useNotificationStore()
const { notifLabel, goTo, onHoverNotif } = useNotificationActions()

const open = ref(false)
const loading = ref(false)
const wrap = ref<HTMLDivElement | null>(null)

function toggle() {
    if (open.value) { open.value = false; return }
    open.value = true
    notifStore.unread_count = 0
    loading.value = true
    notifStore.fetchNotifications().finally(() => { loading.value = false })
}

function goToNotif(n: AppNotification) {
    goTo(n, () => { open.value = false })
}

function goToAll() {
    open.value = false
    router.push({ name: 'notifications' })
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
    <div ref="wrap" class="nb-wrap">
        <button class="nb-btn" @click.stop="toggle" title="Notifications">
            <i class="fa fa-bell"></i>
            <span v-if="notifStore.unread_count > 0" class="nb-badge">
                {{ notifStore.unread_count > 99 ? '99+' : notifStore.unread_count }}
            </span>
        </button>

        <div v-if="open" class="nb-dropdown">
            <div class="nb-head">
                <span class="nb-head-title">Notifications</span>
            </div>

            <div v-if="loading" class="nb-loading">
                <span class="nb-spinner"></span>
            </div>

            <div v-else-if="!notifStore.notifications.length" class="nb-empty">
                No notifications
            </div>

            <div v-else>
                <div
                    v-for="n in notifStore.notifications"
                    :key="n.id"
                    class="nb-item"
                    :class="{ 'nb-item--unread': !n.read_at }"
                    @click="goToNotif(n)"
                    @mouseenter="onHoverNotif(n)"
                >
                    <div class="nb-item-icon" :class="`nb-icon--${n.type}`">
                        <i class="fa" :class="{
                            'fa-reply': n.type === 'reply',
                            'fa-thumbs-up': n.type === 'like',
                            'fa-thumbs-down': n.type === 'dislike',
                            'fa-info-circle': n.type === 'system',
                        }"></i>
                    </div>
                    <div class="nb-item-body">
                        <p class="nb-item-text">{{ notifLabel(n) }}</p>
                        <span class="nb-item-date">{{ new Date(n.created_at).toLocaleDateString('en', { day: 'numeric', month: 'short' }) }}</span>
                    </div>
                </div>
            </div>

            <div class="nb-footer">
                <button class="nb-all-btn" @click="goToAll">View all</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.nb-wrap { position: relative; display: inline-flex; align-items: center; }

.nb-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
    color: inherit;
    padding: 0 4px;
    position: relative;
    display: flex;
    align-items: center;
    transition: color 0.2s;
}

.nb-btn:hover { color: #17b978; }

.nb-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #e74c3c;
    color: #fff;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    min-width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 3px;
    line-height: 1;
}

.nb-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: -10px;
    width: 320px;
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    z-index: 9999;
    overflow: hidden;
}

.nb-head {
    padding: 14px 16px 10px;
    border-bottom: 1px solid #f0f0f0;
}

.nb-head-title {
    font-size: 14px;
    font-weight: 700;
    color: #222;
}

.nb-loading, .nb-empty {
    padding: 24px;
    text-align: center;
    color: #aaa;
    font-size: 13px;
}

.nb-spinner {
    width: 18px;
    height: 18px;
    border: 2px solid #e0e0e0;
    border-top-color: #17b978;
    border-radius: 50%;
    animation: nb-spin 0.7s linear infinite;
    display: inline-block;
}

@keyframes nb-spin { to { transform: rotate(360deg); } }

.nb-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 16px;
    cursor: pointer;
    transition: background 0.1s;
    border-bottom: 1px solid #fafafa;
}

.nb-item:hover { background: #f7f7f7; }
.nb-item--unread { background: #f0fbf7; }

.nb-item-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
    background: #eee;
    color: #888;
}

.nb-icon--reply { background: #e8f5e9; color: #17b978; }
.nb-icon--like { background: #e3f2fd; color: #2196f3; }
.nb-icon--dislike { background: #fce4ec; color: #e74c3c; }
.nb-icon--system { background: #fff8e1; color: #ff9800; }

.nb-item-body { flex: 1; min-width: 0; }

.nb-item-text {
    font-size: 13px;
    color: #333;
    margin: 0 0 3px;
    line-height: 1.4;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.nb-item-date { font-size: 11px; color: #aaa; }

.nb-footer {
    padding: 10px 16px;
    border-top: 1px solid #f0f0f0;
    text-align: center;
}

.nb-all-btn {
    background: none;
    border: none;
    font-size: 13px;
    color: #17b978;
    font-weight: 600;
    cursor: pointer;
}

.nb-all-btn:hover { text-decoration: underline; }
</style>
