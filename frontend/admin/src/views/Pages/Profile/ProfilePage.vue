<script setup lang="ts">
import DeleteTrashIcon from '@/icons/DeleteTrashIcon.vue'
import SpinnerIcon from '@/icons/SpinnerIcon.vue'
import { ref, onMounted } from 'vue'
import moment from 'moment'
import EditPage from '@/views/Core/EditPage.vue'
import { object, string } from 'yup'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import axios from '@/plugins/axios'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const base_url = import.meta.env.VITE_API_BASE_URL
const base = import.meta.env.BASE_URL

const page_title = ref('My Profile')
const breadcrumb = ref<any[]>([])

interface AdminUser {
    id: number
    name: string
    email: string
    img: string | null
    created_at: string
    rule_id: number
    status: number
    [key: string]: any
}

interface ModeratorAccount {
    id: number
    name: string
    username: string | null
    img: string | null
    email: string
}

const user = ref<AdminUser | null>(null)

const form = ref([
    {
        name: 'name',
        model: null,
        placeholder: 'Name',
        type: 'text',
    },
    {
        name: 'img',
        model: null,
        placeholder: 'Avatar',
        type: 'file',
    },
    {
        name: 'password',
        model: null,
        placeholder: 'New password',
        type: 'password',
    },
    {
        name: 'allowed_ip',
        model: null,
        placeholder: 'Allowed IP (leave empty to allow any)',
        type: 'text',
    },
])

const options = ref<Record<string, any>>({})

const schema = object({
    name: string().required().min(4),
    password: string().nullable().transform(val => val === '' ? null : val).min(6),
})

// Moderator accounts
const accounts = ref<ModeratorAccount[]>([])
const accounts_loading = ref(false)
const search_query = ref('')
const search_results = ref<ModeratorAccount[]>([])
const search_loading = ref(false)
let search_timer: ReturnType<typeof setTimeout> | null = null

// Pending add with password confirmation
const pending_add = ref<ModeratorAccount | null>(null)
const pending_password = ref('')
const pending_error = ref('')
const pending_loading = ref(false)

function fillForm(data: AdminUser) {
    user.value = data
    if (data.img) options.value.img = base_url + data.img

    form.value.forEach(field => {
        if (field.type === 'file' || field.type === 'password') return
        if (data[field.name] !== undefined) field.model = data[field.name]
    })

    // Sync name change back to auth store
    if (auth.user) {
        auth.setUser({ ...auth.user, name: data.name, img: data.img ?? auth.user.img })
    }
}

function loadAccounts() {
    accounts_loading.value = true
    axios.get('profile/moderators').then((res) => {
        accounts.value = res.data.data.accounts
    }).finally(() => {
        accounts_loading.value = false
    })
}

function onSearchInput() {
    if (search_timer) clearTimeout(search_timer)
    if (search_query.value.trim().length < 2) {
        search_results.value = []
        return
    }
    search_timer = setTimeout(() => {
        search_loading.value = true
        axios.get('profile/users/search', { params: { q: search_query.value } }).then((res) => {
            search_results.value = res.data.data.users
        }).finally(() => {
            search_loading.value = false
        })
    }, 300)
}

function selectForAdd(account: ModeratorAccount) {
    pending_add.value = account
    pending_password.value = ''
    pending_error.value = ''
}

function confirmAdd() {
    if (!pending_add.value || !pending_password.value.trim() || pending_loading.value) return
    pending_loading.value = true
    pending_error.value = ''

    axios.post('profile/moderators', {
        user_id: pending_add.value.id,
        password: pending_password.value,
    }, { skipGlobalError: true } as any).then((res) => {
        if (!accounts.value.find(a => a.id === res.data.data.user.id)) {
            accounts.value.push(res.data.data.user)
        }
        pending_add.value = null
        pending_password.value = ''
        search_query.value = ''
        search_results.value = []
    }).catch((err) => {
        pending_error.value = err.response?.data?.errors ?? 'Incorrect password'
    }).finally(() => {
        pending_loading.value = false
    })
}

function cancelAdd() {
    pending_add.value = null
    pending_password.value = ''
    pending_error.value = ''
}

function removeAccount(user_id: number) {
    axios.delete(`profile/moderators/${user_id}`).then(() => {
        accounts.value = accounts.value.filter(a => a.id !== user_id)
    })
}

onMounted(() => {
    axios.get('profile').then((res) => {
        fillForm(res.data.data.admin)
    })

    if (auth.accesses('moderator', 'view')) {
        loadAccounts()
    }
})
</script>

