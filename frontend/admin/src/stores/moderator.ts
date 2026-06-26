import { defineStore } from 'pinia'
import axios from '@/plugins/axios'

interface ModeratorUser {
    id: number
    name: string
    username: string | null
    img: string | null
}

export const useModeratorStore = defineStore('moderator', {
    state: (): {
        user: ModeratorUser | null
        accounts: ModeratorUser[]
        loaded: boolean
    } => ({
        user: null,
        accounts: [],
        loaded: false,
    }),

    actions: {
        fetch() {
            return axios.get('session/moderator').then((res) => {
                this.user = res.data.data.user
            })
        },

        fetchAccounts() {
            return axios.get('session/moderator/accounts').then((res) => {
                this.accounts = res.data.data.users
                this.loaded = true
            })
        },

        setUser(user_id: number) {
            return axios.post('session/moderator', { user_id }).then((res) => {
                this.user = res.data.data.user
            })
        },

        clear() {
            return axios.delete('session/moderator').then(() => {
                this.user = null
            })
        },
    },
})
