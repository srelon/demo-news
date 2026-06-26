<script setup lang="ts">
import BaseTitle from '@/components/ui/base/BaseTitle.vue';

defineProps<{
  items: { title: string, slug: string, category_slug: string, subcategory_slug: string }[]
}>()
</script>

<template>
  <div>
    <BaseTitle title="Most Popular"></BaseTitle>

    <ul class="p-t-35">
      <li v-for="(item, index) in items" :key="item.slug" :class="['flex-wr-sb-s', index < items.length - 1 ? 'p-b-22' : '']">
        <div class="size-a-8 flex-c-c borad-3 size-a-8 bg9 f1-m-4 cl0 m-b-6">
          {{ index + 1 }}
        </div>

        <router-link :to="{name: 'news_page', params: {category: item.category_slug, subcategory: item.subcategory_slug, slug: item.slug}}" class="size-w-3 f1-s-7 cl3 hov-cl10 trans-03">
          {{ item.title }}
        </router-link>
      </li>
    </ul>
  </div>
</template>
