<script setup lang="ts">
import { computed, inject } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useModeratorStore } from '@/stores/moderator'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import CommentAuthor from './CommentAuthor.vue'
import CommentBodyForm from './CommentBodyForm.vue'
import CommentReactionButton from './CommentReactionButton.vue'
import ChevronDownSmIcon from '@/icons/ChevronDownSmIcon.vue'
import ChevronUpSmIcon from '@/icons/ChevronUpSmIcon.vue'
import DeleteTrashIcon from '@/icons/DeleteTrashIcon.vue'
import EditPencilIcon from '@/icons/EditPencilIcon.vue'
import ReportFlagIcon from '@/icons/ReportFlagIcon.vue'
import RestoreIcon from '@/icons/RestoreIcon.vue'
import type { ArticleComment, ArticleCommentsCtx } from './types'

const props = defineProps<{
    comment: ArticleComment
    is_reply?: boolean
}>()

const ctx = inject('article_comments') as ArticleCommentsCtx
const moderator = useModeratorStore()
const auth = useAuthStore()

const can_moderate = computed(() => auth.accesses('moderator', 'edit'))

const is_deleted = computed(() => props.comment.status !== 1)
const is_editing = computed(() => ctx.edit_comment?.id === props.comment.id)
const is_replying = computed(() => ctx.reply_to?.id === props.comment.id)
const replies = computed(() => ctx.expanded_replies[props.comment.id])
</script>

<template>
    <div
        :id="`admin-comment-${comment.id}`"
        class="rounded-xl border transition-colors duration-700"
        :class="[
            is_reply
                ? 'border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-white/[0.02]'
                : 'border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]',
            { 'ring-2 ring-brand-400': ctx.highlighted_id === comment.id },
        ]"
    >
        <div>
            <!-- Header -->
            <div class="flex items-start justify-between gap-3 mb-2.5">
                <CommentAuthor
                    :user="comment.user"
                    :created_at="comment.created_at"
                    :deleted="is_deleted"
                    :small="is_reply"
                />
                <!-- Right: likes + dislikes + actions -->
                <div class="flex items-center gap-1 shrink-0">
                    <CommentReactionButton
                        type="like"
                        :count="comment.likes"
                        :active="comment.user_reaction === 2"
                        :disabled="is_deleted || !moderator.user || ctx.like_loading[comment.id]"
                        @click="ctx.likeComment(comment, 2)"
                    />
                    <CommentReactionButton
                        type="dislike"
                        :count="comment.dislikes"
                        :active="comment.user_reaction === 1"
                        :disabled="is_deleted || !moderator.user || ctx.like_loading[comment.id]"
                        @click="ctx.likeComment(comment, 1)"
                    />
                    <button
                        v-if="comment.reports_count > 0"
                        @click="ctx.openReports(comment)"
                        class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-2.5 py-1 text-xs font-medium text-red-500 hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-900/20 transition-colors"
                    >
                        <ReportFlagIcon class="w-3.5 h-3.5" />
                        {{ comment.reports_count }}
                    </button>
                    <button
                        v-if="!is_deleted && can_moderate"
                        @click="ctx.startEdit(comment)"
                        class="p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:text-brand-500 hover:bg-brand-50 dark:hover:bg-brand-500/10 transition-colors"
                        title="Edit"
                    >
                        <EditPencilIcon class="w-4 h-4" />
                    </button>
                    <button
                        v-if="!is_deleted && can_moderate"
                        @click="ctx.deleteComment(comment)"
                        class="p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors"
                        title="Delete"
                    >
                        <DeleteTrashIcon class="w-4 h-4" />
                    </button>
                    <button
                        v-if="is_deleted && comment.deleted_by === 1 && can_moderate"
                        @click="ctx.restoreComment(comment)"
                        :disabled="ctx.restore_loading[comment.id]"
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
                v-model="ctx.edit_body"
                submit_label="Save"
                :loading="ctx.edit_loading"
                class="mb-2"
                @submit="ctx.submitEdit(comment)"
                @cancel="ctx.cancelEdit()"
            />
            <div v-else class="aci-body text-sm text-gray-700 leading-relaxed dark:text-gray-300" :class="{ 'opacity-50': is_deleted }">
                <a
                    v-if="comment.replied_to"
                    class="mr-1 inline-block rounded bg-brand-50 px-1.5 py-0.5 text-xs font-medium text-brand-500 dark:bg-brand-900/20"
                >@{{ comment.replied_to.user.username || comment.replied_to.user.name }}</a>
                <span v-html="comment.body" />
            </div>

            <!-- Footer actions -->
            <div class="flex items-center gap-3 mt-3">
                <button
                    v-if="!is_deleted && can_moderate"
                    @click="ctx.openReply(comment)"
                    class="text-xs font-medium text-gray-400 hover:text-brand-500 transition-colors"
                >Reply</button>
                <button
                    v-if="!is_reply && (comment.replies_count > 0 || replies)"
                    @click="ctx.loadReplies(comment)"
                    class="text-xs font-medium text-brand-500 hover:text-brand-600 transition-colors"
                >
                    <span v-if="replies">
                        <ChevronUpSmIcon class="inline w-3 h-3 mr-0.5" />
                        Hide replies ({{ replies.length }})
                    </span>
                    <span v-else>
                        <ChevronDownSmIcon class="inline w-3 h-3 mr-0.5" />
                        Show replies ({{ comment.replies_count }})
                    </span>
                </button>
            </div>
        </div>

        <!-- Reply form -->
        <div v-if="is_replying" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
            <CommentBodyForm
                v-model="ctx.reply_body"
                submit_label="Post reply"
                placeholder="Write a reply..."
                :loading="ctx.reply_loading"
                @submit="ctx.submitReply()"
                @cancel="ctx.cancelReply()"
            />
        </div>

        <!-- Replies (recursive) -->
        <template v-if="!is_reply">
            <div v-if="ctx.replies_loading[comment.id]" class="mt-3">
                <BaseLoading />
            </div>
            <div v-else-if="replies" class="mt-3 space-y-2 border-l-2 border-gray-100 dark:border-gray-800 pl-4">
                <ArticleCommentItem
                    v-for="reply in replies"
                    :key="reply.id"
                    :comment="reply"
                    is_reply
                />
            </div>
        </template>
    </div>
</template>

<style scoped>
.aci-body { white-space: pre-line; }
.aci-body :deep(b), .aci-body :deep(strong) { font-weight: 600; }
.aci-body :deep(i), .aci-body :deep(em) { font-style: italic; }
.aci-body :deep(u) { text-decoration: underline; }
.aci-body :deep(s) { text-decoration: line-through; }
.aci-body :deep(br) { display: block; content: ''; margin-top: 0.25rem; }
.aci-body :deep(a) { color: #465fff; text-decoration: underline; }
</style>
