<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { normalizeCommentBody } from '@/utils/comment_body'
import { toImageUrl } from '@/stores/layout'
import { useAuthStore } from '@/stores/auth'
import CommentForm from './CommentForm.vue'
import axios from '@/plugins/axios'

export interface CommentData {
    id: number
    article_id: number
    body: string | null
    deleted_by?: number | null
    parent_id: number | null
    replied_to_comment_id?: number | null
    replied_to?: { id: number; user: { name: string; username: string | null } } | null
    likes: number
    dislikes: number
    replies_count: number
    user_reaction: number
    user: { id: number; name: string; username: string | null; img: string | null; is_moderator?: boolean } | null
    created_at: string
    status?: number
}

const props = defineProps<{
    comment: CommentData
    is_reply?: boolean
    max_len: number
    report_reasons: string[]
}>()

const EDIT_WINDOW_MS = 86_400_000 // 24 hours — mirrors backend now()->subDay()

const emit = defineEmits<{
    (e: 'reply-to-me', comment: CommentData): void
    (e: 'deleted', id: number): void
}>()

const route = useRoute()
const toast = useToast()
const authStore = useAuthStore()

const is_anchored = computed(() => route.hash === `#comment-${props.comment.id}`)

const show_replies = ref(false)
const replies = ref<CommentData[]>([])
const replies_loading = ref(false)
const show_reply_form = ref(false)
const reply_loading = ref(false)
const reply_form_el = ref<HTMLDivElement | null>(null)

const show_menu = ref(false)
const show_report = ref(false)
const show_delete_confirm = ref(false)
const show_edit = ref(false)
const report_reason = ref('')
const report_loading = ref(false)
const delete_loading = ref(false)
const edit_loading = ref(false)
const restore_loading = ref(false)
const edit_char_count = ref(0)
const menu_btn = ref<HTMLButtonElement | null>(null)
const menu_el = ref<HTMLDivElement | null>(null)
const edit_editor = ref<HTMLDivElement | null>(null)
const reported = ref(false)
const local_body = ref(props.comment.body)

const is_own = computed(() => !!authStore.user && authStore.user.id === props.comment.user?.id)
const is_site_moderator = computed(() => !!authStore.user?.is_moderator)
const is_moderator_deleted = computed(() => props.comment.deleted_by === 1)

const has_menu_items = computed(() => {
    if (is_moderator_deleted.value) return is_site_moderator.value
    return true
})

const is_editable = computed(() => {
    if (is_moderator_deleted.value) return false
    // Moderator can edit their own comments without time limit
    if (is_site_moderator.value && is_own.value) return true
    if (!is_own.value) return false
    return Date.now() - new Date(props.comment.created_at).getTime() < EDIT_WINDOW_MS
})

// Кому именно отвечают (null = отвечают на корневой комментарий)
const replying_to_comment = ref<CommentData | null>(null)

const local_likes = ref(props.comment.likes)
const local_dislikes = ref(props.comment.dislikes)
const local_reaction = ref(props.comment.user_reaction)
const local_replies_count = ref(props.comment.replies_count)

const reply_to_name = computed(() =>
    replying_to_comment.value?.user?.username ??
    replying_to_comment.value?.user?.name ??
    null
)

