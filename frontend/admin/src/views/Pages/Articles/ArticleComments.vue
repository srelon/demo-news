<script setup lang="ts">
import { ref, reactive, provide, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useModeratorStore } from '@/stores/moderator'
import { useWsStore } from '@/stores/ws'
import { useAuthStore } from '@/stores/auth'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import ModeratorSwitcher from '@/components/ui/base/ModeratorSwitcher.vue'
import ArticleCommentItem from '@/components/ui/comments/ArticleCommentItem.vue'
import CommentReportsModal from '@/components/ui/comments/CommentReportsModal.vue'
import CommentSortButtons from '@/components/ui/comments/CommentSortButtons.vue'
import { deleteCommentRequest, editCommentRequest, fetchRepliesRequest, restoreCommentRequest, useWsEvents } from '@/composables/useAdminComments'
import type { CommentSort } from '@/composables/useAdminComments'
import type { ArticleComment } from '@/components/ui/comments/types'
import axios from '@/plugins/axios'

const props = defineProps<{
    article_id: number
    active: boolean
}>()

const route = useRoute()
const toast = useToast()
const moderator = useModeratorStore()
const ws = useWsStore()
const auth = useAuthStore()

const comments = ref<ArticleComment[]>([])
const is_loading = ref(true)
const current_page = ref(1)
const last_page = ref(1)
const sort = ref<CommentSort>('newest')

watch(sort, () => load(1))

const reply_to = ref<ArticleComment | null>(null)
const reply_body = ref('')
const reply_loading = ref(false)
const expanded_replies = ref<Record<number, ArticleComment[]>>({})
const replies_loading = ref<Record<number, boolean>>({})

const reports_comment_id = ref<number | null>(null)

const edit_comment = ref<ArticleComment | null>(null)
const edit_body = ref('')
const edit_loading = ref(false)
const restore_loading = ref<Record<number, boolean>>({})
const like_loading = ref<Record<number, boolean>>({})

const highlighted_id = ref<number | null>(null)

function fetchReplies(parent_id: number) {
    replies_loading.value[parent_id] = true

    return fetchRepliesRequest(parent_id).then((replies) => {
        expanded_replies.value[parent_id] = replies
    }).finally(() => {
        delete replies_loading.value[parent_id]
    })
}

function highlightComment(id: number): boolean {
    const el = document.getElementById(`admin-comment-${id}`)
    if (!el) return false

    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
    highlighted_id.value = id
    setTimeout(() => { highlighted_id.value = null }, 2500)

    return true
}

function scrollToComment(id: number) {
    nextTick(() => {
        if (highlightComment(id)) return

        // Comment not in DOM — might be a collapsed reply, expand its parent first
        const parent_id = route.query.parent_id ? Number(route.query.parent_id) : null
        if (!parent_id) return

        const parent = comments.value.find(c => c.id === parent_id)
        if (!parent) return

        fetchReplies(parent_id).then(() => {
            nextTick(() => highlightComment(id))
        })
    })
}

function load(page = 1) {
    is_loading.value = true
    current_page.value = page

    const params: Record<string, any> = {
        page,
        sort: sort.value,
    }

    // Pin the target root comment to the top of page 1 so the anchor works
    // even when the comment is old enough to live on a deeper page
    const pin = route.query.parent_id ?? route.query.comment
    if (page === 1 && pin) params.pin = Number(pin)

    axios.get(`comments/article/${props.article_id}`, { params }).then((res) => {
        const data = res.data.data
        comments.value = page === 1 ? data.comments : [...comments.value, ...data.comments]
        last_page.value = data.pagination.last_page

        if (page === 1 && route.query.comment) {
            scrollToComment(Number(route.query.comment))
        }
    }).finally(() => {
        is_loading.value = false
    })
}

function loadReplies(comment: ArticleComment) {
    if (expanded_replies.value[comment.id]) {
        delete expanded_replies.value[comment.id]
        return
    }
    fetchReplies(comment.id)
}

function openReply(comment: ArticleComment) {
    if (!moderator.user) {
        toast.error('Select a moderator account first')
        return
    }
    reply_to.value = reply_to.value?.id === comment.id ? null : comment
    reply_body.value = ''
}

