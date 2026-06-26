<script setup lang="ts">
import { ref } from 'vue'
import { Form } from 'vee-validate'
import axios from '@/plugins/axios'
import { useAuthStore } from '@/stores/auth'
import BaseInput from '@/components/ui/base/BaseInput.vue'
import BaseButton from '@/components/ui/base/BaseButton.vue'

interface FormItem {
    name: string
    type: string
    placeholder?: string
    model?: any
    disabled?: boolean
}

const props = defineProps<{
    form: FormItem[]
    schema?: object
    route: string
    form_btn?: string
    extra_data?: Record<string, any>
}>()

const emit = defineEmits<{
    (e: 'success', data: any): void
}>()

const auth = useAuthStore()
const loading = ref(false)

function onSubmit(_: any, { setErrors }: any) {
    if (loading.value) return
    loading.value = true

    const form_data = {
        ...Object.fromEntries(props.form.map(i => [i.name, i.model])),
        ...props.extra_data,
    }

    axios.post(props.route, form_data).then((res) => {
        if (res.data.data?.user) {
            auth.setUser(res.data.data.user)
        }
        emit('success', res.data.data)
    }).catch((err) => {
        const response = err.response?.data?.errors
        if (response) {
            const formatted: Record<string, string> = {}
            Object.keys(response).forEach(field => {
                formatted[field] = response[field][0]
            })
            setErrors(formatted)
        }
    }).finally(() => {
        loading.value = false
    })
}
</script>

<template>
    <Form :validation-schema="schema" @submit="onSubmit">
        <div class="auth-fields">
            <div v-for="(input, key) in form" :key="key" class="form-group">
                <label class="auth-label">{{ input.placeholder }}</label>
                <BaseInput
                    :name="input.name"
                    :type="input.type"
                    :placeholder="input.placeholder"
                    :disabled="input.disabled"
                    :toggle_type="input.type === 'password'"
                    v-model="input.model"
                />
            </div>
        </div>

        <slot name="middle" />

        <BaseButton :loading="loading">{{ form_btn ?? 'Submit' }}</BaseButton>

        <slot name="footer" />
    </Form>
</template>
