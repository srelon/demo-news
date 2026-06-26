<script setup lang="ts">
import BaseTitle from './BaseTitle.vue'
import type { Tag } from '@/stores/layout'

defineProps<{
  tags: Tag[]
}>()
</script>

<template>
  <div>
    <BaseTitle title="Tags" class="m-b-30" />

    <div class="flex-wr-s-s m-rl--5">
      <router-link
        v-for="tag in tags"
        :key="tag.id"
        :to="{ name: 'news_tag', params: { slug: tag.slug } }"
        class="flex-c-c size-h-2 bo-1-rad-20 bocl12 f1-s-1 cl8 hov-btn2 trans-03 p-rl-20 p-tb-5 m-all-5"
      >
        {{ tag.name }}
      </router-link>
    </div>
  </div>
</template>