function cancelReply() {
    reply_to.value = null
    reply_body.value = ''
}

function submitReply() {
    if (!reply_body.value.trim() || !reply_to.value || reply_loading.value) return
    reply_loading.value = true

    axios.post('comment/reply', {
        article_id: props.article_id,
        body: reply_body.value,
        parent_id: reply_to.value.parent_id ?? reply_to.value.id,
        replied_to_comment_id: reply_to.value.id,
    }).then((res) => {
        cancelReply()
        toast.success('Reply posted')
        showPostedReply(res.data.data.comment)
    }).finally(() => {
        reply_loading.value = false
    })
}

// Make sure the just-posted reply is visible — expand the parent thread
// if it was collapsed, otherwise append (unless WS already delivered it)
function showPostedReply(reply: ArticleComment) {
    const pid = reply.parent_id
    if (!pid) return

    const replies = expanded_replies.value[pid]
    if (replies) {
        if (!replies.some(r => r.id === reply.id)) replies.push(reply)
        return
    }

    fetchReplies(pid)
}

function likeComment(comment: ArticleComment, opp_type: 1 | 2) {
    if (!moderator.user || like_loading.value[comment.id]) return
    like_loading.value[comment.id] = true
    axios.post(`comment/${comment.id}/like`, { opp_type }).then((res) => {
        const d = res.data.data
        comment.likes = d.likes
        comment.dislikes = d.dislikes
        comment.user_reaction = d.opp_type
    }).finally(() => {
        delete like_loading.value[comment.id]
    })
}

// Remove a hard-deleted comment from the visible lists
function removeCommentLocally(comment_id: number, parent_id: number | null) {
    if (!parent_id) {
        comments.value = comments.value.filter(c => c.id !== comment_id)
        return
    }

    const replies = expanded_replies.value[parent_id]
    if (replies) {
        expanded_replies.value[parent_id] = replies.filter(r => r.id !== comment_id)
    }

    const parent = comments.value.find(c => c.id === parent_id)
    if (parent) parent.replies_count = Math.max(0, parent.replies_count - 1)
}

function deleteComment(comment: ArticleComment) {
    deleteCommentRequest(comment.id).then(() => {
        comment.status = 3
        comment.deleted_by = 1
        toast.success('Comment deleted')
    })
}

function restoreComment(comment: ArticleComment) {
    if (restore_loading.value[comment.id]) return
    restore_loading.value[comment.id] = true
    restoreCommentRequest(comment.id).then(() => {
        comment.status = 1
        comment.deleted_by = null
        toast.success('Comment restored')
    }).finally(() => {
        delete restore_loading.value[comment.id]
    })
}

function startEdit(comment: ArticleComment) {
    edit_comment.value = comment
    edit_body.value = comment.body ?? ''
}

function cancelEdit() {
    edit_comment.value = null
    edit_body.value = ''
}

function submitEdit(comment: ArticleComment) {
    if (!edit_body.value.trim() || edit_loading.value) return
    edit_loading.value = true
    editCommentRequest(comment.id, edit_body.value).then((body) => {
        comment.body = body
        cancelEdit()
        toast.success('Comment updated')
    }).finally(() => {
        edit_loading.value = false
    })
}

function openReports(comment: ArticleComment) {
    reports_comment_id.value = comment.id
}

provide('article_comments', reactive({
    highlighted_id,
    edit_comment,
    edit_body,
    edit_loading,
    reply_to,
    reply_body,
    reply_loading,
    expanded_replies,
    replies_loading,
    like_loading,
    restore_loading,
    likeComment,
    deleteComment,
    restoreComment,
    startEdit,
    cancelEdit,
    submitEdit,
    openReply,
    cancelReply,
    submitReply,
    openReports,
    loadReplies,
}))

