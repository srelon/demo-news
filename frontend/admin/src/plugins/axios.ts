import axiosLib from 'axios';
import Cookies from 'js-cookie';
import router from "@/routes/router.ts";

import { useToast } from 'vue-toastification'

const toast = useToast();

const axios = axiosLib.create({
    baseURL: import.meta.env.VITE_API_BASE_URL+'api/admin',
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

        // Requests can opt out of global error handling
        if ((error.config as any)?.skipGlobalError) {
            return Promise.reject(error)
        }

        // Handle known error statuses
        if (response.status === 401) {
            console.error('Validation errors', response.data)
        } else if (response.status === 404) {
            router.push({ name: 'error_404' })
        } else if (response.data?.message) {
            toast.error(response.data.message);
        } else if (response.data?.errors) {
            console.error('Validation errors', response.data.errors)
            toast.error(response.data.errors);
        }

        return Promise.reject(error) // propagate so local .catch() handlers still fire
    }
)


export default axios