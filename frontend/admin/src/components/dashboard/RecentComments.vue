<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import AdminCommentCard from '@/components/ui/comments/AdminCommentCard.vue'
import CommentReportsModal from '@/components/ui/comments/CommentReportsModal.vue'
import { useCommentsFeed } from '@/composables/useAdminComments'
import axios from '@/plugins/axios'

const router = useRouter()

const {
    comments,
    is_loading,
    reports_comment_id,
    goToComment,
    openReports,
} = useCommentsFeed({
    on_new: (comment) => {
        comments.value.unshift(comment)
        if (comments.value.length > 10) comments.value.pop()
    },
})

function load() {
    is_loading.value = true
    axios.get('comments/recent').then((res) => {
        comments.value = res.data.data.comments
    }).finally(() => {
        is_loading.value = false
    })
}

onMounted(() => {
    load()
})
</script>

<template>
    <div class="h-full flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">
        <div class="flex items-center justify-between mb-4 flex-shrink-0">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Recent Comments</h3>
            <button
                class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
                @click="router.push({ name: 'comments' })"
            >
                See all
            </button>
        </div>

        <BaseLoading v-if="is_loading" />

        <div v-else-if="!comments.length" class="flex-1 flex items-center justify-center py-8 text-sm text-gray-400 dark:text-gray-600">
            No comments yet
        </div>

        <div v-else class="flex-1 min-h-0 overflow-y-auto custom-scrollbar space-y-2 py-2 pr-1">
            <AdminCommentCard
                v-for="comment in comments"
                :key="comment.id"
                :comment="comment"
                compact
                @reports="openReports"
                @go="goToComment"
            />
        </div>

        <CommentReportsModal
            v-if="reports_comment_id"
            :comment_id="reports_comment_id"
            @close="reports_comment_id = null"
        />
    </div>
</template>
