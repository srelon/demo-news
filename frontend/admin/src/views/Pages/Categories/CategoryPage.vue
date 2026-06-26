<script setup lang="ts">
import DragDotsIcon from '@/icons/DragDotsIcon.vue'
import EditOutlineIcon from '@/icons/EditOutlineIcon.vue'
import ExternalLinkOutlineIcon from '@/icons/ExternalLinkOutlineIcon.vue'
import TrashOutlineIcon from '@/icons/TrashOutlineIcon.vue'
import { ref, computed, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useForm } from 'vee-validate'
import { object, string } from 'yup'
import axios from '@/plugins/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import EditPage from '@/views/Core/EditPage.vue'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import BaseInput from '@/components/ui/base/BaseInput.vue'
import BaseTable from '@/components/ui/base/BaseTable.vue'
import BaseModal from '@/components/ui/base/BaseModal.vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'

const auth  = useAuthStore()
const route = useRoute()
const toast = useToast()

const current_page_title = ref('Edit Category')
const breadcrumb = ref([
  {
    title: 'Categories',
    route: 'categories',
  },
])
const category           = ref<any>(null)
const subcategories      = ref<any[]>([])

// ─── Category form ────────────────────────────────────────────────────────────

const form = ref([
  {
    name: 'name',
    model: null,
    placeholder: 'Name',
    type: 'text',
  },
  {
    name: 'slug',
    model: null,
    placeholder: 'Slug',
    type: 'text',
  },
  {
    name: 'color',
    model: null,
    placeholder: 'Color',
    type: 'color',
  },
])

const schema = object({
  name: string().required('Required').min(2),
  slug: string().nullable(),
  color: string().nullable(),
})

function fillForm(data: any) {
  category.value      = data
  subcategories.value = data.subcategories ?? []
  form.value.forEach(f => {
    if (data[f.name] !== undefined) f.model = data[f.name]
  })
}

axios.get(`categories/${route.params.id}`).then(res => fillForm(res.data.data))

// ─── Subcategory form ─────────────────────────────────────────────────────────

const sub_schema = object({
  name: string().required('Required').min(2, 'Min 2 characters'),
  slug: string()
    .nullable()
    .matches(/^[a-z0-9-]*$/, { message: 'Only lowercase letters, numbers and hyphens', excludeEmptyString: true }),
})

const { setErrors, resetForm, handleSubmit } = useForm({
  validationSchema: sub_schema,
  initialValues: { name: '', slug: '' },
})

const sub_form    = ref({ name: '', slug: '' })
const sub_edit_id = ref<number | null>(null)
const sub_loading = ref(false)

function startEditSub(sub: any) {
  sub_form.value    = { name: sub.name, slug: sub.slug ?? '' }
  sub_edit_id.value = sub.id
  nextTick(() => resetForm({ values: sub_form.value }))
}

function cancelEditSub() {
  sub_edit_id.value = null
  sub_form.value    = { name: '', slug: '' }
  resetForm({ values: sub_form.value })
}

const saveSub = handleSubmit(() => {
  if (sub_loading.value) return
  sub_loading.value = true

  const payload = Object.fromEntries(
    Object.entries(sub_form.value).filter(([, v]) => v !== null && v !== '')
  )

  const req = sub_edit_id.value
    ? axios.post(`subcategory/edit/${sub_edit_id.value}`, payload)
    : axios.post(`categories/${route.params.id}/subcategory/create`, payload)

  req.then(res => {
    const saved = res.data.data
    if (sub_edit_id.value) {
      const idx = subcategories.value.findIndex(s => s.id === sub_edit_id.value)
      if (idx !== -1) subcategories.value[idx] = saved
    } else {
      subcategories.value.push(saved)
    }
    toast.success('Saved successfully')
    cancelEditSub()
  }).catch(err => {
    if (err.response?.data?.errors) {
      setErrors(err.response.data.errors)
    }
  }).finally(() => { sub_loading.value = false })
})

