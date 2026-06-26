<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import BaseBtn from '@/components/ui/base/BaseBtn.vue'

export interface FilterOption {
    value: string | number
    text: string
}

export interface FilterField {
    key: string
    label: string
    placeholder?: string
    type?: 'number'
    options: FilterOption[]
}

const props = defineProps<{
    filters: FilterField[]
    modelValue: Record<string, any>
}>()

const emit = defineEmits<{
    'update:modelValue': [value: Record<string, any>]
}>()

function update(key: string, raw: string) {
    const field = props.filters.find(f => f.key === key)
    const value: string | number = (field?.type === 'number' && raw !== '') ? Number(raw) : raw
    emit('update:modelValue', { ...props.modelValue, [key]: value })
}

const is_open = ref(false)
const btn_wrap = ref<HTMLElement | null>(null)
const dropdown_ref = ref<HTMLElement | null>(null)
const dropdown_style = ref<Record<string, string>>({})

const active_count = computed(() =>
    props.filters.filter(f => {
        const v = props.modelValue[f.key]
        return v !== '' && v !== null && v !== undefined
    }).length
)

function toggle() {
    if (is_open.value) {
        is_open.value = false
        return
    }
    if (btn_wrap.value) {
        const rect = btn_wrap.value.getBoundingClientRect()
        dropdown_style.value = {
            top: rect.bottom + 8 + 'px',
            right: window.innerWidth - rect.right + 'px',
        }
    }
    is_open.value = true
}

function onClickOutside(e: MouseEvent) {
    if (
        btn_wrap.value?.contains(e.target as Node) ||
        dropdown_ref.value?.contains(e.target as Node)
    ) return
    is_open.value = false
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))

const select_class = 'w-full h-9 appearance-none rounded-lg border border-gray-300 bg-transparent py-2 pl-3 pr-8 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90'
</script>

<template>
    <div ref="btn_wrap" class="inline-block">
        <BaseBtn
            color="secondary"
            base_class="inline-flex h-11 items-center gap-2 px-3.5 text-sm font-medium transition rounded-lg"
            @click="toggle"
        >
            <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 5.5h15M5.5 10h9M8.5 14.5h3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            </svg>
            Filters
            <span
                v-if="active_count"
                class="flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-500 px-1.5 text-xs font-medium text-white"
            >{{ active_count }}</span>
        </BaseBtn>
    </div>

    <Teleport to="body">
        <div
            v-if="is_open"
            ref="dropdown_ref"
            class="fixed z-[9999] w-72 rounded-xl border border-gray-200 bg-white p-4 shadow-lg dark:border-gray-700 dark:bg-gray-900"
            :style="dropdown_style"
        >
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Filters</p>

            <div class="space-y-3">
                <div v-for="field in filters" :key="field.key">
                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">{{ field.label }}</label>
                    <div class="relative">
                        <select
                            :value="modelValue[field.key]"
                            @change="update(field.key, ($event.target as HTMLSelectElement).value)"
                            :class="select_class"
                        >
                            <option value="">{{ field.placeholder ?? 'All' }}</option>
                            <option v-for="opt in field.options" :key="opt.value" :value="opt.value">{{ opt.text }}</option>
                        </select>
                        <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="h-4 w-4 stroke-current" viewBox="0 0 16 16" fill="none">
                                <path d="M4 6l4 4 4-4" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
