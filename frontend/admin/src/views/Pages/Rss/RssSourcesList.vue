<script setup lang="ts">
import EditOutlineIcon from '@/icons/EditOutlineIcon.vue'
import ListIcon from '@/icons/ListIcon.vue'
import TrashOutlineIcon from '@/icons/TrashOutlineIcon.vue'
import RefreshIcon from '@/icons/RefreshIcon.vue'
import { ref, reactive } from 'vue'
import { useForm } from 'vee-validate'
import { object, string } from 'yup'
import axios from '@/plugins/axios.ts'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import BaseInput from '@/components/ui/base/BaseInput.vue'
import BaseBtn from '@/components/ui/base/BaseBtn.vue'
import BaseTable from '@/components/ui/base/BaseTable.vue'
import BaseModal from '@/components/ui/base/BaseModal.vue'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import BaseCascadeSelect from '@/components/ui/base/BaseCascadeSelect.vue'
import RssItemsModal from '@/views/Pages/Rss/RssItemsModal.vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'

const auth = useAuthStore()
const toast = useToast()

// ── Types ─────────────────────────────────────────────────────────────────────

interface RssSource {
    id: number
    name: string
    url: string
    active: boolean
    subcategory_id: number
    subcategory: {
        id: number
        name: string
        category_id: number
    } | null
    last_fetched_at: string | null
    last_status: 'ok' | 'error' | null
    last_error: string | null
    imported_count: number
    rejected_count: number
}

interface Option {
    id: number
    name: string
}

interface SubOption {
    id: number
    name: string
    category_id: number
}

// ── State ─────────────────────────────────────────────────────────────────────

const sources = ref<RssSource[]>([])
const is_loading = ref(true)
const categories = ref<Option[]>([])
const subcategories = ref<SubOption[]>([])

const show_form = ref(false)
const edit_id = ref<number | null>(null)
const delete_source = ref<RssSource | null>(null)
const items_source = ref<RssSource | null>(null)
const subcategory_error = ref(false)
const is_fetching = ref(false)
const refreshing_all = ref(false)
const refresh_progress = ref(0)
const refresh_total = ref(0)

const source_form = ref({
    name: '',
    url: '',
    subcategory_id: null as number | null,
})

// ── Table headers ─────────────────────────────────────────────────────────────

const headers = reactive([
    {
        key: 'id',
        text: 'ID',
    },
    {
        key: 'name',
        text: 'Source',
    },
    {
        key: 'subcategory',
        text: 'Subcategory',
    },
    {
        key: 'stats',
        text: 'Imported / Rejected',
    },
    {
        key: 'last_fetch',
        text: 'Last Fetch',
    },
    {
        key: 'active',
        text: 'Active',
    },
    ...(auth.accesses('rss', 'edit') ? [{
        key: 'action',
        text: 'Action',
    }] : []),
])

// ── Validation ────────────────────────────────────────────────────────────────

const schema = object({
    name: string().required().min(2),
    url: string().required().url(),
})

const { handleSubmit, resetForm, setErrors } = useForm({ validationSchema: schema })

// ── Load ──────────────────────────────────────────────────────────────────────

function loadSources() {
    is_loading.value = true

    axios.get('rss')
        .then((res) => {
            sources.value = res.data.data
        })
        .finally(() => {
            is_loading.value = false
        })
}

function loadOptions() {
    axios.get('rss/options')
        .then((res) => {
            categories.value = res.data.data.categories
            subcategories.value = res.data.data.subcategories
        })
}

loadSources()
loadOptions()

// ── Add / Edit ────────────────────────────────────────────────────────────────

function openCreate() {
    edit_id.value = null
    source_form.value = {
        name: '',
        url: '',
        subcategory_id: null,
    }
    subcategory_error.value = false
    resetForm()
    show_form.value = true
}

function openEdit(source: RssSource) {
    edit_id.value = source.id
    source_form.value = {
        name: source.name,
        url: source.url,
        subcategory_id: source.subcategory_id,
    }
    subcategory_error.value = false
    resetForm({
        values: {
            name: source.name,
            url: source.url,
        },
    })
    show_form.value = true
}

function closeForm() {
    show_form.value = false
    edit_id.value = null
}

const saveSource = handleSubmit(() => {
    if (!source_form.value.subcategory_id) {
        subcategory_error.value = true
        return
    }

    subcategory_error.value = false

    const req = edit_id.value !== null
        ? axios.post(`rss/edit/${edit_id.value}`, source_form.value)
        : axios.post('rss/create', source_form.value)

    req
        .then(() => {
            toast.success('Saved successfully')
            closeForm()
            loadSources()
        })
        .catch((e) => {
            if (e.response?.data?.errors) setErrors(e.response.data.errors)
        })
})

