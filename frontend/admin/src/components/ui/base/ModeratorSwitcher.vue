<script setup lang="ts">
import ChevronDownSmIcon from '@/icons/ChevronDownSmIcon.vue'
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useModeratorStore } from '@/stores/moderator'

const moderator = useModeratorStore()
const router = useRouter()
const open = ref(false)
const base_url = import.meta.env.VITE_API_BASE_URL

onMounted(() => {
    moderator.fetch()
    moderator.fetchAccounts()
})

function select(user_id: number) {
    moderator.setUser(user_id).then(() => {
        open.value = false
    })
}


function goToProfile() {
    router.push({ name: 'profile' })
}
</script>

<template>
    <div class="relative inline-block">
        <!-- Empty list: link to profile instead of dropdown trigger -->
        <button
            v-if="moderator.loaded && !moderator.accounts.length"
            @click="goToProfile"
            class="inline-flex items-center gap-2 rounded-lg border border-dashed border-gray-300 bg-white px-3 py-2 text-sm text-gray-400 hover:border-brand-400 hover:text-brand-500 dark:border-gray-600 dark:bg-gray-900 dark:hover:border-brand-500 transition-colors"
        >
            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
            </svg>
            Add moderator account
        </button>

        <!-- Normal dropdown trigger -->
        <button
            v-else-if="!moderator.loaded || moderator.accounts.length"
            @click="open = !open"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600 hover:border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-gray-600 transition-colors"
        >
            <template v-if="moderator.user">
                <img
                    v-if="moderator.user.img"
                    :src="base_url + moderator.user.img"
                    :alt="moderator.user.name"
                    class="w-5 h-5 rounded-full object-cover"
                />
                <span v-else class="w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs">
                    {{ moderator.user.name.charAt(0) }}
                </span>
                <span class="max-w-[120px] truncate">{{ moderator.user.name }}</span>
            </template>
            <template v-else>
                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                </svg>
                <span class="text-gray-400">Select account</span>
            </template>
            <ChevronDownSmIcon class="w-3 h-3 text-gray-400 ml-auto" />
        </button>

        <!-- Dropdown -->
        <div
            v-if="open"
            class="absolute left-0 top-full mt-1 z-30 w-56 rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900 overflow-hidden"
        >
            <div class="py-1">
                <button
                    v-for="user in moderator.accounts"
                    :key="user.id"
                    @click="select(user.id)"
                    class="flex items-center gap-2 w-full px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                    :class="{ 'bg-brand-50 dark:bg-brand-900/20': moderator.user?.id === user.id }"
                >
                    <img
                        v-if="user.img"
                        :src="base_url + user.img"
                        :alt="user.name"
                        class="w-6 h-6 rounded-full object-cover shrink-0"
                    />
                    <span v-else class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs shrink-0">
                        {{ user.name.charAt(0) }}
                    </span>
                    <span class="flex-1 text-left truncate">{{ user.name }}</span>
                    <span v-if="user.username" class="text-xs text-brand-500">@{{ user.username }}</span>
                </button>

            </div>
        </div>

        <!-- Backdrop -->
        <div v-if="open" class="fixed inset-0 z-20" @click="open = false" />
    </div>
</template>
