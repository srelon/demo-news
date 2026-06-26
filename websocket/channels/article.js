const WebSocket = require('ws')
const logger = require('../logger')

// article_id → Set<ws>
const connections = new Map()

/**
 * Subscribe a WS client to an article channel.
 * Creates a Redis subscription the first time anyone subscribes to that article.
 */
function subscribe(articleId, ws, redis) {
    ws._articleIds.add(articleId)

    if (!connections.has(articleId)) {
        connections.set(articleId, new Set())
        redis.subscribe(`article.${articleId}`, (err) => {
            if (err) logger.error({ err, article_id: articleId }, '[Article] Failed to subscribe')
            else logger.info({ article_id: articleId }, '[Article] Subscribed')
        })
    }

    connections.get(articleId).add(ws)
}

/**
 * Unsubscribe a WS client from an article channel.
 * Drops the Redis subscription when the last client leaves.
 */
function unsubscribe(articleId, ws, redis) {
    const conns = connections.get(articleId)
    if (!conns) return

    conns.delete(ws)

    if (conns.size === 0) {
        connections.delete(articleId)
        redis.unsubscribe(`article.${articleId}`, (err) => {
            if (!err) logger.info({ article_id: articleId }, '[Article] Unsubscribed')
        })
    }
}

/**
 * Remove a WS client from all article subscriptions on disconnect.
 */
function handleClose(ws, redis) {
    ws._articleIds.forEach((articleId) => unsubscribe(articleId, ws, redis))
    ws._articleIds.clear()
}

/**
 * Forward an incoming Redis message to all WS clients subscribed to that article.
 * Message is already a serialised JSON string from PHP — pass through as-is.
 */
function handleMessage(articleId, message) {
    const conns = connections.get(articleId)
    if (!conns || conns.size === 0) return

    conns.forEach((ws) => {
        if (ws.readyState === WebSocket.OPEN) {
            ws.send(message)
        }
    })
}

module.exports = { subscribe, unsubscribe, handleClose, handleMessage }
