<script setup lang="ts">
defineProps({
  title: String,
  tag: {
    type: String,
    default: 'h3'
  },
  title_type: {
    type: String,
    default: 'default'
  },
})
</script>

<template>


  <div v-if="title_type== 'main'">
    <component :is="tag" class="f1-l-1 cl2">
      {{ title }}
    </component>
  </div>
  <div class="how2 how2-cl4 flex-s-c" v-else>
    <component :is="tag" class="f1-m-2 cl3 tab01-title">
      {{ title }}
    </component>
  </div>
</template>
