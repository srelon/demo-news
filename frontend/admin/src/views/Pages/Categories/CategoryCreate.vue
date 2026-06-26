<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import EditPage from '@/views/Core/EditPage.vue'
import { object, string, number } from 'yup'

const router = useRouter()
const currentPageTitle = ref('Add Category')
const breadcrumb = ref([
  {
    title: 'Categories',
    route: 'categories',
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
    name: 'slug',
    model: null,
    placeholder: 'Slug',
    type: 'text',
  },
  {
    name: 'color',
    model: '#333333',
    placeholder: 'Color',
    type: 'color',
  },
  {
    name: 'order',
    model: 0,
    placeholder: 'Order',
    type: 'text',
  },
])

const schema = object({
  name: string().required().min(2),
  slug: string().nullable(),
  color: string().nullable(),
  order: number().nullable(),
})

function onCreated(data: any) {
  router.push({ name: 'category', params: { id: data.id } })
}
</script>

<template>
  <AdminLayout>
    <div>
      <PageBreadcrumb :pageTitle="currentPageTitle" :breadcrumb="breadcrumb" />
      <EditPage
        route="categories/create"
        title="New Category"
        form_btn="Create"
        route_back="categories"
        :form="form"
        :schema="schema"
        @updateForm="onCreated"
      />
    </div>
  </AdminLayout>
</template>
