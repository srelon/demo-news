import axiosLib from 'axios';
import Cookies from 'js-cookie';
import router from "@/routes/router.ts";


const axios = axiosLib.create({
    baseURL: import.meta.env.VITE_API_BASE_URL+'api',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    },
})

axios.defaults.withCredentials  = true // allow sending cookies


axios.interceptors.request.use(async (config) => {
    if ((config.method as string).toLowerCase() !== 'get' && !Cookies.get('XSRF-TOKEN')) {
        await axios.get(import.meta.env.VITE_API_BASE_URL+'api/csrf-cookie').then();
    }

    return config
})


// Global error handler
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const response = error.response

        if (!response) {
            console.error(error)
            return Promise.reject(error)
        }

        // Handle known error statuses
        if (response.status === 401) {
            console.error('Validation errors', response.data)
        } else if (response.status === 404) {
            router.push({ name: 'error_404' })
        } else if (response.data?.errors) {
            console.error('Validation errors', response.data.errors)
        } else if (response.data?.message) {
            console.error('Validation errors', response.data.message)
        }

        return Promise.reject(error) // propagate so local .catch() handlers still fire
    }
)


export default axios