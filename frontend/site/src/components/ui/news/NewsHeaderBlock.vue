<script setup lang="ts">
import NewsItemLarge from './NewsItemLarge.vue';

interface HeaderPost {
  title: string
  image: string
  category_name: string
  category_slug: string
  subcategory_slug: string
  page_slug: string
  author?: string
  date?: string
}

defineProps<{
  featured: HeaderPost | null
  topRight: HeaderPost | null
  bottomLeft: HeaderPost | null
  bottomRight: HeaderPost | null
}>()
</script>

<template>
  <section class="bg0">
    <div class="container">
      <div class="row m-rl--1">
        <div v-if="featured" class="col-md-6 p-rl-1 p-b-2">
          <NewsItemLarge v-bind="featured" size="size-a-3" />
        </div>

        <div class="p-rl-1" :class="featured ? 'col-md-6' : 'col-12'">
          <div class="row m-rl--1">
            <div v-if="topRight" class="col-12 p-rl-1 p-b-2">
              <NewsItemLarge v-bind="topRight" size="size-a-4" />
            </div>

            <div v-if="bottomLeft" class="col-sm-6 p-rl-1 p-b-2">
              <NewsItemLarge v-bind="bottomLeft" size="size-a-5" />
            </div>

            <div v-if="bottomRight" class="col-sm-6 p-rl-1 p-b-2">
              <NewsItemLarge v-bind="bottomRight" size="size-a-5" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
