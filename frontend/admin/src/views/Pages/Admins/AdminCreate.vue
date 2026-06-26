<script setup lang="ts">
import {ref} from "vue";
import EditPage from "@/views/Core/EditPage.vue";
import {object, string} from 'yup';
import axios from "@/plugins/axios.ts";
import router from "@/routes/router.ts";
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from "@/components/common/PageBreadcrumb.vue";

const currentPageTitle = ref("Create Admin");
const breadcrumb = ref([
  {
    title: 'Admins',
    route: 'admins'
  }
])


const form = ref([
  {
    name: 'name',
    model: null,
    placeholder: 'Name',
    type: 'text',
  },
  {
    name: 'email',
    model: null,
    placeholder: 'Email',
    type: 'text'
  },
  {
    name: 'rule_id',
    model: 1,
    placeholder: 'Rules',
    type: 'select',
  },
  {
    name: 'img',
    model: null,
    placeholder: 'Img',
    type: 'file',
  },
  {
    name: 'password',
    model: null,
    placeholder: 'Password',
    type: 'password',
  }
]);


const schema = object({
  email: string().required().email(),
  password: string().min(8).required(),
  name: string().required().min(4),
});

const options= ref({
  rule_id: null,
  status: [
    {
      id: 0,
      name: 'No active',
    },
    {
      id: 1,
      name: 'Active',
    }
  ]
});

axios.get('admins/rules').then((response) => {
  let data= response.data.data;

  options.value.rule_id= data;
});

function fillForm(data: any) {
  router.push({name: 'admin', params: {id: data.id}});
}


</script>

<template>
  <admin-layout>
    <PageBreadcrumb :pageTitle="currentPageTitle" :breadcrumb="breadcrumb"/>
    <EditPage
        route="admins/create"
        title="Create"
        form_btn="Create"
        :form="form"
        route_back="admins"
        :options="options"
        :schema="schema"
        @updateForm="fillForm"
    >
    </EditPage>
  </admin-layout>
</template>
