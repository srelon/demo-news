<script setup lang="ts">
import EditOutlineIcon from '@/icons/EditOutlineIcon.vue'
import TrashOutlineIcon from '@/icons/TrashOutlineIcon.vue'
import { ref, reactive } from 'vue'
import { useForm } from 'vee-validate'
import { object, string } from 'yup'
import axios from '@/plugins/axios.ts'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import BaseInput from '@/components/ui/base/BaseInput.vue'
import BaseBtn from '@/components/ui/base/BaseBtn.vue'
import BaseModal from '@/components/ui/base/BaseModal.vue'
import BaseTablePagination from '@/components/ui/base/BaseTablePagination.vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'

const auth = useAuthStore()
const toast = useToast()

// ── Types ─────────────────────────────────────────────────────────────────────

interface Tag {
    id: number
    name: string
    slug: string
    articles_count: number
}

// ── State ─────────────────────────────────────────────────────────────────────

const table_ref = ref<InstanceType<typeof BaseTablePagination> | null>(null)
const edit_id = ref<number | null>(null)
const delete_tag = ref<Tag | null>(null)
const tag_form = ref({ name: '', slug: '' })

// ── Table headers ─────────────────────────────────────────────────────────────

const headers = reactive([
    {
        key: 'id',
        text: 'ID',
    },
    {
        key: 'name',
        text: 'Name',
    },
    {
        key: 'slug',
        text: 'Slug',
    },
    {
        key: 'articles_count',
        text: 'Articles',
    },
    ...(auth.accesses('tags', 'edit') ? [{
        key: 'action',
        text: 'Action',
    }] : []),
])

function row_class(data: Tag) {
    if (edit_id.value === data.id) return 'bg-brand-50 dark:bg-brand-500/5'
    return 'hover:bg-gray-50 dark:hover:bg-white/[0.02]'
}

// ── Validation ────────────────────────────────────────────────────────────────

const schema = object({
    name: string().required().min(2),
    slug: string().nullable(),
})

const { handleSubmit, resetForm, setErrors } = useForm({ validationSchema: schema })

// ── Add / Edit ────────────────────────────────────────────────────────────────

function startEdit(tag: Tag) {
    edit_id.value = tag.id
    tag_form.value = { name: tag.name, slug: tag.slug }
    resetForm({ values: { name: tag.name, slug: tag.slug } })
}

function cancelEdit() {
    edit_id.value = null
    tag_form.value = { name: '', slug: '' }
    resetForm()
}

const saveTag = handleSubmit(() => {
    const payload = Object.fromEntries(
        Object.entries(tag_form.value).filter(([, v]) => v !== null && v !== '')
    )

    const req = edit_id.value !== null
        ? axios.post(`tag/edit/${edit_id.value}`, payload)
        : axios.post('tag/create', payload)

    req
        .then(() => {
            toast.success('Saved successfully')
            cancelEdit()
            table_ref.value?.reload()
        })
        .catch((e) => {
            if (e.response?.data?.errors) setErrors(e.response.data.errors)
        })
})

// ── Delete ────────────────────────────────────────────────────────────────────

function deleteTag() {
    if (!delete_tag.value) return
    const tag = delete_tag.value

    axios.post(`tag/delete/${tag.id}`)
        .then(() => {
            delete_tag.value = null
            table_ref.value?.reload()
            toast.success(`Tag deleted: ${tag.name}`)
        })
}
</script>