function formatDate(iso: string) {
    return new Date(iso).toLocaleDateString('en', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}

function scrollToComment(id: number) {
    document.getElementById(`comment-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

function scrollToForm() {
    nextTick(() => {
        reply_form_el.value?.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
    })
}

// Загрузить ответы (без переключения, только открыть)
function loadReplies(after?: () => void) {
    if (replies_loading.value) return
    if (replies.value.length) {
        show_replies.value = true
        after?.()
        return
    }
    replies_loading.value = true
    axios.get(`comments/${props.comment.id}/replies`).then((res) => {
        replies.value = res.data.data.replies
        show_replies.value = true
        after?.()
    }).finally(() => { replies_loading.value = false })
}

// Кнопка Show/Hide replies
function toggleReplies() {
    if (show_replies.value) {
        show_replies.value = false
        show_reply_form.value = false
        replying_to_comment.value = null
        return
    }
    loadReplies()
}

// Кнопка Reply на корневом комментарии
function onReplyClick() {
    if (!authStore.user) { authStore.openAuthModal(); return }
    const same = show_reply_form.value && replying_to_comment.value === null
    if (same) {
        show_reply_form.value = false
        return
    }
    replying_to_comment.value = null
    show_reply_form.value = true
    if (!show_replies.value) {
        loadReplies(scrollToForm)
    } else {
        scrollToForm()
    }
}

// Кнопка Reply на дочернем (reply) комментарии → передаём наверх
function onReplyItemClick() {
    if (!authStore.user) { authStore.openAuthModal(); return }
    emit('reply-to-me', props.comment)
}

// Корневой комментарий получает событие от дочернего reply
function onReplyToReply(reply: CommentData) {
    if (!authStore.user) { authStore.openAuthModal(); return }
    const same = show_reply_form.value && replying_to_comment.value?.id === reply.id
    if (same) {
        show_reply_form.value = false
        replying_to_comment.value = null
        return
    }
    replying_to_comment.value = reply
    show_reply_form.value = true
    if (!show_replies.value) {
        loadReplies(scrollToForm)
    } else {
        scrollToForm()
    }
}

function onLike(opp_type: 1 | 2) {
    if (!authStore.user) { authStore.openAuthModal(); return }
    axios.post(`comments/${props.comment.id}/like`, { opp_type }).then((res) => {
        const d = res.data.data
        local_likes.value = d.likes
        local_dislikes.value = d.dislikes
        local_reaction.value = d.opp_type
    })
}

function onReplySubmit(body: string) {
    reply_loading.value = true
    axios.post('comments', {
        article_id: props.comment.article_id,
        body,
        parent_id: props.comment.id,
        replied_to_comment_id: replying_to_comment.value?.id ?? null,
    }).finally(() => {
        reply_loading.value = false
        show_reply_form.value = false
        replying_to_comment.value = null
    })
}

// Открыть ответы и прокрутить к конкретному reply (вызывается из BaseCommentsBlock)
function onCsOpenReplies(e: Event) {
    const { parent_id, scroll_to } = (e as CustomEvent<{ parent_id: number; scroll_to: number }>).detail
    if (props.comment.id !== parent_id) return
    openRepliesAndScrollTo(scroll_to)
}

function openRepliesAndScrollTo(target_id: number) {
    function doScroll() {
        setTimeout(() => {
            document.getElementById(`comment-${target_id}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' })
        }, 150)
    }

    if (replies.value.length > 0) {
        show_replies.value = true
        doScroll()
        return
    }
    replies_loading.value = true
    axios.get(`comments/${props.comment.id}/replies`).then((res) => {
        replies.value = res.data.data.replies
        show_replies.value = true
        doScroll()
    }).finally(() => { replies_loading.value = false })
}

// Sync local_body when parent restores the comment (reactive prop update)
watch(() => props.comment.body, (new_body) => {
    if (new_body) local_body.value = new_body
})

// WS: обновление тела комментария
function onWsCommentUpdated(e: Event) {
    const { comment_id, body } = (e as CustomEvent<{ comment_id: number; body: string }>).detail
    if (comment_id === props.comment.id) local_body.value = body
}

// WS: удаление комментария/реплая
function onWsCommentDeleted(e: Event) {
    const { comment_id, parent_id, deleted_by } = (e as CustomEvent<{ comment_id: number; parent_id: number | null; deleted_by?: number | null }>).detail

    if (parent_id === props.comment.id) {
        if (deleted_by === 1) {
            const reply = replies.value.find(r => r.id === comment_id)
            if (reply) reply.deleted_by = 1
        } else {
            onReplyDeleted(comment_id)
        }
    }
}

