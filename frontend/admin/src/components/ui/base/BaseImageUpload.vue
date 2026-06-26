<script setup lang="ts">
import ImagePlaceholderIcon from '@/icons/ImagePlaceholderIcon.vue'
import { ref, computed } from 'vue'

const props = defineProps<{
  modelValue: File | null
  currentUrl?: string | null
  disabled?: boolean
}>()

const emit = defineEmits<{ 'update:modelValue': [value: File | null] }>()

const input = ref<HTMLInputElement | null>(null)
const preview = ref<string | null>(null)

const displayed = computed(() => preview.value || props.currentUrl || null)

function onClick() {
  if (!props.disabled) input.value?.click()
}

function onChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0] ?? null
  if (!file) return
  emit('update:modelValue', file)
  const reader = new FileReader()
  reader.onload = (ev) => { preview.value = ev.target?.result as string }
  reader.readAsDataURL(file)
}

function clear(e: MouseEvent) {
  e.stopPropagation()
  preview.value = null
  emit('update:modelValue', null)
  if (input.value) input.value.value = ''
}
</script>

<template>
  <div>
    <input
      ref="input"
      type="file"
      accept=".jpg,.jpeg,.png,.webp"
      class="hidden"
      @change="onChange"
    />

    <div
      @click="onClick"
      class="relative group w-full overflow-hidden rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700 transition-colors"
      :class="disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer hover:border-brand-400 dark:hover:border-brand-500'"
    >
      <!-- With image -->
      <template v-if="displayed">
        <img
          :src="displayed"
          alt="Article image"
          class="w-full max-h-64 object-cover block"
        />
        <!-- Hover overlay -->
        <div
          v-if="!disabled"
          class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          <span class="text-white text-sm font-medium">Upload new image</span>
        </div>
        <!-- Clear button -->
        <button
          v-if="!disabled && preview"
          type="button"
          @click="clear"
          class="absolute top-2 right-2 flex items-center justify-center w-7 h-7 rounded-full bg-black/50 text-white hover:bg-black/70 transition-colors text-xs leading-none"
          title="Cancel"
        >✕</button>
      </template>

      <!-- No image placeholder -->
      <template v-else>
        <div class="flex flex-col items-center justify-center gap-2 py-10 text-gray-400 dark:text-gray-500">
          <ImagePlaceholderIcon class="w-10 h-10" :stroke_width="1.5" />
          <span class="text-sm">Click to upload image</span>
          <span class="text-xs">JPG, PNG, WEBP — max 5 MB</span>
        </div>
      </template>
    </div>
  </div>
</template>
