<script setup lang="ts">
defineProps<{
  title: string
  image: string
  category_slug: string
  subcategory_slug: string
  page_slug: string
  author?: string
  date?: string
}>()
</script>

<template>
  <div class="m-b-45">
    <router-link :to="{name: 'news_page', params: {category: category_slug, subcategory: subcategory_slug, slug: page_slug}}" class="wrap-pic-w hov1 trans-03">
      <img :src="image" alt="IMG">
    </router-link>

    <div class="p-t-16">
      <h5 class="p-b-5">
        <router-link :to="{name: 'news_page', params: {category: category_slug, subcategory: subcategory_slug, slug: page_slug}}" class="f1-m-3 cl2 hov-cl10 trans-03">
          {{ title }}
        </router-link>
      </h5>

      <span class="cl8">
        <span v-if="author" class="f1-s-4 cl8">{{ author }}</span>
        <span v-if="author && date" class="f1-s-3 m-rl-3">-</span>
        <span v-if="date" class="f1-s-3">{{ date }}</span>
      </span>
    </div>
  </div>
</template>
