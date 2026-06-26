<script setup lang="ts">
defineProps<{
  items: { year: number, month: number, label: string, count: number }[]
}>()
</script>

<template>
  <div class="p-b-37">
    <div class="how2 how2-cl4 flex-s-c">
      <h3 class="f1-m-2 cl3 tab01-title">Archive</h3>
    </div>

    <ul class="p-t-32">
      <li v-for="item in items" :key="item.label" class="p-rl-4 p-b-19">
        <router-link
          v-if="item.year && item.month"
          :to="{ name: 'news_archive', params: { year: item.year, month: item.month } }"
          class="flex-wr-sb-c f1-s-10 text-uppercase cl2 hov-cl10 trans-03"
        >
          <span>{{ item.label }}</span>
          <span>({{ item.count }})</span>
        </router-link>
        <span v-else class="flex-wr-sb-c f1-s-10 text-uppercase cl2 trans-03">
          <span>{{ item.label }}</span>
          <span>({{ item.count }})</span>
        </span>
      </li>
    </ul>
  </div>
</template>
