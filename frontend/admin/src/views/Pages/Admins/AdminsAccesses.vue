<script setup lang="ts">
import {ref, computed} from 'vue'
import {Form, useForm} from 'vee-validate'

const {setErrors} = useForm();
import {object, string} from 'yup';

import {useRoute, useRouter} from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from "@/components/common/PageBreadcrumb.vue";
import BaseBtn from "@/components/ui/base/BaseBtn.vue";
import BaseInput from "@/components/ui/base/BaseInput.vue";
import EditPage from "@/views/Core/EditPage.vue";
import axios from "@/plugins/axios.ts";
import BaseTable from '@/components/ui/base/BaseTable.vue'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import {useAuthStore} from "@/stores/auth.ts";
import {useToast} from "vue-toastification";

const toast = useToast();
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const is_create = computed(() => !route.params.id)

const currentPageTitle = computed(() => is_create.value ? 'Create Role' : 'Edit Rule')
const loading = ref(false);
const breadcrumb = ref([
  {
    title: 'Rules',
    route: 'admins.rules',
  },
])


const form = ref([
  {
    name: 'key',
    model: null,
    placeholder: 'Key',
    type: 'text',
  },
  {
    name: 'descriptions',
    model: null,
    placeholder: 'Descriptions',
    type: 'text',
  },
]);

interface intAccessItem {
  id: number
  edit: boolean
  view: boolean
  key: string
  descriptions: string
}

interface intRule {
  name: string,
  accesses: Record<string, intAccessItem>
  accesses_id: Record<string, { edit: boolean; view: boolean }>
}

const rule = ref<intRule>({
  name: '',
  accesses: {},
  accesses_id: {},
})

const ready = ref(false)

const schemaCreate = object({
  key: string().required(),
  descriptions: string().required(),
});

const schemaEdit = computed(() => {
  let accessesShape: Record<string, any> = {};

  Object.keys(rule.value.accesses || {}).forEach((key) => {
    accessesShape[key] = object().shape({
      descriptions: string().required(),
    });
  });

  return object().shape({
    name: string().required(),
    accesses: object().shape(accessesShape),
  });
});

function fillForm(data: any) {
  rule.value.accesses[data.key] = {
    id: data.id,
    edit: false,
    view: false,
    key: data.key,
    descriptions: data.descriptions,
  };

  formKey.value++;
}

interface AccessItem {
  id: number
  key: string
  descriptions: string
  edit: boolean
  view: boolean
}

const formKey = ref(0);

function normalizeAccesses(data: any, existing_accesses_id: Record<string, any> = {}) {
  let normalize: Record<string, AccessItem> = {};

  data.forEach((access: any) => {
    let item: AccessItem;

    if (!existing_accesses_id[access.key]) {
      item = {
        edit: false,
        view: false,
        id: access.id,
        key: access.key,
        descriptions: access.descriptions,
      };
    } else {
      item = {
        edit: existing_accesses_id[access.key]?.edit ? true : false,
        view: existing_accesses_id[access.key]?.view ? true : false,
        id: access.id,
        key: access.key,
        descriptions: access.descriptions,
      };
    }

    normalize[access.key] = item;
  });

  rule.value.accesses = normalize;
  formKey.value++;
}

if (is_create.value) {
  axios.get('admins/accesses').then((response) => {
    normalizeAccesses(response.data.data);
    ready.value = true;
  });
} else {
  axios.get('admins/accesses/info/' + route.params.id).then((response) => {
    rule.value = response.data.data.rule;
    normalizeAccesses(response.data.data.accesses, rule.value.accesses_id);
    ready.value = true;
  });
}

function onSubmit() {
  if (loading.value) return;
  loading.value = true;

  if (is_create.value) {
    axios.post('admins/rules/create', rule.value).then((response) => {
      loading.value = false;
      toast.success('Role created successfully');
      router.push({
        name: 'admins.rules.edit',
        params: {
          id: response.data.data.rule.id,
        },
      });
    }).catch(err => {
      loading.value = false;
      handleErrors(err);
    });
  } else {
    axios.post('admins/accesses/edit/' + route.params.id, rule.value).then((response) => {
      loading.value = false;
      rule.value = response.data.data.rule;
      normalizeAccesses(response.data.data.accesses, rule.value.accesses_id);

      if (auth.user && auth.user.rule_id == route.params.id) {
        auth.setAssest(rule.value.accesses_id);
      }

      toast.success('Saved successfully');
    }).catch(err => {
      loading.value = false;
      handleErrors(err);
    });
  }
}

