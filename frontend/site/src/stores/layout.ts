import { defineStore } from 'pinia'
import axios from '@/plugins/axios'
import { useNotificationStore } from '@/stores/notification'

export interface ApiArticle {
  id: number
  title: string
  slug: string
  excerpt?: string
  image?: string
  views?: number
  published_at?: string
  author?: { name: string; image?: string }
  subcategory?: {
    id: number
    name: string
    slug: string
    category?: { id: number; name: string; slug: string }
  }
  tags?: { id: number; name: string; slug: string }[]
}

export interface NavPost {
  title: string
  image: string
  date: string
  category_name: string
  category_slug: string
  subcategory_slug: string
  page_slug: string
}

export interface NavTab {
  id: number
  label: string
  slug: string
  posts: NavPost[]
}

export interface NavItem {
  id: number
  label: string
  slug: string
  color: string
  tabs: NavTab[]
}

export interface Subcategory {
  id: number
  name: string
  slug: string
  category_id: number
}

export interface Category {
  id: number
  name: string
  slug: string
  subcategories: Subcategory[]
}

export interface Tag {
  id: number
  name: string
  slug: string
}

export interface FooterPost {
  title: string
  slug: string
  image: string
  date: string
  category_name: string
  category_slug: string
  subcategory_slug: string
}

export interface ArchiveItem {
  year: number
  month: number
  label: string
  count: number
}

export const useLayoutStore = defineStore('layout', {
  state: () => ({
    loaded: false,
    nav: [] as NavItem[],
    categories: [] as Category[],
    all_tags: [] as Tag[],
    tags: [] as Tag[],
    footer: {
      popular_posts: [] as FooterPost[],
    },
    sidebar: {
      archive: [] as ArchiveItem[],
    },
    subcategory_cache: {} as Record<string, ApiArticle[]>,
    _ws: null as WebSocket | null,
    _ws_user_id: null as number | null,
    _article_ids: [] as number[],
  }),

  actions: {
    fetchLayout() {
      if (this.loaded) return

      axios.get('layout')
        .then((res) => {
          const data = res.data.data
          this.nav = data.nav
          this.categories = data.categories
          this.all_tags = data.tags
          this.footer = data.footer
          this.sidebar = data.sidebar ?? { archive: [] }
          this.loaded = true
          this.shuffleTags()
        })

      this.connectWebSocket()
    },

    shuffleTags() {
      this.tags = [...this.all_tags].sort(() => Math.random() - 0.5).slice(0, 15)
    },

    connectWebSocket() {
      const url = import.meta.env.VITE_WS_URL ?? 'ws://127.0.0.1:6001'
      const ws = new WebSocket(url)
      this._ws = ws

      ws.onopen = () => {
        if (this._ws_user_id) {
          ws.send(JSON.stringify({ type: 'auth', user_id: this._ws_user_id }))
        }
        this._article_ids.forEach((article_id) => {
          ws.send(JSON.stringify({ type: 'subscribe_article', article_id }))
        })
      }

      ws.onmessage = (event) => {
        try {
          const { event: name, data } = JSON.parse(event.data)
          if (name === 'notification') {
            if (data.action === 'deleted') {
              useNotificationStore().removeNotification(data.id)
            } else if (data.action === 'unread_count') {
              useNotificationStore().setUnreadCount(data.count)
            } else {
              useNotificationStore().upsertNotification(data.notification)
            }
          } else if (name === 'comment.new') {
            window.dispatchEvent(new CustomEvent('ws:comment.new', { detail: data }))
          } else if (name === 'comment.updated') {
            window.dispatchEvent(new CustomEvent('ws:comment.updated', { detail: data }))
          } else if (name === 'comment.deleted') {
            window.dispatchEvent(new CustomEvent('ws:comment.deleted', { detail: data }))
          } else if (name === 'comment.restored') {
            window.dispatchEvent(new CustomEvent('ws:comment.restored', { detail: data }))
          } else if (name === 'like.updated') {
            window.dispatchEvent(new CustomEvent('ws:like.updated', { detail: data }))
          }
        } catch {}
      }

      ws.onclose = () => {
        this._ws = null
        setTimeout(() => this.connectWebSocket(), 3000)
      }

      ws.onerror = () => {
        ws.close()
      }
    },

    subscribeArticle(article_id: number) {
      if (!this._article_ids.includes(article_id)) {
        this._article_ids.push(article_id)
      }
      if (this._ws?.readyState === WebSocket.OPEN) {
        this._ws.send(JSON.stringify({ type: 'subscribe_article', article_id }))
      }
    },

    unsubscribeArticle(article_id: number) {
      this._article_ids = this._article_ids.filter((id) => id !== article_id)
      if (this._ws?.readyState === WebSocket.OPEN) {
        this._ws.send(JSON.stringify({ type: 'unsubscribe_article', article_id }))
      }
    },

    authenticateWS(user_id: number) {
      this._ws_user_id = user_id
      if (this._ws?.readyState === WebSocket.OPEN) {
        this._ws.send(JSON.stringify({ type: 'auth', user_id }))
      }
    },

    fetchSubcategoryNews(category_slug: string, slug: string): Promise<ApiArticle[]> {
      if (this.subcategory_cache[slug]) {
        return Promise.resolve(this.subcategory_cache[slug])
      }

      return axios.get(`news/${category_slug}/${slug}`)
        .then((res) => {
          const articles: ApiArticle[] = res.data.data
          this.subcategory_cache[slug] = articles
          return articles
        })
    },
  },
})

const _apiBase = import.meta.env.VITE_API_BASE_URL ?? ''

export function toImageUrl(path?: string | null): string {
  if (!path) return ''
  if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('/')) return path
  return _apiBase + path
}

export function toArticleDate(published_at?: string): string {
  if (!published_at) return ''
  return new Date(published_at).toLocaleDateString('en', { month: 'short', day: 'numeric', year: 'numeric' })
}

export function articleRoute(article: ApiArticle) {
  return {
    name: 'news_page',
    params: {
      category: article.subcategory?.category?.slug ?? '',
      subcategory: article.subcategory?.slug ?? '',
      slug: article.slug,
    },
  }
}
