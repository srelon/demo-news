<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import NewsItemDefault from './NewsItemDefault.vue'
import NewsItemSmall from './NewsItemSmall.vue'

export interface TabArticle {
  id: number
  title: string
  image: string
  category_slug: string
  subcategory_slug: string
  category_name: string
  page_slug: string
  author?: string
  date?: string
}

export interface CategoryTab {
  id: string        // 'all' or subcategory slug
  label: string
  articles: TabArticle[]
  loaded: boolean
}

const props = defineProps<{
  title: string
  category_slug: string
  tabs: CategoryTab[]
  color?: string
  show_view_all?: boolean
  tabs_only?: boolean
  active_tab?: string
}>()

const emit = defineEmits<{
  'fetch-tab': [slug: string]
  'tab-select': [slug: string]
}>()

const local_tab = ref(props.active_tab ?? props.tabs[0]?.id ?? 'all')
const activeTab  = computed(() => props.active_tab ?? local_tab.value)

watch(() => props.active_tab, (val) => {
  if (val !== undefined) local_tab.value = val
})

function selectTab(tabId: string) {
  local_tab.value = tabId
  emit('tab-select', tabId)
  const tab = props.tabs.find(t => t.id === tabId)
  if (tab && !tab.loaded) {
    emit('fetch-tab', tabId)
  }
}

function featured(tab: CategoryTab): TabArticle | undefined {
  return tab.articles[0]
}

function items(tab: CategoryTab): TabArticle[] {
  return tab.articles.slice(1)
}
</script>

<template>
  <div class="tab01 p-b-20" :style="{ '--category-color': color || '#333333' }">
    <div class="tab01-head how2 how2-var bocl12 flex-s-c m-r-10 m-r-0-sr991">
      <h3 class="f1-m-2 tab01-title tab01-title-var">{{ title }}</h3>

      <ul class="nav nav-tabs" role="tablist">
        <li v-for="tab in tabs" :key="tab.id" class="nav-item">
          <a
            :class="['nav-link', activeTab === tab.id ? 'active' : '']"
            href="#"
            @click.prevent="selectTab(tab.id)"
            role="tab"
          >
            {{ tab.label }}
          </a>
        </li>
      </ul>

      <router-link v-if="show_view_all !== false" :to="{name: 'news_category', params: {category: category_slug}}" class="tab01-link f1-s-1 cl9 hov-cl10 trans-03">
        View all <i class="fs-12 m-l-5 fa fa-caret-right"></i>
      </router-link>
    </div>

    <div v-if="!tabs_only" class="tab-content p-t-35">
      <div v-for="tab in tabs" v-show="activeTab === tab.id" :key="tab.id" role="tabpanel">
        <div v-if="!tab.loaded" class="txt-center p-tb-30">
          <span class="cl9">Loading...</span>
        </div>

        <div v-else-if="!featured(tab)" class="txt-center p-tb-30">
          <span class="cl9">No articles yet.</span>
        </div>

        <div v-else class="row">
          <div class="col-sm-6 p-r-25 p-r-15-sr991">
            <NewsItemDefault v-bind="featured(tab)!" />
          </div>

          <div class="col-sm-6 p-r-25 p-r-15-sr991">
            <NewsItemSmall
              v-for="item in items(tab)"
              :key="item.page_slug"
              v-bind="item"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.how2-var::before {
  background-color: var(--category-color);
}

.tab01-title-var {
  color: var(--category-color);
}

@media (max-width: 767px) {
  :deep(.tab01-head) {
    flex-wrap: wrap;
    align-items: center;
    height: auto;
    min-height: 0;
    padding-top: 10px;
    padding-bottom: 6px;
    row-gap: 6px;
  }

  :deep(.tab01-title) {
    order: 1;
    flex: 0 1 auto;
    margin-right: auto;
    padding-right: 8px;
    padding-bottom: 0;
  }

  :deep(.tab01-link) {
    order: 2;
    flex-shrink: 0;
    padding-left: 0;
    width: auto;
  }

  :deep(.nav-tabs) {
    order: 3;
    width: 100%;
    height: auto;
    flex-shrink: 0;
    border-top: 1px solid #ebebeb;
    padding-top: 4px;
  }

  :deep(.nav-tabs .nav-item) {
    height: auto;
  }

  :deep(.nav-link) {
    height: auto;
    padding: 5px 12px;
  }

  :deep(.nav-link.active::after) {
    display: none;
  }
}
</style>
