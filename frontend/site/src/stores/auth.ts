import { defineStore } from 'pinia'
import axios from '@/plugins/axios'
import { useLayoutStore } from '@/stores/layout'
import { useNotificationStore } from '@/stores/notification'

type User = {
    id: number
    public_id: string
    name: string
    username?: string
    email: string
    img?: string
    is_moderator?: boolean
}

type ResetPending = {
    token: string
    email: string
}

export const useAuthStore = defineStore('auth', {
    state: (): { user: User | null; reset_pending: ResetPending | null; auth_modal_open: boolean } => ({
        user: null,
        reset_pending: null,
        auth_modal_open: false,
    }),
    actions: {
        fetchUser() {
            return axios.get('profile').then((res) => {
                this.user = res.data.data.user
                if (this.user?.id) {
                    useLayoutStore().authenticateWS(this.user.id)
                    useNotificationStore().fetchUnreadCount()
                }
            }).catch(() => {
                this.user = null
            })
        },
        setUser(user: User) {
            this.user = user
            useLayoutStore().authenticateWS(user.id)
            useNotificationStore().fetchUnreadCount()
        },
        clearUser() {
            this.user = null
        },
        logout() {
            return axios.post('logout').then(() => {
                this.user = null
                useNotificationStore().reset()
            })
        },
        setResetPending(token: string, email: string) {
            this.reset_pending = { token, email }
        },
        clearResetPending() {
            this.reset_pending = null
        },
        openAuthModal() {
            this.auth_modal_open = true
        },
        closeAuthModal() {
            this.auth_modal_open = false
        },
    }
})
