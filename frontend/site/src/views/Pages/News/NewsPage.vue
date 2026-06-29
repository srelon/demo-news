<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/plugins/axios'
import { useLayoutStore, toArticleDate, toImageUrl } from '@/stores/layout'
import { useSeoMeta } from '@/composables/useSeoMeta'

import BasePageLoader from '@/components/ui/base/BasePageLoader.vue'
import ExternalLinkConfirm from '@/components/ui/base/ExternalLinkConfirm.vue'
import BaseTitle from '@/components/ui/base/BaseTitle.vue'
import BaseCommentsBlock from '@/components/ui/comments/BaseCommentsBlock.vue'
import BaseTagsBlock from '@/components/ui/base/BaseTagsBlock.vue'
import NewsPageHeader from './NewsPageHeader.vue'
import NewsPageFooter from './NewsPageFooter.vue'
import NewsSidebarCategories from '@/components/ui/sidebar/NewsSidebarCategories.vue'
import NewsSidebarArchive from '@/components/ui/sidebar/NewsSidebarArchive.vue'
import NewsSidebarPopular from '@/components/ui/sidebar/NewsSidebarPopular.vue'

const route = useRoute()
const layoutStore = useLayoutStore()

const article = ref<any>(null)
const external_url = ref<string | null>(null)
const comments_total = ref(0)

// Body links to other domains open through a confirmation dialog
function onBodyClick(e: MouseEvent) {
  const link = (e.target as HTMLElement).closest('a')
  if (!link) return

  const href = link.getAttribute('href') ?? ''
  if (!/^https?:\/\//i.test(href)) return

  try {
    if (new URL(href).host === window.location.host) return
  } catch {
    return
  }

  e.preventDefault()
  external_url.value = href
}

const sidebar_popular = computed(() =>
  layoutStore.footer.popular_posts.map(a => ({
    title: a.title,
    category: a.category_name,
    category_slug: a.category_slug,
    subcategory_slug: a.subcategory_slug,
    date: a.date,
    image: toImageUrl(a.image),
    slug: a.slug,
  }))
)
const sidebar_archive = computed(() => layoutStore.sidebar.archive)
const sidebar_categories = computed(() =>
  layoutStore.categories.map(c => ({ name: c.name, slug: c.slug }))
)
const sidebar_tags = computed(() => layoutStore.tags)

function loadArticle(category: string, subcategory: string, slug: string) {
  article.value = null
  comments_total.value = 0

  axios.get(`news/${category}/${subcategory}/${slug}`)
    .then((res) => {
      const data = res.data.data

      article.value = {
        id: data.article.id,
        category_name: data.article.subcategory?.category?.name ?? '',
        category_slug: data.article.subcategory?.category?.slug ?? '',
        subcategory_slug: data.article.subcategory?.slug ?? '',
        title: data.article.title,
        author: data.article.author?.name ?? '',
        source_name: data.article.source_name ?? '',
        source_url: data.article.source_url ?? '',
        date: toArticleDate(data.article.published_at),
        views: data.article.views,
        image: toImageUrl(data.article.image),
        tags: (data.article.tags ?? []).map((t: any) => ({ id: t.id, name: t.name, slug: t.slug })),
        body: data.article.body,
      }

      useSeoMeta({
        title: data.article.seo_title || data.article.title,
        description: data.article.seo_description || data.article.excerpt,
        keywords: data.article.seo_keywords,
        image: data.article.image ? toImageUrl(data.article.image) : null,
        url: window.location.href,
      })
    })
}

onMounted(() => {
  loadArticle(
    String(route.params.category),
    String(route.params.subcategory),
    String(route.params.slug),
  )
})

watch(
  () => route.params.slug,
  () => {
    loadArticle(
      String(route.params.category),
      String(route.params.subcategory),
      String(route.params.slug),
    )
  }
)
</script>

<template>
  <BasePageLoader :is_loading="!article">
  <section class="bg0 p-b-140 p-t-10">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8 p-b-30">
          <div class="p-r-10 p-r-0-sr991">
            <div class="p-b-70">
              <router-link
                :to="{name: 'news_category', params: {category: article.category_slug}}"
                class="f1-s-10 cl2 hov-cl10 trans-03 text-uppercase"
              >
                {{ article.category_name }}
              </router-link>

              <BaseTitle class="p-b-16 p-t-33 respon2" :title="article.title" title_type="main" />

              <NewsPageHeader
                :author="article.author"
                :source_name="article.source_name"
                :source_url="article.source_url"
                :date="article.date"
                :views="article.views"
                :comments="comments_total"
              />

              <div class="wrap-pic-max-w p-b-30">
                <img :src="article.image" alt="IMG">
              </div>

              <div class="f1-s-11 cl6 p-b-25" v-html="article.body" @click="onBodyClick" />

              <NewsPageFooter :tags="article.tags" :title="article.title" :image="article.image" />
            </div>

            <BaseCommentsBlock :id="article.id" @total="comments_total = $event" />
          </div>
        </div>

        <div class="col-md-10 col-lg-4 p-b-30">
          <div class="p-l-10 p-rl-0-sr991 p-t-70">
            <NewsSidebarCategories :items="sidebar_categories" />
            <NewsSidebarArchive :items="sidebar_archive" />
            <NewsSidebarPopular :items="sidebar_popular" />
            <BaseTagsBlock :tags="sidebar_tags" />
          </div>
        </div>
      </div>
    </div>
  </section>

  <ExternalLinkConfirm
    v-if="external_url"
    :url="external_url"
    @close="external_url = null"
  />
  </BasePageLoader>
</template>

<style scoped>
.f1-s-11 :deep(img) {
  max-width: 100%;
  height: auto;
  display: block;
  border-radius: 4px;
  margin: 0.5rem 0;
}

.f1-s-11 :deep(h2),
.f1-s-11 :deep(h3),
.f1-s-11 :deep(h4) {
  font-family: inherit;
  font-weight: 700;
  color: #333;
  line-height: 1.3;
  margin: 1.75rem 0 0.75rem;
}

.f1-s-11 :deep(h2) {
  font-size: 24px;
}

.f1-s-11 :deep(h3) {
  font-size: 20px;
}

.f1-s-11 :deep(h4) {
  font-size: 17px;
}

.f1-s-11 :deep(blockquote) {
  border-left: 3px solid #e6e6e6;
  padding-left: 1rem;
  margin: 1rem 0;
  color: #777;
  font-style: italic;
}
</style>
