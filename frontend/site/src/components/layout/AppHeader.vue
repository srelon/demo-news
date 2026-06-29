<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useLayoutStore, toImageUrl } from '@/stores/layout'
import { useAuthStore } from '@/stores/auth'
import { useBreadcrumbs } from '@/composables/useBreadcrumbs'
import AuthModal from '@/components/Authentication/AuthModal.vue'
import ProfileModal from '@/components/ui/profile/ProfileModal.vue'
import NotificationBell from '@/components/ui/notifications/NotificationBell.vue'

const route = useRoute()
const router = useRouter()
const layoutStore = useLayoutStore()
const authStore = useAuthStore()
const breadcrumbs = useBreadcrumbs()

const search_query = ref('')
const active_nav_tab = ref<Record<string, string>>({})
const menu_open = ref(false)

const auth_modal_tab = ref<'login' | 'register'>('login')
const show_profile_modal = ref(false)

function closeMenu() {
    menu_open.value = false
}

watch(menu_open, (val) => {
    document.body.style.overflow = val ? 'hidden' : ''
})

watch(() => route.fullPath, () => {
    closeMenu()
})

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') {
        closeMenu()
        show_profile_modal.value = false
    }
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown))

function openLogin() {
    auth_modal_tab.value = 'login'
    authStore.openAuthModal()
}

function openRegister() {
    auth_modal_tab.value = 'register'
    authStore.openAuthModal()
}

function submitSearch() {
    const q = search_query.value.trim()
    if (!q) return
    router.push({ name: 'news_search', params: { slug: q } })
    search_query.value = ''
}

function logout() {
    authStore.logout()
}
</script>

