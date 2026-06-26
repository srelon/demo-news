<template>
  <section class="bg0 p-t-100 p-b-140">
    <div class="container">
      <div class="flex-col-c-m txt-center">
        <h1 class="f1-m-1 cl2 p-b-16">404</h1>
        <p class="f1-s-1 cl9 p-b-30">The page you are looking for does not exist.</p>
        <router-link :to="{ name: 'home' }" class="f1-s-1 cl10 hov-cl2 trans-03">
          &larr; Back to Home
        </router-link>
      </div>
    </div>
  </section>
</template>
