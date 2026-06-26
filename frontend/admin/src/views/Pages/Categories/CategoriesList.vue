<script setup lang="ts">
import DragDotsIcon from '@/icons/DragDotsIcon.vue'
import EditOutlineIcon from '@/icons/EditOutlineIcon.vue'
import TrashOutlineIcon from '@/icons/TrashOutlineIcon.vue'
import { ref, computed, nextTick } from 'vue'
import { useForm } from 'vee-validate'
import { object, string } from 'yup'
import axios from '@/plugins/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import BaseInput from '@/components/ui/base/BaseInput.vue'
import BaseTable from '@/components/ui/base/BaseTable.vue'
import BaseModal from '@/components/ui/base/BaseModal.vue'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import SubcategoriesModal from './SubcategoriesModal.vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'

const auth = useAuthStore()
const toast = useToast()

// ── Categories list ───────────────────────────────────────────────────────────

const categories = ref<any[]>([])
const is_loading = ref(true)

axios.get('categories/all').then(res => {
    categories.value = res.data.data
    is_loading.value = false
})

// ── Table headers ─────────────────────────────────────────────────────────────

const headers = computed(() => [
    {
        key: '_drag',
        text: '',
        class: 'w-6',
    },
    {
        key: 'color',
        text: 'Color',
        class: 'w-10',
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
        key: 'subcategories_count',
        text: 'Subcategories',
        class: 'w-28',
    },
    ...(auth.accesses('categories', 'edit') ? [{
        key: 'actions',
        text: '',
        class: 'w-24',
    }] : []),
])

function row_class(data: any) {
    const index = categories.value.findIndex(c => c.id === data.id)
    const is_drag_over = drag_over.value === index && drag_from.value !== index
    return [
        'border-b border-gray-100 dark:border-gray-800 transition-colors',
        is_drag_over ? 'bg-brand-50 dark:bg-brand-500/10' : '',
    ].join(' ')
}

function row_attrs(_: any, index: number) {
    if (cat_edit_id.value !== null) return {}
    return {
        draggable: true,
        onDragstart: () => onDragStart(index),
        onDragover: (e: Event) => { e.preventDefault(); onDragOver(index) },
        onDrop: () => onDrop(index),
        onDragend: onDragEnd,
    }
}

// ── Category form (add / edit) ────────────────────────────────────────────────

const schema = object({
    name: string().required('Required').min(2),
    slug: string().nullable(),
})

const { setErrors, resetForm, handleSubmit } = useForm({
    validationSchema: schema,
    initialValues: { name: '', slug: '' },
})

const cat_form = ref({ name: '', slug: '', color: '#333333' })
const cat_edit_id = ref<number | null>(null)
const cat_loading = ref(false)

function startEditCat(cat: any) {
    cat_form.value = { name: cat.name, slug: cat.slug ?? '', color: cat.color ?? '#333333' }
    cat_edit_id.value = cat.id
    nextTick(() => resetForm({ values: { name: cat.name, slug: cat.slug ?? '' } }))
}

function cancelEditCat() {
    cat_edit_id.value = null
    cat_form.value = { name: '', slug: '', color: '#333333' }
    resetForm({ values: { name: '', slug: '' } })
}

const saveCat = handleSubmit(() => {
    if (cat_loading.value) return
    cat_loading.value = true

    const payload: Record<string, any> = { name: cat_form.value.name, color: cat_form.value.color || '#333333' }
    if (cat_form.value.slug) payload.slug = cat_form.value.slug

    const req = cat_edit_id.value
        ? axios.post(`categories/edit/${cat_edit_id.value}`, payload)
        : axios.post('categories/create', payload)

    req.then(res => {
        const saved = res.data.data
        if (cat_edit_id.value) {
            const idx = categories.value.findIndex(c => c.id === cat_edit_id.value)
            if (idx !== -1) categories.value[idx] = saved
        } else {
            categories.value.push(saved)
        }
        toast.success('Saved successfully')
        cancelEditCat()
    }).catch(err => {
        if (err.response?.data?.errors) setErrors(err.response.data.errors)
    }).finally(() => { cat_loading.value = false })
})

