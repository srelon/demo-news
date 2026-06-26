<script setup lang="ts">
import ExternalLinkOutlineIcon from '@/icons/ExternalLinkOutlineIcon.vue'
import RefreshIcon from '@/icons/RefreshIcon.vue'
import { ref, computed, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/plugins/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import EditPage from '@/views/Core/EditPage.vue'
import ArticleComments from '@/views/Pages/Articles/ArticleComments.vue'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import BaseTabs from '@/components/ui/base/BaseTabs.vue'
import { object, string } from 'yup'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const base_url = import.meta.env.VITE_API_BASE_URL
const site_url = import.meta.env.VITE_SITE_URL ?? ''

const is_create = computed(() => !route.params.id)
const active_tab = ref(route.query.tab === 'comments' ? 'Comments' : 'Edit')

const loaded = ref(false)
const article = ref<any>(null)
const article_user = ref<Record<string, any>>({})

const breadcrumb = [
    {
        title: 'Articles',
        route: 'articles',
    },
]

const today = new Date().toISOString().substring(0, 10)

const options = ref<Record<string, any>>({
  subcategory_id: {
    categories: [],
    subcategories: [],
  },
  tags: [],
  status: [
    {
      id: 'draft',
      name: 'Draft',
      color: 'orange',
    },
    {
      id: 'published',
      name: 'Published',
      color: 'green',
    },
  ],
})

const form = ref([
  {
    name: 'title',
    model: null,
    placeholder: 'Title',
    type: 'text',
  },
  {
    name: 'slug',
    model: null,
    placeholder: 'Slug',
    type: 'text',
  },
  {
    name: 'subcategory_id',
    model: null,
    placeholder: 'Subcategory',
    type: 'cascade',
    full: true,
  },
  {
    name: 'status',
    model: 'draft',
    placeholder: 'Status',
    type: 'status-select',
  },
  {
    name: 'published_at',
    model: today,
    placeholder: 'Published at',
    type: 'date',
  },
  {
    name: 'image',
    model: null,
    placeholder: 'Image',
    type: 'image-upload',
    full: true,
  },
  {
    name: 'excerpt',
    model: null,
    placeholder: 'Excerpt',
    type: 'textarea',
    rows: 3,
    full: true,
  },
  {
    name: 'body',
    model: null,
    placeholder: 'Body',
    type: 'editor',
    full: true,
  },
  {
    name: 'tags',
    model: null,
    placeholder: 'Tags',
    type: 'tags',
    full: true,
  },
  {
    name: 'seo_divider',
    placeholder: 'SEO',
    type: 'divider',
    full: true,
  },
  {
    name: 'seo_title',
    model: null,
    placeholder: 'SEO Title',
    type: 'text',
    full: true,
  },
  {
    name: 'seo_description',
    model: null,
    placeholder: 'SEO Description',
    type: 'textarea',
    rows: 2,
    full: true,
  },
  {
    name: 'seo_keywords',
    model: null,
    placeholder: 'SEO Keywords',
    type: 'text',
    full: true,
  },
])

const schema = object({
  title: string().required().min(3),
  subcategory_id: string().required(),
  status: string().required(),
  body: string().required(),
  slug: string().nullable(),
  published_at: string().nullable(),
})

function fillOptions(data: any) {
    options.value.subcategory_id = {
        categories: data.category_options || [],
        subcategories: data.subcategory_options || [],
    }
    options.value.tags = data.tag_options || []
}

function fillForm(data: any) {
    article.value = data.article
    fillOptions(data)

    options.value.image = data.article.image ? (base_url + data.article.image) : null

    const values: Record<string, any> = {
        ...data.article,
        tags: data.tags,
        published_at: data.article.published_at
            ? data.article.published_at.substring(0, 10)
            : null,
    }

    form.value.forEach(f => {
        if (values[f.name] !== undefined) f.model = values[f.name]
    })

    article_user.value = values
    loaded.value = true
}

function onSubmitResult(data: any) {
    if (is_create.value) {
        // After create the backend returns the article directly — go to edit page
        router.push({ name: 'article', params: { id: data.id } })
    } else {
        fillForm(data)
    }
}

// Load data
if (is_create.value) {
    axios.get('articles/options').then(res => {
        fillOptions(res.data.data)
        loaded.value = true
    })
} else {
    axios.get(`article/${route.params.id}`).then(res => fillForm(res.data.data))
}

// When navigating create → edit, Vue Router reuses the component and setup() does not re-run — reload data manually
watch(() => route.params.id, (newId, oldId) => {
    if (!newId || newId === oldId) return
    loaded.value = false
    article.value = null
    article_user.value = {}
    active_tab.value = route.query.tab === 'comments' ? 'Comments' : 'Edit'
    axios.get(`article/${newId}`).then(res => fillForm(res.data.data))
})

// When navigating to same article (e.g. from notification), only query changes — switch tab accordingly
watch(() => route.query.tab, (tab) => {
    if (tab === 'comments') active_tab.value = 'Comments'
})

const article_url = computed(() => {
    const a = article.value
    if (!a?.slug || !a?.subcategory?.slug || !a?.subcategory?.category?.slug) return null
    return `${site_url}/${a.subcategory.category.slug}/${a.subcategory.slug}/${a.slug}`
})

// ── Refresh from RSS source ───────────────────────────────────────────────────

const toast = useToast()
const is_refreshing = ref(false)

function refreshFromSource() {
    if (is_refreshing.value) return
    is_refreshing.value = true

    axios.post(`article/refresh/${route.params.id}`)
        .then(() => axios.get(`article/${route.params.id}`))
        .then((res) => {
            fillForm(res.data.data)
            toast.success('Article refreshed from source')
        })
        .catch((e) => {
            toast.error(e.response?.data?.errors?.message ?? 'Failed to refresh article')
        })
        .finally(() => {
            is_refreshing.value = false
        })
}
</script>

<template>
    <AdminLayout>
        <div v-if="loaded">
            <div class="flex items-center justify-between mb-4">
                <PageBreadcrumb
                    :pageTitle="is_create ? 'Add Article' : 'Edit Article'"
                    :breadcrumb="breadcrumb"
                />
                <div class="flex items-center gap-2">
                    <button
                        v-if="article?.source_type === 'rss' && auth.accesses('articles', 'edit')"
                        @click="refreshFromSource"
                        :disabled="is_refreshing"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-600 hover:border-brand-400 hover:text-brand-600 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-brand-500 dark:hover:text-brand-400 transition-colors"
                    >
                        <RefreshIcon :class="['w-4 h-4', is_refreshing ? 'animate-spin' : '']" />
                        Refresh from source
                    </button>
                    <a
                        v-if="article_url"
                        :href="article_url"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-600 hover:border-brand-400 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-brand-500 dark:hover:text-brand-400 transition-colors"
                    >
                        <ExternalLinkOutlineIcon class="w-4 h-4" />
                        View article
                    </a>
                </div>
            </div>

            <!-- Imported article source -->
            <div
                v-if="article?.source_name"
                class="mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-800 dark:bg-white/[0.03]"
            >
                <span class="text-gray-500 dark:text-gray-400">Imported from:</span>
                <a
                    :href="article.source_url"
                    target="_blank"
                    rel="noopener"
                    class="font-medium text-brand-500 hover:underline"
                >
                    {{ article.source_name }}
                </a>
                <a
                    v-if="article.source_url"
                    :href="article.source_url"
                    target="_blank"
                    rel="noopener"
                    class="max-w-[480px] truncate text-xs text-gray-400 hover:text-brand-500"
                >
                    {{ article.source_url }}
                </a>
            </div>

            <!-- Create mode: no tabs, just the form -->
            <EditPage
                v-if="is_create"
                route="article/create"
                title="New Article"
                form_btn="Create"
                route_back="articles"
                :form="form"
                :options="options"
                :schema="schema"
                :access="auth.accesses('articles', 'edit')"
                @updateForm="onSubmitResult"
            />

            <!-- Edit mode: tabs -->
            <BaseTabs
                v-else
                :tabs="['Edit', 'Comments']"
                :current="active_tab"
                @updateTab="active_tab = $event"
            >
                <EditPage
                    v-if="active_tab === 'Edit'"
                    :route="`article/edit/${route.params.id}`"
                    title="Article"
                    form_btn="Save"
                    route_back="articles"
                    :form="form"
                    :user="article_user"
                    :options="options"
                    :schema="schema"
                    :access="auth.accesses('articles', 'edit')"
                    @updateForm="onSubmitResult"
                />
                <ArticleComments
                    v-else-if="active_tab === 'Comments'"
                    :article_id="Number(route.params.id)"
                    :active="active_tab === 'Comments'"
                />
            </BaseTabs>
        </div>
        <BaseLoading v-else />
    </AdminLayout>
</template>
