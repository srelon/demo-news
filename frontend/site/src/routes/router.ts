import { createWebHistory, createRouter, type RouteLocationNormalized } from 'vue-router'

import Index from '@/views/Pages/Index.vue'
import NewsCategory from '@/views/Pages/News/NewsCategory.vue'
import NewsPage from '@/views/Pages/News/NewsPage.vue'
import NewsFilterPage from '@/views/Pages/News/NewsFilterPage.vue'
import NotFound from '@/views/Pages/NotFound.vue'
import NotificationsPage from '@/views/Pages/Notifications/NotificationsPage.vue'

import StaticPage from '@/views/Pages/StaticPage.vue'

import middlewarePipeline from './middlewarePipeline'

import { useAuthStore } from '@/stores/auth'
import { useLayoutStore } from '@/stores/layout'
import type { BreadcrumbResolver } from '@/composables/useBreadcrumbs'

const SITE_NAME = import.meta.env.VITE_SITE_NAME ?? 'News'

if (typeof history !== 'undefined' && 'scrollRestoration' in history) {
    history.scrollRestoration = 'manual'
}

function slugToLabel(slug: string): string {
    return slug.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const home = { label: 'Home', to: { name: 'home' } }

function resolveCatSub(route: RouteLocationNormalized, store: ReturnType<typeof useLayoutStore>) {
    const cat_slug = String(route.params.category)
    const sub_slug = String(route.params.subcategory)
    const cat = store.nav.find(n => n.slug === cat_slug)
    const sub = cat?.tabs.find(t => t.slug === sub_slug)
    return {
        cat_slug,
        sub_slug,
        cat_label: cat?.label ?? slugToLabel(cat_slug),
        sub_label: sub?.label ?? slugToLabel(sub_slug),
    }
}

const breadcrumbs: Record<string, BreadcrumbResolver> = {
    home: () => [
        { label: 'Home' },
    ],

    news_category: (route, store) => {
        const cat = store.nav.find(n => n.slug === route.params.category)
        return [
            home,
            { label: cat?.label ?? slugToLabel(String(route.params.category)) },
        ]
    },

    news_subcategory: (route, store) => {
        const { cat_slug, sub_label, cat_label } = resolveCatSub(route, store)
        return [
            home,
            { label: cat_label, to: { name: 'news_category', params: { category: cat_slug } } },
            { label: sub_label },
        ]
    },

    news_page: (route, store) => {
        const { cat_slug, sub_slug, cat_label, sub_label } = resolveCatSub(route, store)
        return [
            home,
            { label: cat_label, to: { name: 'news_category', params: { category: cat_slug } } },
            { label: sub_label, to: { name: 'news_subcategory', params: { category: cat_slug, subcategory: sub_slug } } },
            { label: slugToLabel(String(route.params.slug)) },
        ]
    },

    news_search: (route) => [
        home,
        { label: `Search: ${route.params.slug}` },
    ],

    news_tag: (route, store) => {
        const tag = store.all_tags.find(t => t.slug === route.params.slug)
        return [
            home,
            { label: `Tag: ${tag?.name ?? route.params.slug}` },
        ]
    },

    news_archive: (route, store) => {
        const year = Number(route.params.year)
        const month = Number(route.params.month)
        const item = store.sidebar.archive.find(a => a.year === year && a.month === month)
        const label = item?.label ?? new Date(year, month - 1, 1).toLocaleString('en', {
            month: 'long',
            year: 'numeric',
        })
        return [home, { label }]
    },

    'reset-password': () => [home, { label: 'Reset Password' }],
    static_page: (route) => [home, { label: String(route.params.slug).replace(/-/g, ' ') }],
    notifications: () => [home, { label: 'Notifications' }],
}

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    scrollBehavior(_to, _from, savedPosition) {
        if (savedPosition) return savedPosition
        window.scrollTo({ top: 0, behavior: 'instant' })
        return false
    },
    routes: [
        {
            path: '/',
            children: [
                {
                    name: 'home',
                    path: '',
                    component: Index,
                    meta: { title: 'Home', breadcrumbs: breadcrumbs.home },
                },
                {
                    name: 'news_category',
                    path: ':category',
                    component: NewsCategory,
                    meta: { breadcrumbs: breadcrumbs.news_category },
                },
                {
                    name: 'news_subcategory',
                    path: ':category/:subcategory',
                    component: NewsCategory,
                    meta: { breadcrumbs: breadcrumbs.news_subcategory },
                },
                {
                    name: 'news_page',
                    path: ':category/:subcategory/:slug',
                    component: NewsPage,
                    meta: { breadcrumbs: breadcrumbs.news_page },
                },
                {
                    name: 'news_search',
                    path: 'search/:slug',
                    component: NewsFilterPage,
                    meta: { breadcrumbs: breadcrumbs.news_search },
                },
                {
                    name: 'news_tag',
                    path: 'tags/:slug',
                    component: NewsFilterPage,
                    meta: { breadcrumbs: breadcrumbs.news_tag },
                },
                {
                    name: 'news_archive',
                    path: 'archive/:year/:month',
                    component: NewsFilterPage,
                    meta: { breadcrumbs: breadcrumbs.news_archive },
                },
            ]
        },
        {
            name: 'notifications',
            path: '/notifications',
            component: NotificationsPage,
            meta: { title: 'Notifications', breadcrumbs: breadcrumbs.notifications },
        },
        {
            name: 'reset-password',
            path: '/auth/reset-password',
            component: { template: '<div />' },
            beforeEnter(to) {
                const authStore = useAuthStore()
                authStore.setResetPending(
                    String(to.query.token ?? ''),
                    String(to.query.email ?? ''),
                )
                return { name: 'home' }
            },
        },
        {
            name: 'static_page',
            path: '/page/:slug',
            component: StaticPage,
            meta: { breadcrumbs: breadcrumbs.static_page },
        },
        {
            name: 'error_404',
            path: '/404',
            component: NotFound,
            meta: { title: 'Page Not Found' },
        },
        {
            path: '/:pathMatch(.*)*',
            redirect: '/404',
        },
    ]
})

router.beforeEach(async (to, from, next) => {
    const title = to.meta.title as string | undefined
    document.title = title ? `${title} | ${SITE_NAME}` : SITE_NAME

    if (!to.meta.middleware) {
        return next()
    }

    const middleware = to.meta.middleware
    const authStore = useAuthStore()
    const context = { to, from, next, store: authStore }

    return (middleware as any)[0]({
        ...context,
        next: middlewarePipeline(context, middleware, 1)
    })
})

router.afterEach(() => {
    useLayoutStore().shuffleTags()
})

export default router
