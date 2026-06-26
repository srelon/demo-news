import { onUnmounted } from 'vue'

const SITE_NAME = import.meta.env.VITE_SITE_NAME ?? 'News'

function setMeta(name: string, content: string) {
  let el = document.querySelector(`meta[name="${name}"]`) as HTMLMetaElement | null

  if (!el) {
    el = document.createElement('meta')
    el.setAttribute('name', name)
    document.head.appendChild(el)
  }

  el.setAttribute('content', content)
}

function removeMeta(name: string) {
  document.querySelector(`meta[name="${name}"]`)?.remove()
}

export function useSeoMeta(options: {
  title?: string | null
  description?: string | null
  keywords?: string | null
}) {
  document.title = options.title
    ? `${options.title} | ${SITE_NAME}`
    : SITE_NAME

  if (options.description) {
    setMeta('description', options.description)
  } else {
    removeMeta('description')
  }

  if (options.keywords) {
    setMeta('keywords', options.keywords)
  } else {
    removeMeta('keywords')
  }

  onUnmounted(() => {
    document.title = SITE_NAME
    removeMeta('description')
    removeMeta('keywords')
  })
}