// WS: восстановление реплая
function onWsCommentRestored(e: Event) {
    const { comment_id, parent_id, body } = (e as CustomEvent<{ comment_id: number; parent_id: number | null; body: string }>).detail
    if (parent_id !== props.comment.id) return
    const reply = replies.value.find(r => r.id === comment_id)
    if (reply) {
        reply.deleted_by = null
        reply.body = body
    }
}

// WS: обновление лайков/дизлайков
function onWsLike(e: Event) {
    const d = (e as CustomEvent<{ comment_id: number; from_user_id: number; opp_type: number; likes: number; dislikes: number }>).detail
    if (d.comment_id !== props.comment.id) return
    local_likes.value = d.likes
    local_dislikes.value = d.dislikes
    if (authStore.user?.id === d.from_user_id) {
        local_reaction.value = d.opp_type
    }
}

// WS: получаем ответы от BaseCommentsBlock
function onWsReply(e: Event) {
    const reply = (e as CustomEvent<CommentData>).detail
    if (reply.parent_id !== props.comment.id) return
    if (replies.value.some(r => r.id === reply.id)) return

    local_replies_count.value++

    if (show_replies.value) {
        // Секция ответов открыта — добавляем сразу в список
        replies.value.push(reply)
    }
    // Если секция закрыта — только счётчик, при открытии загрузятся все с сервера
}

function onMenuClickOutside(e: MouseEvent) {
    if (
        !menu_btn.value?.contains(e.target as Node) &&
        !menu_el.value?.contains(e.target as Node)
    ) {
        show_menu.value = false
        show_report.value = false
        show_delete_confirm.value = false
    }
}

function onRestore() {
    if (restore_loading.value) return
    restore_loading.value = true
    axios.post(`comments/${props.comment.id}/restore`).then(() => {
        show_menu.value = false
    }).finally(() => { restore_loading.value = false })
}

function onDeleteConfirm() {
    if (delete_loading.value) return
    delete_loading.value = true
    const url = (is_site_moderator.value && !is_own.value)
        ? `comments/${props.comment.id}/moderate`
        : `comments/${props.comment.id}`
    axios.delete(url).then(() => {
        show_menu.value = false
        show_delete_confirm.value = false
        // Moderator deleting someone else's → placeholder handled by WS; own → remove
        if (!(is_site_moderator.value && !is_own.value)) {
            emit('deleted', props.comment.id)
        }
    }).finally(() => { delete_loading.value = false })
}

function openEdit() {
    show_menu.value = false
    show_delete_confirm.value = false
    show_edit.value = true
    nextTick(() => {
        if (!edit_editor.value) return
        edit_editor.value.innerHTML = local_body.value ?? ''
        edit_char_count.value = edit_editor.value.textContent?.trim().length ?? 0
        edit_editor.value.focus()
        const range = document.createRange()
        range.selectNodeContents(edit_editor.value)
        range.collapse(false)
        window.getSelection()?.removeAllRanges()
        window.getSelection()?.addRange(range)
    })
}

function onEditInput() {
    edit_char_count.value = edit_editor.value?.textContent?.trim().length ?? 0
}

function onEditKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter') {
        e.preventDefault()
        document.execCommand('insertLineBreak')
        onEditInput()
    }
}

function execEdit(cmd: string) {
    edit_editor.value?.focus()
    // Force tag output (<b>, <s>, ...) instead of styled spans in Firefox
    document.execCommand('styleWithCSS', false, 'false')
    document.execCommand(cmd, false)
}

function onEditSubmit() {
    const text = edit_editor.value?.textContent?.trim() ?? ''
    const body = normalizeCommentBody(edit_editor.value?.innerHTML?.trim() ?? '')
    if (!text || !body || body === '<br>') return
    if (text.length > props.max_len) return
    edit_loading.value = true
    const edit_url = (is_site_moderator.value && is_own.value)
        ? `comments/${props.comment.id}/moderate`
        : `comments/${props.comment.id}`
    axios.patch(edit_url, { body }).then((res) => {
        local_body.value = res.data.data.body
        show_edit.value = false
    }).finally(() => { edit_loading.value = false })
}

