<template>
  <div class="min-h-screen xl:flex">
    <app-sidebar />
    <Backdrop />
    <div
      class="flex-1 transition-all duration-300 ease-in-out"
      :class="[isExpanded || isHovered ? 'lg:ml-[290px]' : 'lg:ml-[90px]']"
    >
      <app-header />
      <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import AppSidebar from './AppSidebar.vue'
import AppHeader from './AppHeader.vue'
import { useSidebar } from '@/composables/useSidebar'
import Backdrop from './Backdrop.vue'
import { useAuthStore } from '@/stores/auth'
import { useWsStore } from '@/stores/ws'
import { useModeratorStore } from '@/stores/moderator'
import { useAdminNotificationStore } from '@/stores/notifications'

const { isExpanded, isHovered } = useSidebar()
const auth = useAuthStore()
const ws = useWsStore()
const notifStore = useAdminNotificationStore()

const onWsNotif = (e: Event) => notifStore.handleWsEvent((e as CustomEvent).detail)

onMounted(() => {
    ws.connect()

    const can_see_moderator = auth.accesses('moderator', 'view')
    const can_see_debug = auth.accesses('debug', 'view')

    if (can_see_moderator) {
        const moderator = useModeratorStore()
        moderator.fetchAccounts().then(() => {
            const user_ids = moderator.accounts.map((a) => a.id)
            if (user_ids.length > 0) ws.subscribeNotifications(user_ids)
        })
        notifStore.fetchUnreadCount()
    }

    if (can_see_debug) {
        notifStore.fetchDebugUnreadCount()
    }

    window.addEventListener('admin:ws:notification', onWsNotif)
})

onUnmounted(() => {
    window.removeEventListener('admin:ws:notification', onWsNotif)
})
</script>
