import { defineStore } from 'pinia'

type WsStatus = 'disconnected' | 'connecting' | 'connected'

let socket: WebSocket | null = null
let reconnect_timer: ReturnType<typeof setTimeout> | null = null
const subscribed_articles = new Set<number>()
let comments_subscribers = 0
const notif_user_ids: number[] = []

export const useWsStore = defineStore('ws', {
    state: (): { status: WsStatus } => ({
        status: 'disconnected',
    }),

    actions: {
        connect() {
            if (socket && socket.readyState <= WebSocket.OPEN) return

            const url = import.meta.env.VITE_WS_URL ?? 'ws://127.0.0.1:6001'
            this.status = 'connecting'
            socket = new WebSocket(url)

            socket.onopen = () => {
                this.status = 'connected'
                if (reconnect_timer) {
                    clearTimeout(reconnect_timer)
                    reconnect_timer = null
                }
                // Re-subscribe to all channels after (re)connect — subscribe calls
                // made while the socket was still connecting were dropped by _send
                subscribed_articles.forEach((article_id) => {
                    this._send({ type: 'subscribe_article', article_id })
                })
                if (comments_subscribers > 0) {
                    this._send({ type: 'subscribe_comments' })
                }
                notif_user_ids.forEach((user_id) => {
                    this._send({ type: 'auth', user_id })
                })
            }

            socket.onmessage = (event) => {
                try {
                    const msg = JSON.parse(event.data)
                    window.dispatchEvent(new CustomEvent(`admin:ws:${msg.event}`, { detail: msg.data }))
                } catch {}
            }

            socket.onclose = () => {
                this.status = 'disconnected'
                socket = null
                reconnect_timer = setTimeout(() => this.connect(), 3000)
            }

            socket.onerror = () => {
                socket?.close()
            }
        },

        disconnect() {
            if (reconnect_timer) {
                clearTimeout(reconnect_timer)
                reconnect_timer = null
            }
            socket?.close()
            socket = null
            this.status = 'disconnected'
        },

        subscribeComments() {
            comments_subscribers++
            this._send({ type: 'subscribe_comments' })
        },

        unsubscribeComments() {
            comments_subscribers = Math.max(0, comments_subscribers - 1)
            if (comments_subscribers === 0) {
                this._send({ type: 'unsubscribe_comments' })
            }
        },

        subscribeArticle(article_id: number) {
            subscribed_articles.add(article_id)
            this._send({ type: 'subscribe_article', article_id })
        },

        unsubscribeArticle(article_id: number) {
            subscribed_articles.delete(article_id)
            this._send({ type: 'unsubscribe_article', article_id })
        },

        subscribeNotifications(user_ids: number[]) {
            notif_user_ids.splice(0, notif_user_ids.length, ...user_ids)
            user_ids.forEach((user_id) => {
                this._send({ type: 'auth', user_id })
            })
        },

        _send(data: object) {
            if (socket && socket.readyState === WebSocket.OPEN) {
                socket.send(JSON.stringify(data))
            }
        },
    },
})
