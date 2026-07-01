<script setup lang="ts">
import {Form, Field} from 'vee-validate';
import axios from "@/plugins/axios.ts";
import {ref, reactive} from "vue";
import BaseInput from "@/components/ui/base/BaseInput.vue";
import BaseBtn from "@/components/ui/base/BaseBtn.vue";
import BaseTagSelect from "@/components/ui/base/BaseTagSelect.vue";
import BaseEditor from "@/components/ui/base/BaseEditor.vue";
import BaseCascadeSelect from "@/components/ui/base/BaseCascadeSelect.vue";
import BaseImageUpload from "@/components/ui/base/BaseImageUpload.vue";
import FlatPickr from 'vue-flatpickr-component';
import { useToast } from 'vue-toastification';

interface FormItem {
  name: string
  type: string
  placeholder?: string
  model?: any
  disabled?: boolean
  full?: boolean
  rows?: number
}

interface Props {
  form: FormItem[]
  schema?: object
  user?: object
  options?: Record<string, any>
  access?: boolean
  title?: string | null
  route_back?: string | null
  description?: string | null
  route?: string
  form_btn?: string | null
  clear_on_submit?: boolean
  single_col?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  form: () => [],
  schema: () => ({}),
  user: () => ({}),
  options: () => ({}),
  access: true,
  title: null,
  route_back: null,
  description: null,
  route: '',
  form_btn: null,
  clear_on_submit: false,
  single_col: false,
});

const emit = defineEmits(['updateForm'])

const toast = useToast();
const loading = ref(false);
const previews = reactive<Record<string, string>>({});

function onSubmit(_: any, {setErrors}: any) {
  if (!loading.value) {
    loading.value = true;
    const parsedForm = Object.fromEntries(
      props.form
        .filter(i => !(i.type === 'file' && i.model === null))
        .map(i => [
          i.name,
          (i.type === 'datetime' && i.model) ? new Date(i.model.replace(' ', 'T')).toISOString() : i.model,
        ])
    );

    axios.post(props.route, parsedForm, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    }).then((response) => {
      loading.value = false;
      let data = response.data.data;

      if (props.clear_on_submit) {
        props.form.forEach(field => { field.model = null });
      }

      toast.success('Saved successfully');
      emit('updateForm', data);
    }).catch(err => {

      loading.value = false;
      if (err.response) {
        let response = err.response.data?.errors;

        if (response) {
          const formatted: Record<string, string> = {};

          Object.keys(response).forEach((field) => {
            formatted[field] = response[field][0]
          });


          setErrors(formatted);
        }
      }
    });
  }
}

function uploadFile(e: Event, field: any) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0] || null
  field.model = file
  if (file) {
    previews[field.name] = URL.createObjectURL(file)
  }
}

</script>

