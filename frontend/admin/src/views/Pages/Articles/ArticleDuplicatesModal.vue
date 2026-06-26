<script setup lang="ts">
import TrashOutlineIcon from '@/icons/TrashOutlineIcon.vue'
import { ref } from 'vue'
import axios from '@/plugins/axios'
import BaseModal from '@/components/ui/base/BaseModal.vue'
import BaseBtn from '@/components/ui/base/BaseBtn.vue'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'

const emit = defineEmits<{
    close: []
}>()

const auth = useAuthStore()
const toast = useToast()
const baseUrl = import.meta.env.VITE_API_BASE_URL

interface DuplicateArticle {
    id: number
    title: string
    image: string | null
    status: string
    source_name: string | null
    subcategory: string | null
    category: string | null
    published_at: string | null
    views: number
    comments_count: number
}

interface DuplicateGroup {
    key: string
    articles: DuplicateArticle[]
}

const is_loading = ref(true)
const groups = ref<DuplicateGroup[]>([])
const deleting = ref<Record<number, boolean>>({})
const confirm_clean = ref(false)
const is_cleaning = ref(false)

function loadGroups() {
    is_loading.value = true
    confirm_clean.value = false

    axios.get('articles/duplicates')
        .then((res) => {
            groups.value = res.data.data
        })
        .finally(() => {
            is_loading.value = false
        })
}

loadGroups()

function deleteArticle(article: DuplicateArticle) {
    if (deleting.value[article.id]) return
    deleting.value[article.id] = true

    axios.post(`article/delete/${article.id}`)
        .then(() => {
            toast.success(`Article deleted: ${article.title}`)
            loadGroups()
        })
        .catch((e) => {
            toast.error(e.response?.data?.errors?.message ?? 'Failed to delete article')
        })
        .finally(() => {
            deleting.value[article.id] = false
        })
}

function cleanAll() {
    if (!confirm_clean.value) {
        confirm_clean.value = true
        return
    }

    is_cleaning.value = true

    axios.post('articles/duplicates/clean')
        .then((res) => {
            toast.success(`Deleted ${res.data.data.deleted} duplicates`)
            loadGroups()
        })
        .catch(() => {
            toast.error('Cleanup failed')
        })
        .finally(() => {
            is_cleaning.value = false
        })
}

function formatDate(value: string | null): string {
    if (!value) return '—'
    return new Date(value).toLocaleString()
}
</script>

<template>
    <BaseModal @close="emit('close')">
        <template #body>
            <div class="no-scrollbar relative max-h-[85vh] w-full max-w-4xl overflow-y-auto rounded-3xl bg-white p-6 dark:bg-gray-900 mx-4">
                <button
                    @click="emit('close')"
                    class="absolute right-5 top-5 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-white/[0.07]"
                >✕</button>

                <div class="mb-5 flex flex-wrap items-center justify-between gap-3 pr-12">
                    <h4 class="text-xl font-semibold text-gray-800 dark:text-white/90">
                        Duplicate Articles
                        <span v-if="!is_loading" class="ml-2 text-sm font-normal text-gray-400">{{ groups.length }} groups</span>
                    </h4>

                    <BaseBtn
                        v-if="groups.length && auth.accesses('articles', 'edit')"
                        :color="confirm_clean ? 'error' : 'warning'"
                        add_class="shrink-0 whitespace-nowrap"
                        :disabled="is_cleaning"
                        @click="cleanAll"
                    >
                        {{ confirm_clean ? 'Confirm — delete all duplicates' : 'Auto-clean (keep oldest)' }}
                    </BaseBtn>
                </div>

                <BaseLoading v-if="is_loading" />

                <p v-else-if="!groups.length" class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                    No duplicates found 🎉
                </p>

                <div v-else class="space-y-4">
                    <div
                        v-for="group in groups"
                        :key="group.key"
                        class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800"
                    >
                        <div class="truncate border-b border-gray-100 bg-gray-50 px-4 py-2 text-xs text-gray-400 dark:border-white/[0.05] dark:bg-white/[0.02]">
                            {{ group.key }}
                        </div>

                        <div
                            v-for="(article, index) in group.articles"
                            :key="article.id"
                            :class="[
                                'flex items-center gap-3 px-4 py-3',
                                index > 0 ? 'border-t border-gray-100 dark:border-white/[0.05]' : '',
                            ]"
                        >
                            <div class="h-9 w-12 shrink-0 overflow-hidden rounded bg-gray-100">
                                <img v-if="article.image" :src="baseUrl + article.image" :alt="article.title" class="h-full w-full object-cover" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <router-link
                                    :to="{ name: 'article', params: { id: article.id } }"
                                    class="block truncate text-sm font-medium text-gray-800 hover:text-brand-500 dark:text-white/90"
                                >
                                    #{{ article.id }} — {{ article.title }}
                                </router-link>
                                <div class="text-xs text-gray-400">
                                    {{ article.category }} / {{ article.subcategory }}
                                    · {{ formatDate(article.published_at) }}
                                    · {{ article.views }} views
                                    · {{ article.comments_count }} comments
                                </div>
                            </div>

                            <span
                                v-if="index === 0"
                                class="shrink-0 rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400"
                            >
                                oldest
                            </span>

                            <button
                                v-if="auth.accesses('articles', 'edit')"
                                @click="deleteArticle(article)"
                                :disabled="deleting[article.id]"
                                class="shrink-0 text-gray-400 hover:text-error-500 disabled:opacity-50 dark:hover:text-error-400"
                                title="Delete"
                            >
                                <TrashOutlineIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </BaseModal>
</template>
