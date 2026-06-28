import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
// import vueDevTools from 'vite-plugin-vue-devtools'
import path from 'path';

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd())

    return {
        base: env.VITE_ADMIN_BASE ?? '/admin/',
        plugins: [
            vue(),
            vueJsx(),
        ],
        server: {
            host: '0.0.0.0',
            port: 5200,
            hmr: {
                host: 'localhost',
                port: 5200,
            },
        },
        resolve: {
            alias: [
                { find: '@', replacement: path.resolve(__dirname, 'src') },
                { find: '@public', replacement: path.resolve(__dirname, 'public') },
                { find: '@assets', replacement: path.resolve(__dirname, 'src/assets') },
            ],
        },
    }
})
