<script setup lang="ts">
import ExternalLinkOutlineIcon from '@/icons/ExternalLinkOutlineIcon.vue'
import TrashOutlineIcon from '@/icons/TrashOutlineIcon.vue'
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/plugins/axios'
import { object, string } from 'yup'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import EditPage from '@/views/Core/EditPage.vue'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import BaseBtn from '@/components/ui/base/BaseBtn.vue'
import BaseModal from '@/components/ui/base/BaseModal.vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'

const auth = useAuthStore()
const toast = useToast()
const route = useRoute()
const router = useRouter()
const site_url = import.meta.env.VITE_SITE_URL ?? ''

const is_create = computed(() => !route.params.id)
const loaded = ref(false)
const page_slug = ref<string | null>(null)
const page_protected = ref(false)
const page_user = ref<Record<string, any>>({})
const show_delete = ref(false)
const deleting = ref(false)

const breadcrumb = [{ title: 'Pages', route: 'static_pages' }]

const page_url = computed(() =>
    page_slug.value ? `${site_url}/page/${page_slug.value}` : null
)

const status_options = [
    { id: 1, name: 'Active', color: 'green' },
    { id: 0, name: 'Inactive', color: 'orange' },
]

const form = ref([
    {
        name: 'title',
        model: null,
        placeholder: 'Title',
        type: 'text',
        full: true,
    },
    {
        name: 'active',
        model: 1,
        placeholder: 'Status',
        type: 'status-select',
        full: true,
    },
    {
        name: 'deletion_protected',
        model: 0,
        placeholder: 'Protect this page from being deleted',
        type: 'checkbox',
        full: true,
    },
    {
        name: 'content',
        model: null,
        placeholder: 'Content',
        type: 'editor',
        full: true,
    },
])

const options = ref({
    active: status_options,
})

const schema = object({
    title: string().required().min(2),
    content: string().nullable(),
})

function fillForm(data: any) {
    form.value.forEach(f => {
        if (data[f.name] !== undefined) {
            f.model = ['active', 'deletion_protected'].includes(f.name) ? Number(data[f.name]) : data[f.name]
        }
    })
    page_slug.value = data.slug ?? null
    page_protected.value = !!data.deletion_protected
    page_user.value = {
        title: data.title,
        content: data.content,
        active: Number(data.active),
        deletion_protected: Number(data.deletion_protected),
    }
    loaded.value = true
}

function onSubmitResult(data: any) {
    if (is_create.value) {
        router.push({ name: 'static_page', params: { id: data.id } })
    } else {
        fillForm(data)
    }
}

function deletePage() {
    if (deleting.value) return
    deleting.value = true

    axios.post(`page/delete/${route.params.id}`)
        .then(() => {
            toast.success('Page deleted successfully')
            router.push({ name: 'static_pages' })
        })
        .catch(err => {
            deleting.value = false
            show_delete.value = false
            toast.error(err.response?.data?.errors ?? 'Failed to delete page')
        })
}

if (is_create.value) {
    loaded.value = true
} else {
    axios.get(`page/${route.params.id}`).then(res => fillForm(res.data.data))
}

watch(() => route.params.id, (new_id) => {
    if (new_id) {
        loaded.value = false
        axios.get(`page/${new_id}`).then(res => fillForm(res.data.data))
    }
})
</script>

<template>
    <AdminLayout>
        <div v-if="loaded">
            <div class="flex items-center justify-between mb-4">
                <PageBreadcrumb
                    :pageTitle="is_create ? 'Create Page' : 'Edit Page'"
                    :breadcrumb="breadcrumb"
                />
                <div class="flex items-center gap-2">
                    <a
                        v-if="page_url"
                        :href="page_url"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-600 hover:border-brand-400 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-brand-500 dark:hover:text-brand-400 transition-colors"
                    >
                        <ExternalLinkOutlineIcon class="w-4 h-4" />
                        View page
                    </a>
                    <button
                        v-if="!is_create && !page_protected && auth.accesses('articles', 'edit')"
                        @click="show_delete = true"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-600 hover:border-error-400 hover:text-error-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-error-500 dark:hover:text-error-400 transition-colors"
                    >
                        <TrashOutlineIcon class="w-4 h-4" />
                        Delete
                    </button>
                </div>
            </div>

            <EditPage
                :route="is_create ? 'page/create' : `page/edit/${route.params.id}`"
                :title="is_create ? 'New Page' : 'Page'"
                :form_btn="is_create ? 'Create' : 'Save'"
                route_back="static_pages"
                :form="form"
                :user="is_create ? undefined : page_user"
                :options="options"
                :schema="schema"
                :access="auth.accesses('articles', 'edit')"
                @updateForm="onSubmitResult"
            />
        </div>
        <BaseLoading v-else />

        <BaseModal v-if="show_delete" @close="show_delete = false">
            <template #body>
                <div class="no-scrollbar relative w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-6 dark:bg-gray-900 mx-5">
                    <button
                        @click="show_delete = false"
                        class="absolute right-5 top-5 flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-white/[0.07]"
                    >✕</button>

                    <h4 class="mb-2 text-xl font-semibold text-gray-800 dark:text-white/90">Delete Page</h4>

                    <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
                        Are you sure you want to delete this page? This action cannot be undone from the admin panel.
                    </p>

                    <div class="flex justify-end gap-3">
                        <BaseBtn color="secondary" @click="show_delete = false">Cancel</BaseBtn>
                        <BaseBtn color="error" :loading="deleting" @click="deletePage">Delete</BaseBtn>
                    </div>
                </div>
            </template>
        </BaseModal>
    </AdminLayout>
</template>
