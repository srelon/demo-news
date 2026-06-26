<script setup lang="ts">
defineProps<{
  author: string
  source_name?: string
  source_url?: string
  date: string
  views?: number
  comments?: number
}>()
</script>

<template>
  <div class="flex-wr-s-s p-b-40">
    <span class="f1-s-3 cl8 m-r-15">
      <a
        v-if="source_name && source_url"
        :href="source_url"
        target="_blank"
        rel="noopener nofollow"
        class="f1-s-4 cl8 hov-cl10 trans-03"
      >
        by {{ source_name }}
      </a>
      <span v-else-if="source_name" class="f1-s-4 cl8">by {{ source_name }}</span>
      <span v-else-if="author" class="f1-s-4 cl8">by {{ author }}</span>

      <span v-if="source_name || author" class="m-rl-3">-</span>
      <span>{{ date }}</span>
    </span>

    <span v-if="views !== undefined" class="f1-s-3 cl8 m-r-15">
      {{ views }} Views
    </span>

    <a href="#comments" class="f1-s-3 cl8 hov-cl10 trans-03 m-r-15">
      {{ comments ?? 0 }} Comment
    </a>
  </div>
</template>
