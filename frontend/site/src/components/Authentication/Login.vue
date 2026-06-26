<script setup lang="ts">
import { reactive } from 'vue'
import { object, string } from 'yup'
import AuthBox from './AuthBox.vue'

const emit = defineEmits<{
    (e: 'success'): void
    (e: 'go-register'): void
    (e: 'go-forgot'): void
}>()

const form = reactive([
    {
        name: 'email',
        model: null,
        placeholder: 'Email',
        type: 'text',
    },
    {
        name: 'password',
        model: null,
        placeholder: 'Password',
        type: 'password',
    },
])

const schema = object({
    email: string().required('Email is required').email('Invalid email address'),
    password: string().required('Password is required').min(6, 'Minimum 6 characters'),
})
</script>

<template>
    <AuthBox
        route="login"
        form_btn="Sign In"
        :form="form"
        :schema="schema"
        @success="$emit('success')"
    >
        <template #middle>
            <div class="auth-forgot-row">
                <a href="#" class="auth-link-sm" @click.prevent="$emit('go-forgot')">Forgot password?</a>
            </div>
        </template>

        <template #footer>
            <p class="auth-switch">
                No account? <a href="#" @click.prevent="$emit('go-register')">Sign up</a>
            </p>
        </template>
    </AuthBox>
</template>
