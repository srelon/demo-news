<script setup lang="ts">
import { Form } from 'vee-validate';
import axios from "@/plugins/axios.ts";
import {ref} from "vue";
import { useAuthStore } from '@/stores/auth.ts';
import router from "@/routes/router.ts";
import BaseInput from "@/components/ui/base/BaseInput.vue";
import FullScreenLayout from "@/components/layout/FullScreenLayout.vue";
import CommonGridShape from "@/components/common/CommonGridShape.vue";
import BaseBtn from "@/components/ui/base/BaseBtn.vue";


interface FormItem {
  name: string
  type: string
  placeholder?: string
  model?: any
  disabled?: boolean
}

interface Props {
  form: FormItem[]
  schema?: object
  title?: string | null
  description?: string | null
  route?: string
  form_btn?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  form: () => [],
  schema: () => ({}),
  title: null,
  description: null,
  route: '',
  form_btn: null
});

const auth = useAuthStore();
const base = import.meta.env.BASE_URL
const loading= ref(false);
function onSubmit(_: any, {setErrors}: any) {
  if(!loading.value) {
    loading.value= true;
    const parsedForm = Object.fromEntries(props.form.map(i => [i.name, i.model]));

    axios.post(props.route, parsedForm).then((response) => {
      loading.value= false;
      let data= response.data.data;

      auth.setUser(data.user);

      router.push({ name: 'home' });
    }).catch(err => {

      loading.value= false;
      if(err.response) {
        let response= err.response.data?.errors;

        if (response) {
          const formatted: Record<string, string> = {};

          Object.keys(response).forEach((field) => {
            formatted[field] = response[field][0]
          });

          console.log('formatted');
          console.log(formatted);

          setErrors(formatted);
        }
      }
    });
  }
}

// function onSubmit(values) {
//   $emit('onSubmit', values)
// }

</script>

<template>

  <FullScreenLayout>
    <div class="relative p-6 bg-white z-1 dark:bg-gray-900 sm:p-0">
      <div
          class="relative flex flex-col justify-center w-full h-screen lg:flex-row dark:bg-gray-900"
      >
        <div class="flex flex-col flex-1 w-full lg:w-1/2">
          <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto">
            <div>
              <div class="mb-5 sm:mb-8">
                <h1
                    class="mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md"
                >
                  {{title}}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{description}}
                </p>
              </div>
              <div>
                <Form :validation-schema="schema" @submit="onSubmit" >
                  <div class="space-y-5">
                    <!-- Email -->
                    <div v-for="(input, key) in form" :key="key">
                      <label
                          :for="input.name"
                          class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                      >
                        {{ input.name }}<span class="text-error-500">*</span>
                      </label>
                      <div class="relative">
                        <BaseInput
                            :name="input.name"
                            :type="input.type"
                            :toggleType="(input.name == 'password') ? true : false"
                            :placeholder="input.placeholder"
                            v-model="input.model"
                        />
                      </div>
                    </div>
                    <!-- Button -->
                    <div>
                      <BaseBtn
                          class="flex items-center justify-center"
                          :loading="loading"
                      >{{ form_btn }}</BaseBtn>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        <div
            class="relative items-center hidden w-full h-full lg:w-1/2 bg-brand-950 dark:bg-white/5 lg:grid"
        >
          <div class="flex items-center justify-center z-1">
            <common-grid-shape />
            <div class="flex flex-col items-center max-w-xs">
              <router-link to="/" class="block mb-4">
                <img width="{231}" height="{48}" :src="`${base}images/logo/auth-logo.svg`" alt="Logo" />
              </router-link>
              <p class="text-center text-gray-400 dark:text-white/60">
                Tailwind CSS Admin Dashboard Template
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </FullScreenLayout>


</template>

<style scoped>

</style>