// ── Delete ────────────────────────────────────────────────────────────────────

const delete_cat = ref<any>(null)

function deleteCat() {
    if (!delete_cat.value) return
    const cat = delete_cat.value
    delete_cat.value = null
    axios.post(`categories/delete/${cat.id}`).then(() => {
        categories.value = categories.value.filter(c => c.id !== cat.id)
        toast.success(`Category deleted: ${cat.name}`)
    })
}

// ── Drag-and-drop reorder ─────────────────────────────────────────────────────

const drag_from = ref<number | null>(null)
const drag_over = ref<number | null>(null)

function onDragStart(index: number) { drag_from.value = index }
function onDragOver(index: number) { drag_over.value = index }
function onDragEnd() { drag_from.value = null; drag_over.value = null }

function onDrop(index: number) {
    if (drag_from.value === null || drag_from.value === index) {
        drag_from.value = null; drag_over.value = null; return
    }
    const items = [...categories.value]
    const [moved] = items.splice(drag_from.value, 1)
    items.splice(index, 0, moved)
    categories.value = items
    drag_from.value = null; drag_over.value = null
    axios.post('categories/reorder', { items: items.map((c, i) => ({ id: c.id, order: i })) })
}

// ── Subcategories modal ───────────────────────────────────────────────────────

const subs_modal_cat = ref<any>(null)
</script>

