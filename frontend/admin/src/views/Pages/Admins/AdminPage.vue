<script setup lang="ts">
import CameraIcon from '@/icons/CameraIcon.vue'
import { ref, onMounted } from 'vue'
import moment from 'moment'
import EditPage from '@/views/Core/EditPage.vue'
import { object, string } from 'yup'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import BaseTabs from '@/components/ui/base/BaseTabs.vue'
import LoginLogsTable from '@/components/common/LoginLogsTable.vue'
import axios from '@/plugins/axios.ts'
import { useRoute } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()

const page_title = ref('Edit Admin')
const active_tab = ref('Edit')
const breadcrumb = ref([
    {
        title: 'Admins',
        route: 'admins',
    },
])

const form = ref([
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
        disabled: true,
    },
    {
        name: 'rule_id',
        model: null,
        placeholder: 'Rules',
        type: 'select',
    },
    {
        name: 'img',
        model: null,
        placeholder: 'Img',
        type: 'file',
    },
    {
        name: 'password',
        model: null,
        placeholder: 'Password',
        type: 'password',
    },
    {
        name: 'status',
        model: 0,
        placeholder: 'Status',
        type: 'select',
    },
    ...(auth.accesses('admins', 'edit') ? [{
        name: 'allowed_ip',
        model: null,
        placeholder: 'Allowed IP (leave empty to allow any)',
        type: 'text',
    }] : []),
])

const options = ref<Record<string, any>>({
    rule_id: null,
    status: [
        {
            id: 0,
            name: 'No active',
        },
        {
            id: 1,
            name: 'Active',
        },
    ],
})

interface AdminUser {
    id: number
    name: string
    email: string
    img: string
    created_at: string
    rule_id: string
    accesses_id: Record<string, Record<string, boolean>>
    status: number
    [key: string]: any
}

const user = ref<AdminUser | null>(null)
const base_url = import.meta.env.VITE_API_BASE_URL
const base = import.meta.env.BASE_URL

const schema = object({
    password: string().nullable().transform(val => val === '' ? null : val).min(6),
    name: string().required().min(4),
})

function fillForm(data: AdminUser) {
    user.value = data
    if (data.img) options.value.img = base_url + data.img

    form.value.forEach(field => {
        if (field.type === 'file') return
        if (data[field.name] !== undefined) field.model = data[field.name]
    })

    if (auth.user && Number(route.params.id) === auth.user.id) {
        auth.setUser(data)
    }
}

onMounted(() => {
    axios.get('admins/info/' + route.params.id).then((response) => {
        const data = response.data.data
        fillForm(data.admin)
        options.value.rule_id = data.rules
    })
})
</script>

<template>
    <admin-layout>
        <div v-if="user">
            <PageBreadcrumb :pageTitle="page_title" :breadcrumb="breadcrumb" />

            <BaseTabs :tabs="auth.accesses('admins', 'edit') ? ['Edit', 'Logs'] : ['Edit']" :current="active_tab" @updateTab="active_tab = $event">
                <EditPage
                    v-if="active_tab === 'Edit'"
                    :route="`admins/edit/${route.params.id}`"
                    title="Edit"
                    form_btn="Edit"
                    :form="form"
                    :user="user"
                    :options="options"
                    :schema="schema"
                    route_back="admins"
                    :access="auth.accesses('admins', 'edit')"
                    @updateForm="fillForm"
                >
                    <template #top_content>
                        <div class="p-4 sm:p-6 dark:border-gray-800">
                            <div class="p-5 mb-6 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6">
                                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                                    <div class="flex flex-col items-center w-full gap-6 xl:flex-row">
                                        <label
                                            for="img"
                                            class="relative w-20 h-20 overflow-hidden border border-gray-200 rounded-full dark:border-gray-800 cursor-pointer group"
                                        >
                                            <img :src="user.img ? base_url + user.img : `${base}images/user/default.jpg`" alt="user" class="w-full h-full object-cover" />
                                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                <CameraIcon class="w-6 h-6 text-white" />
                                            </div>
                                        </label>
                                        <div class="order-3 xl:order-2">
                                            <h4 class="mb-2 text-lg font-semibold text-center text-gray-800 dark:text-white/90 xl:text-left">
                                                {{ user.name }}
                                            </h4>
                                            <div class="flex flex-col items-center gap-1 text-center xl:flex-row xl:gap-3 xl:text-left">
                                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                                    {{ user.email }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-content-end order-2 gap-2 grow w-full xl:order-3 xl:justify-end">
                                        <div class="mr-3">
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Registration</p>
                                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                                {{ moment.utc(user.created_at).format('DD.MM.YYYY') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </EditPage>

                <LoginLogsTable
                    v-else-if="active_tab === 'Logs'"
                    :route="`admins/logs/${route.params.id}`"
                />
            </BaseTabs>
        </div>
        <BaseLoading v-else />
    </admin-layout>
</template>
