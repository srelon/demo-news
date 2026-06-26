<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/plugins/axios'
import { useLayoutStore, toArticleDate, toImageUrl, type ApiArticle } from '@/stores/layout'
import { useSeoMeta } from '@/composables/useSeoMeta'

import BaseTitle from '@/components/ui/base/BaseTitle.vue'
import NewsItemDefault from '@/components/ui/news/NewsItemDefault.vue'
import NewsPopularList from '@/components/ui/news/NewsPopularList.vue'
import BaseSubscribe from '@/components/ui/base/BaseSubscribe.vue'
import BaseAdvertising from '@/components/ui/base/BaseAdvertising.vue'
import BaseTagsBlock from '@/components/ui/base/BaseTagsBlock.vue'
import BasePagination from '@/components/ui/base/BasePagination.vue'
import BasePageLoader from '@/components/ui/base/BasePageLoader.vue'

const route = useRoute()
const layoutStore = useLayoutStore()

const is_loading = ref(true)
const page_title = ref('')
const articles = ref<any[]>([])
const current_page = ref(1)
const last_page = ref(1)

const is_search = computed(() => route.name === 'news_search')
const is_archive = computed(() => route.name === 'news_archive')
const slug = computed(() => String(route.params.slug))
const year = computed(() => Number(route.params.year))
const month = computed(() => Number(route.params.month))

const popular_items = computed(() => layoutStore.footer.popular_posts.map(p => ({
  title: p.title,
  slug: p.slug,
  category_slug: p.category_slug,
  subcategory_slug: p.subcategory_slug,
})))

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

function load(page = 1) {
  is_loading.value = true

  let request
  if (is_search.value) {
    request = axios.get('search', { params: { q: slug.value, page } })
  } else if (is_archive.value) {
    request = axios.get(`archive/${year.value}/${month.value}`, { params: { page } })
  } else {
    request = axios.get(`tags/${slug.value}`, { params: { page } })
  }

  request.then((res) => {
    const data = res.data.data

    if (is_search.value) {
      page_title.value = `Search results for: ${slug.value}`
    } else if (is_archive.value) {
      page_title.value = data.label
    } else {
      page_title.value = `${data.tag.name}: All news`
    }

    articles.value = data.data.map(toCardArticle)
    current_page.value = data.pagination.current_page
    last_page.value = data.pagination.last_page
    is_loading.value = false

    useSeoMeta({ title: page_title.value })
  })
}

watch(
  () => JSON.stringify(route.params),
  (_, prev) => load(prev === undefined ? Number(route.query.page) || 1 : 1),
  { immediate: true },
)
</script>

<template>
  <BasePageLoader :is_loading="is_loading">
    <div>
      <BaseTitle :title="page_title" title_type="main" class="p-t-4 p-b-40 container" tag="h2" />

      <section class="bg0 p-t-20 p-b-55">
        <div class="container">
          <div class="row justify-content-center">
            <BasePagination
              :current_page="current_page"
              :last_page="last_page"
              @page-change="load"
            >
              <div
                v-for="article in articles"
                :key="article.page_slug"
                class="col-sm-6 p-r-25 p-r-15-sr991"
              >
                <NewsItemDefault v-bind="article" />
              </div>

              <div v-if="!articles.length && !is_loading" class="col-12 p-t-20 p-b-20 text-center">
                <p class="f1-s-3 cl8">{{ is_search ? `No results found for "${slug}"` : 'No articles found' }}</p>
              </div>
            </BasePagination>

            <div class="col-md-10 col-lg-4 p-b-80">
              <div class="p-l-10 p-rl-0-sr991">
                <BaseSubscribe />

                <div class="p-b-23">
                  <NewsPopularList :items="popular_items" />
                </div>

                <BaseAdvertising />
                <BaseTagsBlock :tags="layoutStore.tags" />
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </BasePageLoader>
</template>