<template>
    <AdminLayout>
        <div>
            <PageBreadcrumb pageTitle="Categories" />

            <BaseLoading v-if="is_loading" />

            <div v-else class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                    <h2 class="text-lg font-medium text-gray-800 dark:text-white">Categories</h2>
                </div>

                <div class="p-4 sm:p-6">
                    <BaseTable
                        :headers="headers"
                        :table="categories"
                        :row_class="row_class"
                        :row_attrs="row_attrs"
                    >
                        <!-- Add new row -->
                        <template #prepend>
                            <tr v-if="auth.accesses('categories', 'edit') && cat_edit_id === null">
                                <td class="align-top pt-4"></td>
                                <td class="align-top pt-4 pr-2">
                                    <input
                                        type="color"
                                        v-model="cat_form.color"
                                        class="h-10 w-10 cursor-pointer rounded border border-gray-300 p-0.5 dark:border-gray-700"
                                    />
                                </td>
                                <td class="align-top pt-4 pr-3" @keydown.enter.prevent="saveCat">
                                    <BaseInput name="name" type="text" placeholder="Name" v-model="cat_form.name" />
                                </td>
                                <td class="align-top pt-4 pr-3" @keydown.enter.prevent="saveCat">
                                    <BaseInput name="slug" type="text" placeholder="Slug (auto)" v-model="cat_form.slug" />
                                </td>
                                <td></td>
                                <td class="align-top pt-4">
                                    <button
                                        @click="saveCat"
                                        :disabled="cat_loading"
                                        class="text-xs font-medium text-white bg-brand-500 hover:bg-brand-600 disabled:opacity-50 rounded px-3 py-2"
                                    >
                                        Add
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <!-- Drag handle -->
                        <template #_drag>
                            <DragDotsIcon v-if="cat_edit_id === null" class="w-4 h-4 cursor-grab text-gray-400" />
                        </template>

                        <!-- Color -->
                        <template #color="{ data }">
                            <template v-if="cat_edit_id === data.id">
                                <input
                                    type="color"
                                    v-model="cat_form.color"
                                    class="h-10 w-10 cursor-pointer rounded border border-gray-300 p-0.5 dark:border-gray-700"
                                />
                            </template>
                            <span
                                v-else
                                class="inline-block w-6 h-6 rounded-full border border-gray-200"
                                :style="{ backgroundColor: data.color }"
                            ></span>
                        </template>

                        <!-- Name -->
                        <template #name="{ data }">
                            <div v-if="cat_edit_id === data.id" @keydown.enter.prevent="saveCat">
                                <BaseInput name="name" type="text" placeholder="Name" v-model="cat_form.name" />
                            </div>
                            <span v-else class="font-medium text-gray-800 dark:text-white">{{ data.name }}</span>
                        </template>

                        <!-- Slug -->
                        <template #slug="{ data }">
                            <div v-if="cat_edit_id === data.id" @keydown.enter.prevent="saveCat">
                                <BaseInput name="slug" type="text" placeholder="Slug (auto)" v-model="cat_form.slug" />
                            </div>
                            <span v-else class="text-gray-500 font-mono text-xs">{{ data.slug }}</span>
                        </template>

                        <!-- Subcategories count -->
                        <template #subcategories_count="{ data }">
                            <button
                                v-if="cat_edit_id !== data.id"
                                @click.stop="subs_modal_cat = data"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-2.5 py-1 text-xs text-gray-600 hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:text-gray-400 dark:hover:border-brand-700 dark:hover:text-brand-400 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h8M4 18h8" />
                                </svg>
                                {{ data.subcategories_count ?? 0 }}
                            </button>
                        </template>

                        <!-- Actions -->
                        <template #actions="{ data }">
                            <div v-if="cat_edit_id === data.id" class="flex gap-2 mt-3">
                                <button @click="saveCat" :disabled="cat_loading" class="text-xs font-medium text-brand-500 hover:text-brand-600 disabled:opacity-50">Save</button>
                                <button @click="cancelEditCat" class="text-xs text-gray-400 hover:text-gray-600">Cancel</button>
                            </div>
                            <div v-else class="flex gap-3">
                                <button @click.stop="startEditCat(data)" class="text-gray-400 hover:text-gray-700 dark:hover:text-white">
                                    <EditOutlineIcon class="w-4 h-4" />
                                </button>
                                <button @click.stop="delete_cat = data" class="text-gray-400 hover:text-red-500">
                                    <TrashOutlineIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </template>
                    </BaseTable>
                </div>
            </div>
        </div>

        <!-- Delete category confirmation -->
        <BaseModal v-if="delete_cat" @close="delete_cat = null">
            <template #body>
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 dark:bg-gray-900">
                    <div class="flex flex-col items-center text-center">
                        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/10">
                            <TrashOutlineIcon class="h-7 w-7 text-red-500" />
                        </div>
                        <h3 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white">Delete category</h3>
                        <div class="mb-4 w-full rounded-lg bg-gray-50 px-4 py-3 text-left dark:bg-gray-800">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Name</span>
                                <span class="font-medium text-gray-800 dark:text-white">{{ delete_cat.name }}</span>
                            </div>
                            <div v-if="delete_cat.subcategories_count > 0" class="mt-1.5 flex items-center justify-between text-sm">
                                <span class="text-gray-500">Subcategories</span>
                                <span class="font-medium text-red-500">{{ delete_cat.subcategories_count }} with all articles will be deleted</span>
                            </div>
                        </div>
                        <p class="mb-6 text-sm text-gray-500">This action cannot be undone.</p>
                        <div class="flex w-full gap-3">
                            <button @click="delete_cat = null" class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Cancel</button>
                            <button @click="deleteCat" class="flex-1 rounded-lg bg-red-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-600">Delete</button>
                        </div>
                    </div>
                </div>
            </template>
        </BaseModal>

        <!-- Subcategories modal -->
        <SubcategoriesModal
            v-if="subs_modal_cat"
            :category_id="subs_modal_cat.id"
            :category_name="subs_modal_cat.name"
            :category_slug="subs_modal_cat.slug"
            @close="subs_modal_cat = null"
        />
    </AdminLayout>
</template>
