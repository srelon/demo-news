<script setup lang="ts">
import { ref, computed } from 'vue'
import BaseModal from '@/components/ui/base/BaseModal.vue'
import Login from './Login.vue'
import Register from './Register.vue'
import Forgot from './Forgot.vue'
import ResetPassword from './ResetPassword.vue'

type Tab = 'login' | 'register' | 'forgot' | 'reset-password'

const props = defineProps<{
    initial_tab?: Tab
    token?: string
    email?: string
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const active_tab = ref<Tab>(props.initial_tab ?? 'login')

const tab_components: Record<Tab, any> = {
    login: Login,
    register: Register,
    forgot: Forgot,
    'reset-password': ResetPassword,
}

const tab_props = computed(() => {
    if (active_tab.value === 'reset-password') {
        return { token: props.token ?? '', email: props.email ?? '' }
    }
    return {}
})
</script>

<template>
    <BaseModal @close="$emit('close')">
        <div class="auth-box">
            <button class="auth-close" @click="$emit('close')" aria-label="Close">&times;</button>

            <div v-if="active_tab === 'login' || active_tab === 'register'" class="auth-tabs">
                <button
                    :class="['auth-tab', active_tab === 'login' ? 'is-active' : '']"
                    @click="active_tab = 'login'"
                >Sign In</button>
                <button
                    :class="['auth-tab', active_tab === 'register' ? 'is-active' : '']"
                    @click="active_tab = 'register'"
                >Sign Up</button>
            </div>

            <div v-else-if="active_tab === 'forgot'" class="auth-tabs-title">Forgot Password</div>
            <div v-else-if="active_tab === 'reset-password'" class="auth-tabs-title">Set New Password</div>

            <component
                :is="tab_components[active_tab]"
                :key="active_tab"
                v-bind="tab_props"
                @success="$emit('close')"
                @go-login="active_tab = 'login'"
                @go-register="active_tab = 'register'"
                @go-forgot="active_tab = 'forgot'"
            />
        </div>
    </BaseModal>
</template>

<style scoped>
.auth-box {
    background: #fff;
    border-radius: 10px;
    padding: 40px 44px 36px;
    width: 100%;
    max-width: 420px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
}

.auth-close {
    position: absolute;
    top: 14px;
    right: 18px;
    background: none;
    border: none;
    font-size: 26px;
    line-height: 1;
    color: #aaa;
    cursor: pointer;
    padding: 0;
    transition: color 0.15s;
}

.auth-close:hover {
    color: #333;
}

.auth-tabs {
    display: flex;
    border-bottom: 2px solid #eee;
    margin-bottom: 28px;
}

.auth-tab {
    flex: 1;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    padding: 10px 0;
    font-size: 15px;
    font-weight: 700;
    color: #aaa;
    cursor: pointer;
    letter-spacing: 0.3px;
    transition: color 0.2s, border-color 0.2s;
}

.auth-tab.is-active {
    color: #222;
    border-bottom-color: #222;
}

.auth-tabs-title {
    font-size: 18px;
    font-weight: 700;
    color: #222;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #eee;
}
</style>
