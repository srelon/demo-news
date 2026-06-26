const pino = require('pino')
const path = require('path')

const is_dev = process.env.NODE_ENV !== 'production'
const log_level = process.env.LOG_LEVEL || 'info'

const logger = pino(
    {
        level: log_level,
        timestamp: pino.stdTimeFunctions.isoTime,
    },
    pino.transport({
        targets: [
            {
                target: 'pino-roll',
                level: log_level,
                options: {
                    file: path.join(__dirname, 'logs', 'websocket.log'),
                    frequency: 'daily',
                    size: '20m',
                    mkdir: true,
                },
            },
            {
                target: is_dev ? 'pino-pretty' : 'pino/file',
                level: log_level,
                options: is_dev
                    ? {
                        colorize: true,
                        translateTime: 'SYS:HH:MM:ss',
                        ignore: 'pid,hostname',
                    }
                    : { destination: 1 },
            },
        ],
    })
)

module.exports = logger
