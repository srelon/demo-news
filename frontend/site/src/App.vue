<script setup lang="ts">
import { computed, onMounted } from 'vue'
import Layout from '@/components/layout/Layout.vue'
import AuthModal from '@/components/Authentication/AuthModal.vue'
import { useLayoutStore } from '@/stores/layout'
import { useAuthStore } from '@/stores/auth'

const layoutStore = useLayoutStore()
const authStore = useAuthStore()

const reset_pending = computed(() => authStore.reset_pending)

onMounted(() => {
    layoutStore.fetchLayout()
})
</script>

<template>
    <Layout>
        <router-view />
    </Layout>

    <AuthModal
        v-if="reset_pending"
        initial_tab="reset-password"
        :token="reset_pending.token"
        :email="reset_pending.email"
        @close="authStore.clearResetPending()"
    />
</template>
