<script setup lang="ts">
import ChevronDownIcon from '@/icons/ChevronDownIcon.vue'
import { ref, computed, watch } from 'vue'

interface Option { id: number; name: string }
interface SubOption { id: number; name: string; category_id: number }

const props = defineProps<{
  modelValue: number | null
  categories: Option[]
  subcategories: SubOption[]
  disabled?: boolean
  error?: boolean
}>()

const emit = defineEmits<{ 'update:modelValue': [value: number | null] }>()

const category_id = ref<number | null>(null)

const filtered = computed(() =>
  category_id.value
    ? props.subcategories.filter(s => s.category_id === category_id.value)
    : []
)

// Init on load — determine category from current subcategory
watch(() => [props.modelValue, props.subcategories] as const, ([val]) => {
  if (val && !category_id.value) {
    const sub = props.subcategories.find(s => s.id === val)
    if (sub) category_id.value = sub.category_id
  }
}, { immediate: true })

function onCategoryChange() {
  emit('update:modelValue', null)
}

function onSubChange(e: Event) {
  const val = (e.target as HTMLSelectElement).value
  emit('update:modelValue', val ? Number(val) : null)
}

const selectClass = 'dark:bg-dark-900 h-11 w-full appearance-none rounded-lg border bg-transparent px-4 py-2.5 pr-11 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden focus:ring-3 dark:bg-gray-900 dark:text-white/90'
const selectNormal = 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-800'
const selectError  = 'border-red-400 focus:border-red-400 focus:ring-red-500/10 dark:border-red-600 dark:focus:border-red-600'
const arrowClass = 'absolute z-30 text-gray-500 -translate-y-1/2 pointer-events-none right-4 top-1/2 dark:text-gray-400'
</script>

<template>
  <div class="flex flex-col gap-3 sm:flex-row">
    <!-- Category -->
    <div class="relative flex-1">
      <select
        v-model="category_id"
        :disabled="disabled"
        :class="[selectClass, selectNormal]"
        @change="onCategoryChange"
      >
        <option :value="null">— Category —</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
      </select>
      <span :class="arrowClass">
        <ChevronDownIcon class="stroke-current" />
      </span>
    </div>

    <!-- Subcategory -->
    <div class="relative flex-1">
      <select
        :value="modelValue"
        :disabled="disabled || !category_id"
        :class="[selectClass, error ? selectError : selectNormal]"
        @change="onSubChange"
      >
        <option :value="null">— Subcategory —</option>
        <option v-for="sub in filtered" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
      </select>
      <span :class="arrowClass">
        <ChevronDownIcon class="stroke-current" />
      </span>
    </div>
  </div>
</template>