// ── Fetch ─────────────────────────────────────────────────────────────────────

function fetchNow() {
    is_fetching.value = true
    axios.post('rss/fetch')
        .then((res) => {
            sources.value = res.data.data
            toast.success('RSS fetch completed')
        })
        .catch(() => {
            toast.error('Fetch failed')
        })
        .finally(() => {
            is_fetching.value = false
        })
}

// ── Refresh all articles ──────────────────────────────────────────────────────

function refreshAll(last_id = 0) {
    if (last_id === 0) {
        refreshing_all.value = true
        refresh_progress.value = 0
        refresh_total.value = 0
    }

    axios.post('rss/refresh-articles', {
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
            }
        })
        .catch(() => {
            refreshing_all.value = false
            toast.error('Refresh failed')
        })
}

// ── Toggle ────────────────────────────────────────────────────────────────────

function toggleSource(source: RssSource) {
    axios.post(`rss/toggle/${source.id}`)
        .then((res) => {
            source.active = res.data.data.active
        })
}

// ── Delete ────────────────────────────────────────────────────────────────────

function deleteSource() {
    if (!delete_source.value) return
    const source = delete_source.value

    axios.post(`rss/delete/${source.id}`)
        .then(() => {
            delete_source.value = null
            loadSources()
            toast.success(`Source deleted: ${source.name}`)
        })
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatDate(value: string | null): string {
    if (!value) return '—'
    return new Date(value).toLocaleString()
}
</script>

<template>
    <AdminLayout>
        <PageBreadcrumb pageTitle="RSS Sources" />

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <!-- Header -->
            <div class="flex flex-col gap-2 px-4 py-4 border-b border-gray-100 dark:border-white/[0.05] sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Active sources are fetched automatically every 5 minutes.
                </p>
                <div v-if="auth.accesses('rss', 'edit')" class="flex items-center gap-2">
                    <BaseBtn color="secondary" size="sm" :disabled="is_fetching" @click="fetchNow">
                        {{ is_fetching ? 'Fetching…' : 'Fetch Now' }}
                    </BaseBtn>
                    <BaseBtn
                        color="info"
                        size="sm"
                        add_class="inline-flex items-center gap-1.5"
                        :disabled="refreshing_all"
                        @click="refreshAll()"
                    >
                        <RefreshIcon :class="['w-4 h-4', refreshing_all ? 'animate-spin' : '']" />
                        <template v-if="refreshing_all">
                            {{ refresh_progress }}<template v-if="refresh_total"> / {{ refresh_total }}</template>
                        </template>
                        <template v-else>Refresh all</template>
                    </BaseBtn>
                    <BaseBtn color="success" size="sm" @click="openCreate">
                        Add Source
                    </BaseBtn>
                </div>
            </div>

            <!-- Table -->
            <BaseLoading v-if="is_loading && !sources.length" />
            <BaseTable
                v-else
                :headers="headers"
                :table="sources"
            >
                <!-- ID cell -->
                <template #id="{ data }">
                    <span class="text-sm text-gray-400">{{ data.id }}</span>
                </template>

                <!-- Name + URL -->
                <template #name="{ data }">
                    <div class="text-sm text-gray-700 dark:text-gray-300">{{ data.name }}</div>
                    <a :href="data.url" target="_blank" rel="noopener" class="block max-w-[280px] truncate text-xs text-gray-400 hover:text-brand-500">
                        {{ data.url }}
                    </a>
                </template>

                <!-- Subcategory -->
                <template #subcategory="{ data }">
                    <span class="text-sm text-gray-700 dark:text-gray-400">{{ data.subcategory?.name ?? '—' }}</span>
                </template>

                <!-- Stats -->
                <template #stats="{ data }">
                    <button
                        @click="items_source = data"
                        class="group inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 hover:border-brand-400 hover:bg-brand-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-brand-500 dark:hover:bg-brand-500/10 transition-colors"
                        title="Show imported / rejected items"
                    >
                        <ListIcon class="w-4 h-4 text-gray-400 group-hover:text-brand-500" />
                        <span class="text-sm font-medium text-success-600 dark:text-success-500">{{ data.imported_count }}</span>
                        <span class="text-sm text-gray-400">/</span>
                        <span class="text-sm font-medium text-error-500">{{ data.rejected_count }}</span>
                    </button>
                </template>

                <!-- Last fetch -->
                <template #last_fetch="{ data }">
                    <div class="text-sm text-gray-700 dark:text-gray-400">{{ formatDate(data.last_fetched_at) }}</div>
                    <div v-if="data.last_status === 'error'" class="max-w-[220px] truncate text-xs text-error-500" :title="data.last_error ?? ''">
                        {{ data.last_error }}
                    </div>
                    <div v-else-if="data.last_status === 'ok'" class="text-xs text-success-600 dark:text-success-500">OK</div>
                </template>

                <!-- Active toggle -->
                <template #active="{ data }">
                    <button
                        v-if="auth.accesses('rss', 'edit')"
                        @click="toggleSource(data)"
                        :class="[
                            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                            data.active ? 'bg-brand-500' : 'bg-gray-300 dark:bg-gray-700',
                        ]"
                    >
                        <span
                            :class="[
                                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                data.active ? 'translate-x-6' : 'translate-x-1',
                            ]"
                        />
                    </button>
                    <span v-else class="text-sm" :class="data.active ? 'text-success-600' : 'text-gray-400'">
                        {{ data.active ? 'On' : 'Off' }}
                    </span>
                </template>

                <!-- Action cell -->
                <template #action="{ data }">
                    <div class="flex items-center gap-3">
                        <button @click="openEdit(data)" class="text-gray-400 hover:text-gray-700 dark:hover:text-white/80" title="Edit">
                            <EditOutlineIcon class="w-4 h-4" />
                        </button>
                        <button @click="delete_source = data" class="text-gray-400 hover:text-error-500 dark:hover:text-error-400" title="Delete">
                            <TrashOutlineIcon class="w-4 h-4" />
                        </button>
                    </div>
                </template>
            </BaseTable>
        </div>

        <!-- Create / Edit modal -->
        <BaseModal v-if="show_form" @close="closeForm">
            <template #body>
                <div class="no-scrollbar relative w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-6 dark:bg-gray-900 mx-5">
                    <button
                        @click="closeForm"
                        class="absolute right-5 top-5 flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-white/[0.07]"
                    >✕</button>

                    <h4 class="mb-5 text-xl font-semibold text-gray-800 dark:text-white/90">
                        {{ edit_id === null ? 'Add RSS Source' : 'Edit RSS Source' }}
                    </h4>

                    <div class="space-y-4" @keydown.enter.prevent="saveSource">
                        <BaseInput name="name" type="text" placeholder="Name (e.g. RBC)" v-model="source_form.name" />
                        <BaseInput name="url" type="text" placeholder="Feed URL (https://...)" v-model="source_form.url" />
                        <div>
                            <BaseCascadeSelect
                                v-model="source_form.subcategory_id"
                                :categories="categories"
                                :subcategories="subcategories"
                                :error="subcategory_error"
                            />
                            <p v-if="subcategory_error" class="mt-1 text-xs text-error-500">Subcategory is required</p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <BaseBtn color="secondary" @click="closeForm">Cancel</BaseBtn>
                        <BaseBtn color="success" @click="saveSource">Save</BaseBtn>
                    </div>
                </div>
            </template>
        </BaseModal>

        <!-- Imported / Rejected items modal -->
        <RssItemsModal
            v-if="items_source"
            :source="items_source"
            @close="items_source = null; loadSources()"
        />

        <!-- Delete modal -->
        <BaseModal v-if="delete_source" @close="delete_source = null">
            <template #body>
                <div class="no-scrollbar relative w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-6 dark:bg-gray-900 mx-5">
                    <button
                        @click="delete_source = null"
                        class="absolute right-5 top-5 flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-white/[0.07]"
                    >✕</button>

                    <h4 class="mb-2 text-xl font-semibold text-gray-800 dark:text-white/90">Delete RSS Source</h4>

                    <div class="mb-5 space-y-1 text-sm text-gray-500 dark:text-gray-400">
                        <p><span class="font-medium text-gray-700 dark:text-gray-300">Name:</span> {{ delete_source.name }}</p>
                        <p><span class="font-medium text-gray-700 dark:text-gray-300">URL:</span> {{ delete_source.url }}</p>
                        <p class="text-xs">Imported articles will not be deleted.</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <BaseBtn color="secondary" @click="delete_source = null">Cancel</BaseBtn>
                        <BaseBtn color="error" @click="deleteSource">Delete</BaseBtn>
                    </div>
                </div>
            </template>
        </BaseModal>
    </AdminLayout>
</template>
