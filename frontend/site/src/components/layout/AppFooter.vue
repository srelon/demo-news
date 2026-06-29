<script setup lang="ts">
import { useLayoutStore, toImageUrl } from '@/stores/layout'

const layoutStore = useLayoutStore()
</script>

<template>
  <footer>
    <div class="bg2 p-t-40 p-b-25">
      <div class="container">
        <div class="row">
          <div class="col-lg-4 p-b-20">
            <div class="size-h-3 flex-s-c">
              <router-link :to="{name: 'home'}">
                <img class="max-s-full" src="/images/icons/logo-02.png" alt="LOGO">
              </router-link>
            </div>

            <div>
              <p class="f1-s-1 cl11 p-b-16">
                Your trusted source for breaking news, in-depth analysis, and the stories that matter most.
              </p>

              <p class="f1-s-1 cl11 p-b-16">
                Any questions? Call us on (+1) 96 716 6879
              </p>

            </div>
          </div>

          <div class="col-sm-6 col-lg-4 p-b-20">
            <div class="size-h-3 flex-s-c">
              <h5 class="f1-m-7 cl0">Popular Posts</h5>
            </div>

            <ul>
              <li v-for="post in layoutStore.footer.popular_posts.slice(0, 3)" :key="post.slug" class="flex-wr-sb-s p-b-20">
                <router-link
                  :to="{name: 'news_category', params: {category: post.category_slug}}"
                  class="size-w-4 wrap-pic-w hov1 trans-03"
                >
                  <img :src="toImageUrl(post.image)" alt="IMG">
                </router-link>

                <div class="size-w-5">
                  <h6 class="p-b-5">
                    <router-link
                      :to="{name: 'news_category', params: {category: post.category_slug}}"
                      class="f1-s-5 cl11 hov-cl10 trans-03"
                    >
                      {{ post.title }}
                    </router-link>
                  </h6>

                  <span class="f1-s-3 cl6">{{ post.date }}</span>
                </div>
              </li>
            </ul>
          </div>

          <div class="col-sm-6 col-lg-4 p-b-20">
            <div class="size-h-3 flex-s-c">
              <h5 class="f1-m-7 cl0">Category</h5>
            </div>

            <ul class="m-t--12">
              <li v-for="cat in layoutStore.categories" :key="cat.slug" class="how-bor1 p-rl-5 p-tb-10">
                <router-link
                  :to="{name: 'news_category', params: {category: cat.slug}}"
                  class="f1-s-5 cl11 hov-cl10 trans-03 p-tb-8"
                >
                  {{ cat.name }}
                </router-link>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="bg11">
      <div class="container size-h-4 flex-c-c p-tb-15">
        <span class="f1-s-1 cl0 txt-center">
          Copyright &copy; {{ new Date().getFullYear() }} News Portal. All rights reserved.
        </span>
      </div>
    </div>
  </footer>
</template>
