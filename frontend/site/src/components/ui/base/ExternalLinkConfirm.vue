<script setup lang="ts">
import { computed } from 'vue'
import BaseModal from '@/components/ui/base/BaseModal.vue'

const props = defineProps<{
  url: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const host = computed(() => {
  try {
    return new URL(props.url).host
  } catch {
    return props.url
  }
})

function proceed() {
  window.open(props.url, '_blank', 'noopener,noreferrer')
  emit('close')
}
</script>

<template>
  <BaseModal @close="emit('close')">
    <div class="elc-card">
      <h4 class="elc-title">Leaving the site</h4>

      <p class="elc-text">
        You are about to open an external resource:
      </p>

      <p class="elc-host">{{ host }}</p>
      <p class="elc-url">{{ url }}</p>

      <p class="elc-text elc-note">
        We are not responsible for the content of third-party websites.
      </p>

      <div class="elc-actions">
        <button class="elc-btn elc-btn-cancel" @click="emit('close')">Cancel</button>
        <button class="elc-btn elc-btn-go" @click="proceed">Continue</button>
      </div>
    </div>
  </BaseModal>
</template>

<style scoped>
.elc-card {
  background: #fff;
  border-radius: 8px;
  padding: 28px;
  width: 100%;
  max-width: 440px;
}

.elc-title {
  font-size: 20px;
  font-weight: 700;
  color: #333;
  margin: 0 0 12px;
}

.elc-text {
  font-size: 14px;
  color: #666;
  margin: 0 0 8px;
}

.elc-host {
  font-size: 16px;
  font-weight: 700;
  color: #333;
  margin: 0 0 4px;
  word-break: break-all;
}

.elc-url {
  font-size: 12px;
  color: #999;
  margin: 0 0 12px;
  word-break: break-all;
  max-height: 60px;
  overflow: hidden;
}

.elc-note {
  color: #999;
  font-size: 12px;
}

.elc-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 16px;
}

.elc-btn {
  border: none;
  border-radius: 4px;
  padding: 9px 18px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
}

.elc-btn:hover {
  opacity: 0.85;
}

.elc-btn-cancel {
  background: #eee;
  color: #555;
}

.elc-btn-go {
  background: #e71d69;
  color: #fff;
}
</style>
