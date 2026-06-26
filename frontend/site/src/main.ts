import { createApp } from 'vue'
import 'bootstrap/dist/css/bootstrap.min.css'
import './assets/fonts/font-awesome-4.7.0/css/font-awesome.min.css'
import './assets/fonts/fontawesome-5.0.8/css/fontawesome-all.min.css'
import './assets/fonts/iconic/css/material-design-iconic-font.min.css'
import 'hamburgers/dist/hamburgers.min.css'
import './assets/css/util.min.css'
import './assets/css/main.css'
import './assets/css/auth.css'

import { createPinia } from 'pinia'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import router from './routes/router.ts'
import App from './App.vue'
import { useAuthStore } from '@/stores/auth'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(Toast)

function init() {
    const auth = useAuthStore()

    auth.fetchUser().then(() => {
        app.use(router)
        app.mount('#app')
    })
}

init()