// ─── Delete ───────────────────────────────────────────────────────────────────

const delete_sub = ref<any>(null)

function deleteSub() {
  if (!delete_sub.value) return
  const sub    = delete_sub.value
  delete_sub.value = null
  axios.post(`subcategory/delete/${sub.id}`).then(() => {
    subcategories.value = subcategories.value.filter(s => s.id !== sub.id)
    toast.success(`Subcategory deleted: ${sub.name}`)
  })
}

// ─── Drag-and-drop reorder ────────────────────────────────────────────────────

const drag_from = ref<number | null>(null)
const drag_over = ref<number | null>(null)

function onDragStart(index: number) {
  drag_from.value = index
}

function onDragOver(index: number) {
  drag_over.value = index
}

function onDrop(index: number) {
  if (drag_from.value === null || drag_from.value === index) {
    drag_from.value = null
    drag_over.value = null
    return
  }
  const items     = [...subcategories.value]
  const [moved]   = items.splice(drag_from.value, 1)
  items.splice(index, 0, moved)
  subcategories.value = items
  drag_from.value     = null
  drag_over.value     = null
  axios.post('subcategory/reorder', { items: items.map((s, i) => ({ id: s.id, order: i })) })
}

function onDragEnd() {
    drag_from.value = null
    drag_over.value = null
}

// ── Table ─────────────────────────────────────────────────────────────────────

const headers = computed(() => [
    {
        key: '_drag',
        text: '',
        class: 'w-6',
    },
    {
        key: 'name',
        text: 'Name',
    },
    {
        key: 'slug',
        text: 'Slug',
    },
    ...(auth.accesses('categories', 'edit') ? [{
        key: 'actions',
        text: '',
        class: 'w-20',
    }] : []),
])

function row_class(data: any) {
    const index = subcategories.value.findIndex((s: any) => s.id === data.id)
    const is_drag_over = drag_over.value === index && drag_from.value !== index
    return [
        'border-b border-gray-100 dark:border-gray-800 transition-colors',
        is_drag_over ? 'bg-brand-50 dark:bg-brand-500/10' : '',
    ].join(' ')
}

function row_attrs(_: any, index: number) {
    if (sub_edit_id.value !== null) return {}
    return {
        draggable: true,
        onDragstart: () => onDragStart(index),
        onDragover: (e: Event) => { e.preventDefault(); onDragOver(index) },
        onDrop: () => onDrop(index),
        onDragend: onDragEnd,
    }
}
</script>

