<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/plugins/axios'
import { useLayoutStore, toArticleDate, toImageUrl, type ApiArticle } from '@/stores/layout'
import { useSeoMeta } from '@/composables/useSeoMeta'
import type { CategoryTab } from '@/components/ui/news/NewsCategoryTabs.vue'

import BaseTitle from '@/components/ui/base/BaseTitle.vue'
import NewsHeaderBlock from '@/components/ui/news/NewsHeaderBlock.vue'
import NewsCategoryTabs from '@/components/ui/news/NewsCategoryTabs.vue'
import NewsItemDefault from '@/components/ui/news/NewsItemDefault.vue'
import NewsPopularList from '@/components/ui/news/NewsPopularList.vue'
import BaseSubscribe from '@/components/ui/base/BaseSubscribe.vue'
import BaseAdvertising from '@/components/ui/base/BaseAdvertising.vue'
import BaseTagsBlock from '@/components/ui/base/BaseTagsBlock.vue'
import BasePagination from '@/components/ui/base/BasePagination.vue'
import BasePageLoader from '@/components/ui/base/BasePageLoader.vue'

const route       = useRoute()
const layoutStore = useLayoutStore()

const is_loading = ref(true)
const category_name = ref('')
const category_color = ref('#333333')
const header_posts = ref<any>(null)
const tabs = ref<CategoryTab[]>([])
const articles = ref<any[]>([])
const current_page = ref(1)
const last_page = ref(1)
const active_tab = ref(String(route.params.subcategory || 'all'))

const popular_items = computed(() => layoutStore.footer.popular_posts.map(p => ({
  title: p.title,
  slug: p.slug,
  category_slug: p.category_slug,
  subcategory_slug: p.subcategory_slug,
})))

const tags = computed(() => layoutStore.tags)

function toCardArticle(a: ApiArticle) {
  return {
    id: a.id,
    title: a.title,
    image: toImageUrl(a.image),
    category_slug: a.subcategory?.category?.slug ?? '',
    subcategory_slug: a.subcategory?.slug ?? '',
    category_name: a.subcategory?.category?.name ?? '',
    page_slug: a.slug,
    author: a.author?.name,
    date: toArticleDate(a.published_at),
  }
}

function toBlockPost(a: any) {
  if (!a) return null
  return {
    title: a.title,
    image: toImageUrl(a.image),
    category_name: a.category_name ?? '',
    category_slug: a.category_slug ?? '',
    subcategory_slug: a.subcategory_slug ?? '',
    page_slug: a.page_slug ?? a.slug,
  }
}

function setArticles(data: any) {
  articles.value = data.data.map(toCardArticle)
  current_page.value = data.pagination.current_page
  last_page.value = data.pagination.last_page
}

function loadCategory(slug: string, page = 1) {
  const subcategory = active_tab.value !== 'all' ? active_tab.value : undefined

  axios.get(`news/${slug}`, { params: { page, ...(subcategory ? { subcategory } : {}) } })
    .then((res) => {
      const data = res.data.data

      category_name.value  = data.category.name
      category_color.value = data.category.color ?? '#333333'

      const active_sub = active_tab.value !== 'all'
        ? data.category.subcategories.find((s: any) => s.slug === active_tab.value)
        : null
      const seo_title = active_sub
        ? `${data.category.name} — ${active_sub.name}`
        : data.category.name
      useSeoMeta({ title: seo_title })

      if (data.featured_block) {
        header_posts.value = {
          featured:    toBlockPost(data.featured_block.featured),
          topRight:    toBlockPost(data.featured_block.top_right),
          bottomLeft:  toBlockPost(data.featured_block.bottom_left),
          bottomRight: toBlockPost(data.featured_block.bottom_right),
        }
      } else {
        header_posts.value = null
      }

      tabs.value = [
        { id: 'all', label: 'All', articles: [], loaded: true },
        ...data.category.subcategories.map((sub: any) => ({
          id: sub.slug,
          label: sub.name,
          articles: [],
          loaded: true,
        })),
      ]

      setArticles(data.articles)
      is_loading.value = false
    })
}

function loadSubcategory(slug: string, page = 1) {
  axios.get(`news/${route.params.category}/${slug}/articles`, { params: { page } })
    .then((res) => {
      setArticles(res.data.data)
    })
}

function onTabSelect(tab_id: string) {
  active_tab.value   = tab_id
  current_page.value = 1

  const url = tab_id === 'all'
    ? `/${route.params.category}`
    : `/${route.params.category}/${tab_id}`
  window.history.pushState({}, '', url)

  if (tab_id === 'all') {
    useSeoMeta({ title: category_name.value })
    loadCategory(String(route.params.category))
  } else {
    const sub = tabs.value.find(t => t.id === tab_id)
    useSeoMeta({ title: sub ? `${category_name.value} — ${sub.label}` : category_name.value })
    loadSubcategory(tab_id)
  }
}

function onPageChange(page: number) {
  if (active_tab.value === 'all') {
    loadCategory(String(route.params.category), page)
  } else {
    loadSubcategory(active_tab.value, page)
  }
}

onMounted(() => loadCategory(String(route.params.category), Number(route.query.page) || 1))

watch(
  () => [route.params.category, route.params.subcategory],
  ([new_cat, new_sub], [old_cat, old_sub]) => {
    if (!new_cat) return
    active_tab.value  = String(new_sub || 'all')
    current_page.value = 1

    if (new_cat !== old_cat) {
      is_loading.value = true
      loadCategory(String(new_cat))
    } else if (new_sub !== old_sub) {
      if (new_sub) {
        loadSubcategory(String(new_sub))
      } else {
        loadCategory(String(new_cat))
      }
    }
  }
)
</script>

<template>
  <BasePageLoader :is_loading="is_loading">
  <div>
    <BaseTitle :title="category_name" title_type="main" class="p-t-4 p-b-40 container" tag="h2" />

    <NewsHeaderBlock v-if="header_posts" v-bind="header_posts" />

    <section class="bg0 p-t-50 p-b-30">
      <div class="container">
        <NewsCategoryTabs
          v-if="tabs.length"
          :title="category_name"
          :category_slug="String(route.params.category)"
          :color="category_color"
          :tabs="tabs"
          :active_tab="active_tab"
          :show_view_all="false"
          :tabs_only="true"
          @tab-select="onTabSelect"
          class="p-b-40"
        />
      </div>
    </section>

    <section class="bg0 p-t-20 p-b-55">
      <div class="container">
        <div class="row justify-content-center">
          <BasePagination
            :current_page="current_page"
            :last_page="last_page"
            @page-change="onPageChange"
          >
            <div v-for="article in articles" :key="article.page_slug" class="col-sm-6 p-r-25 p-r-15-sr991">
              <NewsItemDefault v-bind="article" />
            </div>
          </BasePagination>

          <div class="col-md-10 col-lg-4 p-b-80">
            <div class="p-l-10 p-rl-0-sr991">
              <BaseSubscribe />

              <div class="p-b-23">
                <NewsPopularList :items="popular_items" />
              </div>

              <BaseAdvertising />
              <BaseTagsBlock :tags="tags" />
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
  </BasePageLoader>
</template>
