<script setup lang="ts">
import { ref, onMounted } from 'vue'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import BaseModal from '@/components/ui/base/BaseModal.vue'
import CommentAuthor from './CommentAuthor.vue'
import CommentReactionButton from './CommentReactionButton.vue'
import axios from '@/plugins/axios'
import type { AdminComment } from './types'

const props = defineProps<{
    comment_id: number
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const is_loading = ref(true)
const comment = ref<AdminComment | null>(null)

onMounted(() => {
    axios.get(`comment/${props.comment_id}`).then((res) => {
        comment.value = res.data.data.comment
    }).finally(() => {
        is_loading.value = false
    })
})
</script>

<template>
    <BaseModal @close="emit('close')">
        <template #body>
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 dark:bg-gray-900 mx-4">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">Parent comment</h3>

                <BaseLoading v-if="is_loading" />

                <div v-else-if="!comment" class="text-sm text-gray-400 text-center py-4">
                    Comment not found
                </div>

                <div v-else>
                    <div class="flex items-start justify-between gap-3 mb-2.5">
                        <CommentAuthor
                            :user="comment.user"
                            :created_at="comment.created_at"
                            :deleted="comment.status !== 1"
                        />
                        <div class="flex items-center gap-1 shrink-0">
                            <CommentReactionButton type="like" :count="comment.likes" readonly />
                            <CommentReactionButton type="dislike" :count="comment.dislikes" readonly />
                        </div>
                    </div>
                    <div class="cpm-body max-h-60 overflow-y-auto text-sm text-gray-700 leading-relaxed dark:text-gray-300">
                        <span v-html="comment.body" />
                    </div>
                </div>

                <button
                    @click="emit('close')"
                    class="mt-4 w-full rounded-lg border border-gray-200 py-2 text-sm text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors"
                >Close</button>
            </div>
        </template>
    </BaseModal>
</template>

<style scoped>
.cpm-body { white-space: pre-line; }
.cpm-body :deep(b), .cpm-body :deep(strong) { font-weight: 600; }
.cpm-body :deep(i), .cpm-body :deep(em) { font-style: italic; }
.cpm-body :deep(u) { text-decoration: underline; }
.cpm-body :deep(s) { text-decoration: line-through; }
.cpm-body :deep(br) { display: block; content: ''; margin-top: 0.25rem; }
.cpm-body :deep(a) { color: #465fff; text-decoration: underline; }
</style>
