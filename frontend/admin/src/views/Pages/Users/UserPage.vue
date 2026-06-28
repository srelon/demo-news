<script setup lang="ts">
import CameraIcon from '@/icons/CameraIcon.vue'
import { ref } from 'vue'
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
import BaseBtn from '@/components/ui/base/BaseBtn.vue'
import { useAuthStore } from '@/stores/auth.ts'

const auth = useAuthStore()

const currentPageTitle = ref('Edit users')
const active_tab = ref('Edit')
const breadcrumb = ref([
    {
        title: 'Users',
        route: 'users',
    },
])

const route = useRoute()

interface FormField {
    name: string
    model: any
    placeholder: string
    type: string
    disabled?: boolean
}

const form = ref<FormField[]>([
    {
        name: 'img',
        model: null,
        placeholder: 'Img',
        type: 'file',
    },
    {
        name: 'email',
        model: null,
        placeholder: 'Email',
        type: 'text',
        disabled: true,
    },
    {
        name: 'password',
        model: null,
        placeholder: 'Password',
        type: 'password',
    },
    {
        name: 'name',
        model: null,
        placeholder: 'Name',
        type: 'text',
    },
])

interface intUser {
    name: string
    email: string
    img?: string
    created_at: string
    [key: string]: any
}

const user = ref<intUser | null>(null)

const schema = object({
    password: string().nullable().transform(val => val === '' ? null : val).min(8),
    name: string().required().min(4),
})

axios.get('users/' + route.params.id).then((response) => {
    const data: intUser = response.data.data
    fillForm(data)
})

const baseUrl = import.meta.env.VITE_API_BASE_URL
const base = import.meta.env.BASE_URL

const options = ref<Record<string, any>>({})

function fillForm(data: intUser) {
    user.value = data
    if (data.img) options.value.img = baseUrl + data.img

    form.value.forEach(field => {
        if (field.type === 'file') return
        if (data[field.name] !== undefined) field.model = data[field.name]
    })
}
</script>

<template>
    <admin-layout>
        <div v-if="user">
            <PageBreadcrumb :pageTitle="currentPageTitle" :breadcrumb="breadcrumb" />

            <BaseTabs :tabs="['Edit', 'Logs']" :current="active_tab" @updateTab="active_tab = $event">
                <EditPage
                    v-if="active_tab === 'Edit'"
                    :route="`users/edit/${route.params.id}`"
                    title="Edit"
                    form_btn="Edit"
                    :form="form"
                    :user="user"
                    :options="options"
                    route_back="users"
                    :schema="schema"
                    @updateForm="fillForm"
                    :access="auth.accesses('users', 'edit')"
                >
                    <template #top_content>
                        <div class="p-4 sm:p-6 dark:border-gray-800">
                            <div class="p-5 mb-6 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6">
                                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                                    <div class="flex flex-col items-center w-full gap-6 xl:flex-row">
                                        <label for="img" class="relative w-20 h-20 overflow-hidden border border-gray-200 rounded-full dark:border-gray-800 cursor-pointer group">
                                            <img :src="(user.img) ? baseUrl + user.img : `${base}images/user/default.jpg`" alt="user" class="w-full h-full object-cover">
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
                                        <BaseBtn color="success" v-if="auth.accesses('admins', 'edit')">
                                            Login user
                                        </BaseBtn>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </EditPage>

                <LoginLogsTable
                    v-else-if="active_tab === 'Logs'"
                    :route="`users/logs/${user.id}`"
                />
            </BaseTabs>
        </div>
        <BaseLoading v-else />
    </admin-layout>
</template>
