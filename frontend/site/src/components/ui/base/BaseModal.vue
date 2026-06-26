<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'

defineEmits<{ (e: 'close'): void }>()

onMounted(() => { document.body.style.overflow = 'hidden' })
onUnmounted(() => { document.body.style.overflow = '' })
</script>

<template>
    <div class="base-modal-overlay" @click.self="$emit('close')">
        <slot />
    </div>
</template>

<style scoped>
.base-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
</style>
