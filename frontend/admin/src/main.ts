import { createApp } from 'vue'
import { createPinia } from 'pinia'
import '@assets/css/main.css'
import 'jsvectormap/dist/jsvectormap.css'
import 'flatpickr/dist/flatpickr.css'

import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
import { useAuthStore } from '@/stores/auth'



import router from './routes/router.ts'
import App from './App.vue'
import VueApexCharts from 'vue3-apexcharts'

const app = createApp(App)

const pinia = createPinia()


app.use(Toast, {
    position: "top-right",
    timeout: 3000,
});

app.use(pinia)
app.use(VueApexCharts)


function init() {
    const auth = useAuthStore()

    auth.fetchUser().then(() => {
        app.use(router)
        app.mount('#app')
    })
}

init();