<script setup lang="ts">
import RefreshIcon from '@/icons/RefreshIcon.vue'
import CheckIcon from '@/icons/CheckIcon.vue'
import ExternalLinkOutlineIcon from '@/icons/ExternalLinkOutlineIcon.vue'
import TrashOutlineIcon from '@/icons/TrashOutlineIcon.vue'
import { ref, reactive } from 'vue'
import axios from '@/plugins/axios'
import BaseModal from '@/components/ui/base/BaseModal.vue'
import BaseTabs from '@/components/ui/base/BaseTabs.vue'
import BaseBtn from '@/components/ui/base/BaseBtn.vue'
import BaseTablePagination from '@/components/ui/base/BaseTablePagination.vue'
import ArticlesTable from '@/views/Pages/Articles/ArticlesTable.vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'

const props = defineProps<{
    source: {
        id: number
        name: string
    }
}>()

const emit = defineEmits<{
    close: []
}>()

const auth = useAuthStore()
const toast = useToast()

const active_tab = ref('Imported')

// ── Imported: refresh all ─────────────────────────────────────────────────────

const articles_table = ref<InstanceType<typeof ArticlesTable> | null>(null)
const refreshing_all = ref(false)
const refresh_progress = ref(0)
const refresh_total = ref(0)

function refreshAll(last_id = 0) {
    if (last_id === 0) {
        refreshing_all.value = true
        refresh_progress.value = 0
        refresh_total.value = 0
    }

    axios.post('rss/refresh-articles', {
        source_id: props.source.id,
        last_id,
        limit: 5,
    })
        .then((res) => {
            const data = res.data.data
            refresh_progress.value += data.processed

            if (refresh_total.value === 0) {
                refresh_total.value = data.processed + data.remaining
            }

            if (data.remaining > 0 && data.processed > 0) {
                refreshAll(data.last_id)
            } else {
                refreshing_all.value = false
                toast.success(`Refreshed ${refresh_progress.value} articles`)
                articles_table.value?.reload()
            }
        })
        .catch(() => {
            refreshing_all.value = false
            toast.error('Refresh failed')
        })
}

// ── Rejected ──────────────────────────────────────────────────────────────────

const rejected_table = ref<InstanceType<typeof BaseTablePagination> | null>(null)
const retrying = ref<Record<number, boolean>>({})
const retrying_all = ref(false)
const retry_progress = ref(0)
const retry_total = ref(0)
const retry_imported = ref(0)
const deleting_all = ref(false)

function retryAll(last_id = 0) {
    if (last_id === 0) {
        retrying_all.value = true
        retry_progress.value = 0
        retry_total.value = 0
        retry_imported.value = 0
    }

    axios.post('rss/retry-all', {
        source_id: props.source.id,
        last_id,
        limit: 5,
    })
        .then((res) => {
            const data = res.data.data
            retry_progress.value += data.processed
            retry_imported.value += data.imported

            if (retry_total.value === 0) {
                retry_total.value = data.processed + data.remaining
            }

            if (data.remaining > 0 && data.processed > 0) {
                retryAll(data.last_id)
            } else {
                retrying_all.value = false
                toast.success(`Retried ${retry_progress.value} items, imported ${retry_imported.value}`)
                rejected_table.value?.reload()
                articles_table.value?.reload()
            }
        })
        .catch(() => {
            retrying_all.value = false
            toast.error('Retry failed')
        })
}

const rejected_headers = reactive([
    {
        key: 'id',
        text: 'Id',
    },
    {
        key: 'title',
        text: 'Title',
    },
    {
        key: 'reason_code',
        text: 'Type',
    },
    {
        key: 'reason',
        text: 'Reason',
    },
    {
        key: 'created_at',
        text: 'Date',
        time: true,
    },
    ...(auth.accesses('rss', 'edit') ? [{
        key: 'action',
        text: 'Action',
    }] : []),
])

const code_classes: Record<string, string> = {
    blacklist: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    body_too_short: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    skip_url: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    duplicate: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    error: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
}

function itemLink(data: any): string | null {
    if (data.link) return data.link
    if (typeof data.guid === 'string' && data.guid.startsWith('http')) return data.guid
    return null
}

function deleteItem(data: any) {
    if (retrying.value[data.id]) return
    retrying.value[data.id] = true

    axios.post(`rss/items/delete/${data.id}`)
        .then(() => {
            toast.success('Item removed from the list')
            rejected_table.value?.reload()
        })
        .catch((e) => {
            toast.error(e.response?.data?.errors?.message ?? 'Failed to remove item')
        })
        .finally(() => {
            retrying.value[data.id] = false
        })
}

function deleteAllRejected() {
    if (deleting_all.value) return
    deleting_all.value = true

    axios.post('rss/items/delete-rejected', {
        source_id: props.source.id,
    })
        .then((res) => {
            toast.success(`Removed ${res.data.data.deleted} items from the list`)
            rejected_table.value?.reload()
        })
        .catch(() => {
            toast.error('Failed to delete all rejected items')
        })
        .finally(() => {
            deleting_all.value = false
        })
}

function retryItem(data: any, force = false) {
    if (retrying.value[data.id]) return
    retrying.value[data.id] = true

    axios.post(`rss/retry/${data.id}`, { force })
        .then(() => {
            toast.success('Item imported successfully')
            rejected_table.value?.reload()
            articles_table.value?.reload()
        })
        .catch((e) => {
            toast.error(e.response?.data?.errors?.message ?? 'Retry failed')
            rejected_table.value?.reload()
        })
        .finally(() => {
            retrying.value[data.id] = false
        })
}
</script>

