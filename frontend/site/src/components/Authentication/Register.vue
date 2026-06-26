<script setup lang="ts">
import { reactive } from 'vue'
import { object, string, ref as yupRef } from 'yup'
import AuthBox from './AuthBox.vue'

const emit = defineEmits<{
    (e: 'success'): void
    (e: 'go-login'): void
}>()

const form = reactive([
    {
        name: 'name',
        model: null,
        placeholder: 'Name',
        type: 'text',
    },
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
    {
        name: 'password_confirmation',
        model: null,
        placeholder: 'Confirm Password',
        type: 'password',
    },
])

const schema = object({
    name: string().required('Name is required'),
    email: string().required('Email is required').email('Invalid email address'),
    password: string().required('Password is required').min(6, 'Minimum 6 characters'),
    password_confirmation: string()
        .required('Please confirm your password')
        .oneOf([yupRef('password')], 'Passwords do not match'),
})
</script>

<template>
    <AuthBox
        route="register"
        form_btn="Create Account"
        :form="form"
        :schema="schema"
        @success="$emit('success')"
    >
        <template #footer>
            <p class="auth-switch">
                Already have an account? <a href="#" @click.prevent="$emit('go-login')">Sign in</a>
            </p>
        </template>
    </AuthBox>
</template>
