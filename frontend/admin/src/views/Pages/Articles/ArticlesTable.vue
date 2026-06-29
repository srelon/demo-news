<script setup lang="ts">
import EditOutlineIcon from '@/icons/EditOutlineIcon.vue'
import RefreshIcon from '@/icons/RefreshIcon.vue'
import TrashOutlineIcon from '@/icons/TrashOutlineIcon.vue'
import { ref, reactive } from 'vue'
import axios from '@/plugins/axios'
import BaseTablePagination from '@/components/ui/base/BaseTablePagination.vue'
import BaseBtn from '@/components/ui/base/BaseBtn.vue'
import BaseModal from '@/components/ui/base/BaseModal.vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'

const props = withDefaults(defineProps<{
    params?: Record<string, any>
    filterable_category?: boolean
    update_url?: boolean
}>(), {
    params: () => ({}),
    filterable_category: false,
    update_url: true,
})

const emit = defineEmits<{
    filter_category: [category_id: number | null, subcategory_id: number | null]
}>()

const auth = useAuthStore()
const toast = useToast()
const baseUrl = import.meta.env.VITE_API_BASE_URL

const table = ref<InstanceType<typeof BaseTablePagination> | null>(null)
const refreshing = ref<Record<number, boolean>>({})

function reload() {
    table.value?.reload()
}

defineExpose({
    reload,
})

const delete_article = ref<any>(null)

function deleteArticle() {
    if (!delete_article.value) return
    const article = delete_article.value

    axios.post(`article/delete/${article.id}`)
        .then(() => {
            delete_article.value = null
            toast.success(`Article deleted: ${article.title}`)
            reload()
        })
        .catch((e) => {
            toast.error(e.response?.data?.errors?.message ?? 'Failed to delete article')
        })
}

function refreshArticle(data: any) {
    if (refreshing.value[data.id]) return
    refreshing.value[data.id] = true

    axios.post(`article/refresh/${data.id}`)
        .then(() => {
            toast.success('Article refreshed from source')
            reload()
        })
        .catch((e) => {
            toast.error(e.response?.data?.errors?.message ?? 'Failed to refresh article')
        })
        .finally(() => {
            refreshing.value[data.id] = false
        })
}

const headers = reactive([
    {
        key: 'id',
        text: 'Id',
    },
    {
        key: 'image',
        text: 'Image',
    },
    {
        key: 'title',
        text: 'Title',
    },
    {
        key: 'category',
        text: 'Category',
    },
    {
        key: 'status',
        text: 'Status',
    },
    {
        key: 'published_at',
        text: 'Published',
        time: true,
    },
    {
        key: 'action',
        text: 'Action',
    },
])
</script>

<template>
    <BaseTablePagination ref="table" route="articles" :headers="headers" :params="props.params" :update_url="props.update_url">

        <template #header_right>
            <slot name="header_right" />
        </template>

        <template #image="{ data }">
            <div class="w-12 h-9 overflow-hidden rounded bg-gray-100">
                <img v-if="data.image" :src="baseUrl + data.image" :alt="data.title" class="w-full h-full object-cover" />
            </div>
        </template>

        <template #title="{ data }">
            <router-link
                :to="{ name: 'article', params: { id: data.id } }"
                class="font-medium text-gray-800 hover:text-brand-500 dark:text-white/90 dark:hover:text-brand-400 line-clamp-2 max-w-xs"
            >
                {{ data.title }}
            </router-link>
        </template>

        <template #category="{ data }">
            <span v-if="props.filterable_category" class="text-sm text-gray-500">
                <button
                    @click="emit('filter_category', data.subcategory?.category?.id ?? null, null)"
                    class="hover:text-brand-500 hover:underline"
                    title="Filter by category"
                >{{ data.subcategory?.category?.name }}</button>
                /
                <button
                    @click="emit('filter_category', data.subcategory?.category?.id ?? null, data.subcategory?.id ?? null)"
                    class="hover:text-brand-500 hover:underline"
                    title="Filter by subcategory"
                >{{ data.subcategory?.name }}</button>
            </span>
            <span v-else class="text-sm text-gray-500">{{ data.subcategory?.category?.name }} / {{ data.subcategory?.name }}</span>
        </template>

        <template #status="{ data }">
            <span
                :class="[
                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                    data.status === 'published'
                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                ]"
            >
                {{ data.status }}
            </span>
        </template>

        <template #action="{ data }">
            <div class="flex items-center gap-3">
                <router-link :to="{ name: 'article', params: { id: data.id } }" class="text-gray-500 hover:text-gray-800 dark:hover:text-white/80">
                    <EditOutlineIcon class="w-5 h-5" />
                </router-link>
                <button
                    v-if="data.source_type === 'rss' && auth.accesses('articles', 'edit')"
                    @click="refreshArticle(data)"
                    :disabled="refreshing[data.id]"
                    class="text-gray-500 hover:text-brand-500 disabled:opacity-50"
                    title="Refresh from source"
                >
                    <RefreshIcon :class="['w-5 h-5', refreshing[data.id] ? 'animate-spin' : '']" />
                </button>
                <button
                    v-if="auth.accesses('articles', 'edit')"
                    @click="delete_article = data"
                    class="text-gray-500 hover:text-error-500 dark:hover:text-error-400"
                    title="Delete"
                >
                    <TrashOutlineIcon class="w-5 h-5" />
                </button>
            </div>
        </template>

    </BaseTablePagination>

    <!-- Delete confirmation -->
    <BaseModal v-if="delete_article" @close="delete_article = null">
        <template #body>
            <div class="no-scrollbar relative w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-6 dark:bg-gray-900 mx-5">
                <button
                    @click="delete_article = null"
                    class="absolute right-5 top-5 flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-white/[0.07]"
                >✕</button>

                <h4 class="mb-2 text-xl font-semibold text-gray-800 dark:text-white/90">Delete Article</h4>

                <div class="mb-5 space-y-1 text-sm text-gray-500 dark:text-gray-400">
                    <p><span class="font-medium text-gray-700 dark:text-gray-300">Title:</span> {{ delete_article.title }}</p>
                    <p v-if="delete_article.source_name"><span class="font-medium text-gray-700 dark:text-gray-300">Source:</span> {{ delete_article.source_name }}</p>
                    <p v-if="delete_article.source_type === 'rss'" class="text-xs">The journal entry is kept, so the article will not be re-imported.</p>
                </div>

                <div class="flex justify-end gap-3">
                    <BaseBtn color="secondary" @click="delete_article = null">Cancel</BaseBtn>
                    <BaseBtn color="error" @click="deleteArticle">Delete</BaseBtn>
                </div>
            </div>
        </template>
    </BaseModal>
</template>
