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

function setOg(property: string, content: string) {
    let el = document.querySelector(`meta[property="${property}"]`) as HTMLMetaElement | null

    if (!el) {
        el = document.createElement('meta')
        el.setAttribute('property', property)
        document.head.appendChild(el)
    }

    el.setAttribute('content', content)
}

function removeOg(property: string) {
    document.querySelector(`meta[property="${property}"]`)?.remove()
}

const OG_PROPS = ['og:title', 'og:description', 'og:image', 'og:url', 'og:type', 'og:site_name']

export function useSeoMeta(options: {
    title?: string | null
    description?: string | null
    keywords?: string | null
    image?: string | null
    url?: string | null
}) {
    const full_title = options.title ? `${options.title} | ${SITE_NAME}` : SITE_NAME
    document.title = full_title

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

    // Open Graph
    setOg('og:type', 'article')
    setOg('og:site_name', SITE_NAME)
    setOg('og:title', full_title)
    setOg('og:url', options.url ?? window.location.href)

    if (options.description) {
        setOg('og:description', options.description)
    } else {
        removeOg('og:description')
    }

    if (options.image) {
        setOg('og:image', options.image)
        setMeta('twitter:card', 'summary_large_image')
        setMeta('twitter:image', options.image)
    } else {
        removeOg('og:image')
        removeMeta('twitter:card')
        removeMeta('twitter:image')
    }

    onUnmounted(() => {
        document.title = SITE_NAME
        removeMeta('description')
        removeMeta('keywords')
        removeMeta('twitter:card')
        removeMeta('twitter:image')
        OG_PROPS.forEach(removeOg)
    })
}
