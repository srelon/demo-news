<script setup lang="ts">
defineProps<{
  title: string
  image: string
  category_name: string
  category_slug: string
  subcategory_slug: string
  page_slug: string
  author?: string
  date?: string
  size?: string
}>()
</script>

<template>
  <div :class="['bg-img1 how1 pos-relative', size ?? 'size-a-5']" :style="`background-image: url(${image});`">
    <router-link :to="{name: 'news_page', params: {category: category_slug, subcategory: subcategory_slug, slug: page_slug}}" class="dis-block how1-child1 trans-03"></router-link>

    <div class="flex-col-e-s s-full p-rl-25 p-tb-20">
      <router-link :to="{name: 'news_category', params: {category: category_slug}}" class="dis-block how1-child2 f1-s-2 cl0 bo-all-1 bocl0 hov-btn1 trans-03 p-rl-5 p-t-2">
        {{ category_name }}
      </router-link>

      <h3 class="how1-child2 m-t-14">
        <router-link :to="{name: 'news_page', params: {category: category_slug, subcategory: subcategory_slug, slug: page_slug}}" class="how-txt1 size-h-1 f1-m-1 cl0 hov-cl10 trans-03">
          {{ title }}
        </router-link>
      </h3>

      <span v-if="author || date" class="how1-child2">
        <span v-if="author" class="f1-s-4 cl11">{{ author }}</span>
        <span v-if="author && date" class="f1-s-3 cl11 m-rl-3">-</span>
        <span v-if="date" class="f1-s-3 cl11">{{ date }}</span>
      </span>
    </div>
  </div>
</template>
