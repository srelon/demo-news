import { defineStore } from 'pinia'
import axios from '@/plugins/axios'

type User = {
    id: number,
    rule_id: string,
    accesses_id: Record<string, Record<string, boolean>>,
    img: string,
    email: string,
    status: number,
    name: string
}

export const useAuthStore = defineStore('auth', {
    state: (): { user: User | null } => ({
        user: null
    }),
    getters: {
        accesses: (state) => {
            return (module: string, action: string) => {
                if (!state.user?.accesses_id) return false;

                return !!state.user.accesses_id?.[module]?.[action];
            };
        }
    },
    actions: {
        fetchUser() {
            return axios.get('info').then((res) => {
                this.user = res.data.data.user
            })
        },
        setUser(user: User) {
            this.user = user
        },
        setAssest(accesses_id: any) {
            if (this.user) {
                this.user.accesses_id = accesses_id
            }
        }
    }
})