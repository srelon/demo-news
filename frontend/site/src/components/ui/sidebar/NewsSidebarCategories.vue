<script setup lang="ts">
defineProps<{
  items: { name: string, slug: string }[]
}>()
</script>

<template>
  <div class="p-b-60">
    <div class="how2 how2-cl4 flex-s-c">
      <h3 class="f1-m-2 cl3 tab01-title">Category</h3>
    </div>

    <ul class="p-t-35">
      <li v-for="item in items" :key="item.slug" class="how-bor3 p-rl-4">
        <router-link :to="{name: 'news_category', params: {category: item.slug}}" class="dis-block f1-s-10 text-uppercase cl2 hov-cl10 trans-03 p-tb-13">
          {{ item.name }}
        </router-link>
      </li>
    </ul>
  </div>
</template>