<template>
  <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
      <h2 class="text-lg font-medium text-gray-800 dark:text-white">
        {{ title }}
      </h2>
    </div>
    <slot name="top_content"></slot>

    <div class="p-4 sm:p-6 dark:border-gray-800">
      <Form
          :initial-values="user"
          :validation-schema="schema"
          @submit="onSubmit"
          class="space-y-6">
        <div :class="['grid grid-cols-1 gap-5', !props.single_col && 'md:grid-cols-2']">
          <div v-for="(input, key) in form" :key="key" :class="input.full ? 'md:col-span-2' : ''"  >
            <div v-if="input.type === 'divider'" class="flex items-center gap-3 pt-2">
              <span class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 whitespace-nowrap">
                {{ input.placeholder }}
              </span>
              <div class="flex-1 border-t border-gray-200 dark:border-gray-700"></div>
            </div>
            <div v-else>
              <label
                  :for="input.name"
                  :class="(input.disabled) ? 'mb-1.5 block text-sm font-medium text-gray-400 dark:text-white/15' : 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400'"
              >
                {{ input.placeholder }}
              </label>

              <Field
                  v-if="input.type === 'image-upload'"
                  :name="input.name"
                  v-model="input.model"
                  v-slot="{ errorMessage }"
              >
                <BaseImageUpload
                    v-model="input.model"
                    :currentUrl="options[input.name] || null"
                    :disabled="input.disabled || !access"
                />
                <p v-if="errorMessage" class="text-red-500 text-xs mt-1">{{ errorMessage }}</p>
              </Field>

              <Field
                  :name="input.name"
                  v-slot="{ errorMessage }"
                  v-else-if="input.type == 'file'"
              >
                <label v-if="previews[input.name] || options[input.name]" :for="input.name" class="relative mb-2 h-40 w-40 overflow-hidden rounded-lg cursor-pointer group block">
                  <img
                      :src="previews[input.name] || options[input.name]"
                      class="h-full w-full object-cover block"
                      alt="preview"
                  />
                  <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <CameraIcon class="w-8 h-8 text-white" />
                  </div>
                </label>
                <input
                    :id="input.name"
                    @change="uploadFile($event, input)"
                    type="file"
                    :name="input.name"
                    accept=".jpg,.jpeg,.png,.webp"
                    :disabled="!access"
                    class="focus:border-ring-brand-300 h-11 w-full overflow-hidden rounded-lg border border-gray-300 bg-transparent text-sm text-gray-500 shadow-theme-xs transition-colors file:mr-5 file:border-collapse file:cursor-pointer file:rounded-l-lg file:border-0 file:border-r file:border-solid file:border-gray-200 file:bg-gray-50 file:py-3 file:pl-3.5 file:pr-3 file:text-sm file:text-gray-700 placeholder:text-gray-400 hover:file:bg-gray-100 focus:outline-hidden focus:file:ring-brand-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:text-white/90 dark:file:border-gray-800 dark:file:bg-white/[0.03] dark:file:text-gray-400 dark:placeholder:text-gray-400"
                />
                <p v-if="errorMessage" class="text-red-500 text-xs mt-1">
                  {{ errorMessage }}
                </p>
              </Field>

              <Field
                  v-else-if="input.type === 'status-select'"
                  :name="input.name"
                  v-model="input.model"
                  v-slot="{ errorMessage }"
              >
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="option in options[input.name]"
                    :key="option.id"
                    type="button"
                    :disabled="!access"
                    @click="input.model = option.id"
                    :class="[
                      'inline-flex items-center gap-2 rounded-lg border-2 px-4 py-2 text-sm font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed',
                      input.model === option.id && option.color === 'green'
                        ? 'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 dark:border-green-600'
                        : '',
                      input.model === option.id && option.color === 'orange'
                        ? 'border-orange-400 bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 dark:border-orange-500'
                        : '',
                      input.model === option.id && option.color === 'red'
                        ? 'border-red-500 bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400 dark:border-red-600'
                        : '',
                      input.model !== option.id
                        ? 'border-gray-200 bg-white text-gray-500 hover:border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-gray-600'
                        : '',
                    ]"
                  >
                    <span :class="[
                      'h-2 w-2 rounded-full',
                      option.color === 'green'  ? 'bg-green-500'  : '',
                      option.color === 'orange' ? 'bg-orange-400' : '',
                      option.color === 'red'    ? 'bg-red-500'    : '',
                      !option.color || !['green','orange','red'].includes(option.color) ? 'bg-gray-400' : '',
                    ]"></span>
                    {{ option.name }}
                  </button>
                </div>
                <p v-if="errorMessage" class="text-red-500 text-xs mt-1">{{ errorMessage }}</p>
              </Field>

              <Field
                  v-else-if="input.type === 'checkbox'"
                  :name="input.name"
                  v-model="input.model"
                  v-slot="{ errorMessage }"
              >
                <BaseInput
                    type="checkbox"
                    :name="input.name"
                    :modelValue="input.model"
                    @update:modelValue="(val: boolean) => input.model = val ? 1 : 0"
                    :disabled="input.disabled || !access"
                />
                <p v-if="errorMessage" class="text-red-500 text-xs mt-1">{{ errorMessage }}</p>
              </Field>

              <Field
                  v-else-if="input.type === 'select'"
                  :name="input.name"
                  v-model="input.model"
                  v-slot="{ errorMessage }"
              >
                <div class="relative z-20 bg-transparent">
                  <select
                      :name="input.name"
                      v-model="input.model"
                      :disabled="!access"
                      :class="[
                        'dark:bg-dark-900 h-11 w-full appearance-none rounded-lg border bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden focus:ring-3 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        errorMessage
                          ? 'border-red-400 focus:border-red-400 focus:ring-red-500/10 dark:border-red-600'
                          : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-800'
                      ]"
                  >
                    <option v-for="(option, o) in options[input.name]" :value="option.id" :key="o">{{ option.name }}</option>
                  </select>
                  <span class="absolute z-30 text-gray-500 -translate-y-1/2 pointer-events-none right-4 top-1/2 dark:text-gray-400">
                    <ChevronDownIcon class="stroke-current" />
                  </span>
                </div>
                <p v-if="errorMessage" class="text-red-500 text-xs mt-1">{{ errorMessage }}</p>
              </Field>
              <Field
                  :name="input.name"
                  v-slot="{ errorMessage }"
                  v-else-if="input.type === 'textarea'"
              >
                <textarea
                    :id="input.name"
                    v-model="input.model"
                    :disabled="input.disabled || !access"
                    :rows="input.rows || 5"
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                ></textarea>
                <p v-if="errorMessage" class="text-red-500 text-xs mt-1">{{ errorMessage }}</p>
              </Field>

              <Field
                  v-else-if="input.type === 'editor'"
                  :name="input.name"
                  v-model="input.model"
                  v-slot="{ errorMessage }"
              >
                <BaseEditor
                    v-model="input.model"
                    :disabled="input.disabled || !access"
                />
                <p v-if="errorMessage" class="text-red-500 text-xs mt-1">{{ errorMessage }}</p>
              </Field>

              <Field
                  v-else-if="input.type === 'cascade'"
                  :name="input.name"
                  v-model="input.model"
                  v-slot="{ errorMessage }"
              >
                <BaseCascadeSelect
                    :categories="options[input.name]?.categories || []"
                    :subcategories="options[input.name]?.subcategories || []"
                    v-model="input.model"
                    :disabled="input.disabled || !access"
                    :error="!!errorMessage"
                />
                <p v-if="errorMessage" class="text-red-500 text-xs mt-1">{{ errorMessage }}</p>
              </Field>

              <Field
                  v-else-if="input.type === 'date'"
                  :name="input.name"
                  v-model="input.model"
                  v-slot="{ errorMessage }"
              >
                <FlatPickr
                    v-model="input.model"
                    :config="{ dateFormat: 'Y-m-d', allowInput: true, disableMobile: true }"
                    :disabled="input.disabled || !access"
                    :class="[
                      'dark:bg-dark-900 h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden focus:ring-3 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                      errorMessage
                        ? 'border-red-400 focus:border-red-400 focus:ring-red-500/10 dark:border-red-600'
                        : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-800'
                    ]"
                />
                <p v-if="errorMessage" class="text-red-500 text-xs mt-1">{{ errorMessage }}</p>
              </Field>

              <Field
                  v-else-if="input.type === 'datetime'"
                  :name="input.name"
                  v-model="input.model"
                  v-slot="{ errorMessage }"
              >
                <FlatPickr
                    v-model="input.model"
                    :config="{ dateFormat: 'Y-m-d H:i', enableTime: true, time_24hr: true, minuteIncrement: 1, allowInput: true, disableMobile: true }"
                    :disabled="input.disabled || !access"
                    :class="[
                      'dark:bg-dark-900 h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden focus:ring-3 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                      errorMessage
                        ? 'border-red-400 focus:border-red-400 focus:ring-red-500/10 dark:border-red-600'
                        : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-800'
                    ]"
                />
                <p v-if="errorMessage" class="text-red-500 text-xs mt-1">{{ errorMessage }}</p>
              </Field>

              <Field
                  v-else-if="input.type === 'tags'"
                  :name="input.name"
                  v-model="input.model"
                  v-slot="{ errorMessage }"
              >
                <BaseTagSelect
                    :options="options[input.name] || []"
                    v-model="input.model"
                    :disabled="input.disabled || !access"
                />
                <p v-if="errorMessage" class="text-red-500 text-xs mt-1">{{ errorMessage }}</p>
              </Field>

              <Field
                  v-else-if="input.type === 'color'"
                  :name="input.name"
                  v-model="input.model"
                  v-slot="{ errorMessage }"
              >
                <div class="flex items-center gap-2">
                  <input
                      type="color"
                      :value="input.model || '#333333'"
                      @input="(e) => input.model = (e.target as HTMLInputElement).value"
                      :disabled="input.disabled || !access"
                      class="h-11 w-14 cursor-pointer rounded-lg border border-gray-300 bg-transparent p-1 shadow-theme-xs dark:border-gray-700"
                  />
                  <input
                      type="text"
                      :value="input.model || ''"
                      @input="(e) => input.model = (e.target as HTMLInputElement).value"
                      :disabled="input.disabled || !access"
                      placeholder="#333333"
                      class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                  />
                </div>
                <p v-if="errorMessage" class="text-red-500 text-xs mt-1">{{ errorMessage }}</p>
              </Field>

              <div
                  class="relative"
                  v-else
              >
                <BaseInput
                    :name="input.name"
                    :type="input.type"
                    :toggleType="(input.name == 'password') ? true : false"
                    :placeholder="input.placeholder"
                    v-model="input.model"
                    :disabled="input.disabled || !access"
                />
              </div>
            </div>
          </div>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
          <BaseBtn
              :to="{name: route_back}"
              color="light"
              v-if="route_back"
          >
            Back
          </BaseBtn>
          <BaseBtn
              :loading="loading"
              v-if="access"
          >
            {{ form_btn }}
          </BaseBtn>
        </div>
      </Form>
    </div>
  </div>

</template>

<style scoped>

</style>