<template>
    <AdminLayout>
        <PageBreadcrumb pageTitle="Tags" />

        <BaseTablePagination
            ref="table_ref"
            route="tags"
            :headers="headers"
            :row_class="row_class"
        >
            <!-- Add row -->
            <template #prepend>
                <tr v-if="auth.accesses('tags', 'edit') && edit_id === null" class="bg-gray-50 dark:bg-white/[0.02]">
                    <td class="px-4 py-2 border border-gray-100 dark:border-white/[0.05] text-sm text-gray-400">#</td>
                    <td class="px-4 py-2 border border-gray-100 dark:border-white/[0.05] align-top" @keydown.enter.prevent="saveTag">
                        <BaseInput name="name" type="text" placeholder="Name" v-model="tag_form.name" />
                    </td>
                    <td class="px-4 py-2 border border-gray-100 dark:border-white/[0.05] align-top" @keydown.enter.prevent="saveTag">
                        <BaseInput name="slug" type="text" placeholder="Slug (auto)" v-model="tag_form.slug" />
                    </td>
                    <td class="px-4 py-2 border border-gray-100 dark:border-white/[0.05]"></td>
                    <td class="px-4 py-2 border border-gray-100 dark:border-white/[0.05]">
                        <div class="flex items-center gap-2 mt-3">
                            <BaseBtn color="success" size="sm" @click="saveTag">Add</BaseBtn>
                        </div>
                    </td>
                </tr>
            </template>

            <!-- ID cell -->
            <template #id="{ data }">
                <span class="text-sm text-gray-400">{{ data.id }}</span>
            </template>

            <!-- Name cell (view / edit) -->
            <template #name="{ data }">
                <div v-if="edit_id === data.id" @keydown.enter.prevent="saveTag">
                    <BaseInput name="name" type="text" placeholder="Name" v-model="tag_form.name" />
                </div>
                <span v-else class="text-sm text-gray-700 dark:text-gray-300">{{ data.name }}</span>
            </template>

            <!-- Slug cell (view / edit) -->
            <template #slug="{ data }">
                <div v-if="edit_id === data.id" @keydown.enter.prevent="saveTag">
                    <BaseInput name="slug" type="text" placeholder="Slug (auto)" v-model="tag_form.slug" />
                </div>
                <span v-else class="text-sm text-gray-500 dark:text-gray-400">{{ data.slug }}</span>
            </template>

            <!-- Articles count -->
            <template #articles_count="{ data }">
                <span class="text-sm text-gray-700 dark:text-gray-400">{{ data.articles_count }}</span>
            </template>

            <!-- Action cell -->
            <template #action="{ data }">
                <div v-if="edit_id === data.id" class="flex items-center gap-2 mt-3">
                    <BaseBtn color="success" size="sm" @click="saveTag">Save</BaseBtn>
                    <BaseBtn color="secondary" size="sm" @click="cancelEdit">Cancel</BaseBtn>
                </div>
                <div v-else class="flex items-center gap-3">
                    <button @click="startEdit(data)" class="text-gray-400 hover:text-gray-700 dark:hover:text-white/80" title="Edit">
                        <EditOutlineIcon class="w-4 h-4" />
                    </button>
                    <button @click="delete_tag = data" class="text-gray-400 hover:text-error-500 dark:hover:text-error-400" title="Delete">
                        <TrashOutlineIcon class="w-4 h-4" />
                    </button>
                </div>
            </template>
        </BaseTablePagination>

        <!-- Delete modal -->
        <BaseModal v-if="delete_tag" @close="delete_tag = null">
            <template #body>
                <div class="no-scrollbar relative w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-6 dark:bg-gray-900 mx-5">
                    <button
                        @click="delete_tag = null"
                        class="absolute right-5 top-5 flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-white/[0.07]"
                    >✕</button>

                    <h4 class="mb-2 text-xl font-semibold text-gray-800 dark:text-white/90">Delete Tag</h4>

                    <div class="mb-5 space-y-1 text-sm text-gray-500 dark:text-gray-400">
                        <p><span class="font-medium text-gray-700 dark:text-gray-300">Name:</span> {{ delete_tag.name }}</p>
                        <p><span class="font-medium text-gray-700 dark:text-gray-300">Slug:</span> {{ delete_tag.slug }}</p>
                        <p><span class="font-medium text-gray-700 dark:text-gray-300">Articles:</span> {{ delete_tag.articles_count }}</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <BaseBtn color="secondary" @click="delete_tag = null">Cancel</BaseBtn>
                        <BaseBtn color="error" @click="deleteTag">Delete</BaseBtn>
                    </div>
                </div>
            </template>
        </BaseModal>
    </AdminLayout>
</template>
