const WebSocket = require('ws')
const redis = require('./redis')
const logger = require('./logger')
const article = require('./channels/article')
const notification = require('./channels/notification')
const comments = require('./channels/comments')

const WS_PORT = process.env.WS_PORT || 6001

const wss = new WebSocket.Server({ port: Number(WS_PORT) })

// ── Redis subscriptions ───────────────────────────────────────────────────────

redis.on('message', (channel, message) => {
    if (channel === 'comments.all') {
        comments.handleMessage(message)
        return
    }

    if (channel.startsWith('notification.')) {
        const userId = parseInt(channel.split('.')[1], 10)
        notification.handleMessage(userId, message)
        return
    }

    if (channel.startsWith('article.')) {
        const articleId = parseInt(channel.split('.')[1], 10)
        article.handleMessage(articleId, message)
    }
})

// ── WebSocket connections ─────────────────────────────────────────────────────

wss.on('connection', (ws) => {
    ws._userId = null
    ws._articleIds = new Set()
    ws._subscribed_comments = false

    ws.on('message', (raw) => {
        try {
            const msg = JSON.parse(raw)

            if (msg.type === 'auth' && msg.user_id) {
                const userId = parseInt(msg.user_id, 10)
                if (!isNaN(userId)) notification.subscribe(userId, ws, redis)
                return
            }

            if (msg.type === 'subscribe_article' && msg.article_id) {
                const articleId = parseInt(msg.article_id, 10)
                if (!isNaN(articleId)) article.subscribe(articleId, ws, redis)
                return
            }

            if (msg.type === 'unsubscribe_article' && msg.article_id) {
                const articleId = parseInt(msg.article_id, 10)
                if (!isNaN(articleId)) article.unsubscribe(articleId, ws, redis)
                return
            }

            if (msg.type === 'subscribe_comments') {
                comments.subscribe(ws, redis)
                return
            }

            if (msg.type === 'unsubscribe_comments') {
                comments.unsubscribe(ws)
            }
        } catch (err) {
            logger.warn({ err }, '[WS] Failed to parse client message')
        }
    })

    ws.on('close', () => {
        notification.handleClose(ws, redis)
        article.handleClose(ws, redis)
        comments.handleClose(ws)
    })

    ws.on('error', (err) => logger.error({ err }, '[WS] Client error'))
})

logger.info({ port: WS_PORT }, '[WS] Server started')
