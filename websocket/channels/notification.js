const WebSocket = require('ws')
const logger = require('../logger')

// user_id → Set<ws>
const connections = new Map()

/**
 * Authenticate a WS client for a user_id and subscribe to their notification channel.
 * Supports multiple auth calls per connection (for admin multi-account subscriptions).
 */
function subscribe(userId, ws, redis) {
    if (!ws._notifUserIds) ws._notifUserIds = new Set()
    ws._notifUserIds.add(userId)

    if (!connections.has(userId)) {
        connections.set(userId, new Set())
        redis.subscribe(`notification.${userId}`, (err) => {
            if (err) logger.error({ err, user_id: userId }, '[Notification] Failed to subscribe')
            else logger.info({ user_id: userId }, '[Notification] Subscribed')
        })
    }

    connections.get(userId).add(ws)
    logger.info({ user_id: userId }, '[Notification] User authenticated on WS')
}

/**
 * Remove a WS client from all user notification sets on disconnect.
 * Drops Redis subscriptions for users with no remaining connections.
 */
function handleClose(ws, redis) {
    if (!ws._notifUserIds || ws._notifUserIds.size === 0) return

    ws._notifUserIds.forEach((userId) => {
        const conns = connections.get(userId)
        if (!conns) return
        conns.delete(ws)
        if (conns.size === 0) {
            connections.delete(userId)
            redis.unsubscribe(`notification.${userId}`, (err) => {
                if (!err) logger.info({ user_id: userId }, '[Notification] Unsubscribed')
            })
        }
    })
}

/**
 * Forward an incoming Redis notification message to all WS connections for that user.
 */
function handleMessage(userId, message) {
    const conns = connections.get(userId)
    if (!conns || conns.size === 0) return

    const payload = JSON.stringify({ event: 'notification', data: JSON.parse(message) })
    conns.forEach((ws) => {
        if (ws.readyState === WebSocket.OPEN) {
            ws.send(payload)
        }
    })
}

module.exports = { subscribe, handleClose, handleMessage }