<template>
    <admin-layout>
        <div v-if="user">
            <PageBreadcrumb :pageTitle="page_title" :breadcrumb="breadcrumb" />

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2 xl:items-start">
                <!-- Left: Edit Profile -->
                <EditPage
                    route="profile"
                    title="Edit Profile"
                    form_btn="Save"
                    :form="form"
                    :user="user"
                    :options="options"
                    :schema="schema"
                    :single_col="true"
                    @updateForm="(data: any) => fillForm(data.admin)"
                >
                    <template #top_content>
                        <div class="flex items-center gap-4 px-6 py-5 border-b border-gray-100 dark:border-gray-800">
                            <div class="w-14 h-14 rounded-full overflow-hidden border border-gray-200 dark:border-gray-700 shrink-0">
                                <img
                                    :src="user.img ? base_url + user.img : `${base}images/user/default.jpg`"
                                    alt="avatar"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-800 dark:text-white truncate">{{ user.name }}</p>
                                <p class="text-sm text-gray-400 truncate">{{ user.email }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs text-gray-400">Member since</p>
                                <p class="text-sm font-medium text-gray-700 dark:text-white/80">
                                    {{ moment.utc(user.created_at).format('DD.MM.YYYY') }}
                                </p>
                            </div>
                        </div>
                    </template>
                </EditPage>

                <!-- Right: Moderator Accounts -->
                <div
                    v-if="auth.accesses('moderator', 'edit')"
                    class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
                >
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                        <h2 class="text-lg font-medium text-gray-800 dark:text-white">Moderator Accounts</h2>
                    </div>
                    <div class="p-5 lg:p-6">
                        <p class="mb-5 text-sm text-gray-400">
                            Accounts you can act as when replying to comments.
                        </p>

                        <!-- Search -->
                        <div class="mb-4">
                            <div class="relative">
                                <input
                                    v-model="search_query"
                                    @input="onSearchInput"
                                    type="text"
                                    placeholder="Search by name, username or email..."
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 pr-10 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:bg-white focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800 dark:text-white/90 dark:placeholder:text-white/30"
                                />
                                <div v-if="search_loading" class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <SpinnerIcon class="w-4 h-4 animate-spin text-gray-400" />
                                </div>
                            </div>

                            <!-- Search results (hidden while confirming) -->
                            <div v-if="search_results.length && !pending_add" class="mt-1 rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900 overflow-hidden">
                                <button
                                    v-for="u in search_results"
                                    :key="u.id"
                                    @click="selectForAdd(u)"
                                    class="flex w-full items-center gap-3 px-4 py-2.5 text-left hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors border-b border-gray-100 dark:border-gray-800 last:border-b-0"
                                >
                                    <div class="w-8 h-8 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700 shrink-0">
                                        <img v-if="u.img" :src="base_url + u.img" :alt="u.name" class="w-full h-full object-cover" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-medium text-gray-800 dark:text-white truncate">
                                            {{ u.name }}
                                            <span v-if="u.username" class="ml-1 text-xs text-brand-500 font-normal">@{{ u.username }}</span>
                                        </div>
                                        <div class="text-xs text-gray-400 truncate">{{ u.email }}</div>
                                    </div>
                                    <span class="shrink-0 text-xs font-medium text-brand-500">Add</span>
                                </button>
                            </div>

                            <!-- Password confirmation -->
                            <div v-if="pending_add" class="mt-2 rounded-xl border border-brand-200 bg-brand-50/50 dark:border-brand-900/40 dark:bg-brand-900/10 overflow-hidden">
                                <div class="flex items-center gap-3 px-4 py-3 border-b border-brand-100 dark:border-brand-900/30">
                                    <div class="w-8 h-8 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700 shrink-0">
                                        <img v-if="pending_add.img" :src="base_url + pending_add.img" :alt="pending_add.name" class="w-full h-full object-cover" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-medium text-gray-800 dark:text-white truncate">
                                            {{ pending_add.name }}
                                            <span v-if="pending_add.username" class="ml-1 text-xs text-brand-500 font-normal">@{{ pending_add.username }}</span>
                                        </div>
                                        <div class="text-xs text-gray-400 truncate">{{ pending_add.email }}</div>
                                    </div>
                                </div>
                                <div class="px-4 py-3">
                                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                        Enter this account's password to confirm
                                    </p>
                                    <input
                                        v-model="pending_password"
                                        @keyup.enter="confirmAdd"
                                        type="password"
                                        placeholder="Password"
                                        autofocus
                                        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800 dark:text-white/90 dark:placeholder:text-white/30"
                                    />
                                    <p v-if="pending_error" class="mt-1.5 text-xs font-medium text-red-500">
                                        {{ pending_error }}
                                    </p>
                                    <div class="flex gap-2 mt-3">
                                        <button
                                            @click="confirmAdd"
                                            :disabled="!pending_password.trim() || pending_loading"
                                            class="rounded-lg bg-brand-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-brand-600 disabled:opacity-50 transition-colors"
                                        >{{ pending_loading ? 'Checking…' : 'Confirm' }}</button>
                                        <button
                                            @click="cancelAdd"
                                            class="rounded-lg border border-gray-200 px-4 py-1.5 text-xs text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors"
                                        >Cancel</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Linked accounts -->
                        <BaseLoading v-if="accounts_loading" />

                        <div v-else-if="!accounts.length" class="py-6 text-center text-sm text-gray-400">
                            No moderator accounts linked yet
                        </div>

                        <div v-else class="space-y-2">
                            <div
                                v-for="account in accounts"
                                :key="account.id"
                                class="flex items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-800/50"
                            >
                                <div class="w-9 h-9 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 shrink-0">
                                    <img v-if="account.img" :src="base_url + account.img" :alt="account.name" class="w-full h-full object-cover" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-gray-800 dark:text-white truncate">
                                        {{ account.name }}
                                        <span v-if="account.username" class="ml-1 text-xs text-brand-500 font-normal">@{{ account.username }}</span>
                                    </div>
                                    <div class="text-xs text-gray-400 truncate">{{ account.email }}</div>
                                </div>
                                <button
                                    @click="removeAccount(account.id)"
                                    class="shrink-0 rounded-lg p-1.5 text-gray-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors"
                                    title="Remove"
                                >
                                    <DeleteTrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <BaseLoading v-else />
    </admin-layout>
</template>
