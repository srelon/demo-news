<script setup lang="ts">
import { computed } from 'vue'

interface ArticleTag {
  id: number
  name: string
  slug: string
}

const props = defineProps<{
  tags: ArticleTag[]
  title: string
  excerpt?: string
  image?: string
}>()

const page_url = computed(() => encodeURIComponent(window.location.href))
const page_excerpt = computed(() => encodeURIComponent(props.excerpt ?? ''))

const share_facebook = computed(() => `https://www.facebook.com/sharer/sharer.php?u=${page_url.value}`)
const share_twitter = computed(() => {
  const text = page_excerpt.value ? `&text=${page_excerpt.value}` : ''
  return `https://twitter.com/intent/tweet?url=${page_url.value}${text}`
})
const share_telegram = computed(() => {
  const text = page_excerpt.value ? `&text=${page_excerpt.value}` : ''
  return `https://t.me/share/url?url=${page_url.value}${text}`
})

</script>

<template>
  <div v-if="tags.length" class="flex-s-s p-t-12 p-b-15">
    <span class="f1-s-12 cl5 m-r-8">Tags:</span>
    <div class="flex-wr-s-s size-w-0">
      <router-link
        v-for="tag in tags"
        :key="tag.id"
        :to="{ name: 'news_tag', params: { slug: tag.slug } }"
        class="f1-s-12 cl8 hov-link1 m-r-15"
      >
        {{ tag.name }}
      </router-link>
    </div>
  </div>

  <div class="flex-s-s">
    <span class="f1-s-12 cl5 p-t-1 m-r-15">Share:</span>

    <div class="flex-wr-s-s size-w-0">
      <a :href="share_facebook" target="_blank" rel="noopener" class="dis-block f1-s-13 cl0 bg-facebook borad-3 p-tb-4 p-rl-18 hov-btn1 m-r-3 m-b-3 trans-03">
        <i class="fab fa-facebook-f m-r-7"></i>Facebook
      </a>
      <a :href="share_twitter" target="_blank" rel="noopener" class="dis-block f1-s-13 cl0 bg-twitter borad-3 p-tb-4 p-rl-18 hov-btn1 m-r-3 m-b-3 trans-03">
        <i class="fab fa-twitter m-r-7"></i>Twitter
      </a>
      <a :href="share_telegram" target="_blank" rel="noopener" class="dis-block f1-s-13 cl0 bg-telegram borad-3 p-tb-4 p-rl-18 hov-btn1 m-r-3 m-b-3 trans-03">
        <i class="fab fa-telegram-plane m-r-7"></i>Telegram
      </a>
    </div>
  </div>
</template>
