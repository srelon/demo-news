<script setup lang="ts">
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import TableCard from '@/components/ui/TableCard.vue'
import BaseTable from '@/components/ui/base/BaseTable.vue'
import CheckIcon from '@/icons/CheckIcon.vue'
import DraftIcon from '@/icons/DraftIcon.vue'

defineProps<{
    news: {
        id: number
        title: string
        status: string
        image: string | null
        category: string
        subcategory: string
        created_at: string
    }[]
}>()

const router = useRouter()
const base_url = import.meta.env.VITE_API_BASE_URL

const headers = reactive([
    {
        key: 'image',
        text: 'Image',
        class: 'w-14',
    },
    {
        key: 'title',
        text: 'Title',
    },
    {
        key: 'category',
        text: 'Category',
        class: 'whitespace-nowrap',
    },
    {
        key: 'status',
        text: '',
        class: 'w-6',
    },
    {
        key: 'created_at',
        text: 'Date',
        class: 'whitespace-nowrap',
    },
])

function row_class() {
    return 'border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-white/[0.02] cursor-pointer'
}
</script>

<template>
    <TableCard title="Recent News" action_label="See all" @action="router.push({ name: 'articles' })">
        <BaseTable plain :headers="headers" :table="news" :row_class="row_class" @row_click="(item) => router.push({ name: 'article', params: { id: item.id } })">
            <template #image="{ data }">
                <div class="w-12 h-9 overflow-hidden rounded bg-gray-100 dark:bg-gray-800">
                    <img
                        v-if="data.image"
                        :src="base_url + data.image"
                        :alt="data.title"
                        class="w-full h-full object-cover"
                    />
                </div>
            </template>

            <template #title="{ data }">
                <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90 max-w-[240px] truncate">{{ data.title }}</p>
                <span class="text-gray-400 text-theme-xs">{{ data.subcategory }}</span>
            </template>

            <template #category="{ data }">
                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ data.category }}</p>
            </template>

            <template #status="{ data }">
                <span :title="data.status">
                    <CheckIcon
                        v-if="data.status === 'published'"
                        class="w-4 h-4 text-green-500"
                    />
                    <DraftIcon
                        v-else
                        class="w-4 h-4 text-orange-400"
                    />
                </span>
            </template>

            <template #created_at="{ data }">
                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ data.created_at }}</p>
            </template>
        </BaseTable>
    </TableCard>
</template>
