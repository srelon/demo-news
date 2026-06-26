const WebSocket = require('ws')
const logger = require('../logger')

// All admin clients subscribed to the global comments feed
const connections = new Set()
let redis_subscribed = false

function subscribe(ws, redis) {
    ws._subscribed_comments = true
    connections.add(ws)

    if (!redis_subscribed) {
        redis_subscribed = true
        redis.subscribe('comments.all', (err) => {
            if (err) logger.error({ err }, '[Comments] Subscribe error')
            else logger.info('[Comments] Subscribed to: comments.all')
        })
    }
}

function unsubscribe(ws) {
    ws._subscribed_comments = false
    connections.delete(ws)
    // Keep the Redis subscription alive — no per-client Redis overhead
}

function handleClose(ws) {
    connections.delete(ws)
}

function handleMessage(message) {
    connections.forEach((ws) => {
        if (ws.readyState === WebSocket.OPEN) {
            ws.send(message)
        }
    })
}

module.exports = { subscribe, unsubscribe, handleClose, handleMessage }
