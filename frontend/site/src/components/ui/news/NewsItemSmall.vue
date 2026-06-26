<script setup lang="ts">
defineProps<{
  title: string
  image: string
  category_name: string
  category_slug: string
  subcategory_slug: string
  page_slug: string
  date?: string
}>()
</script>

<template>
  <div class="flex-wr-sb-s m-b-30">
    <router-link :to="{name: 'news_page', params: {category: category_slug, subcategory: subcategory_slug, slug: page_slug}}" class="size-w-1 wrap-pic-w hov1 trans-03">
      <img :src="image" alt="IMG">
    </router-link>

    <div class="size-w-2">
      <h5 class="p-b-5">
        <router-link :to="{name: 'news_page', params: {category: category_slug, subcategory: subcategory_slug, slug: page_slug}}" class="f1-s-5 cl3 hov-cl10 trans-03">
          {{ title }}
        </router-link>
      </h5>

      <span class="cl8">
        <router-link :to="{name: 'news_category', params: {category: category_slug}}" class="f1-s-6 cl8 hov-cl10 trans-03">
          {{ category_name }}
        </router-link>
        <span v-if="date" class="f1-s-3 m-rl-3">-</span>
        <span v-if="date" class="f1-s-3">{{ date }}</span>
      </span>
    </div>
  </div>
</template>
