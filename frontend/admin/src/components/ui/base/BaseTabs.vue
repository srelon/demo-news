<script setup>

const emit = defineEmits(['current', 'updateTab'])

const props = defineProps({
  tabs: {
    type: Array
  },
  current: {
    type: String,
  }
})

function changeTab(data) {
  emit('current', data);
  emit('updateTab', data);
}
</script>

<template>
  <div>
    <div class="p-3 border border-gray-200 rounded-t-xl dark:border-gray-800">
      <nav class="flex overflow-x-auto rounded-lg bg-gray-100 p-1 dark:bg-gray-900 [&amp;::-webkit-scrollbar]:h-1.5 [&amp;::-webkit-scrollbar-track]:bg-white dark:[&amp;::-webkit-scrollbar-track]:bg-transparent [&amp;::-webkit-scrollbar-thumb]:rounded-full [&amp;::-webkit-scrollbar-thumb]:bg-gray-200 dark:[&amp;::-webkit-scrollbar-thumb]:bg-gray-600">
        <button
            v-for="(item, key) in tabs" :key="key"
            :class="(item== current) ? 'inline-flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200 ease-in-out bg-white text-gray-900 shadow-theme-xs dark:bg-white/[0.03] dark:text-white' : 'inline-flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200 ease-in-out bg-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
            @click="changeTab(item)"
        >
          {{ item }}
        </button>
      </nav>
    </div>
    <div class="p-6 pt-4 border border-t-0 border-gray-200 rounded-b-xl dark:border-gray-800">
      <slot></slot>
    </div>
  </div>
</template>