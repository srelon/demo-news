<script setup lang="ts">
withDefaults(defineProps<{
    loading?: boolean
    disabled?: boolean
    type?: 'submit' | 'button' | 'reset'
    block?: boolean
}>(), {
    loading: false,
    disabled: false,
    type: 'submit',
    block: true,
})
</script>

<template>
    <button
        :type="type"
        class="btn auth-btn"
        :class="{ 'btn-block': block }"
        :disabled="disabled || loading"
    >
        <span v-if="loading" class="auth-spinner"></span>
        <span v-else><slot /></span>
    </button>
</template>
