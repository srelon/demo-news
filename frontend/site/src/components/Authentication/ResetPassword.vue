<script setup lang="ts">
import { reactive } from 'vue'
import { object, string, ref as yupRef } from 'yup'
import AuthBox from './AuthBox.vue'

const props = defineProps<{
    token: string
    email: string
}>()

const emit = defineEmits<{
    (e: 'success'): void
}>()

const form = reactive([
    {
        name: 'email',
        model: props.email,
        placeholder: 'Email',
        type: 'text',
        disabled: true,
    },
    {
        name: 'password',
        model: null,
        placeholder: 'New Password',
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
    password: string().required('Password is required').min(6, 'Minimum 6 characters'),
    password_confirmation: string()
        .required('Please confirm your password')
        .oneOf([yupRef('password')], 'Passwords do not match'),
})
</script>

<template>
    <AuthBox
        route="reset-password"
        form_btn="Set New Password"
        :form="form"
        :schema="schema"
        :extra_data="{ token }"
        @success="$emit('success')"
    />
</template>
