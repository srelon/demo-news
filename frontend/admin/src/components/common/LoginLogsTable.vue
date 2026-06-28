<script setup lang="ts">
import { reactive } from 'vue'
import BaseTablePagination from '@/components/ui/base/BaseTablePagination.vue'
import moment from 'moment'

defineProps<{
    route: string
}>()

const headers = reactive([
    {
        key: 'ip',
        text: 'IP',
    },
    {
        key: 'browser',
        text: 'Browser',
    },
    {
        key: 'created_at',
        text: 'Date',
    },
])
</script>

<template>
    <BaseTablePagination :route="route" :headers="headers">
        <template #browser="{ data }">
            <span class="block max-w-sm truncate text-sm text-gray-500 dark:text-gray-400" :title="data.browser">
                {{ data.browser }}
            </span>
        </template>
        <template #created_at="{ data }">
            <span class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                {{ moment.utc(data.created_at).local().format('DD.MM.YYYY HH:mm') }}
            </span>
        </template>
    </BaseTablePagination>
</template>