function handleErrors(err: any) {
  if (err.response) {
    let response = err.response.data?.errors;

    if (response) {
      const formatted: Record<string, string> = {};

      Object.keys(response).forEach((field) => {
        formatted[field] = response[field][0];
      });

      setErrors(formatted);
    }
  }
}

const accesses_headers = [
  {
    key: 'view',
    text: 'View',
  },
  {
    key: 'edit',
    text: 'Edit',
  },
  {
    key: 'key',
    text: 'Key',
  },
  {
    key: 'descriptions',
    text: 'Name',
  },
]

const accesses_array = computed(() => Object.values(rule.value.accesses))

function onChange(item: any, type: string) {
  if (type == 'edit') {
    if (item.edit) {
      item.view = true;
    }
  } else {
    if (!item.view) {
      item.edit = false;
    }
  }
}
</script>

<template>
  <admin-layout>
    <PageBreadcrumb :pageTitle="currentPageTitle" :breadcrumb="breadcrumb"/>
    <div v-if="ready" class="pb-6">
      <EditPage
          v-if="!is_create"
          route="admins/accesses/create"
          title="Create Accesses"
          form_btn="Create"
          :form="form"
          :schema="schemaCreate"
          :clear_on_submit="true"
          @updateForm="fillForm"
          :access="auth.accesses('admins', 'edit')"
      >
      </EditPage>

      <Form
          :key="formKey"
          :initial-values="rule"
          :validation-schema="schemaEdit"
          @submit="onSubmit"
          class="space-y-6"
      >
        <div
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03] mt-4">

          <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h2 class="text-lg font-medium text-gray-800 dark:text-white">
              {{ is_create ? 'Create Role' : 'Edit Rule' }}
            </h2>
          </div>
          <div class="p-4 sm:p-6 dark:border-gray-800">
            <label
                for="name"
                class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
            >
              Role name
            </label>
            <div class="relative">
              <BaseInput
                  name="name"
                  type="text"
                  placeholder="Role name"
                  v-model="rule.name"
                  :disabled="!auth.accesses('admins', 'edit')"
              />
            </div>
          </div>

          <div class="flex flex-col gap-5 px-6 mb-4 sm:flex-row sm:items-center sm:justify-between">
            <div><h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Accesses</h3></div>
          </div>
          <BaseTable :headers="accesses_headers" :table="accesses_array">
            <template #view="{ data }">
              <BaseInput
                  :name="`accesses.${data.key}.view`"
                  type="checkbox"
                  v-model="data.view"
                  @update:modelValue="onChange(data, 'view')"
                  :disabled="!auth.accesses('admins', 'edit')"
              />
            </template>

            <template #edit="{ data }">
              <BaseInput
                  :name="`accesses.${data.key}.edit`"
                  type="checkbox"
                  v-model="data.edit"
                  @update:modelValue="onChange(data, 'edit')"
                  :disabled="!auth.accesses('admins', 'edit')"
              />
            </template>

            <template #key="{ data }">
              <BaseInput
                  :name="`accesses.${data.key}.key`"
                  type="text"
                  placeholder="Key"
                  v-model="data.key"
                  :disabled="true"
              />
            </template>

            <template #descriptions="{ data }">
              <BaseInput
                  :name="`accesses.${data.key}.descriptions`"
                  type="text"
                  placeholder="Descriptions"
                  v-model="data.descriptions"
                  :disabled="!auth.accesses('admins', 'edit')"
              />
            </template>
          </BaseTable>
          <div class="px-4 sm:px-3 pb-4 pt-3 sm:pb-3 dark:border-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
              <BaseBtn
                  v-if="auth.accesses('admins', 'edit')"
                  :loading="loading"
              >
                {{ is_create ? 'Create' : 'Save' }}
              </BaseBtn>
            </div>
          </div>
        </div>
      </Form>
    </div>
    <div v-else>
      <BaseLoading></BaseLoading>
    </div>
  </admin-layout>
</template>
