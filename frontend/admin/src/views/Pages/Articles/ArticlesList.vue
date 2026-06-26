<script setup lang="ts">
import { ref, computed } from 'vue'
import axios from '@/plugins/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ArticlesTable from '@/views/Pages/Articles/ArticlesTable.vue'
import ArticleDuplicatesModal from '@/views/Pages/Articles/ArticleDuplicatesModal.vue'
import BaseBtn from '@/components/ui/base/BaseBtn.vue'
import BaseFilterDropdown from '@/components/ui/base/BaseFilterDropdown.vue'
import type { FilterField } from '@/components/ui/base/BaseFilterDropdown.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const currentPageTitle = ref('Articles')

const table = ref<InstanceType<typeof ArticlesTable> | null>(null)
const show_duplicates = ref(false)

function closeDuplicates() {
    show_duplicates.value = false
    table.value?.reload()
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

const source_options = ref<string[]>([])
const category_options = ref<Option[]>([])
const subcategory_options = ref<SubOption[]>([])

const category_id = ref<number | ''>('')
const subcategory_id = ref<number | ''>('')
const source_name = ref('')
const sort = ref('date_desc')

const filtered_subcategories = computed(() =>
    category_id.value
        ? subcategory_options.value.filter(s => s.category_id === category_id.value)
        : subcategory_options.value
)

const sort_options = [
    {
        value: 'date_desc',
        text: 'Newest first',
    },
    {
        value: 'date_asc',
        text: 'Oldest first',
    },
    {
        value: 'views_desc',
        text: 'Most viewed',
    },
    {
        value: 'comments_desc',
        text: 'Most commented',
    },
]

axios.get('articles/source-options').then((res) => {
    source_options.value = res.data.data
})

axios.get('articles/options').then((res) => {
    category_options.value = res.data.data.category_options ?? []
    subcategory_options.value = res.data.data.subcategory_options ?? []
})

// ── Filter dropdown ────────────────────────────────────────────────────────────

const filter_model = computed(() => ({
    category_id: category_id.value,
    subcategory_id: subcategory_id.value,
    source_name: source_name.value,
    sort: sort.value,
}))

const filter_fields = computed((): FilterField[] => [
    {
        key: 'category_id',
        label: 'Category',
        placeholder: 'All categories',
        type: 'number',
        options: category_options.value.map(c => ({
            value: c.id,
            text: c.name,
        })),
    },
    {
        key: 'subcategory_id',
        label: 'Subcategory',
        placeholder: 'All subcategories',
        type: 'number',
        options: filtered_subcategories.value.map(s => ({
            value: s.id,
            text: s.name,
        })),
    },
    {
        key: 'source_name',
        label: 'Source',
        placeholder: 'All sources',
        options: source_options.value.map(n => ({
            value: n,
            text: n,
        })),
    },
    {
        key: 'sort',
        label: 'Sort',
        options: sort_options,
    },
])

function onFilterUpdate(val: Record<string, any>) {
    const old = filter_model.value

    if (val.category_id !== old.category_id) {
        category_id.value = val.category_id
        subcategory_id.value = ''
        return
    }

    if (val.subcategory_id !== old.subcategory_id) {
        subcategory_id.value = val.subcategory_id
        if (val.subcategory_id) {
            const sub = subcategory_options.value.find(s => s.id === val.subcategory_id)
            if (sub) category_id.value = sub.category_id
        }
        return
    }

    source_name.value = val.source_name
    sort.value = val.sort
}

function onFilterCategory(cat_id: number | null, sub_id: number | null) {
    category_id.value = cat_id ?? ''
    subcategory_id.value = sub_id ?? ''
}

// ── Table params ───────────────────────────────────────────────────────────────

const table_params = computed(() => ({
    source_name: source_name.value || null,
    category_id: category_id.value || null,
    subcategory_id: subcategory_id.value || null,
    sort: sort.value,
}))

// ── Active filter chips ────────────────────────────────────────────────────────

interface Chip {
    key: string
    prefix: string
    label: string
    clear: () => void
}

const active_chips = computed((): Chip[] => {
    const chips: Chip[] = []

    if (category_id.value) {
        const cat = category_options.value.find(c => c.id === category_id.value)
        chips.push({
            key: 'category',
            prefix: 'Category',
            label: cat?.name ?? String(category_id.value),
            clear: () => {
                category_id.value = ''
                subcategory_id.value = ''
            },
        })
    }

    if (subcategory_id.value) {
        const sub = subcategory_options.value.find(s => s.id === subcategory_id.value)
        chips.push({
            key: 'subcategory',
            prefix: 'Subcategory',
            label: sub?.name ?? String(subcategory_id.value),
            clear: () => { subcategory_id.value = '' },
        })
    }

    if (source_name.value) {
        chips.push({
            key: 'source',
            prefix: 'Source',
            label: source_name.value,
            clear: () => { source_name.value = '' },
        })
    }

    if (sort.value !== 'date_desc') {
        const s = sort_options.find(o => o.value === sort.value)
        chips.push({
            key: 'sort',
            prefix: 'Sort',
            label: s?.text ?? sort.value,
            clear: () => { sort.value = 'date_desc' },
        })
    }

    return chips
})

function clearAll() {
    category_id.value = ''
    subcategory_id.value = ''
    source_name.value = ''
    sort.value = 'date_desc'
}
</script>

<template>
    <AdminLayout>
        <PageBreadcrumb :pageTitle="currentPageTitle" />

        <ArticlesTable
            ref="table"
            :params="table_params"
            filterable_category
            @filter_category="onFilterCategory"
        >
            <template #header_right>
                <div class="flex items-center gap-2">
                    <BaseFilterDropdown
                        :filters="filter_fields"
                        :model-value="filter_model"
                        @update:model-value="onFilterUpdate"
                    />

                    <BaseBtn color="warning" add_class="shrink-0 whitespace-nowrap" @click="show_duplicates = true">
                        Duplicates
                    </BaseBtn>

                    <BaseBtn v-if="auth.accesses('articles', 'edit')" :to="{ name: 'article.create' }" color="info" add_class="shrink-0 whitespace-nowrap">
                        Add Article
                    </BaseBtn>
                </div>
            </template>

            <template v-if="active_chips.length" #header_below>
                <div class="flex flex-wrap items-center gap-2 px-4 py-2.5">
                    <span class="text-xs text-gray-400 dark:text-gray-500">Active filters:</span>

                    <button
                        v-for="chip in active_chips"
                        :key="chip.key"
                        @click="chip.clear()"
                        class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-0.5 text-xs text-gray-600 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-600 dark:border-gray-700 dark:bg-white/[0.04] dark:text-gray-400 dark:hover:border-red-800 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                    >
                        <span class="font-medium">{{ chip.prefix }}:</span>
                        <span>{{ chip.label }}</span>
                        <svg class="h-3 w-3 shrink-0" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2 2l8 8M10 2L2 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                    </button>

                    <button
                        v-if="active_chips.length > 1"
                        @click="clearAll()"
                        class="text-xs text-gray-400 underline hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400"
                    >
                        Clear all
                    </button>
                </div>
            </template>
        </ArticlesTable>

        <ArticleDuplicatesModal v-if="show_duplicates" @close="closeDuplicates" />
    </AdminLayout>
</template>
