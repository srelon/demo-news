<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

interface TagOption {
  id: number
  name: string
}

const props = defineProps<{
  modelValue: string | null
  options: TagOption[]
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const search    = ref('')
const isOpen    = ref(false)
const container = ref<HTMLElement | null>(null)

const selected = computed<TagOption[]>(() => {
  if (!props.modelValue || !props.modelValue.trim()) return []
  const names = props.modelValue.split(',').map(n => n.trim()).filter(Boolean)
  return names.flatMap(name => {
    const found = props.options.find(o => o.name.toLowerCase() === name.toLowerCase())
    return found ? [found] : [{ id: 0, name }]
  })
})

const filtered = computed(() =>
  (props.options || []).filter(o =>
    !selected.value.some(s => s.name === o.name) &&
    o.name.toLowerCase().includes(search.value.toLowerCase())
  )
)

function add(tag: TagOption) {
  const names = [...selected.value.map(s => s.name), tag.name]
  emit('update:modelValue', names.join(', '))
  search.value = ''
}

function remove(tag: TagOption) {
  const names = selected.value.filter(s => s.name !== tag.name).map(s => s.name)
  emit('update:modelValue', names.join(', '))
}

function onClickOutside(e: MouseEvent) {
  if (container.value && !container.value.contains(e.target as Node)) {
    isOpen.value = false
  }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<template>
  <div ref="container" class="relative">
    <!-- Selected chips + search input -->
    <div
      class="min-h-[44px] w-full flex flex-wrap gap-1.5 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm shadow-theme-xs transition-colors focus-within:border-brand-300 focus-within:ring-3 focus-within:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900"
      :class="disabled ? 'opacity-60 cursor-not-allowed' : 'cursor-text'"
      @click="!disabled && (isOpen = true)"
    >
      <span
        v-for="tag in selected"
        :key="tag.name"
        class="inline-flex items-center gap-1 rounded-md bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700 dark:bg-brand-500/10 dark:text-brand-400"
      >
        {{ tag.name }}
        <button
          v-if="!disabled"
          type="button"
          @click.stop="remove(tag)"
          class="ml-0.5 text-brand-400 hover:text-brand-600 dark:text-brand-500 dark:hover:text-brand-300 leading-none"
        >×</button>
      </span>

      <input
        v-if="!disabled"
        v-model="search"
        type="text"
        placeholder="Search tags..."
        class="flex-1 min-w-[120px] bg-transparent text-gray-800 placeholder:text-gray-400 outline-none dark:text-white/90 dark:placeholder:text-white/30"
        @focus="isOpen = true"
        @keydown.escape="isOpen = false"
      />
    </div>

    <!-- Dropdown -->
    <div
      v-if="isOpen && filtered.length > 0"
      class="absolute z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900 max-h-52 overflow-y-auto"
    >
      <button
        v-for="tag in filtered"
        :key="tag.id"
        type="button"
        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/[0.04]"
        @mousedown.prevent="add(tag)"
      >
        {{ tag.name }}
      </button>
    </div>

    <div
      v-if="isOpen && filtered.length === 0 && search"
      class="absolute z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900 px-4 py-2 text-sm text-gray-400 dark:text-gray-500"
    >
      No tags found
    </div>
  </div>
</template>
