<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from '@/plugins/axios'
import { useLayoutStore, toArticleDate, toImageUrl, type ApiArticle } from '@/stores/layout'
import type { CategoryTab, TabArticle } from '@/components/ui/news/NewsCategoryTabs.vue'

import NewsHeaderBlock from '@/components/ui/news/NewsHeaderBlock.vue'
import NewsCategoryTabs from '@/components/ui/news/NewsCategoryTabs.vue'
import NewsPopularList from '@/components/ui/news/NewsPopularList.vue'
import NewsItemDefault from '@/components/ui/news/NewsItemDefault.vue'
import SocialsList from '@/components/ui/base/SocialsList.vue'
import BaseTitle from '@/components/ui/base/BaseTitle.vue'
import BaseSubscribe from '@/components/ui/base/BaseSubscribe.vue'
import BaseTagsBlock from '@/components/ui/base/BaseTagsBlock.vue'
import BasePageLoader from '@/components/ui/base/BasePageLoader.vue'

const layoutStore = useLayoutStore()

const is_loading = ref(true)
const header_posts = ref<any>(null)
const category_tabs_data = ref<{
  id: number
  title: string
  category_slug: string
  color: string
  tabs: CategoryTab[]
}[]>([])
const latest_articles = ref<any[]>([])

const popular_items = computed(() => layoutStore.footer.popular_posts.map(p => ({
  title: p.title,
  slug: p.slug,
  category_slug: p.category_slug,
  subcategory_slug: p.subcategory_slug,
})))

const tags = computed(() => layoutStore.tags)

function toTabArticle(a: any): TabArticle {
  return {
    id: a.id,
    title: a.title,
    image: toImageUrl(a.image),
    category_slug: a.category_slug ?? '',
    subcategory_slug: a.subcategory_slug ?? '',
    category_name: a.category_name ?? '',
    page_slug: a.page_slug ?? a.slug,
    author: a.author,
    date: a.date ?? '',
  }
}

function toTabArticleFromApi(a: ApiArticle): TabArticle {
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
    author: a.author,
    date: a.date ?? '',
  }
}

onMounted(() => {
  axios.get('home')
    .then((res) => {
      const data = res.data.data

      if (data.featured_block) {
        header_posts.value = {
          featured:    toBlockPost(data.featured_block.featured),
          topRight:    toBlockPost(data.featured_block.top_right),
          bottomLeft:  toBlockPost(data.featured_block.bottom_left),
          bottomRight: toBlockPost(data.featured_block.bottom_right),
        }
      }

      category_tabs_data.value = data.category_tabs.map((cat: any) => {
        const all_tab: CategoryTab = {
          id: 'all',
          label: 'All',
          articles: cat.articles.map(toTabArticle),
          loaded: true,
        }

        const subcategory_tabs: CategoryTab[] = cat.subcategories.map((sub: any) => ({
          id: sub.slug,
          label: sub.name,
          articles: [],
          loaded: false,
        }))

        return {
          id: cat.id,
          title: cat.name,
          category_slug: cat.slug,
          color: cat.color ?? '#333333',
          tabs: [all_tab, ...subcategory_tabs],
        }
      })

      const last_cat = data.category_tabs[data.category_tabs.length - 1]
      latest_articles.value = (last_cat?.articles ?? []).map(toTabArticle)

      is_loading.value = false
    })
})

function onFetchTab(category_index: number, subcategory_slug: string) {
  const category_slug = category_tabs_data.value[category_index]?.category_slug ?? ''
  layoutStore.fetchSubcategoryNews(category_slug, subcategory_slug)
    .then((result) => {
    const tab = category_tabs_data.value[category_index]?.tabs.find(t => t.id === subcategory_slug)

    if (tab) {
      tab.articles = result.map(toTabArticleFromApi)
      tab.loaded = true
    }
  })
}
</script>

<template>
  <BasePageLoader :is_loading="is_loading">
  <div>
    <NewsHeaderBlock v-if="header_posts" v-bind="header_posts" />

    <section class="bg0 p-t-70">
      <div class="container">
        <div class="row justify-content-center p-b-20">
          <div class="col-md-10 col-lg-8">
            <div
              v-for="(cat, cat_index) in category_tabs_data"
              :key="cat.id"
              class="p-b-20"
            >
              <NewsCategoryTabs
                :title="cat.title"
                :category_slug="cat.category_slug"
                :color="cat.color"
                :tabs="cat.tabs"
                :show_view_all="true"
                @fetch-tab="(slug) => onFetchTab(cat_index, slug)"
              />
            </div>
          </div>

          <div class="col-md-10 col-lg-4">
            <div class="p-l-10 p-rl-0-sr991 p-b-20">
              <NewsPopularList :items="popular_items" />

              <div class="flex-c-s p-t-8">
                <a href="#"><img class="max-w-full" src="/images/banner-02.jpg" alt="IMG"></a>
              </div>

              <div class="p-t-50">
                <BaseTitle title="Stay Connected" class="m-r-10 m-r-0-sr991" />
                <SocialsList />
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="container">
      <div class="flex-c-c">
        <a href="#"><img class="max-w-full" src="/images/banner-01.jpg" alt="IMG"></a>
      </div>
    </div>

    <section class="bg0 p-t-60 p-b-35">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-10 col-lg-8 p-b-20">
            <BaseTitle title="Latest Articles" class="m-r-10 m-r-0-sr991" />

            <div class="row p-t-35">
              <div v-for="article in latest_articles" :key="article.page_slug" class="col-sm-6 p-r-25 p-r-15-sr991">
                <NewsItemDefault v-bind="article" />
              </div>
            </div>
          </div>

          <div class="col-md-10 col-lg-4">
            <div class="p-l-10 p-rl-0-sr991 p-b-20">
              <BaseTagsBlock :tags="tags" class="p-b-55" />
              <BaseSubscribe />
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="btn-back-to-top" id="myBtn">
      <span class="symbol-btn-back-to-top">
        <span class="fas fa-angle-up"></span>
      </span>
    </div>
  </div>
  </BasePageLoader>
</template>