<template>
    <header>
        <div class="container-menu-desktop">
            <div class="topbar">
                <div class="content-topbar container h-100">

                    <!-- Left: links + social icons -->
                    <div class="left-topbar">
                        <router-link :to="{name: 'static_page', params: {slug: 'about'}}" class="left-topbar-item">About</router-link>
                        <router-link :to="{name: 'static_page', params: {slug: 'contact'}}" class="left-topbar-item">Contact</router-link>
                        <a href="#" class="left-topbar-item"><span class="fab fa-facebook-f"></span></a>
                        <a href="#" class="left-topbar-item"><span class="fab fa-twitter"></span></a>
                        <a href="#" class="left-topbar-item"><span class="fab fa-vimeo-v"></span></a>
                        <a href="#" class="left-topbar-item"><span class="fab fa-youtube"></span></a>
                    </div>

                    <!-- Right: auth section — uses left-topbar to match styles -->
                    <div class="left-topbar">
                        <template v-if="authStore.user">
                            <NotificationBell class="left-topbar-item" />
                            <a
                                href="#"
                                class="left-topbar-item topbar-username"
                                @click.prevent="show_profile_modal = true"
                            >
                                <span v-if="authStore.user.img" class="topbar-avatar-wrap">
                                    <img :src="toImageUrl(authStore.user.img)" class="topbar-avatar" alt="avatar" />
                                </span>
                                <i v-else class="fa fa-user topbar-avatar-icon"></i>
                                {{ authStore.user.name }}
                            </a>
                            <a href="#" class="left-topbar-item" @click.prevent="logout">Log out</a>
                        </template>
                        <template v-else>
                            <a href="#" class="left-topbar-item" @click.prevent="openLogin">Sign In</a>
                            <a href="#" class="left-topbar-item" @click.prevent="openRegister">Sign Up</a>
                        </template>
                    </div>

                </div>
            </div>

            <div class="wrap-header-mobile">
                <div class="logo-mobile">
                    <router-link :to="{name: 'home'}">
                        <img src="/images/icons/logo-01.png" alt="IMG-LOGO">
                    </router-link>
                </div>
                <div
                    :class="['btn-show-menu-mobile hamburger hamburger--squeeze m-r--8', { 'is-active': menu_open }]"
                    @click="menu_open = !menu_open"
                >
                    <span class="hamburger-box">
                        <span class="hamburger-inner"></span>
                    </span>
                </div>
            </div>

            <div v-show="menu_open" class="menu-mobile">
                <ul class="topbar-mobile">
                    <li class="left-topbar">
                        <router-link :to="{name: 'static_page', params: {slug: 'about'}}" class="left-topbar-item">About</router-link>
                        <router-link :to="{name: 'static_page', params: {slug: 'contact'}}" class="left-topbar-item">Contact</router-link>
                        <a href="#" class="left-topbar-item"><span class="fab fa-facebook-f"></span></a>
                        <a href="#" class="left-topbar-item"><span class="fab fa-twitter"></span></a>
                        <a href="#" class="left-topbar-item"><span class="fab fa-vimeo-v"></span></a>
                        <a href="#" class="left-topbar-item"><span class="fab fa-youtube"></span></a>
                    </li>
                    <li class="right-topbar">
                        <template v-if="authStore.user">
                            <NotificationBell class="left-topbar-item" />
                            <a
                                href="#"
                                class="left-topbar-item topbar-username"
                                @click.prevent="show_profile_modal = true"
                            >
                                <span v-if="authStore.user.img" class="topbar-avatar-wrap">
                                    <img :src="toImageUrl(authStore.user.img)" class="topbar-avatar" alt="avatar" />
                                </span>
                                <i v-else class="fa fa-user topbar-avatar-icon"></i>
                                {{ authStore.user.name }}
                            </a>
                            <a href="#" class="left-topbar-item" @click.prevent="logout">Log out</a>
                        </template>
                        <template v-else>
                            <a href="#" class="left-topbar-item" @click.prevent="openLogin">Sign In</a>
                            <a href="#" class="left-topbar-item" @click.prevent="openRegister">Sign Up</a>
                        </template>
                    </li>
                </ul>

                <ul class="main-menu-m">
                    <li>
                        <router-link :to="{name: 'home'}">Home</router-link>
                    </li>
                    <li v-for="item in layoutStore.nav" :key="item.slug">
                        <router-link :to="{name: 'news_category', params: {category: item.slug}}">
                            {{ item.label }}
                        </router-link>
                    </li>
                </ul>
            </div>

            <div class="wrap-logo container">
                <div class="logo">
                    <router-link :to="{name: 'home'}">
                        <img src="/images/icons/logo-01.png" alt="LOGO">
                    </router-link>
                </div>
                <div class="banner-header">
                    <a href="#">
                        <img src="/images/banner-01.jpg" alt="IMG">
                    </a>
                </div>
            </div>

            <div class="wrap-main-nav">
                <div class="main-nav">
                    <nav class="menu-desktop">
                        <router-link class="logo-stick" :to="{name: 'home'}">
                            <img src="/images/icons/logo-01.png" alt="LOGO">
                        </router-link>

                        <ul class="main-menu">
                            <li :class="{ 'main-menu-active': route.name === 'home' }">
                                <router-link :to="{name: 'home'}">Home</router-link>
                            </li>

                            <li
                                v-for="item in layoutStore.nav"
                                :key="item.slug"
                                :class="['mega-menu-item', { 'main-menu-active': route.params.category === item.slug }]"
                                :style="{ '--category-color': item.color }"
                            >
                                <router-link :to="{name: 'news_category', params: {category: item.slug}}">
                                    {{ item.label }}
                                    <span v-if="item.tabs.length" class="menu-arrow">&#xf2f9;</span>
                                </router-link>

                                <div class="sub-mega-menu">
                                    <div class="nav flex-column nav-pills" role="tablist">
                                        <router-link
                                            v-for="tab in item.tabs"
                                            :key="tab.id"
                                            :to="{name: 'news_subcategory', params: {category: item.slug, subcategory: tab.slug}}"
                                            :class="['nav-link', (active_nav_tab[item.slug] ?? item.tabs[0]?.slug) === tab.slug ? 'active' : '']"
                                            :style="(active_nav_tab[item.slug] ?? item.tabs[0]?.slug) === tab.slug ? { backgroundColor: item.color } : {}"
                                            @mouseenter="active_nav_tab[item.slug] = tab.slug"
                                            role="tab"
                                        >
                                            {{ tab.label }}
                                        </router-link>
                                    </div>

                                    <div class="tab-content">
                                        <div
                                            v-for="tab in item.tabs"
                                            :key="tab.id"
                                            :class="['tab-pane', (active_nav_tab[item.slug] ?? item.tabs[0]?.slug) === tab.slug ? 'show active' : '']"
                                            role="tabpanel"
                                        >
                                            <div class="row">
                                                <div v-for="post in tab.posts" :key="post.page_slug" class="col-3">
                                                    <div>
                                                        <router-link
                                                            :to="{name: 'news_page', params: {category: post.category_slug, subcategory: post.subcategory_slug, slug: post.page_slug}}"
                                                            class="wrap-pic-w hov1 trans-03"
                                                        >
                                                            <img :src="toImageUrl(post.image)" alt="IMG">
                                                        </router-link>

                                                        <div class="p-t-10">
                                                            <h5 class="p-b-5">
                                                                <router-link
                                                                    :to="{name: 'news_page', params: {category: post.category_slug, subcategory: post.subcategory_slug, slug: post.page_slug}}"
                                                                    class="f1-s-5 cl3 hov-cl10 trans-03"
                                                                >
                                                                    {{ post.title }}
                                                                </router-link>
                                                            </h5>

                                                            <span class="cl8">
                                                                <router-link
                                                                    :to="{name: 'news_category', params: {category: post.category_slug}}"
                                                                    class="f1-s-6 cl8 hov-cl10 trans-03"
                                                                >
                                                                    {{ post.category_name }}
                                                                </router-link>
                                                                <span class="f1-s-3 m-rl-3">-</span>
                                                                <span class="f1-s-3">{{ post.date }}</span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="bg0 flex-wr-sb-c p-rl-20 p-tb-8 breadcrumbs-row">
            <div class="breadcrumbs-wrap f2-s-1 p-r-30 m-tb-6">
                <template v-for="(crumb, i) in breadcrumbs" :key="i">
                    <router-link v-if="crumb.to" :to="crumb.to" class="breadcrumb-item f1-s-3 cl9">
                        {{ crumb.label }}
                    </router-link>
                    <span v-else class="breadcrumb-item breadcrumb-item--last f1-s-3 cl9">{{ crumb.label }}</span>
                </template>
            </div>

            <form class="pos-relative size-a-2 bo-1-rad-22 of-hidden bocl11 m-tb-6 search-form" @submit.prevent="submitSearch">
                <input
                    v-model="search_query"
                    class="f1-s-1 cl6 plh9 s-full p-l-25 p-r-45"
                    type="text"
                    name="search"
                    placeholder="Search"
                />
                <button type="submit" class="flex-c-c size-a-1 ab-t-r fs-20 cl2 hov-cl10 trans-03">
                    <i class="zmdi zmdi-search"></i>
                </button>
            </form>
        </div>
    </div>

    <AuthModal
        v-if="authStore.auth_modal_open"
        :initial_tab="auth_modal_tab"
        @close="authStore.closeAuthModal()"
    />

    <ProfileModal
        v-if="show_profile_modal"
        @close="show_profile_modal = false"
    />
</template>

<style scoped>
.topbar-username {
    text-transform: capitalize;
}

.breadcrumbs-wrap {
    min-width: 0;
    overflow: hidden;
}

.breadcrumb-item {
    text-transform: capitalize;
}

.breadcrumb-item--last {
    display: inline-block;
    max-width: 40ch;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: bottom;
}

.topbar-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid rgba(255, 255, 255, 0.4);
    margin-right: 4px;
    vertical-align: middle;
}

.topbar-avatar-icon {
    margin-right: 4px;
    opacity: 0.85;
}

@media (max-width: 575px) {
    .breadcrumbs-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .breadcrumbs-wrap {
        max-width: 100%;
        padding-right: 0;
        margin-bottom: 0 !important;
        margin-top: 0 !important;
    }

    .breadcrumb-item {
        font-size: 11px;
    }

    .breadcrumb-item--last {
        max-width: 14ch;
    }

    .search-form {
        width: 100% !important;
        margin-bottom: 0 !important;
        margin-top: 0 !important;
    }
}
</style>