function onReplyDeleted(id: number) {
    replies.value = replies.value.filter((r) => r.id !== id)
    local_replies_count.value = Math.max(0, local_replies_count.value - 1)
}

function openReport() {
    if (!authStore.user) { authStore.openAuthModal(); return }
    report_reason.value = ''
    show_menu.value = false
    show_report.value = true
}

function onReportSubmit() {
    if (!report_reason.value || report_loading.value) return
    report_loading.value = true
    axios.post(`comments/${props.comment.id}/report`, { reason: report_reason.value }).then(() => {
        reported.value = true
        show_report.value = false
        toast.success('Report submitted')
    }).finally(() => { report_loading.value = false })
}

onMounted(() => {
    document.addEventListener('click', onMenuClickOutside)
    window.addEventListener('ws:like.updated', onWsLike)
    window.addEventListener('ws:comment.updated', onWsCommentUpdated)
    if (!props.is_reply) {
        window.addEventListener('ws:reply.new', onWsReply)
        window.addEventListener('cs:open-replies', onCsOpenReplies)
        window.addEventListener('ws:comment.deleted', onWsCommentDeleted)
        window.addEventListener('ws:comment.restored', onWsCommentRestored)
    }
})

onBeforeUnmount(() => {
    document.removeEventListener('click', onMenuClickOutside)
    window.removeEventListener('ws:like.updated', onWsLike)
    window.removeEventListener('ws:comment.updated', onWsCommentUpdated)
    if (!props.is_reply) {
        window.removeEventListener('ws:reply.new', onWsReply)
        window.removeEventListener('cs:open-replies', onCsOpenReplies)
        window.removeEventListener('ws:comment.deleted', onWsCommentDeleted)
        window.removeEventListener('ws:comment.restored', onWsCommentRestored)
    }
})
</script>

