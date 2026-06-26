<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import BasePagination from '@/components/ui/base/BasePagination.vue'
import AdminCommentCard from '@/components/ui/comments/AdminCommentCard.vue'
import CommentReportsModal from '@/components/ui/comments/CommentReportsModal.vue'
import CommentSortButtons from '@/components/ui/comments/CommentSortButtons.vue'
import { useCommentsFeed } from '@/composables/useAdminComments'
import type { CommentSort } from '@/composables/useAdminComments'
import axios from '@/plugins/axios'

type Filter = 'all' | 'reported' | 'deleted'

const current_page = ref(1)
const last_page = ref(1)
const total = ref(0)
const filter = ref<Filter>('all')
const sort = ref<CommentSort>('newest')

const {
    comments,
    is_loading,
    reports_comment_id,
    goToComment,
    openReports,
} = useCommentsFeed({
    on_new: (comment) => {
        if (filter.value !== 'all' || current_page.value !== 1) return
        comments.value.unshift(comment)
        total.value++
    },
})

const pagination = computed(() => ({
    current_page: current_page.value,
    last_page: last_page.value,
    prev_page_url: current_page.value > 1 ? String(current_page.value - 1) : null,
    next_page_url: current_page.value < last_page.value ? String(current_page.value + 1) : null,
}))

const FILTERS: { key: Filter; label: string }[] = [
    {
        key: 'all',
        label: 'All',
    },
    {
        key: 'reported',
        label: 'Reported',
    },
    {
        key: 'deleted',
        label: 'Deleted',
    },
]

function load(page = 1) {
    is_loading.value = true
    current_page.value = page
    axios.post('comments', {
        page,
        per_page: 20,
        filter: filter.value,
        sort: sort.value,
    }).then((res) => {
        const data = res.data.data
        comments.value = data.comments
        last_page.value = data.pagination.last_page
        total.value = data.pagination.total
    }).finally(() => {
        is_loading.value = false
    })
}

watch(filter, () => load(1))
watch(sort, () => load(1))

onMounted(() => {
    load()
})
</script>

<template>
    <AdminLayout>
        <PageBreadcrumb pageTitle="Comments" />

        <!-- Toolbar -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div class="flex gap-1">
                <button
                    v-for="f in FILTERS"
                    :key="f.key"
                    @click="filter = f.key"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="filter === f.key
                        ? 'bg-brand-500 text-white'
                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                >{{ f.label }}</button>
            </div>
            <CommentSortButtons v-if="filter === 'all'" v-model="sort" />
        </div>

        <BaseLoading v-if="is_loading" />

        <div v-else-if="!comments.length" class="py-12 text-center text-sm text-gray-400 dark:text-gray-600">
            No comments
        </div>

        <div v-else class="space-y-3">
            <AdminCommentCard
                v-for="comment in comments"
                :key="comment.id"
                :comment="comment"
                @reports="openReports"
                @go="goToComment"
            />
        </div>

        <!-- Pagination -->
        <div v-if="last_page > 1" class="mt-4 flex justify-end">
            <BasePagination
                :pagination="pagination"
                @change="load"
            />
        </div>

        <!-- Reports modal -->
        <CommentReportsModal
            v-if="reports_comment_id"
            :comment_id="reports_comment_id"
            @close="reports_comment_id = null"
        />
    </AdminLayout>
</template>
