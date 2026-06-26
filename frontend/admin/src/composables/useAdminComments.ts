import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useWsStore } from '@/stores/ws'
import axios from '@/plugins/axios'
import type { AdminComment, ArticleComment } from '@/components/ui/comments/types'

export type CommentSort = 'newest' | 'oldest' | 'likes' | 'dislikes'

export const COMMENT_SORTS: [CommentSort, string][] = [
    [
        'newest',
        'Newest',
    ],
    [
        'oldest',
        'Oldest',
    ],
    [
        'likes',
        'Likes',
    ],
    [
        'dislikes',
        'Dislikes',
    ],
]

export function editCommentRequest(comment_id: number, body: string): Promise<string> {
    return axios.post(`comment/${comment_id}/edit`, {
        body,
    }).then(res => res.data.data.body)
}

export function deleteCommentRequest(comment_id: number): Promise<'soft'> {
    return axios.post(`comment/delete/${comment_id}`).then(res => res.data.data.deleted)
}

export function restoreCommentRequest(comment_id: number): Promise<void> {
    return axios.post(`comment/restore/${comment_id}`).then(() => {})
}

export function fetchRepliesRequest(comment_id: number): Promise<ArticleComment[]> {
    return axios.get(`comments/${comment_id}/replies`).then(res => res.data.data.replies)
}

export function formatCommentDate(iso: string) {
    return new Date(iso).toLocaleDateString('en', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}

/**
 * Attach/detach a set of admin WS event listeners in one call.
 * Keys are event names without the `admin:ws:` prefix.
 */
export function useWsEvents(events: Record<string, EventListener>) {
    function attach() {
        for (const [name, handler] of Object.entries(events)) {
            window.addEventListener(`admin:ws:${name}`, handler)
        }
    }

    function detach() {
        for (const [name, handler] of Object.entries(events)) {
            window.removeEventListener(`admin:ws:${name}`, handler)
        }
    }

    return {
        attach,
        detach,
    }
}

interface CommentsFeedOptions {
    on_new: (comment: AdminComment) => void
    on_removed?: (comment: AdminComment) => void
}

/**
 * Shared state and live-update wiring for comment lists fed by the
 * `comments.all` WS channel (Comments page, dashboard Recent Comments).
 */
export function useCommentsFeed(options: CommentsFeedOptions) {
    const router = useRouter()
    const ws = useWsStore()

    const comments = ref<AdminComment[]>([])
    const is_loading = ref(true)
    const reports_comment_id = ref<number | null>(null)

    function goToComment(comment: AdminComment) {
        if (!comment.article?.id) return
        router.push({
            name: 'article',
            params: {
                id: comment.article.id,
            },
            query: {
                tab: 'comments',
                comment: comment.id,
                ...(comment.parent_id ? { parent_id: comment.parent_id } : {}),
            },
        })
    }

    function removeComment(comment: AdminComment) {
        comments.value = comments.value.filter(c => c.id !== comment.id)
        options.on_removed?.(comment)
    }

    function openReports(comment: AdminComment) {
        reports_comment_id.value = comment.id
    }

    function onWsNew(e: Event) {
        const comment = (e as CustomEvent<AdminComment>).detail
        if (!comment || !comment.id) return
        options.on_new(comment)
    }

    function onWsDeleted(e: Event) {
        const { comment_id, deleted_by, status } = (e as CustomEvent<{ comment_id: number; deleted_by?: number | null; status?: number }>).detail
        const comment = comments.value.find(c => c.id === comment_id)
        if (!comment) return

        if (deleted_by === null || deleted_by === undefined) {
            removeComment(comment)
        } else {
            comment.status = status ?? 2
            comment.deleted_by = deleted_by
        }
    }

    function onWsRestored(e: Event) {
        const { comment_id } = (e as CustomEvent<{ comment_id: number }>).detail
        const comment = comments.value.find(c => c.id === comment_id)
        if (!comment) return
        comment.status = 1
        comment.deleted_by = null
    }

    const ws_events = useWsEvents({
        'comment.new': onWsNew,
        'comment.deleted': onWsDeleted,
        'comment.restored': onWsRestored,
    })

    onMounted(() => {
        ws.connect()
        ws.subscribeComments()
        ws_events.attach()
    })

    onBeforeUnmount(() => {
        ws.unsubscribeComments()
        ws_events.detach()
    })

    return {
        comments,
        is_loading,
        reports_comment_id,
        goToComment,
        removeComment,
        openReports,
    }
}
