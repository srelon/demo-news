<script setup>
  import AppHeader from './AppHeader.vue'
  import AppFooter from './AppFooter.vue'
</script>

<template>
  <div>
    <app-header />
    <slot></slot>
    <app-footer />
  </div>
</template>
