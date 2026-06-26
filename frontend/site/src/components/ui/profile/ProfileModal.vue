<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { object, string, ref as yupRef } from 'yup'
import { useAuthStore } from '@/stores/auth'
import { toImageUrl } from '@/stores/layout'
import BaseModal from '@/components/ui/base/BaseModal.vue'
import BaseForm from '@/components/ui/base/BaseForm.vue'

defineEmits<{ (e: 'close'): void }>()

const authStore = useAuthStore()

// ── Profile form ──────────────────────────────────────────────────────────────

const initial_name = ref('')
const initial_username = ref('')

const profile_form = reactive([
    {
        name: 'img',
        model: null as File | null,
        placeholder: 'Avatar',
        type: 'file',
        current_url: '' as string,
        width: 150,
        height: 150,
    },
    {
        name: 'name',
        model: null as string | null,
        placeholder: 'Your name',
        type: 'text',
    },
    {
        name: 'username',
        model: null as string | null,
        placeholder: 'Username (e.g. john_doe)',
        type: 'text',
    },
    {
        name: 'email',
        model: null as string | null,
        placeholder: 'Email',
        type: 'text',
        disabled: true,
    },
])

const profile_schema = object({
    name: string().required('Name is required'),
    username: string()
        .nullable()
        .optional()
        .matches(/^[a-z0-9_]+$/, { message: 'Only lowercase letters, numbers and underscore', excludeEmptyString: true }),
})

const profile_unchanged = computed(() => {
    const name_field = profile_form.find(f => f.name === 'name')
    const img_field = profile_form.find(f => f.name === 'img')
    const username_field = profile_form.find(f => f.name === 'username')
    return (
        !img_field?.model &&
        name_field?.model === initial_name.value &&
        (username_field?.model ?? '') === initial_username.value
    )
})

const profile_saved = ref(false)

function onProfileSuccess(data: any) {
    authStore.setUser(data.user)
    initial_name.value = data.user?.name ?? ''
    initial_username.value = data.user?.username ?? ''

    const img_field = profile_form.find(f => f.name === 'img')
    if (img_field) {
        img_field.model = null
        img_field.current_url = toImageUrl(data.user?.img ?? '')
    }

    profile_saved.value = true
    setTimeout(() => { profile_saved.value = false }, 3000)
}

// ── Password form ─────────────────────────────────────────────────────────────

const password_form = reactive([
    {
        name: 'old_password',
        model: null,
        placeholder: 'Current password',
        type: 'password',
    },
    {
        name: 'new_password',
        model: null,
        placeholder: 'New password',
        type: 'password',
    },
    {
        name: 'new_password_confirmation',
        model: null,
        placeholder: 'Confirm new password',
        type: 'password',
    },
])

const password_schema = object({
    old_password: string().required('Current password is required'),
    new_password: string()
        .required('New password is required')
        .min(6, 'Minimum 6 characters')
        .test('not-same', 'New password must differ from current password', function (value) {
            return value !== this.parent.old_password
        }),
    new_password_confirmation: string()
        .required('Please confirm your password')
        .oneOf([yupRef('new_password')], 'Passwords do not match'),
})

const password_saved = ref(false)

function onPasswordSuccess() {
    password_form.forEach(f => { f.model = null })
    password_saved.value = true
    setTimeout(() => { password_saved.value = false }, 3000)
}

// ── Init ──────────────────────────────────────────────────────────────────────

onMounted(() => {
    const name = authStore.user?.name ?? ''
    const username = authStore.user?.username ?? ''
    const img_field = profile_form.find(f => f.name === 'img')
    const name_field = profile_form.find(f => f.name === 'name')
    const username_field = profile_form.find(f => f.name === 'username')
    const email_field = profile_form.find(f => f.name === 'email')

    if (img_field) img_field.current_url = toImageUrl(authStore.user?.img ?? '')
    if (name_field) name_field.model = name
    if (username_field) username_field.model = username
    if (email_field) email_field.model = authStore.user?.email ?? ''
    initial_name.value = name
    initial_username.value = username
})
</script>

<template>
    <BaseModal @close="$emit('close')">
        <div class="pm-box">
            <button class="pm-close" type="button" @click="$emit('close')" aria-label="Close">&times;</button>

            <div class="pm-grid">
                <!-- Left: Profile -->
                <div class="pm-col">
                    <div class="pm-section-title">Profile</div>
                    <BaseForm
                        route="profile/update"
                        form_btn="Save Changes"
                        :form="profile_form"
                        :schema="profile_schema"
                        :disabled="profile_unchanged"
                        @success="onProfileSuccess"
                    >
                        <template #footer>
                            <div v-if="profile_saved" class="pm-success">Profile saved!</div>
                        </template>
                    </BaseForm>
                </div>

                <div class="pm-divider"></div>

                <!-- Right: Password -->
                <div class="pm-col">
                    <div class="pm-section-title">Change Password</div>
                    <BaseForm
                        route="profile/password"
                        form_btn="Change Password"
                        :form="password_form"
                        :schema="password_schema"
                        @success="onPasswordSuccess"
                    >
                        <template #footer>
                            <div v-if="password_saved" class="pm-success">Password changed!</div>
                        </template>
                    </BaseForm>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<style scoped>
.pm-box {
    background: #fff;
    border-radius: 10px;
    padding: 36px 40px 32px;
    width: 100%;
    max-width: 820px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
}

.pm-grid {
    display: flex;
    gap: 0;
    align-items: flex-start;
}

.pm-col {
    flex: 1;
    min-width: 0;
}

.pm-col:first-child {
    padding-right: 36px;
}

.pm-col:last-child {
    padding-left: 36px;
}

.pm-divider {
    width: 1px;
    background: #f0f0f0;
    align-self: stretch;
    flex-shrink: 0;
}

.pm-close {
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

.pm-close:hover { color: #333; }

.pm-section-title {
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #aaa;
    margin-bottom: 16px;
}

.pm-success {
    font-size: 13px;
    color: #166534;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 6px;
    padding: 8px 14px;
    margin-top: 12px;
}
</style>
