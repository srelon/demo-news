<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

interface Pagination {
  current_page: number
  last_page: number
  prev_page_url: string | null
  next_page_url: string | null
}

const props = defineProps<{
  pagination: Pagination
}>()

const emit = defineEmits(['change'])

const route = useRoute()
const router = useRouter()

const currentPage = computed(() => props.pagination.current_page)
const lastPage = computed(() => props.pagination.last_page)

// page range logic (±2 from current)
const pages = computed(() => {
  let start = Math.max(1, currentPage.value - 2)
  let end = Math.min(lastPage.value, currentPage.value + 2)

  const arr = []
  for (let i = start; i <= end; i++) {
    arr.push(i)
  }
  return arr
})

const firstVisible = computed(() => pages.value[0] ?? 0)
const lastVisible = computed(() => pages.value[pages.value.length - 1] ?? 0)

const changePage = (page: number) => {
  if (page < 1 || page > lastPage.value) return
  router.replace({ query: { ...route.query, page: page > 1 ? page : undefined } })
  emit('change', page)
}

</script>

<template>
  <div class="flex items-center justify-center gap-0.5 pt-4 xl:justify-end xl:pt-0">

    <!-- PREV -->
    <button
        @click="changePage(currentPage - 1)"
        :disabled="!pagination.prev_page_url"
        class="mr-2.5 flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 shadow-theme-xs hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
    >
      ←
    </button>

    <!-- FIRST -->
    <button
        v-if="firstVisible > 1"
        @click="changePage(1)"
        class="text-gray-700 flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium"
    >
      1
    </button>

    <span v-if="firstVisible > 2">...</span>

    <!-- MAIN PAGES -->
    <button
        v-for="page in pages"
        :key="page"
        @click="changePage(page)"
        :disabled="page === currentPage"
        :class="[
      'flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium',
      page === currentPage
        ? 'bg-blue-500/[0.08] text-brand-500'
        : 'text-gray-700 dark:text-gray-400 hover:bg-blue-500/[0.08] hover:text-brand-500'
    ]"
    >
      {{ page }}
    </button>

    <span v-if="lastVisible < lastPage - 1">...</span>

    <!-- LAST -->
    <button
        v-if="lastVisible < lastPage"
        @click="changePage(lastPage)"
        class="text-gray-700 flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium"
    >
      {{ lastPage }}
    </button>

    <!-- NEXT -->
    <button
        @click="changePage(currentPage + 1)"
        :disabled="!pagination.next_page_url"
        class="ml-2.5 flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 shadow-theme-xs hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
    >
      →
    </button>

  </div>
</template>

<style>

</style>
