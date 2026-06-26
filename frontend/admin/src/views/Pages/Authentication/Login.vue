<script setup lang="ts">
  import AuthBox from "@/views/Core/AuthBox.vue";
  import { object, string } from 'yup';

  import {reactive} from "vue";
  const form= reactive([
    {
      name: 'email',
      model: null,
      placeholder: 'Email',
      type: 'text',
      icon: 'bi-envelope'
    },
    {
      name: 'password',
      model: null,
      placeholder: 'Password',
      type: 'password',
      icon: 'bi-lock-fill'
    }
  ]);


  const schema = object({
    email: string().required().email(),
    password: string().required().min(8),
  });

</script>

<template>
  <AuthBox
      route="login"
      title="Sign in"
      description="Enter your email and password to sign in! "
      form_btn="Sign In"
      :form="form"
      :schema="schema"
  ></AuthBox>
</template>