<template>
  <AdminLayout>
    <div v-if="category">
      <PageBreadcrumb :pageTitle="current_page_title" :breadcrumb="breadcrumb" />

      <EditPage
        :route="`categories/edit/${route.params.id}`"
        title="Category"
        form_btn="Save"
        route_back="categories"
        :form="form"
        :schema="schema"
        :access="auth.accesses('categories', 'edit')"
        @updateForm="fillForm"
      />

      <!-- Subcategories -->
      <div class="mt-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
          <h2 class="text-lg font-medium text-gray-800 dark:text-white">Subcategories</h2>
        </div>

        <div class="p-4 sm:p-6">
            <BaseTable
                plain
                :headers="headers"
                :table="subcategories"
                :row_class="row_class"
                :row_attrs="row_attrs"
            >
                <template #prepend>
                    <tr v-if="auth.accesses('categories', 'edit') && sub_edit_id === null">
                        <td class="align-top pt-4"></td>
                        <td class="align-top pt-4 pr-3" @keydown.enter.prevent="saveSub">
                            <BaseInput name="name" type="text" placeholder="Name" v-model="sub_form.name" />
                        </td>
                        <td class="align-top pt-4 pr-3" @keydown.enter.prevent="saveSub">
                            <BaseInput name="slug" type="text" placeholder="Slug (auto)" v-model="sub_form.slug" />
                        </td>
                        <td class="align-top pt-4">
                            <button @click="saveSub" :disabled="sub_loading" class="text-xs font-medium text-white bg-brand-500 hover:bg-brand-600 disabled:opacity-50 rounded px-3 py-2">
                                Add
                            </button>
                        </td>
                    </tr>
                </template>

                <template #_drag>
                    <DragDotsIcon v-if="sub_edit_id === null" class="w-4 h-4 cursor-grab text-gray-400" />
                </template>

                <template #name="{ data }">
                    <div v-if="sub_edit_id === data.id" @keydown.enter.prevent="saveSub">
                        <BaseInput name="name" type="text" placeholder="Name" v-model="sub_form.name" />
                    </div>
                    <span v-else class="font-medium text-gray-800 dark:text-white">{{ data.name }}</span>
                </template>

                <template #slug="{ data }">
                    <div v-if="sub_edit_id === data.id" @keydown.enter.prevent="saveSub">
                        <BaseInput name="slug" type="text" placeholder="Slug" v-model="sub_form.slug" />
                    </div>
                    <span v-else class="text-gray-500 font-mono text-xs">{{ data.slug }}</span>
                </template>

                <template #actions="{ data }">
                    <div v-if="sub_edit_id === data.id" class="flex gap-2 mt-3">
                        <button @click="saveSub" :disabled="sub_loading" class="text-xs font-medium text-brand-500 hover:text-brand-600">Save</button>
                        <button @click="cancelEditSub" class="text-xs text-gray-400 hover:text-gray-600">Cancel</button>
                    </div>
                    <div v-else class="flex gap-3">
                        <a :href="`http://127.0.0.1:8880/${category.slug}/${data.slug}`" target="_blank" class="text-gray-400 hover:text-gray-700 dark:hover:text-white">
                            <ExternalLinkOutlineIcon class="w-4 h-4" />
                        </a>
                        <button @click="startEditSub(data)" class="text-gray-400 hover:text-gray-700 dark:hover:text-white">
                            <EditOutlineIcon class="w-4 h-4" />
                        </button>
                        <button @click="delete_sub = data" class="text-gray-400 hover:text-red-500">
                            <TrashOutlineIcon class="w-4 h-4" />
                        </button>
                    </div>
                </template>
            </BaseTable>
        </div>
      </div>

    </div>
    <BaseLoading v-else />

    <!-- Delete confirmation modal -->
    <BaseModal v-if="delete_sub !== null" @close="delete_sub = null">
      <template #body>
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 dark:bg-gray-900">
          <div class="flex flex-col items-center text-center">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/10">
              <TrashOutlineIcon class="h-7 w-7 text-red-500" />
            </div>
            <h3 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white">Delete subcategory</h3>

            <div class="mb-4 w-full rounded-lg bg-gray-50 px-4 py-3 text-left dark:bg-gray-800">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Name</span>
                <span class="font-medium text-gray-800 dark:text-white">{{ delete_sub.name }}</span>
              </div>
              <div class="mt-1.5 flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Slug</span>
                <span class="font-mono text-gray-600 dark:text-gray-300">{{ delete_sub.slug }}</span>
              </div>
              <div class="mt-1.5 flex items-center justify-between text-sm" v-if="delete_sub.articles_count > 0">
                <span class="text-gray-500 dark:text-gray-400">Articles</span>
                <span class="font-medium text-red-500">{{ delete_sub.articles_count }} will be deleted</span>
              </div>
            </div>

            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
              This action cannot be undone.
              <template v-if="delete_sub.articles_count > 0">
                All articles in this subcategory will be soft deleted.
              </template>
            </p>

            <div class="flex w-full gap-3">
              <button
                @click="delete_sub = null"
                class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
              >
                Cancel
              </button>
              <button
                @click="deleteSub"
                class="flex-1 rounded-lg bg-red-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-600"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </template>
    </BaseModal>

  </AdminLayout>
</template>
