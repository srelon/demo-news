<script setup lang="ts">
import { ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import ChatIcon from '@/icons/ChatIcon.vue'
import CommentAuthor from './CommentAuthor.vue'
import CommentBodyForm from './CommentBodyForm.vue'
import CommentParentModal from './CommentParentModal.vue'
import CommentReactionButton from './CommentReactionButton.vue'
import DeleteTrashIcon from '@/icons/DeleteTrashIcon.vue'
import EditPencilIcon from '@/icons/EditPencilIcon.vue'
import ExternalLinkIcon from '@/icons/ExternalLinkIcon.vue'
import ReportFlagIcon from '@/icons/ReportFlagIcon.vue'
import RestoreIcon from '@/icons/RestoreIcon.vue'
import { deleteCommentRequest, editCommentRequest, restoreCommentRequest } from '@/composables/useAdminComments'
import type { AdminComment } from './types'

const props = defineProps<{
    comment: AdminComment
    compact?: boolean
}>()

const emit = defineEmits<{
    (e: 'reports', comment: AdminComment): void
    (e: 'go', comment: AdminComment): void
}>()

const toast = useToast()
const auth = useAuthStore()

const can_moderate = computed(() => auth.accesses('moderator', 'edit'))

const is_editing = ref(false)
const edit_body = ref('')
const edit_loading = ref(false)
const show_parent = ref(false)
const delete_loading = ref(false)
const restore_loading = ref(false)

const base_url = import.meta.env.VITE_API_BASE_URL

const is_deleted = computed(() => props.comment.status !== 1)

function startEdit() {
    is_editing.value = true
    edit_body.value = props.comment.body ?? ''
}

function cancelEdit() {
    is_editing.value = false
    edit_body.value = ''
}

function submitEdit() {
    if (!edit_body.value.trim() || edit_loading.value) return
    edit_loading.value = true
    editCommentRequest(props.comment.id, edit_body.value).then((body) => {
        props.comment.body = body
        is_editing.value = false
        edit_body.value = ''
        toast.success('Comment updated')
    }).finally(() => {
        edit_loading.value = false
    })
}

function deleteComment() {
    if (delete_loading.value) return
    delete_loading.value = true
    deleteCommentRequest(props.comment.id).then(() => {
        props.comment.status = 3
        props.comment.deleted_by = 1
        toast.success('Comment deleted')
    }).finally(() => {
        delete_loading.value = false
    })
}

function restoreComment() {
    if (restore_loading.value) return
    restore_loading.value = true
    restoreCommentRequest(props.comment.id).then(() => {
        props.comment.status = 1
        props.comment.deleted_by = null
        toast.success('Comment restored')
    }).finally(() => {
        restore_loading.value = false
    })
}

</script>

<template>
    <div
        class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
        :class="compact ? 'p-3' : 'p-4'"
    >
        <!-- Article link -->
        <div v-if="comment.article" class="flex items-center gap-2 mb-3">
            <button
                @click="emit('go', comment)"
                class="shrink-0 w-10 h-7 overflow-hidden rounded bg-gray-100 dark:bg-gray-800 block"
            >
                <img
                    v-if="comment.article.image"
                    :src="base_url + comment.article.image"
                    :alt="comment.article.title"
                    class="w-full h-full object-cover"
                />
            </button>
            <button
                @click="emit('go', comment)"
                class="text-xs text-gray-400 hover:text-brand-500 truncate text-left"
            >{{ comment.article.title }}</button>
            <a
                v-if="comment.article.url"
                :href="comment.article.url"
                target="_blank"
                class="shrink-0 p-1 rounded text-gray-500 dark:text-gray-400 hover:text-brand-500 transition-colors"
                title="Open on site"
            >
                <ExternalLinkIcon class="w-3.5 h-3.5" />
            </a>
        </div>

        <div>
            <!-- Header -->
            <div class="flex items-start justify-between gap-3 mb-2.5">
                <CommentAuthor
                    :user="comment.user"
                    :created_at="comment.created_at"
                    :deleted="is_deleted"
                    :small="compact"
                    :reply="!!comment.parent_id"
                />

                <!-- Right: like counts + actions -->
                <div class="flex items-center gap-1 shrink-0">
                    <CommentReactionButton type="like" :count="comment.likes" readonly />
                    <CommentReactionButton type="dislike" :count="comment.dislikes" readonly />
                    <button
                        v-if="comment.reports_count > 0"
                        @click="emit('reports', comment)"
                        class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-2.5 py-1 text-xs font-medium text-red-500 hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-900/20 transition-colors"
                    >
                        <ReportFlagIcon class="w-3.5 h-3.5" />
                        {{ comment.reports_count }}
                    </button>
                    <button
                        v-if="comment.parent_id"
                        @click="show_parent = true"
                        class="p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:text-brand-500 hover:bg-brand-50 dark:hover:bg-brand-500/10 transition-colors"
                        title="Show parent comment"
                    >
                        <ChatIcon class="w-4 h-4" />
                    </button>
                    <button
                        v-if="!is_deleted && can_moderate"
                        @click="startEdit"
                        class="p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:text-brand-500 hover:bg-brand-50 dark:hover:bg-brand-500/10 transition-colors"
                        title="Edit"
                    >
                        <EditPencilIcon class="w-4 h-4" />
                    </button>
                    <button
                        v-if="!is_deleted && can_moderate"
                        @click="deleteComment"
                        :disabled="delete_loading"
                        class="p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors disabled:opacity-50"
                        title="Delete"
                    >
                        <DeleteTrashIcon class="w-4 h-4" />
                    </button>
                    <button
                        v-if="is_deleted && comment.deleted_by === 1 && can_moderate"
                        @click="restoreComment"
                        :disabled="restore_loading"
                        class="p-1.5 rounded-lg text-green-500 hover:text-green-600 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-500/10 transition-colors disabled:opacity-50"
                        title="Restore"
                    >
                        <RestoreIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Body / edit form -->
            <CommentBodyForm
                v-if="is_editing"
                v-model="edit_body"
                submit_label="Save"
                :loading="edit_loading"
                @submit="submitEdit"
                @cancel="cancelEdit"
            />
            <div v-else class="acc-body text-sm text-gray-700 leading-relaxed dark:text-gray-300" :class="{ 'opacity-50': is_deleted }">
                <span v-html="comment.body" />
            </div>
        </div>

        <!-- Parent comment modal (fetched on open) -->
        <CommentParentModal
            v-if="show_parent && comment.parent_id"
            :comment_id="comment.parent_id"
            @close="show_parent = false"
        />
    </div>
</template>

<style scoped>
.acc-body { white-space: pre-line; }
.acc-body :deep(b), .acc-body :deep(strong) { font-weight: 600; }
.acc-body :deep(i), .acc-body :deep(em) { font-style: italic; }
.acc-body :deep(u) { text-decoration: underline; }
.acc-body :deep(s) { text-decoration: line-through; }
.acc-body :deep(br) { display: block; content: ''; margin-top: 0.25rem; }
.acc-body :deep(a) { color: #465fff; text-decoration: underline; }
</style>