function onWsComment(e: Event) {
    const comment = (e as CustomEvent<ArticleComment>).detail
    if (!comment || comment.article_id !== props.article_id) return

    if (!comment.parent_id) {
        comments.value.unshift(comment)
    } else {
        const pid = comment.parent_id
        const replies = expanded_replies.value[pid]
        if (replies && !replies.some(r => r.id === comment.id)) replies.push(comment)
        const parent = comments.value.find(c => c.id === pid)
        if (parent) parent.replies_count++
    }
}

function findComment(comment_id: number, parent_id: number | null): ArticleComment | undefined {
    if (parent_id) {
        return expanded_replies.value[parent_id]?.find(r => r.id === comment_id)
    }
    return comments.value.find(c => c.id === comment_id)
}

function onWsDeleted(e: Event) {
    const { comment_id, parent_id, deleted_by, status } = (e as CustomEvent<{ comment_id: number; parent_id: number | null; deleted_by?: number | null; status?: number }>).detail

    if (deleted_by === null || deleted_by === undefined) {
        // Physical delete (moderator's own comment) — remove from list
        removeCommentLocally(comment_id, parent_id)
        return
    }

    // Soft delete: status 2 = deleted by site moderator, status 3 = deleted by admin
    const comment = findComment(comment_id, parent_id)
    if (comment) {
        comment.status = status ?? 2
        comment.deleted_by = deleted_by
    }
}

function onWsRestored(e: Event) {
    const { comment_id, parent_id } = (e as CustomEvent<{ comment_id: number; parent_id: number | null }>).detail
    const comment = findComment(comment_id, parent_id)
    if (comment) {
        comment.status = 1
        comment.deleted_by = null
    }
}

function onWsLikeUpdated(e: Event) {
    const { comment_id, from_user_id, opp_type, likes, dislikes } = (e as CustomEvent<{ comment_id: number; from_user_id: number; opp_type: number; likes: number; dislikes: number }>).detail
    const is_own = moderator.user?.id === from_user_id

    const update = (c: ArticleComment) => {
        c.likes = likes
        c.dislikes = dislikes
        if (is_own) c.user_reaction = opp_type
    }

    const root = comments.value.find(c => c.id === comment_id)
    if (root) {
        update(root)
        return
    }

    for (const replies of Object.values(expanded_replies.value)) {
        const reply = replies.find(r => r.id === comment_id)
        if (reply) {
            update(reply)
            return
        }
    }
}

const ws_events = useWsEvents({
    'comment.new': onWsComment,
    'comment.deleted': onWsDeleted,
    'comment.restored': onWsRestored,
    'like.updated': onWsLikeUpdated,
})

function activate() {
    load()
    ws.connect()
    ws.subscribeArticle(props.article_id)
    ws_events.attach()
}

function deactivate() {
    ws.unsubscribeArticle(props.article_id)
    ws_events.detach()
}

watch(() => props.active, (val) => {
    if (val) activate()
    else deactivate()
})

// Re-scroll when navigating to same article from a notification (query changes, component stays mounted)
watch(() => route.query.comment, (comment_id) => {
    if (!comment_id || !props.active) return
    load(1)
})

onMounted(() => {
    if (props.active) activate()
})

onBeforeUnmount(() => {
    deactivate()
})
</script>

<template>
    <div>
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div v-if="auth.accesses('moderator', 'view')" class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Acting as:</span>
                <ModeratorSwitcher />
            </div>
            <CommentSortButtons v-model="sort" />
        </div>

        <BaseLoading v-if="is_loading" />

        <div v-else-if="!comments.length" class="py-10 text-center text-sm text-gray-400">
            No comments yet
        </div>

        <div v-else class="space-y-3">
            <ArticleCommentItem
                v-for="comment in comments"
                :key="comment.id"
                :comment="comment"
            />
        </div>

        <!-- Load more -->
        <div v-if="!is_loading && current_page < last_page" class="mt-4 text-center">
            <button
                @click="load(current_page + 1)"
                class="rounded-lg border border-gray-200 px-5 py-2 text-sm text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors"
            >Load more</button>
        </div>

        <!-- Reports modal -->
        <CommentReportsModal
            v-if="reports_comment_id"
            :comment_id="reports_comment_id"
            @close="reports_comment_id = null"
        />
    </div>
</template>
