<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import axios from '@/plugins/axios.ts'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import RecentNews from '@/components/dashboard/RecentNews.vue'
import RecentComments from '@/components/dashboard/RecentComments.vue'
import MetricCard from '@/components/ui/MetricCard.vue'
import BarChart from '@/components/ui/BarChart.vue'
import AreaChart from '@/components/ui/AreaChart.vue'

const stats = ref<any>(null)
const left_col = ref<HTMLElement | null>(null)
const right_height = ref('auto')
let ro: ResizeObserver | null = null

const statistics_series = computed(() => {
    if (!stats.value) return []
    return [
        { name: 'Comments', data: stats.value.statistics.comments },
        { name: 'Users', data: stats.value.statistics.users },
        { name: 'News', data: stats.value.statistics.news },
    ]
})

onMounted(() => {
    axios.get('stats').then((res) => {
        stats.value = res.data.data
        nextTick(() => {
            if (!left_col.value) return
            ro = new ResizeObserver(() => {
                right_height.value = left_col.value!.offsetHeight + 'px'
            })
            ro.observe(left_col.value)
        })
    })
})

onUnmounted(() => ro?.disconnect())
</script>

<template>
    <AdminLayout>
        <div v-if="stats" class="grid grid-cols-12 gap-4 md:gap-6">
            <div ref="left_col" class="col-span-12 xl:col-span-6 space-y-4 md:space-y-6 xl:self-start">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6">
                    <MetricCard label="Users" :value="stats.users.total" :growth="stats.users.growth">
                        <template #icon>
                            <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M8.80443 5.60156C7.59109 5.60156 6.60749 6.58517 6.60749 7.79851C6.60749 9.01185 7.59109 9.99545 8.80443 9.99545C10.0178 9.99545 11.0014 9.01185 11.0014 7.79851C11.0014 6.58517 10.0178 5.60156 8.80443 5.60156ZM5.10749 7.79851C5.10749 5.75674 6.76267 4.10156 8.80443 4.10156C10.8462 4.10156 12.5014 5.75674 12.5014 7.79851C12.5014 9.84027 10.8462 11.4955 8.80443 11.4955C6.76267 11.4955 5.10749 9.84027 5.10749 7.79851ZM4.86252 15.3208C4.08769 16.0881 3.70377 17.0608 3.51705 17.8611C3.48384 18.0034 3.5211 18.1175 3.60712 18.2112C3.70161 18.3141 3.86659 18.3987 4.07591 18.3987H13.4249C13.6343 18.3987 13.7992 18.3141 13.8937 18.2112C13.9797 18.1175 14.017 18.0034 13.9838 17.8611C13.7971 17.0608 13.4132 16.0881 12.6383 15.3208C11.8821 14.572 10.6899 13.955 8.75042 13.955C6.81096 13.955 5.61877 14.572 4.86252 15.3208ZM3.8071 14.2549C4.87163 13.2009 6.45602 12.455 8.75042 12.455C11.0448 12.455 12.6292 13.2009 13.6937 14.2549C14.7397 15.2906 15.2207 16.5607 15.4446 17.5202C15.7658 18.8971 14.6071 19.8987 13.4249 19.8987H4.07591C2.89369 19.8987 1.73504 18.8971 2.05628 17.5202C2.28015 16.5607 2.76117 15.2906 3.8071 14.2549ZM15.3042 11.4955C14.4702 11.4955 13.7006 11.2193 13.0821 10.7533C13.3742 10.3314 13.6054 9.86419 13.7632 9.36432C14.1597 9.75463 14.7039 9.99545 15.3042 9.99545C16.5176 9.99545 17.5012 9.01185 17.5012 7.79851C17.5012 6.58517 16.5176 5.60156 15.3042 5.60156C14.7039 5.60156 14.1597 5.84239 13.7632 6.23271C13.6054 5.73284 13.3741 5.26561 13.082 4.84371C13.7006 4.37777 14.4702 4.10156 15.3042 4.10156C17.346 4.10156 19.0012 5.75674 19.0012 7.79851C19.0012 9.84027 17.346 11.4955 15.3042 11.4955ZM19.9248 19.8987H16.3901C16.7014 19.4736 16.9159 18.969 16.9827 18.3987H19.9248C20.1341 18.3987 20.2991 18.3141 20.3936 18.2112C20.4796 18.1175 20.5169 18.0034 20.4837 17.861C20.2969 17.0607 19.913 16.088 19.1382 15.3208C18.4047 14.5945 17.261 13.9921 15.4231 13.9566C15.2232 13.6945 14.9995 13.437 14.7491 13.1891C14.5144 12.9566 14.262 12.7384 13.9916 12.5362C14.3853 12.4831 14.8044 12.4549 15.2503 12.4549C17.5447 12.4549 19.1291 13.2008 20.1936 14.2549C21.2395 15.2906 21.7206 16.5607 21.9444 17.5202C22.2657 18.8971 21.107 19.8987 19.9248 19.8987Z" fill="" />
                            </svg>
                        </template>
                    </MetricCard>
                    <MetricCard label="News" :value="stats.news.total" :growth="stats.news.growth">
                        <template #icon>
                            <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M4 4a2 2 0 012-2h12a2 2 0 012 2v16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0h12v16H6V4zm2 3a1 1 0 000 2h8a1 1 0 100-2H8zm0 4a1 1 0 000 2h8a1 1 0 100-2H8zm0 4a1 1 0 000 2h4a1 1 0 100-2H8z" fill="" />
                            </svg>
                        </template>
                    </MetricCard>
                </div>
                <BarChart
                    title="Comments this month"
                    :labels="stats.daily_comments.days"
                    :series="stats.daily_comments.series"
                    tooltip_suffix=" comments"
                />
                <AreaChart
                    title="Statistics"
                    subtitle="Comments, users and news over the last 12 months"
                    :labels="stats.statistics.labels"
                    :series="statistics_series"
                    :colors="['#465FFF', '#17b978', '#f59e0b']"
                />
                <RecentNews :news="stats.recent_news" />
            </div>

            <div class="col-span-12 xl:col-span-6 overflow-hidden" :style="{ height: right_height }">
                <RecentComments />
            </div>
        </div>

        <div v-else class="flex items-center justify-center h-64">
            <div class="w-8 h-8 border-4 border-brand-500 border-t-transparent rounded-full animate-spin"></div>
        </div>
    </AdminLayout>
</template>
