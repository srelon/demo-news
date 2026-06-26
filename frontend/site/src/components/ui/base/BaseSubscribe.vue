<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import axios from '@/plugins/axios.ts'

const toast = useToast()
const email = ref('')
const loading = ref(false)
const error = ref('')

function submit() {
    if (loading.value) return
    loading.value = true
    error.value = ''

    axios.post('subscribe', {
        email: email.value,
    }).then(() => {
        toast.success('Successfully subscribed!')
        email.value = ''
    }).catch((err) => {
        const errors = err.response?.data?.errors
        if (errors?.email) {
            error.value = errors.email[0]
        }
    }).finally(() => {
        loading.value = false
    })
}
</script>

<template>
  <div class="bg10 p-rl-35 p-t-28 p-b-35 m-b-55">
    <h5 class="f1-m-5 cl0 p-b-10">
      Subscribe
    </h5>

    <p class="f1-s-1 cl0 p-b-25">
      Get all latest content delivered to your email a few times a month.
    </p>

    <form class="size-a-9 pos-relative" @submit.prevent="submit">
      <input
        v-model="email"
        class="s-full f1-m-6 cl6 plh9 p-l-20 p-r-55"
        type="email"
        name="email"
        placeholder="Email"
      >

      <button
        class="size-a-10 flex-c-c ab-t-r fs-16 cl9 hov-cl10 trans-03"
        type="submit"
        :disabled="loading"
      >
        <i class="fa fa-arrow-right"></i>
      </button>
    </form>

    <p v-if="error" class="f1-s-1 cl-red p-t-10">
      {{ error }}
    </p>
  </div>
</template>
