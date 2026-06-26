<script setup lang="ts">
import ExternalLinkOutlineIcon from '@/icons/ExternalLinkOutlineIcon.vue'
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/plugins/axios'
import { object, string } from 'yup'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import EditPage from '@/views/Core/EditPage.vue'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const site_url = import.meta.env.VITE_SITE_URL ?? ''

const is_create = computed(() => !route.params.id)
const loaded = ref(false)
const page_slug = ref<string | null>(null)
const page_user = ref<Record<string, any>>({})

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
            f.model = f.name === 'active' ? Number(data[f.name]) : data[f.name]
        }
    })
    page_slug.value = data.slug ?? null
    page_user.value = { title: data.title, content: data.content, active: Number(data.active) }
    loaded.value = true
}

function onSubmitResult(data: any) {
    if (is_create.value) {
        router.push({ name: 'static_page', params: { id: data.id } })
    } else {
        fillForm(data)
    }
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
                <a
                    v-if="page_url"
                    :href="page_url"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-600 hover:border-brand-400 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-brand-500 dark:hover:text-brand-400 transition-colors"
                >
                    <ExternalLinkOutlineIcon class="w-4 h-4" />
                    View page
                </a>
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
    </AdminLayout>
</template>
