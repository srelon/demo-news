<script setup lang="ts">
import { ref, onMounted } from 'vue'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import BaseModal from '@/components/ui/base/BaseModal.vue'
import { formatCommentDate } from '@/composables/useAdminComments'
import axios from '@/plugins/axios'

interface Report {
    id: number
    reason: string | null
    user: { id: number; name: string; username: string | null } | null
    created_at: string
}

const props = defineProps<{
    comment_id: number
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const is_loading = ref(true)
const reports = ref<Report[]>([])


onMounted(() => {
    axios.get(`comment/${props.comment_id}/reports`).then((res) => {
        reports.value = res.data.data.reports
    }).finally(() => {
        is_loading.value = false
    })
})
</script>

<template>
    <BaseModal @close="emit('close')">
        <template #body>
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 dark:bg-gray-900 mx-4">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">Reports</h3>
                <BaseLoading v-if="is_loading" />
                <div v-else-if="!reports.length" class="text-sm text-gray-400 text-center py-4">
                    No reports
                </div>
                <div v-else class="space-y-3 max-h-80 overflow-y-auto">
                    <div
                        v-for="r in reports"
                        :key="r.id"
                        class="rounded-lg border border-gray-100 dark:border-gray-800 p-3"
                    >
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ r.user?.name ?? 'Unknown' }}
                                <span v-if="r.user?.username" class="text-brand-500 font-normal text-xs"> @{{ r.user.username }}</span>
                            </span>
                            <span class="text-xs text-gray-400">{{ formatCommentDate(r.created_at) }}</span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ r.reason ?? '—' }}</p>
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