<template>
    <div :id="`comment-${comment.id}`" class="ci-wrap" :class="{ 'ci-reply': is_reply, 'ci-anchored': is_anchored }">
        <!-- Avatar -->
        <div class="ci-avatar-col">
            <div class="ci-avatar">
                <img v-if="comment.user?.img" :src="toImageUrl(comment.user.img)" :alt="comment.user.name" />
                <i v-else class="fa fa-user"></i>
            </div>
        </div>

        <div class="ci-body">
            <!-- Header row -->
            <div class="ci-header">
                <div class="ci-header-left">
                    <div class="ci-name-row">
                        <span class="ci-name">{{ comment.user?.name ?? 'Deleted user' }}</span>
                        <span v-if="comment.user?.username" class="ci-username">@{{ comment.user.username }}</span>
                        <!-- Moderator badge -->
                        <span v-if="comment.user?.is_moderator" class="ci-mod-badge">Moderator</span>
                        <!-- Reply badge — hidden on deleted-by-moderator -->
                        <template v-if="!is_moderator_deleted">
                            <button
                                v-if="!is_reply"
                                class="ci-reply-badge"
                                @click="onReplyClick"
                            >Reply</button>
                            <button
                                v-else
                                class="ci-reply-badge"
                                @click="onReplyItemClick"
                            >Reply</button>
                        </template>
                    </div>
                    <span class="ci-date">{{ formatDate(comment.created_at) }}</span>
                </div>

                <div class="ci-header-right">
                    <button
                        class="ci-like-btn"
                        :class="{ 'ci-like-btn--active': local_reaction === 2 }"
                        :disabled="is_moderator_deleted"
                        @click="onLike(2)"
                        title="Like"
                    >
                        <i class="fa fa-thumbs-up"></i>
                        <span>{{ local_likes }}</span>
                    </button>
                    <button
                        class="ci-like-btn ci-like-btn--dis"
                        :class="{ 'ci-like-btn--dis-active': local_reaction === 1 }"
                        :disabled="is_moderator_deleted"
                        @click="onLike(1)"
                        title="Dislike"
                    >
                        <i class="fa fa-thumbs-down"></i>
                        <span>{{ local_dislikes }}</span>
                    </button>
                    <div class="ci-menu-wrap">
                        <button v-if="has_menu_items" ref="menu_btn" class="ci-menu-btn" @click.stop="show_menu = !show_menu; show_report = false; show_delete_confirm = false">•••</button>
                        <div ref="menu_el">
                            <!-- Dropdown menu -->
                            <div v-if="show_menu" class="ci-menu">
                                <template v-if="!show_delete_confirm">
                                    <!-- Moderator: restore deleted comment -->
                                    <button v-if="is_site_moderator && is_moderator_deleted" class="ci-menu-item ci-menu-item--restore" :disabled="restore_loading" @click.stop="onRestore">
                                        <span v-if="restore_loading" class="ci-spinner-sm"></span>
                                        <template v-else><i class="fa fa-undo"></i> Restore</template>
                                    </button>
                                    <template v-if="!is_moderator_deleted">
                                        <button v-if="is_editable" class="ci-menu-item" @click.stop="openEdit">
                                            <i class="fa fa-pencil-alt"></i> Edit
                                        </button>
                                        <!-- Own comment delete -->
                                        <button v-if="is_own" class="ci-menu-item ci-menu-item--danger" @click.stop="show_delete_confirm = true">
                                            <i class="fa fa-trash"></i> Delete
                                        </button>
                                        <template v-if="!is_own">
                                            <!-- Moderator: delete other's comment -->
                                            <button v-if="is_site_moderator" class="ci-menu-item ci-menu-item--danger" @click.stop="show_delete_confirm = true">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                            <!-- Report -->
                                            <button v-if="!reported" class="ci-menu-item ci-menu-item--danger" @click.stop="openReport">
                                                <i class="fa fa-flag"></i> Report
                                            </button>
                                            <span v-else class="ci-menu-item">
                                                <i class="fa fa-check"></i> Reported
                                            </span>
                                        </template>
                                    </template>
                                </template>
                                <template v-else>
                                    <div class="ci-menu-confirm">
                                        <span class="ci-menu-confirm-text">Delete this comment?</span>
                                        <div class="ci-menu-confirm-btns">
                                            <button class="ci-menu-confirm-yes" :disabled="delete_loading" @click.stop="onDeleteConfirm">
                                                <span v-if="delete_loading" class="ci-spinner-sm"></span>
                                                <span v-else>Yes</span>
                                            </button>
                                            <button class="ci-menu-confirm-no" @click.stop="show_delete_confirm = false">Cancel</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <!-- Report form -->
                            <div v-if="show_report" class="ci-report-panel">
                                <div class="ci-report-head">
                                    <span>Report comment</span>
                                    <button class="ci-report-close" @click.stop="show_report = false">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                                <select v-model="report_reason" class="ci-report-select">
                                    <option value="" disabled>Select reason</option>
                                    <option v-for="r in props.report_reasons" :key="r" :value="r">{{ r }}</option>
                                </select>
                                <button
                                    class="ci-report-submit"
                                    :disabled="!report_reason || report_loading"
                                    @click.stop="onReportSubmit"
                                >
                                    <span v-if="report_loading" class="ci-report-spinner"></span>
                                    <span v-else>Submit</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comment body / edit form -->
            <div v-if="!show_edit">
                <!-- Moderator-deleted placeholder -->
                <template v-if="is_moderator_deleted">
                    <div class="ci-deleted-placeholder">
                        <i class="fa fa-shield-alt"></i> Deleted by moderator
                    </div>
                    <!-- Show original body for site moderators -->
                    <div v-if="is_site_moderator && local_body" class="ci-deleted-original" v-html="local_body" />
                </template>
                <div v-else class="ci-text">
                    <a
                        v-if="comment.replied_to"
                        class="ci-mention"
                        href="#"
                        @click.prevent="scrollToComment(comment.replied_to.id)"
                    >@{{ comment.replied_to.user.username || comment.replied_to.user.name }}</a>
                    <span v-html="local_body" />
                </div>
            </div>

            <div v-else class="ci-edit-form">
                <div
                    ref="edit_editor"
                    contenteditable="true"
                    class="ci-edit-editor"
                    @input="onEditInput"
                    @keydown="onEditKeydown"
                />
                <div class="ci-edit-toolbar">
                    <div class="ci-edit-tools">
                        <button type="button" class="ci-tool" @click="execEdit('bold')"><b>B</b></button>
                        <button type="button" class="ci-tool ci-tool--i" @click="execEdit('italic')"><i>I</i></button>
                        <button type="button" class="ci-tool ci-tool--u" @click="execEdit('underline')"><u>U</u></button>
                        <button type="button" class="ci-tool ci-tool--s" @click="execEdit('strikeThrough')"><s>S</s></button>
                    </div>
                    <div class="ci-edit-right">
                        <span class="ci-edit-counter" :class="{ 'ci-edit-counter--warn': edit_char_count > props.max_len - 100, 'ci-edit-counter--over': edit_char_count > props.max_len }">
                            {{ edit_char_count }}/{{ props.max_len }}
                        </span>
                        <button class="ci-edit-cancel" @click="show_edit = false">Cancel</button>
                        <button
                            class="ci-edit-save"
                            :disabled="edit_loading || edit_char_count < 1 || edit_char_count > props.max_len"
                            @click="onEditSubmit"
                        >
                            <span v-if="edit_loading" class="ci-spinner"></span>
                            <span v-else>Save</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Show/Hide replies button -->
            <button
                v-if="!is_reply && (local_replies_count > 0 || replies.length > 0)"
                class="ci-show-replies-btn"
                @click="toggleReplies"
            >
                <i class="fa" :class="show_replies ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                {{ show_replies ? `Hide replies (${replies.length})` : `Show replies (${local_replies_count})` }}
            </button>

            <!-- Replies + form -->
            <div v-if="show_replies || show_reply_form" class="ci-replies">
                <div v-if="replies_loading" class="ci-replies-loading">
                    <span class="ci-spinner"></span>
                </div>

                <CommentItem
                    v-for="reply in replies"
                    :key="reply.id"
                    :comment="reply"
                    :is_reply="true"
                    :max_len="props.max_len"
                    :report_reasons="props.report_reasons"
                    @reply-to-me="onReplyToReply"
                    @deleted="onReplyDeleted"
                />

                <div v-if="show_reply_form" ref="reply_form_el" class="ci-reply-form">
                    <CommentForm
                        :loading="reply_loading"
                        :reply_to="reply_to_name"
                        :max_len="props.max_len"
                        @submit="onReplySubmit"
                        @cancel="show_reply_form = false; replying_to_comment = null"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ci-wrap {
    display: flex;
    gap: 12px;
    padding: 14px 0;
    border-bottom: 1px solid #f0f0f0;
}

