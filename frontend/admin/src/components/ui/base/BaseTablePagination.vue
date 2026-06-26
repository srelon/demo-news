<script setup lang="ts">
import SearchIcon from '@/icons/SearchIcon.vue'
import { ref, computed, watch, useSlots } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BasePagination from '@/components/ui/base/BasePagination.vue'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import BaseTable from '@/components/ui/base/BaseTable.vue'
import axios from '@/plugins/axios.js'

type RouteType = 'get' | 'post' | 'put' | 'delete' | 'patch'

const props = withDefaults(defineProps<{
    headers: any[]
    route: string
    route_type?: RouteType
    select_page?: number[] | false
    params?: Record<string, any>
    row_class?: (data: any) => string
    row_attrs?: (data: any, index: number) => Record<string, any>
}>(), {
    route_type: 'post',
    select_page: () => [10, 50, 100],
    params: () => ({}),
})

defineSlots<{
    header_right: any
    header_below: any
    [key: string]: (props: { data: any; item: any }) => any
}>()

const slots = useSlots()

// All slots except header_right are passed through to BaseTable
const table_slots = computed(() => {
    const { header_right, ...rest } = slots
    return rest
})

interface intPage {
    current_page: number
    last_page: number
    prev_page_url: string | null
    next_page_url: string | null
    total?: string
    per_page?: string
    from?: string
    data?: any
    [key: string]: any
}

const route = useRoute()
const router = useRouter()

const per_page = ref(props.select_page ? props.select_page[0] : 10)
const page = ref<intPage | null>(null)
const loading = ref(false)
const search = ref(null)
const current_page = ref(Number(route.query.page) || 1)

loadPage()

function changePage(val = 1) {
    current_page.value = val
    if (val === 1) router.replace({ query: { ...route.query, page: undefined } })
    loadPage()
}

function changePerPage() {
    current_page.value = 1
    loadPage()
}

let timer: ReturnType<typeof setTimeout> | null = null

watch(search, () => {
    if (timer) clearTimeout(timer)
    timer = setTimeout(() => loadPage(), 600)
})

watch(() => props.params, () => changePage(1), { deep: true })

defineExpose({
    reload: loadPage,
})

function loadPage() {
    if (loading.value) return
    loading.value = true

    axios[props.route_type](props.route, {
        per_page: per_page.value,
        page: current_page.value,
        search: search.value,
        ...props.params,
    }).then((response: any) => {
        let data = response.data

        // Laravel paginator is wrapped in the response "data" key —
        // detect it by structure, not by total (total can be 0)
        if (data.data && typeof data.data === 'object' && 'current_page' in data.data) {
            data = data.data
        }

        page.value = data
    }).finally(() => {
        loading.value = false
    })
}
</script>

<template>
    <BaseLoading v-if="!page" />

    <div v-else class="rounded-xl bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <!-- Header: per-page select + search + slot -->
        <div class="flex flex-col gap-2 px-4 py-4 border border-b-0 border-gray-100 dark:border-white/[0.05] rounded-t-xl sm:flex-row sm:items-center sm:justify-between">
            <div v-if="select_page" class="flex shrink-0 items-center gap-3">
                <span class="text-gray-500 dark:text-gray-400">Show</span>
                <div class="relative z-20">
                    <select
                        v-model="per_page"
                        @change="changePerPage"
                        class="w-full py-2 pl-3 pr-8 text-sm text-gray-800 bg-transparent border border-gray-300 rounded-lg appearance-none h-9 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                    >
                        <option v-for="(item, key) in select_page" :key="key" :value="item">{{ item }}</option>
                    </select>
                    <span class="absolute z-30 text-gray-500 -translate-y-1/2 pointer-events-none right-2 top-1/2 dark:text-gray-400">
                        <svg class="stroke-current" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3.8335 5.9165L8.00016 10.0832L12.1668 5.9165" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <span class="text-gray-500 dark:text-gray-400">entries</span>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                <div class="relative min-w-[180px] flex-1 sm:max-w-[300px]">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search..."
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pl-11 pr-4 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                    />
                    <button class="absolute text-gray-500 -translate-y-1/2 left-4 top-1/2 dark:text-gray-400">
                        <SearchIcon class="fill-current w-5 h-5" />
                    </button>
                </div>
                <slot name="header_right" />
            </div>
        </div>

        <!-- Active filter chips (optional slot below the header row) -->
        <div v-if="$slots.header_below" class="border-x border-gray-100 dark:border-white/[0.05]">
            <slot name="header_below" />
        </div>

        <!-- Table -->
        <div class="max-w-full overflow-x-auto">
            <BaseLoading v-if="loading" />
            <template v-else>
                <BaseTable plain :headers="headers" :table="page.data ?? []" :row_class="row_class" :row_attrs="row_attrs">
                    <template v-for="(_, name) in table_slots" :key="name" #[name]="slotProps">
                        <slot :name="name" v-bind="slotProps ?? {}" />
                    </template>
                </BaseTable>
                <p v-if="!page.data?.length" class="border border-t-0 border-gray-100 py-8 text-center text-sm text-gray-400 dark:border-white/[0.05]">
                    No entries
                </p>
            </template>
        </div>

        <!-- Pagination -->
        <div v-if="page.total" class="border border-t-0 rounded-b-xl border-gray-100 py-4 pl-[18px] pr-4 dark:border-white/[0.05]">
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between">
                <p class="pb-3 text-sm font-medium text-center text-gray-500 border-b border-gray-100 dark:border-gray-800 dark:text-gray-400 xl:border-b-0 xl:pb-0 xl:text-left">
                    Showing {{ page.from }} to {{ page.per_page }} of {{ page.total }} entries
                </p>
                <BasePagination :pagination="page" @change="changePage" />
            </div>
        </div>
    </div>
</template>
