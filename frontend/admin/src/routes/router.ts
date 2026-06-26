import { createWebHistory, createRouter } from 'vue-router'

import Home from '@/views/Pages/Home.vue'
import Login from '@/views/Pages/Authentication/Login.vue'
import FourZeroFour from '@/views/Pages/Errors/FourZeroFour.vue'
import UsersList from '@/views/Pages/Users/UsersList.vue'
import UserPage from '@/views/Pages/Users/UserPage.vue'
import AdminsList from '@/views/Pages/Admins/AdminsList.vue'
import AdminPage from '@/views/Pages/Admins/AdminPage.vue'
import AdminCreate from '@/views/Pages/Admins/AdminCreate.vue'
import AdminsAccesses from '@/views/Pages/Admins/AdminsAccesses.vue'
import AdminsRulesList from '@/views/Pages/Admins/RulesList.vue'
import DebugList from '@/views/Pages/Debug/DebugList.vue'
import TestsPage from '@/views/Pages/Debug/TestsPage.vue'
import NotificationsList from '@/views/Pages/Notifications/NotificationsList.vue'
import CategoriesList from '@/views/Pages/Categories/CategoriesList.vue'
import ArticlesList from '@/views/Pages/Articles/ArticlesList.vue'
import ArticlePage from '@/views/Pages/Articles/ArticlePage.vue'
import TagsList from '@/views/Pages/Tags/TagsList.vue'
import RssSourcesList from '@/views/Pages/Rss/RssSourcesList.vue'
import PagesList from '@/views/Pages/StaticPages/PagesList.vue'
import PageEdit from '@/views/Pages/StaticPages/PageEdit.vue'
import CommentsList from '@/views/Pages/Comments/CommentsList.vue'
import ProfilePage from '@/views/Pages/Profile/ProfilePage.vue'

import middlewarePipeline from './middlewarePipeline'
import guest from './middleware/guest'
import auth from './middleware/auth'

import { useAuthStore } from '@/stores/auth'

const SITE_NAME = 'Admin'

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    scrollBehavior(_to, _from, savedPosition) {
        return savedPosition || { left: 0, top: 0 }
    },
    routes: [
        {
            path: '/authentication/',
            meta: {
                middleware: [guest],
            },
            children: [
                {
                    name: 'login',
                    path: '/login',
                    component: Login,
                    meta: { title: 'Login' },
                },
            ],
        },
        {
            path: '/',
            meta: {
                middleware: [auth],
            },
            children: [
                {
                    name: 'home',
                    path: '',
                    component: Home,
                    meta: { title: 'Dashboard' },
                },
                {
                    path: 'users',
                    children: [
                        {
                            name: 'users',
                            path: '',
                            component: UsersList,
                            meta: { title: 'Users' },
                        },
                        {
                            name: 'user',
                            path: ':id',
                            component: UserPage,
                            meta: { title: 'User' },
                        },
                    ],
                },
                {
                    path: 'admins',
                    children: [
                        {
                            name: 'admins',
                            path: '',
                            component: AdminsList,
                            meta: { title: 'Admins' },
                        },
                        {
                            name: 'admin.create',
                            path: 'create',
                            component: AdminCreate,
                            meta: { title: 'Create Admin' },
                        },
                        {
                            name: 'admin',
                            path: ':id',
                            component: AdminPage,
                            meta: { title: 'Admin' },
                        },
                        {
                            path: 'rules',
                            children: [
                                {
                                    name: 'admins.rules',
                                    path: '',
                                    component: AdminsRulesList,
                                    meta: { title: 'Rules' },
                                },
                                {
                                    name: 'admins.rules.create',
                                    path: 'create',
                                    component: AdminsAccesses,
                                    meta: { title: 'Create Rule' },
                                },
                                {
                                    name: 'admins.rules.edit',
                                    path: ':id',
                                    component: AdminsAccesses,
                                    meta: { title: 'Edit Rule' },
                                },
                            ],
                        },
                    ],
                },
                {
                    path: 'categories',
                    children: [
                        {
                            name: 'categories',
                            path: '',
                            component: CategoriesList,
                            meta: { title: 'Categories' },
                        },
                    ],
                },
                {
                    path: 'articles',
                    children: [
                        {
                            name: 'articles',
                            path: '',
                            component: ArticlesList,
                            meta: { title: 'Articles' },
                        },
                        {
                            name: 'article.create',
                            path: 'create',
                            component: ArticlePage,
                            meta: { title: 'Create Article' },
                        },
                        {
                            name: 'article',
                            path: ':id',
                            component: ArticlePage,
                            meta: { title: 'Edit Article' },
                        },
                    ],
                },
                {
                    path: 'tags',
                    children: [
                        {
                            name: 'tags',
                            path: '',
                            component: TagsList,
                            meta: { title: 'Tags' },
                        },
                    ],
                },
                {
                    path: 'rss',
                    children: [
                        {
                            name: 'rss_sources',
                            path: '',
                            component: RssSourcesList,
                            meta: { title: 'RSS Sources' },
                        },
                    ],
                },
                {
                    path: 'pages',
                    children: [
                        {
                            name: 'static_pages',
                            path: '',
                            component: PagesList,
                            meta: { title: 'Pages' },
                        },
                        {
                            name: 'static_page.create',
                            path: 'create',
                            component: PageEdit,
                            meta: { title: 'Create Page' },
                        },
                        {
                            name: 'static_page',
                            path: ':id',
                            component: PageEdit,
                            meta: { title: 'Edit Page' },
                        },
                    ],
                },
                {
                    path: 'comments',
                    children: [
                        {
                            name: 'comments',
                            path: '',
                            component: CommentsList,
                            meta: { title: 'Comments' },
                        },
                    ],
                },
                {
                    name: 'profile',
                    path: 'profile',
                    component: ProfilePage,
                    meta: { title: 'Profile' },
                },
                {
                    name: 'notifications',
                    path: 'notifications',
                    component: NotificationsList,
                    meta: { title: 'Notifications' },
                },
                {
                    name: 'debug',
                    path: 'debug/:current?',
                    component: DebugList,
                    meta: { title: 'Debug' },
                },
                {
                    name: 'tests',
                    path: 'tests',
                    component: TestsPage,
                    meta: { title: 'Tests' },
                },
            ],
        },
        {
            name: 'error_404',
            path: '/:pathMatch(.*)*',
            component: FourZeroFour,
            meta: { title: 'Not Found' },
        },
    ],
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
        next: middlewarePipeline(context, middleware, 1),
    })
})

export default router
