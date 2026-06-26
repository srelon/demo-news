<script setup lang="ts">
import { reactive, ref } from 'vue'
import { object, string } from 'yup'
import AuthBox from './AuthBox.vue'

const emit = defineEmits<{
    (e: 'go-login'): void
}>()

const sent = ref(false)
const sent_email = ref('')

const form = reactive([
    {
        name: 'email',
        model: null,
        placeholder: 'Email',
        type: 'text',
    },
])

const schema = object({
    email: string().required('Email is required').email('Invalid email address'),
})

function onSuccess() {
    sent_email.value = form[0]?.model ?? ''
    sent.value = true
}
</script>

<template>
    <div v-if="sent" class="auth-sent">
        <p>We sent a reset link to <strong>{{ sent_email }}</strong>.</p>
        <a href="#" class="auth-link-sm" @click.prevent="$emit('go-login')">← Back to Sign In</a>
    </div>

    <AuthBox
        v-else
        route="forgot"
        form_btn="Send Reset Link"
        :form="form"
        :schema="schema"
        @success="onSuccess"
    >
        <template #footer>
            <p class="auth-switch">
                <a href="#" @click.prevent="$emit('go-login')">← Back to Sign In</a>
            </p>
        </template>
    </AuthBox>
</template>
