const Redis = require('ioredis')
const logger = require('./logger')

const redis = new Redis({
    host: process.env.REDIS_HOST || '127.0.0.1',
    port: Number(process.env.REDIS_PORT) || 6379,
    password: process.env.REDIS_PASSWORD || undefined,
    retryStrategy: (times) => Math.min(times * 500, 5000),
    reconnectOnError: () => true,
})

redis.on('error', (err) => logger.error({ err: err.message }, '[Redis] Connection error'))
redis.on('connect', () => logger.info('[Redis] Connected'))
redis.on('reconnecting', () => logger.warn('[Redis] Reconnecting...'))

module.exports = redis
