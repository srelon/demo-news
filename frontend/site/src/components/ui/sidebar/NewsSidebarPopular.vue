<script setup lang="ts">
defineProps<{
  items: {
    title: string
    category: string
    category_slug: string
    subcategory_slug: string
    date: string
    image: string
    slug: string
  }[]
}>()
</script>

<template>
  <div class="p-b-30">
    <div class="how2 how2-cl4 flex-s-c">
      <h3 class="f1-m-2 cl3 tab01-title">Popular Post</h3>
    </div>

    <ul class="p-t-35">
      <li v-for="item in items" :key="item.slug" class="flex-wr-sb-s p-b-30">
        <router-link :to="{name: 'news_page', params: {category: item.category_slug, subcategory: item.subcategory_slug, slug: item.slug}}" class="size-w-10 wrap-pic-w hov1 trans-03">
          <img :src="item.image" alt="IMG">
        </router-link>

        <div class="size-w-11">
          <h6 class="p-b-4">
            <router-link :to="{name: 'news_page', params: {category: item.category_slug, subcategory: item.subcategory_slug, slug: item.slug}}" class="f1-s-5 cl3 hov-cl10 trans-03">
              {{ item.title }}
            </router-link>
          </h6>

          <span class="cl8 txt-center p-b-24">
            <router-link :to="{name: 'news_category', params: {category: item.category_slug}}" class="f1-s-6 cl8 hov-cl10 trans-03">
              {{ item.category }}
            </router-link>
            <span class="f1-s-3 m-rl-3">-</span>
            <span class="f1-s-3">{{ item.date }}</span>
          </span>
        </div>
      </li>
    </ul>
  </div>
</template>
