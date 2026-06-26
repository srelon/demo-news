<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import EditPage from '@/views/Core/EditPage.vue'
import { object, string } from 'yup'

const router = useRouter()
const currentPageTitle = ref('Add Tag')
const breadcrumb = ref([
  {
    title: 'Tags',
    route: 'tags',
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
])

const schema = object({
  name: string().required().min(2),
  slug: string().nullable(),
})

function onCreated(data: any) {
  router.push({ name: 'tag', params: { id: data.id } })
}
</script>

<template>
  <AdminLayout>
    <div>
      <PageBreadcrumb :pageTitle="currentPageTitle" :breadcrumb="breadcrumb" />
      <EditPage
        route="tag/create"
        title="New Tag"
        form_btn="Create"
        route_back="tags"
        :form="form"
        :schema="schema"
        @updateForm="onCreated"
      />
    </div>
  </AdminLayout>
</template>