<template>
    <BaseModal @close="emit('close')">
        <template #body>
            <div class="no-scrollbar relative max-h-[85vh] w-full max-w-5xl overflow-y-auto rounded-3xl bg-white p-6 dark:bg-gray-900 mx-4">
                <button
                    @click="emit('close')"
                    class="absolute right-5 top-5 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-white/[0.07]"
                >✕</button>

                <h4 class="mb-5 text-xl font-semibold text-gray-800 dark:text-white/90">
                    {{ source.name }}
                </h4>

                <BaseTabs
                    :tabs="['Imported', 'Rejected']"
                    :current="active_tab"
                    @updateTab="active_tab = $event"
                >
                    <!-- Imported articles -->
                    <div v-if="active_tab === 'Imported'">
                        <ArticlesTable ref="articles_table" :params="{ rss_source_id: source.id }">
                            <template #header_right>
                                <BaseBtn
                                    v-if="auth.accesses('rss', 'edit')"
                                    color="info"
                                    add_class="shrink-0 whitespace-nowrap inline-flex items-center gap-1.5"
                                    :disabled="refreshing_all"
                                    @click="refreshAll()"
                                >
                                    <RefreshIcon :class="['w-4 h-4', refreshing_all ? 'animate-spin' : '']" />
                                    <template v-if="refreshing_all">
                                        {{ refresh_progress }}<template v-if="refresh_total"> / {{ refresh_total }}</template>
                                    </template>
                                    <template v-else>Refresh all</template>
                                </BaseBtn>
                            </template>
                        </ArticlesTable>
                    </div>

                    <!-- Rejected / failed items -->
                    <div v-else>
                        <BaseTablePagination
                            ref="rejected_table"
                            route="rss/items"
                            :headers="rejected_headers"
                            :params="{ source_id: source.id }"
                        >
                            <template #header_right>
                                <template v-if="auth.accesses('rss', 'edit')">
                                    <BaseBtn
                                        color="error"
                                        add_class="shrink-0 whitespace-nowrap inline-flex items-center gap-1.5"
                                        :disabled="deleting_all || retrying_all"
                                        @click="deleteAllRejected()"
                                    >
                                        <TrashOutlineIcon :class="['w-4 h-4', deleting_all ? 'animate-pulse' : '']" />
                                        Delete all
                                    </BaseBtn>
                                    <BaseBtn
                                        color="info"
                                        add_class="shrink-0 whitespace-nowrap inline-flex items-center gap-1.5"
                                        :disabled="retrying_all || deleting_all"
                                        @click="retryAll()"
                                    >
                                        <RefreshIcon :class="['w-4 h-4', retrying_all ? 'animate-spin' : '']" />
                                        <template v-if="retrying_all">
                                            {{ retry_progress }}<template v-if="retry_total"> / {{ retry_total }}</template>
                                        </template>
                                        <template v-else>Retry all</template>
                                    </BaseBtn>
                                </template>
                            </template>

                            <template #id="{ data }">
                                <span class="text-sm text-gray-400">{{ data.id }}</span>
                            </template>

                            <template #title="{ data }">
                                <a
                                    v-if="itemLink(data)"
                                    :href="itemLink(data)!"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-start gap-1.5 text-sm font-medium text-gray-800 hover:text-brand-500 dark:text-white/90"
                                >
                                    <span class="line-clamp-2 max-w-xs">{{ data.title }}</span>
                                    <ExternalLinkOutlineIcon class="mt-0.5 w-3.5 h-3.5 shrink-0 text-gray-400" />
                                </a>
                                <span v-else class="text-sm font-medium text-gray-800 dark:text-white/90 line-clamp-2 max-w-xs">
                                    {{ data.title }}
                                </span>
                            </template>

                            <template #reason_code="{ data }">
                                <span
                                    v-if="data.reason_code"
                                    :class="[
                                        'inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded text-xs font-medium',
                                        code_classes[data.reason_code] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                                    ]"
                                >
                                    {{ data.reason_code }}
                                </span>
                            </template>

                            <template #reason="{ data }">
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ data.reason }}</span>
                            </template>

                            <template #action="{ data }">
                                <div class="flex items-center gap-3">
                                    <button
                                        @click="retryItem(data)"
                                        :disabled="retrying[data.id]"
                                        class="text-gray-500 hover:text-brand-500 disabled:opacity-50"
                                        title="Retry import"
                                    >
                                        <RefreshIcon :class="['w-5 h-5', retrying[data.id] ? 'animate-spin' : '']" />
                                    </button>
                                    <button
                                        @click="retryItem(data, true)"
                                        :disabled="retrying[data.id]"
                                        class="text-gray-500 hover:text-success-600 disabled:opacity-50 dark:hover:text-success-500"
                                        title="Force import — ignore blacklist and length checks"
                                    >
                                        <CheckIcon class="w-5 h-5" />
                                    </button>
                                    <button
                                        @click="deleteItem(data)"
                                        :disabled="retrying[data.id]"
                                        class="text-gray-500 hover:text-error-500 disabled:opacity-50 dark:hover:text-error-400"
                                        title="Remove from list — the item stays in the journal and will not be re-imported"
                                    >
                                        <TrashOutlineIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </template>
                        </BaseTablePagination>
                    </div>
                </BaseTabs>
            </div>
        </template>
    </BaseModal>
</template>
