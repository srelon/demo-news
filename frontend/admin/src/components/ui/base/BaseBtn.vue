<script setup>
import { Field } from 'vee-validate'
import {ref} from "vue";

defineProps({
  loading: false,
  to: null,
  type: String,
  base_class: {
    default: 'px-4 py-3 text-sm font-medium transition rounded-lg',
    type: String
  },
  add_class: {
    default: null,
    type: String
  },
  color: {
    default: 'primary',
    type: String
  }
})

const btnColors = ref({
  primary: 'bg-brand-500 text-white shadow-theme-xs hover:bg-brand-600 disabled:bg-brand-300',
  default: 'bg-white text-gray-700 border border-gray-300 shadow-theme-xs hover:bg-gray-50 disabled:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700',
  secondary: 'bg-white text-gray-700 border border-gray-300 shadow-theme-xs hover:bg-gray-50 disabled:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700',
  success: 'bg-green-600 text-white shadow-theme-xs hover:bg-green-700 disabled:bg-green-300',
  danger: 'bg-red-500 text-white shadow-theme-xs hover:bg-red-600 disabled:bg-red-300',
  error: 'bg-red-500 text-white shadow-theme-xs hover:bg-red-600 disabled:bg-red-300',
  warning: 'bg-yellow-500 text-white shadow-theme-xs hover:bg-yellow-600 disabled:bg-yellow-300',
  info: 'bg-sky-500 text-white shadow-theme-xs hover:bg-sky-600 disabled:bg-sky-300',
  dark: 'bg-gray-900 text-white shadow-theme-xs hover:bg-gray-800 disabled:bg-gray-600',
  light: 'bg-gray-400 text-gray-700 shadow-theme-xs hover:bg-gray-500 disabled:bg-gray-100 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600',
});
const showPassword = ref(false);

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

defineEmits(['update:modelValue'])
</script>

<template>
  <router-link
      :to="to"
      v-if="to"
      :class="base_class+' '+add_class+' '+btnColors[color]"
  >
    <slot></slot>
  </router-link>
  <button
      v-else
      type="submit"
      :class="base_class+' '+add_class+' '+btnColors[color]"
      :disabled="loading"
  >
    <div v-if="loading">loading</div>
    <slot v-else></slot>
  </button>
</template>