<script setup lang="ts">
import { ref } from 'vue'
import axios from '@/plugins/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import BaseTablePagination from '@/components/ui/base/BaseTablePagination.vue'
import BaseBtn from '@/components/ui/base/BaseBtn.vue'
import BaseModal from '@/components/ui/base/BaseModal.vue'
import TrashOutlineIcon from '@/icons/TrashOutlineIcon.vue'
import { useToast } from 'vue-toastification'

const toast = useToast()
const currentPageTitle = ref('Pages')
const table_ref = ref<InstanceType<typeof BaseTablePagination> | null>(null)
const delete_page = ref<any>(null)

function deletePage() {
    if (!delete_page.value) return
    const page = delete_page.value

    axios.post(`page/delete/${page.id}`)
        .then(() => {
            delete_page.value = null
            table_ref.value?.reload()
            toast.success(`Page deleted: ${page.title}`)
        })
        .catch(err => {
            delete_page.value = null
            toast.error(err.response?.data?.errors ?? 'Failed to delete page')
        })
}

const headers = ref([
    {
        key: 'id',
        text: 'ID',
    },
    {
        key: 'title',
        text: 'Title',
    },
    {
        key: 'slug',
        text: 'Slug',
    },
    {
        key: 'active',
        text: 'Status',
    },
    {
        key: 'updated_at',
        text: 'Updated',
        time: true,
    },
    {
        key: 'action',
        text: 'Action',
    },
])
</script>

<template>
    <AdminLayout>
        <div>
            <PageBreadcrumb :pageTitle="currentPageTitle" />
            <BaseTablePagination
                ref="table_ref"
                route="pages"
                route_type="get"
                :select_page="false"
                :headers="headers"
            >
                <template #header_right>
                    <BaseBtn :to="{name: 'static_page.create'}" color="info">Create Page</BaseBtn>
                </template>

                <template #active="{data}">
                    <span
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium',
                            data.active
                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                : 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                        ]"
                    >
                        <span :class="['h-1.5 w-1.5 rounded-full', data.active ? 'bg-green-500' : 'bg-orange-400']"></span>
                        {{ data.active ? 'Active' : 'Inactive' }}
                    </span>
                </template>

                <template #action="{data}">
                    <div class="flex items-center gap-3">
                        <router-link
                            :to="{name: 'static_page', params: {id: data.id}}"
                            class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/90"
                        >
                            <svg class="fill-current" width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M17.0911 3.53206C16.2124 2.65338 14.7878 2.65338 13.9091 3.53206L5.6074 11.8337C5.29899 12.1421 5.08687 12.5335 4.99684 12.9603L4.26177 16.445C4.20943 16.6931 4.286 16.9508 4.46529 17.1301C4.64458 17.3094 4.90232 17.3859 5.15042 17.3336L8.63507 16.5985C9.06184 16.5085 9.45324 16.2964 9.76165 15.988L18.0633 7.68631C18.942 6.80763 18.942 5.38301 18.0633 4.50433L17.0911 3.53206ZM14.9697 4.59272C15.2626 4.29982 15.7375 4.29982 16.0304 4.59272L17.0027 5.56499C17.2956 5.85788 17.2956 6.33276 17.0027 6.62565L16.1043 7.52402L14.0714 5.49109L14.9697 4.59272ZM13.0107 6.55175L6.66806 12.8944C6.56526 12.9972 6.49455 13.1277 6.46454 13.2699L5.96704 15.6283L8.32547 15.1308C8.46772 15.1008 8.59819 15.0301 8.70099 14.9273L15.0436 8.58468L13.0107 6.55175Z" fill="" />
                            </svg>
                        </router-link>
                        <button
                            v-if="!data.deletion_protected"
                            @click="delete_page = data"
                            class="text-gray-400 hover:text-error-500 dark:hover:text-error-400"
                            title="Delete"
                        >
                            <TrashOutlineIcon class="w-4 h-4" />
                        </button>
                    </div>
                </template>
            </BaseTablePagination>
        </div>

        <BaseModal v-if="delete_page" @close="delete_page = null">
            <template #body>
                <div class="no-scrollbar relative w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-6 dark:bg-gray-900 mx-5">
                    <button
                        @click="delete_page = null"
                        class="absolute right-5 top-5 flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-white/[0.07]"
                    >✕</button>

                    <h4 class="mb-2 text-xl font-semibold text-gray-800 dark:text-white/90">Delete Page</h4>

                    <div class="mb-5 space-y-1 text-sm text-gray-500 dark:text-gray-400">
                        <p><span class="font-medium text-gray-700 dark:text-gray-300">Title:</span> {{ delete_page.title }}</p>
                        <p><span class="font-medium text-gray-700 dark:text-gray-300">Slug:</span> {{ delete_page.slug }}</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <BaseBtn color="secondary" @click="delete_page = null">Cancel</BaseBtn>
                        <BaseBtn color="error" @click="deletePage">Delete</BaseBtn>
                    </div>
                </div>
            </template>
        </BaseModal>
    </AdminLayout>
</template>
