<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/plugins/axios'
import { useSeoMeta } from '@/composables/useSeoMeta'
import BasePageLoader from '@/components/ui/base/BasePageLoader.vue'
import BaseTitle from '@/components/ui/base/BaseTitle.vue'

const route = useRoute()

const page = ref<{ title: string; content: string } | null>(null)

function loadPage(slug: string) {
    page.value = null

    axios.get(`page/${slug}`).then((res) => {
        const data = res.data.data
        page.value = data
        useSeoMeta({ title: data.title })
    })
}

onMounted(() => loadPage(String(route.params.slug)))

watch(() => route.params.slug, (slug) => loadPage(String(slug)))
</script>

<template>
    <BasePageLoader :is_loading="!page">
        <section class="bg0 p-t-20 p-b-100">
            <div class="container">
                <div class="row">
                    <div class="col-12">

                        <BaseTitle :title="page!.title" title_type="main" tag="h1" class="p-b-24 p-t-10" />

                        <div class="page-divider m-b-36"></div>

                        <div class="page-content f1-s-11 cl6" v-html="page!.content" />

                    </div>
                </div>
            </div>
        </section>
    </BasePageLoader>
</template>

<style scoped>
.page-divider {
    height: 2px;
    background: #f0f0f0;
}

.page-content :deep(p) {
    margin-bottom: 1.2em;
    line-height: 1.8;
}

.page-content :deep(h2),
.page-content :deep(h3),
.page-content :deep(h4) {
    margin-top: 1.6em;
    margin-bottom: 0.6em;
    color: #222;
}

.page-content :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    margin: 0.8rem 0;
}

.page-content :deep(a) {
    color: #6c757d;
    text-decoration: underline;
}

.page-content :deep(ul),
.page-content :deep(ol) {
    padding-left: 1.4em;
    margin-bottom: 1em;
}
</style>
