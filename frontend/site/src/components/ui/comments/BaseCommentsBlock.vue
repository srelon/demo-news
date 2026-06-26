<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useLayoutStore } from '@/stores/layout'
import axios from '@/plugins/axios'
import CommentForm from './CommentForm.vue'
import CommentItem from './CommentItem.vue'
import type { CommentData } from './CommentItem.vue'

const props = defineProps<{
    id: number
}>()

const emit = defineEmits<{
    total: [count: number]
}>()

const MAX_LEN = 1000
const REPORT_REASONS = [
    'Spam',
    'Harassment',
    'Hate speech',
    'Misinformation',
    'Inappropriate content',
    'Other',
]

const route = useRoute()
const authStore = useAuthStore()
const layoutStore = useLayoutStore()

const comments = ref<CommentData[]>([])
const is_loading = ref(true)
const submit_loading = ref(false)
const sort = ref<'newest' | 'oldest' | 'rating'>('newest')
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

watch(() => pagination.value.total, (val) => emit('total', val))

const pin_id = computed<number | null>(() => {
    const pin = route.query.pin
    return pin ? parseInt(String(pin), 10) : null
})

function loadComments(page = 1, with_pin = true, scroll = true) {
    is_loading.value = true
    const params: Record<string, any> = { sort: sort.value, page }
    if (with_pin && pin_id.value) params.pin = pin_id.value

    axios.get(`comments/${props.id}`, { params }).then((res) => {
        const data = res.data.data
        comments.value = page === 1 ? data.comments : [...comments.value, ...data.comments]
        pagination.value = data.pagination
        if (page === 1 && scroll) nextTick(() => scrollToAnchor())
    }).finally(() => { is_loading.value = false })
}

function onSortChange(s: 'newest' | 'oldest' | 'rating') {
    sort.value = s
    loadComments(1, false, false)
}

function onSubmit(body: string) {
    if (!authStore.user) { authStore.openAuthModal(); return }
    submit_loading.value = true

    axios.post('comments', { article_id: props.id, body })
        .finally(() => { submit_loading.value = false })
}

function onCommentDeleted(id: number) {
    comments.value = comments.value.filter((c) => c.id !== id)
    pagination.value.total = Math.max(0, pagination.value.total - 1)
}

function onWsCommentDeleted(e: Event) {
    const { comment_id, parent_id, deleted_by } = (e as CustomEvent<{ comment_id: number; parent_id: number | null; deleted_by?: number | null }>).detail
    if (parent_id !== null) return

    if (deleted_by === 1) {
        // Moderator delete — replace with placeholder in-place
        const comment = comments.value.find(c => c.id === comment_id)
        if (comment) {
            comment.deleted_by = 1
        }
    } else {
        onCommentDeleted(comment_id)
    }
}

function onWsCommentRestored(e: Event) {
    const { comment_id, parent_id, body } = (e as CustomEvent<{ comment_id: number; parent_id: number | null; body: string }>).detail
    if (parent_id !== null) return
    const comment = comments.value.find(c => c.id === comment_id)
    if (comment) {
        comment.deleted_by = null
        comment.body = body
    }
}

function onWsComment(e: Event) {
    const comment = (e as CustomEvent<CommentData>).detail
    if (comment.article_id !== props.id) return

    if (!comment.parent_id) {
        comments.value.unshift(comment)
        pagination.value.total++
    } else {
        window.dispatchEvent(new CustomEvent('ws:reply.new', { detail: comment }))
    }
}

function scrollToAnchor() {
    if (!route.hash) return
    const target_id = parseInt(route.hash.replace('#comment-', ''), 10)
    if (isNaN(target_id)) return

    const el = document.getElementById(`comment-${target_id}`)
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' })
    } else if (pin_id.value) {
        // Элемент не найден — это reply, нужно открыть ответы родителя
        window.dispatchEvent(new CustomEvent('cs:open-replies', {
            detail: { parent_id: pin_id.value, scroll_to: target_id },
        }))
    }
}

onMounted(() => {
    loadComments()
    layoutStore.subscribeArticle(props.id)
    window.addEventListener('ws:comment.new', onWsComment)
    window.addEventListener('ws:comment.deleted', onWsCommentDeleted)
    window.addEventListener('ws:comment.restored', onWsCommentRestored)
})

onBeforeUnmount(() => {
    layoutStore.unsubscribeArticle(props.id)
    window.removeEventListener('ws:comment.new', onWsComment)
    window.removeEventListener('ws:comment.deleted', onWsCommentDeleted)
    window.removeEventListener('ws:comment.restored', onWsCommentRestored)
})

