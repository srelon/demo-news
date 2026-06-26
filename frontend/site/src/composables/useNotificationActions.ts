import { useRouter } from 'vue-router'
import axios from '@/plugins/axios'
import { useNotificationStore } from '@/stores/notification'
import type { AppNotification } from '@/stores/notification'

export function useNotificationActions() {
    const router = useRouter()
    const notifStore = useNotificationStore()
    const pending_read = new Set<number>()

    function notifLabel(n: AppNotification): string {
        if (n.type === 'reply') return `${n.data.from_name} replied to your comment`
        if (n.type === 'like') return `${n.data.from_name} liked your comment`
        if (n.type === 'dislike') return `${n.data.from_name} disliked your comment`
        return n.data.message ?? 'System notification'
    }

    function buildLink(n: AppNotification): object | null {
        if (!n.article_url) return null
        return {
            name: 'news_page',
            params: {
                category: n.article_url.category_slug,
                subcategory: n.article_url.subcategory_slug,
                slug: n.article_url.slug,
            },
            query: n.parent_id ? { pin: String(n.parent_id) } : {},
            hash: n.comment_id ? `#comment-${n.comment_id}` : '',
        }
    }

    function goTo(n: AppNotification, after?: () => void) {
        const link = buildLink(n)
        if (link) router.push(link)
        after?.()
    }

    function onHoverNotif(n: AppNotification) {
        if (n.read_at || pending_read.has(n.id)) return
        pending_read.add(n.id)
        axios.patch(`notifications/${n.id}/read`).then(() => {
            n.read_at = new Date().toISOString()
            if (notifStore.unread_count > 0) notifStore.unread_count--
            const store_item = notifStore.notifications.find((s) => s.id === n.id)
            if (store_item) store_item.read_at = n.read_at
        })
    }

    return { notifLabel, buildLink, goTo, onHoverNotif }
}