.ci-anchored {
    animation: ci-highlight 3s ease forwards;
    border-radius: 6px;
    padding-left: 10px;
    padding-right: 10px;
}

@keyframes ci-highlight {
    0%, 30% { background-color: #c8edd9; }
    100% { background-color: transparent; }
}

.ci-reply {
    padding: 10px 0 0;
    border-bottom: none;
}

.ci-avatar-col { flex-shrink: 0; }

.ci-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    overflow: hidden;
    background: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    color: #aaa;
}

.ci-reply .ci-avatar { width: 32px; height: 32px; font-size: 13px; }
.ci-avatar img { width: 100%; height: 100%; object-fit: cover; }

.ci-body { flex: 1; min-width: 0; }

/* Header */
.ci-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 6px;
}

.ci-header-left { display: flex; flex-direction: column; gap: 2px; }

.ci-name-row {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.ci-name {
    font-size: 14px;
    font-weight: 700;
    color: #222;
}

.ci-username {
    font-size: 12px;
    color: #17b978;
    font-weight: 600;
}

.ci-date { font-size: 12px; color: #aaa; }

/* Reply badge */
.ci-reply-badge {
    background: none;
    border: 1px solid #ddd;
    border-radius: 20px;
    padding: 1px 10px;
    font-size: 12px;
    color: #888;
    cursor: pointer;
    transition: border-color 0.15s, color 0.15s;
    line-height: 1.6;
}

.ci-reply-badge:hover { border-color: #17b978; color: #17b978; }

/* Header right: likes + menu */
.ci-header-right {
    display: flex;
    align-items: center;
    gap: 2px;
    flex-shrink: 0;
}

.ci-like-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 13px;
    color: #aaa;
    padding: 3px 7px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: color 0.15s, background 0.15s;
}

.ci-like-btn:hover:not(:disabled) { background: #f3f3f3; color: #555; }
.ci-like-btn--active { color: #17b978 !important; }
.ci-like-btn--dis:hover:not(:disabled) { color: #e74c3c; }
.ci-like-btn--dis-active { color: #e74c3c !important; }
.ci-like-btn:disabled { opacity: 0.4; cursor: default; }

/* Comment text */
.ci-text {
    font-size: 14px;
    color: #444;
    line-height: 1.6;
    margin-bottom: 8px;
    word-break: break-word;
    white-space: pre-line;
}

/* @mention badge */
.ci-mention {
    display: inline-block;
    background: #e8f5e9;
    color: #17b978;
    font-weight: 600;
    font-size: 13px;
    border-radius: 4px;
    padding: 0 6px;
    margin-right: 4px;
    text-decoration: none;
    transition: background 0.15s;
}

.ci-mention:hover { background: #c8edd9; color: #13a368; }

/* Show replies button */
.ci-show-replies-btn {
    background: none;
    border: none;
    font-size: 13px;
    color: #17b978;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 4px;
    transition: color 0.15s;
}

.ci-show-replies-btn:hover { color: #13a368; }

/* Replies block */
.ci-replies {
    margin-top: 10px;
    padding-left: 14px;
    border-left: 2px solid #e8e8e8;
}

.ci-reply-form { margin-top: 10px; }

.ci-replies-loading { padding: 12px 0; display: flex; justify-content: center; }

.ci-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid #e0e0e0;
    border-top-color: #17b978;
    border-radius: 50%;
    animation: ci-spin 0.7s linear infinite;
    display: inline-block;
}

@keyframes ci-spin { to { transform: rotate(360deg); } }

/* Menu */
.ci-menu-wrap { position: relative; }

.ci-menu-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    color: #bbb;
    padding: 2px 6px;
    border-radius: 4px;
    letter-spacing: 1px;
    font-weight: 700;
    transition: color 0.15s, background 0.15s;
}

.ci-menu-btn:hover { background: #f3f3f3; color: #555; }

.ci-menu {
    position: absolute;
    right: 0;
    top: calc(100% + 4px);
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    min-width: 130px;
    z-index: 10;
    overflow: hidden;
}

.ci-menu-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    font-size: 13px;
    color: #555;
    cursor: pointer;
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    transition: background 0.1s;
}

.ci-menu-item:hover { background: #f8f8f8; }
.ci-menu-item--danger { color: #e74c3c; }

/* Edit form */
.ci-edit-form {
    border: 1px solid #17b978;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 8px;
    background: #fff;
}

.ci-edit-editor {
    min-height: 70px;
    max-height: 260px;
    overflow-y: auto;
    padding: 10px 12px;
    font-size: 14px;
    color: #333;
    outline: none;
    line-height: 1.5;
    white-space: pre-line;
}

.ci-edit-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 10px;
    border-top: 1px solid #e8f5e9;
    background: #f0fbf7;
    gap: 8px;
}

.ci-edit-tools {
    display: flex;
    gap: 2px;
}

.ci-edit-right {
    display: flex;
    align-items: center;
    gap: 6px;
}

.ci-edit-counter {
    font-size: 11px;
    color: #bbb;
    white-space: nowrap;
}

.ci-edit-counter--warn { color: #f39c12; }
.ci-edit-counter--over { color: #e74c3c; font-weight: 600; }

.ci-edit-cancel {
    background: none;
    border: none;
    font-size: 12px;
    color: #888;
    cursor: pointer;
    padding: 0 4px;
    transition: color 0.15s;
}

.ci-edit-cancel:hover { color: #333; }

.ci-edit-save {
    background: #17b978;
    color: #fff;
    border: none;
    border-radius: 5px;
    padding: 5px 14px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
    min-width: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ci-edit-save:hover:not(:disabled) { background: #13a368; }
.ci-edit-save:disabled { background: #aaa; cursor: default; }

/* Delete confirm inside menu */
.ci-menu-confirm {
    padding: 10px 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ci-menu-confirm-text {
    font-size: 13px;
    color: #333;
    font-weight: 500;
}

.ci-menu-confirm-btns {
    display: flex;
    gap: 6px;
}

.ci-menu-confirm-yes {
    flex: 1;
    padding: 6px;
    background: #e74c3c;
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 28px;
}

.ci-menu-confirm-yes:hover:not(:disabled) { background: #c0392b; }
.ci-menu-confirm-yes:disabled { opacity: 0.6; cursor: default; }

.ci-menu-confirm-no {
    flex: 1;
    padding: 6px;
    background: #f3f3f3;
    color: #555;
    border: none;
    border-radius: 5px;
    font-size: 12px;
    cursor: pointer;
    transition: background 0.15s;
}

.ci-menu-confirm-no:hover { background: #e8e8e8; }

.ci-spinner-sm {
    width: 12px;
    height: 12px;
    border: 2px solid rgba(255,255,255,0.4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: ci-spin 0.7s linear infinite;
    display: inline-block;
}

/* Report panel */
.ci-report-panel {
    position: absolute;
    right: 0;
    top: calc(100% + 4px);
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    padding: 12px;
    width: 210px;
    z-index: 10;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ci-report-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 600;
    color: #333;
}

.ci-report-close {
    background: none;
    border: none;
    color: #aaa;
    cursor: pointer;
    font-size: 13px;
    padding: 0 2px;
    line-height: 1;
    transition: color 0.15s;
}

.ci-report-close:hover { color: #555; }

.ci-report-select {
    width: 100%;
    padding: 7px 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 13px;
    color: #444;
    background: #fafafa;
    outline: none;
    cursor: pointer;
    appearance: auto;
    transition: border-color 0.15s;
}

.ci-report-select:focus { border-color: #17b978; }

.ci-report-submit {
    width: 100%;
    padding: 8px;
    background: #17b978;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
}

.ci-report-submit:hover:not(:disabled) { background: #13a368; }
.ci-report-submit:disabled { opacity: 0.5; cursor: default; }

.ci-report-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255,255,255,0.4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: ci-spin 0.7s linear infinite;
    display: inline-block;
}

/* Moderator badge */
.ci-mod-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    background: #e8f5e9;
    color: #17b978;
    font-size: 11px;
    font-weight: 700;
    border-radius: 4px;
    padding: 1px 7px;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    border: 1px solid #c8edd9;
}

/* Deleted-by-moderator placeholder */
.ci-deleted-placeholder {
    font-size: 13px;
    color: #aaa;
    font-style: italic;
    padding: 6px 0 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.ci-deleted-placeholder .fa {
    color: #ccc;
    font-size: 12px;
}

/* Original body shown to moderators below the placeholder */
.ci-deleted-original {
    font-size: 13px;
    color: #aaa;
    font-style: italic;
    padding: 0 0 8px;
    line-height: 1.5;
    word-break: break-word;
}

/* Restore menu item */
.ci-menu-item--restore {
    color: #17b978;
    display: flex;
    align-items: center;
    gap: 6px;
}

.ci-menu-item--restore:disabled {
    opacity: 0.6;
    cursor: default;
}
</style>
