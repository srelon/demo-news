<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const props = defineProps<{
  current_page: number
  last_page: number
}>()

const emit = defineEmits<{
  'page-change': [page: number]
}>()

const route = useRoute()
const router = useRouter()

const container = ref<HTMLElement | null>(null)

const pages = computed(() => {
  const start = Math.max(1, props.current_page - 2)
  const end = Math.min(props.last_page, props.current_page + 2)
  const arr = []
  for (let i = start; i <= end; i++) arr.push(i)
  return arr
})

const first_visible = computed(() => pages.value[0] ?? 0)
const last_visible = computed(() => pages.value[pages.value.length - 1] ?? 0)

function changePage(page: number) {
  if (page < 1 || page > props.last_page || page === props.current_page) return
  router.replace({ query: { ...route.query, page: page > 1 ? page : undefined } })
  emit('page-change', page)
  if (container.value) {
    window.scrollTo({ top: container.value.offsetTop - 200, behavior: 'smooth' })
  }
}
</script>

<template>
  <div ref="container" class="col-md-10 col-lg-8 p-b-80">
    <div class="row">
      <slot></slot>
    </div>

    <nav v-if="last_page > 1" class="p-t-20">
      <ul class="pagination justify-content-center">

        <li class="page-item" :class="{ disabled: current_page === 1 }">
          <a class="page-link" href="#" @click.prevent="changePage(current_page - 1)">←</a>
        </li>

        <li v-if="first_visible > 1" class="page-item">
          <a class="page-link" href="#" @click.prevent="changePage(1)">1</a>
        </li>
        <li v-if="first_visible > 2" class="page-item disabled">
          <span class="page-link">…</span>
        </li>

        <li
          v-for="page in pages"
          :key="page"
          class="page-item"
          :class="{ active: page === current_page }"
        >
          <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
        </li>

        <li v-if="last_visible < last_page - 1" class="page-item disabled">
          <span class="page-link">…</span>
        </li>
        <li v-if="last_visible < last_page" class="page-item">
          <a class="page-link" href="#" @click.prevent="changePage(last_page)">{{ last_page }}</a>
        </li>

        <li class="page-item" :class="{ disabled: current_page === last_page }">
          <a class="page-link" href="#" @click.prevent="changePage(current_page + 1)">→</a>
        </li>

      </ul>
    </nav>
  </div>
</template>

<style scoped>
.pagination {
    gap: 5px;
    flex-wrap: wrap;
}

.page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    height: 38px;
    padding: 0 10px;
    margin-left: 0;
    background-color: #2a2a2a;
    border: 1px solid #333;
    border-radius: 4px !important;
    color: #999;
    font-size: 14px;
    line-height: 1;
    transition: background-color 0.25s, color 0.25s, border-color 0.25s;
}

.page-link:hover {
    background-color: #17b978;
    border-color: #17b978;
    color: #fff;
}

.page-item.active .page-link {
    background-color: #17b978;
    border-color: #17b978;
    color: #fff;
    pointer-events: none;
}

.page-item.disabled .page-link {
    background-color: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.07);
    color: #555;
    pointer-events: none;
}
</style>
