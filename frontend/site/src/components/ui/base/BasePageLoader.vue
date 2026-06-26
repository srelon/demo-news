<script setup lang="ts">
defineProps<{ is_loading: boolean }>()
</script>

<template>
  <slot v-if="!is_loading" />

  <div v-else class="page-loader container p-t-40 p-b-80">
    <div class="row">
      <div class="col-lg-8">
        <div class="sk-block sk-hero m-b-30"></div>

        <div class="row m-b-20">
          <div class="col-6"><div class="sk-block sk-card"></div></div>
          <div class="col-6"><div class="sk-block sk-card"></div></div>
        </div>
        <div class="row m-b-20">
          <div class="col-6"><div class="sk-block sk-card"></div></div>
          <div class="col-6"><div class="sk-block sk-card"></div></div>
        </div>

        <div class="sk-block sk-line m-b-12" style="width:60%"></div>
        <div class="sk-block sk-line m-b-12"></div>
        <div class="sk-block sk-line m-b-12" style="width:80%"></div>
      </div>

      <div class="col-lg-4">
        <div class="sk-block sk-sidebar-item m-b-20"></div>
        <div class="sk-block sk-sidebar-item m-b-20"></div>
        <div class="sk-block sk-sidebar-item"></div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes sk-pulse {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.4; }
}

.page-loader {
  min-height: 70vh;
}

.sk-block {
  background: #e8e8e8;
  border-radius: 4px;
  animation: sk-pulse 1.5s ease-in-out infinite;
}

.sk-hero {
  height: 320px;
}

.sk-card {
  height: 220px;
}

.sk-line {
  height: 14px;
  width: 100%;
}

.sk-sidebar-item {
  height: 160px;
}
</style>
