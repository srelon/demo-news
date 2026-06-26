<script setup lang="ts">
import moment from 'moment'

interface TableHeader {
    key: string
    text: string
    time?: boolean
    format?: string
    class?: string
}

const props = defineProps<{
    headers: TableHeader[]
    table: Record<string, any>[]
    row_class?: (data: any) => string
    row_attrs?: (data: any, index: number) => Record<string, any>
    plain?: boolean
}>()

const emit = defineEmits<{
    row_click: [data: any]
}>()

defineSlots<{
    prepend(): any
    [key: string]: (props: { data: any; item: any }) => any
}>()
</script>

<template>
    <div :class="!plain ? 'overflow-hidden rounded-xl bg-white dark:border-gray-800 dark:bg-white/[0.03]' : undefined">
        <div class="max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-y border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
                        <th
                            v-for="(item, key) in headers"
                            :key="key"
                            class="px-4 py-3 border border-gray-100 dark:border-white/[0.05]"
                        >
                            <div class="flex items-center justify-between w-full">
                                <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ item.text }}</p>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <slot name="prepend" />
                    <tr
                        v-for="(data, i) in table"
                        :key="i"
                        :class="row_class ? row_class(data) : ''"
                        v-bind="row_attrs ? row_attrs(data, i) : {}"
                        @click="emit('row_click', data)"
                    >
                        <td
                            v-for="(item, key) in headers"
                            :key="key"
                            :class="`px-4 py-3 border border-gray-100 dark:border-white/[0.05] ${item.class ?? ''}`"
                        >
                            <slot :name="item.key" :item="item" :data="data">
                                <p class="text-gray-700 text-theme-sm dark:text-gray-400" v-if="item.time">
                                    {{ data[item.key] ? moment(data[item.key]).format(item.format ?? 'DD.MM.YYYY HH:mm') : '—' }}
                                </p>
                                <p class="text-gray-700 text-theme-sm dark:text-gray-400" v-else-if="data[item.key]">
                                    {{ data[item.key] }}
                                </p>
                                <p class="text-gray-700 text-theme-sm dark:text-gray-400" v-else></p>
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