watch(
    () => [String(route.query.pin ?? ''), route.hash],
    ([new_pin, new_hash], [old_pin, old_hash]) => {
        if (new_pin !== old_pin) {
            // Пин сменился — перезагружаем список (scroll внутри loadComments)
            loadComments(1)
        } else if (new_hash !== old_hash && new_hash) {
            // Только хэш сменился — просто скроллим
            nextTick(() => scrollToAnchor())
        }
    },
)
</script>

<template>
    <div class="cs-wrap">
        <h4 class="f1-l-4 cl3 p-b-20">
            Comments
            <span v-if="pagination.total" class="cs-count">{{ pagination.total }}</span>
        </h4>

        <!-- New comment form -->
        <div class="cs-form-wrap p-b-30">
            <template v-if="authStore.user">
                <CommentForm :loading="submit_loading" :max_len="MAX_LEN" @submit="onSubmit" />
            </template>
            <div v-else class="cs-auth-prompt">
                <span>
                    <a href="#" @click.prevent="authStore.openAuthModal()" class="cs-auth-link">Sign in</a>
                    to leave a comment
                </span>
            </div>
        </div>

        <!-- Sort -->
        <div v-if="comments.length || !is_loading" class="cs-sort p-b-20">
            <span class="cs-sort-label">Sort:</span>
            <button
                v-for="s in [['newest', 'Newest first'], ['oldest', 'Oldest first'], ['rating', 'By rating']]"
                :key="s[0]"
                class="cs-sort-btn"
                :class="{ 'cs-sort-btn--active': sort === s[0] }"
                @click="onSortChange(s[0] as any)"
            >{{ s[1] }}</button>
        </div>

        <!-- Comments list -->
        <div v-if="is_loading && !comments.length" class="cs-loading">
            <span class="cs-spinner"></span>
        </div>

        <div v-else-if="!comments.length" class="cs-empty">
            No comments yet. Be the first!
        </div>

        <div v-else>
            <CommentItem
                v-for="comment in comments"
                :key="comment.id"
                :comment="comment"
                :max_len="MAX_LEN"
                :report_reasons="REPORT_REASONS"
                @deleted="onCommentDeleted"
            />

            <div v-if="pagination.current_page < pagination.last_page" class="cs-load-more">
                <button class="cs-load-more-btn" :disabled="is_loading" @click="loadComments(pagination.current_page + 1)">
                    Load more
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.cs-wrap {
    padding-top: 20px;
    border-top: 2px solid #f0f0f0;
}

.cs-count {
    font-size: 14px;
    font-weight: 600;
    color: #888;
    margin-left: 6px;
}

.cs-auth-prompt {
    padding: 18px;
    background: #fafafa;
    border: 1px dashed #ddd;
    border-radius: 8px;
    font-size: 14px;
    color: #888;
    text-align: center;
}

.cs-auth-link {
    color: #17b978;
    font-weight: 600;
    text-decoration: none;
}

.cs-auth-link:hover { text-decoration: underline; }

.cs-sort {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.cs-sort-label {
    font-size: 13px;
    color: #888;
    margin-right: 4px;
}

.cs-sort-btn {
    background: none;
    border: 1px solid #ddd;
    border-radius: 20px;
    padding: 4px 14px;
    font-size: 13px;
    color: #555;
    cursor: pointer;
    transition: all 0.15s;
}

.cs-sort-btn:hover { border-color: #17b978; color: #17b978; }
.cs-sort-btn--active { background: #17b978; border-color: #17b978; color: #fff; cursor: default; }
.cs-sort-btn--active:hover { color: #fff; }

.cs-loading, .cs-empty {
    padding: 30px 0;
    text-align: center;
    color: #aaa;
    font-size: 14px;
}

.cs-spinner {
    width: 24px;
    height: 24px;
    border: 3px solid #e0e0e0;
    border-top-color: #17b978;
    border-radius: 50%;
    animation: cs-spin 0.7s linear infinite;
    display: inline-block;
}

@keyframes cs-spin { to { transform: rotate(360deg); } }

.cs-load-more {
    padding: 20px 0;
    text-align: center;
}

.cs-load-more-btn {
    background: none;
    border: 1px solid #17b978;
    color: #17b978;
    border-radius: 6px;
    padding: 8px 24px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s;
}

.cs-load-more-btn:hover { background: #17b978; color: #fff; }
.cs-load-more-btn:disabled { opacity: 0.5; cursor: default; }
</style>
