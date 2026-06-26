import { defineStore } from 'pinia'
import axios from '@/plugins/axios'

export interface AppNotification {
    id: number
    type: string
    data: Record<string, any>
    article_id: number | null
    comment_id: number | null
    parent_id: number | null
    article_url: { slug: string; subcategory_slug: string; category_slug: string } | null
    read_at: string | null
    created_at: string
}

export const useNotificationStore = defineStore('notification', {
    state: () => ({
        notifications: [] as AppNotification[],
        unread_count: 0,
        loaded: false,
    }),

    actions: {
        fetchNotifications() {
            return axios.get('notifications').then((res) => {
                const data = res.data.data
                this.notifications = data.notifications
                this.unread_count = data.unread_count
                this.loaded = true
            })
        },

        fetchUnreadCount() {
            return axios.get('notifications/unread-count').then((res) => {
                this.unread_count = res.data.data.count
            })
        },

        addNotification(notification: AppNotification) {
            this.upsertNotification(notification)
        },

        setUnreadCount(count: number) {
            this.unread_count = count
        },

        upsertNotification(notification: AppNotification) {
            const idx = this.notifications.findIndex((n) => n.id === notification.id)
            if (idx >= 0) {
                const old = this.notifications[idx]
                const was_read = !!old?.read_at
                this.notifications[idx] = notification
                // Increment only if it was read before and is now unread
                if (was_read && !notification.read_at) {
                    this.unread_count++
                }
            } else {
                this.notifications.unshift(notification)
                if (this.notifications.length > 10) this.notifications.pop()
                if (!notification.read_at) this.unread_count++
            }
        },

        removeNotification(id: number) {
            const idx = this.notifications.findIndex((n) => n.id === id)
            if (idx < 0) return
            const item = this.notifications[idx]
            const was_unread = item ? !item.read_at : false
            this.notifications.splice(idx, 1)
            if (was_unread && this.unread_count > 0) this.unread_count--
        },

        reset() {
            this.notifications = []
            this.unread_count = 0
            this.loaded = false
        },
    },
})
