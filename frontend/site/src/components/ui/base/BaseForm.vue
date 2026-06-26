<script setup lang="ts">
import { ref, reactive } from 'vue'
import { Form } from 'vee-validate'
import axios from '@/plugins/axios'
import BaseInput from './BaseInput.vue'
import BaseButton from './BaseButton.vue'

interface FormItem {
    name: string
    type: string
    placeholder?: string
    model?: any
    disabled?: boolean
    current_url?: string
    width?: number
    height?: number
}

const props = defineProps<{
    form: FormItem[]
    schema?: object
    route: string
    form_btn?: string
    extra_data?: Record<string, any>
    disabled?: boolean
}>()

const emit = defineEmits<{
    (e: 'success', data: any): void
}>()

const loading = ref(false)
const previews = reactive<Record<string, string>>({})
const file_refs = {} as Record<string, HTMLInputElement>

function onFileChange(e: Event, item: FormItem) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file) return
    item.model = file
    previews[item.name] = URL.createObjectURL(file)
}

function onSubmit(_: any, { setErrors }: any) {
    if (loading.value) return
    loading.value = true

    const payload: Record<string, any> = {
        ...Object.fromEntries(
            props.form
                .filter(i => !i.disabled && !(i.type === 'file' && i.model === null))
                .map(i => [i.name, i.model])
        ),
        ...props.extra_data,
    }

    const has_file = Object.values(payload).some(v => v instanceof File)

    let request
    if (has_file) {
        const form_data = new FormData()
        Object.entries(payload).forEach(([k, v]) => {
            if (v !== null && v !== undefined) form_data.append(k, v)
        })
        request = axios.post(props.route, form_data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
    } else {
        request = axios.post(props.route, payload)
    }

    request.then((res) => {
        emit('success', res.data.data)
    }).catch((err) => {
        const errors = err.response?.data?.errors
        if (errors) {
            const formatted: Record<string, string> = {}
            Object.keys(errors).forEach(k => {
                formatted[k] = Array.isArray(errors[k]) ? errors[k][0] : errors[k]
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
        <div v-for="(input, key) in form" :key="key" class="form-group">
            <label class="auth-label">{{ input.placeholder }}</label>

            <template v-if="input.type === 'file'">
                <input
                    :ref="(el) => { if (el) file_refs[input.name] = el as HTMLInputElement }"
                    type="file"
                    accept="image/*"
                    class="d-none"
                    @change="onFileChange($event, input)"
                />
                <div
                    class="bf-upload"
                    :style="{
                        width: input.width ? input.width + 'px' : '100%',
                        height: input.height ? input.height + 'px' : undefined,
                        borderRadius: input.width && input.width === input.height ? '50%' : '8px',
                    }"
                    @click="file_refs[input.name]?.click()"
                >
                    <template v-if="previews[input.name] || input.current_url">
                        <img
                            :src="previews[input.name] || input.current_url"
                            class="bf-upload__img"
                            alt="preview"
                        />
                        <div class="bf-upload__overlay">
                            <i class="fa fa-camera"></i>
                        </div>
                    </template>
                    <div v-else class="bf-upload__placeholder">
                        <i class="fa fa-user fa-2x" v-if="input.width && input.width === input.height"></i>
                        <i class="fa fa-image fa-2x" v-else></i>
                        <span>Upload</span>
                    </div>
                </div>
            </template>

            <BaseInput
                v-else
                :name="input.name"
                :type="input.type"
                :placeholder="input.placeholder"
                :disabled="input.disabled"
                :toggle_type="input.type === 'password'"
                v-model="input.model"
            />
        </div>

        <slot name="middle" />

        <BaseButton :loading="loading" :disabled="disabled">{{ form_btn ?? 'Save' }}</BaseButton>

        <slot name="footer" />
    </Form>
</template>

<style scoped>
.bf-upload {
    position: relative;
    width: 100%;
    border: 2px dashed #ddd;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: border-color 0.15s;
    min-height: 110px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bf-upload:hover {
    border-color: #17b978;
}

.bf-upload__img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.bf-upload__overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
    color: #fff;
    font-size: 18px;
}

.bf-upload:hover .bf-upload__overlay {
    opacity: 1;
}

.bf-upload__placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #bbb;
    font-size: 13px;
    padding: 20px;
}
</style>
