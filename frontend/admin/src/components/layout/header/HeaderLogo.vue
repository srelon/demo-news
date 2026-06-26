<template>
    <a :href="site_url" target="_blank" rel="noopener" class="lg:hidden">
        <img class="dark:hidden" src="/images/logo/logo.svg" alt="Logo" />
        <img class="hidden dark:block" src="/images/logo/logo-dark.svg" alt="Logo" />
    </a>
</template>

<script setup lang="ts">
const site_url = import.meta.env.VITE_SITE_URL ?? '/'
</script>
