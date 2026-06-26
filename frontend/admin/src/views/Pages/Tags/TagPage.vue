<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/plugins/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import EditPage from '@/views/Core/EditPage.vue'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import { object, string } from 'yup'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const currentPageTitle = ref('Edit Tag')
const breadcrumb = ref([
  {
    title: 'Tags',
    route: 'tags',
  },
])

const tag = ref<any>(null)

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

function fillForm(data: any) {
  tag.value = data
  form.value.forEach(f => {
    if (data[f.name] !== undefined) f.model = data[f.name]
  })
}

axios.get(`tag/${route.params.id}`).then(res => fillForm(res.data.data))
</script>

<template>
  <AdminLayout>
    <div v-if="tag">
      <PageBreadcrumb :pageTitle="currentPageTitle" :breadcrumb="breadcrumb" />
      <EditPage
        :route="`tag/edit/${route.params.id}`"
        title="Tag"
        form_btn="Save"
        route_back="tags"
        :form="form"
        :schema="schema"
        :access="auth.accesses('tags', 'edit')"
        @updateForm="fillForm"
      />
    </div>
    <BaseLoading v-else />
  </AdminLayout>
</template>
