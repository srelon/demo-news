import { defineStore } from 'pinia'
import axios from '@/plugins/axios'

export interface AdminNotification {
    id: number
    user_id: number
    type: string
    data: Record<string, any>
    article_id: number | null
    comment_id: number | null
    parent_id: number | null
    article_url: {
        slug: string
        subcategory_slug: string
        category_slug: string
    } | null
    read_at: string | null
    created_at: string
    moderator_user: {
        id: number
        name: string
        img: string | null
    } | null
}

export const useAdminNotificationStore = defineStore('admin_notifications', {
    state: () => ({
        notifications: [] as AdminNotification[],
        unread_count: 0,
        debug_unread_count: 0,
        loaded: false,
    }),

    getters: {
        total_unread: (state) => state.unread_count + state.debug_unread_count,
    },

    actions: {
        fetchUnreadCount() {
            return axios.get('notifications/unread-count').then((res) => {
                this.unread_count = res.data.data.count
            })
        },

        fetchDebugUnreadCount() {
            return axios.get('debug/unread-count').then((res) => {
                this.debug_unread_count = res.data.data.count
            })
        },

        fetchRecent() {
            return axios.get('notifications').then((res) => {
                const data = res.data.data
                this.notifications = data.notifications
                this.unread_count = data.unread_count
                this.loaded = true
            })
        },

        fetchAll(page = 1) {
            return axios.get('notifications/all', { params: { page } })
        },

        markRead(id: number) {
            return axios.post(`notifications/mark-read/${id}`)
        },

        upsertNotification(n: AdminNotification) {
            const idx = this.notifications.findIndex((x) => x.id === n.id)
            if (idx >= 0) {
                const old = this.notifications[idx]
                const was_read = !!old?.read_at
                this.notifications[idx] = n
                if (was_read && !n.read_at) this.unread_count++
            } else {
                this.notifications.unshift(n)
                if (this.notifications.length > 15) this.notifications.pop()
                if (!n.read_at) this.unread_count++
            }
        },

        removeNotification(id: number) {
            const idx = this.notifications.findIndex((x) => x.id === id)
            if (idx < 0) return
            const item = this.notifications[idx]
            if (item && !item.read_at && this.unread_count > 0) this.unread_count--
            this.notifications.splice(idx, 1)
        },

        handleWsEvent(data: any) {
            if (data.action === 'upserted') {
                this.upsertNotification(data.notification)
            } else if (data.action === 'deleted') {
                this.removeNotification(data.id)
            } else if (data.action === 'unread_count') {
                this.fetchUnreadCount()
            }
        },

        clearDebugUnread() {
            this.debug_unread_count = 0
        },
    },
})
