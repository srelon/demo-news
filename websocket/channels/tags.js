const WebSocket = require('ws')

/**
 * Broadcast tags update to every connected client.
 * @param {WebSocket.Server} wss
 * @param {any} data
 */
function broadcast(wss, data) {
    const payload = JSON.stringify({ event: 'tags.updated', data })
    wss.clients.forEach((client) => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(payload)
        }
    })
}

module.exports = { broadcast }
