import { computed } from 'vue'
import { useRoute, type RouteLocationNormalized } from 'vue-router'
import { useLayoutStore } from '@/stores/layout'

export interface Breadcrumb {
  label: string
  to?: object
}

export type BreadcrumbResolver = (
  route: RouteLocationNormalized,
  store: ReturnType<typeof useLayoutStore>
) => Breadcrumb[]

export function useBreadcrumbs() {
  const route = useRoute()
  const store = useLayoutStore()

  return computed<Breadcrumb[]>(() => {
    const resolver = route.meta.breadcrumbs as BreadcrumbResolver | undefined
    if (!resolver) return []
    return resolver(route, store)
  })
}
