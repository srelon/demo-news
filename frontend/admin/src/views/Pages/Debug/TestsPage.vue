<script setup lang="ts">
import ChevronDownOutlineIcon from '@/icons/ChevronDownOutlineIcon.vue'
import CloseIcon from '@/icons/CloseIcon.vue'
import PlayCircleIcon from '@/icons/PlayCircleIcon.vue'
import SpinnerIcon from '@/icons/SpinnerIcon.vue'
import { ref, computed, onMounted } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import BaseLoading from '@/components/ui/base/BaseLoading.vue'
import BaseModal from '@/components/ui/base/BaseModal.vue'
import axios from '@/plugins/axios.ts'

interface TestItem  { name: string; filter: string }
interface SuiteItem { name: string; class: string; type: string; group: string; filter: string; tests: TestItem[] }

interface HttpCall  { method: string; url: string; status: number | null; body: any; response: any }
interface TestCase  { name: string; status: 'passed' | 'failed' | 'error' | 'skipped'; time: number; message: string | null; http: HttpCall | null }
interface TestSuite { name: string; tests: number; failures: number; errors: number; time: number; cases: TestCase[] }
interface Summary   { total: number; passed: number; failed: number; time: number }
interface Results   { suites: TestSuite[]; summary: Summary; output: string | null; error: string | null; exit_code: number; command: string }

const suites      = ref<SuiteItem[]>([])
const loadingList = ref(true)
const expanded    = ref<Record<string, boolean>>({})
const groupOpen   = ref<Record<string, boolean>>({ Admin: true, Site: true, Unit: false })

const groups = computed(() => {
  const map: Record<string, SuiteItem[]> = {}
  suites.value.forEach(s => {
    ;(map[s.group] ??= []).push(s)
  })
  return map
})

const running  = ref(false)
const activeFilter = ref<string | null>(null)
const results  = ref<Results | null>(null)
const resExpanded = ref<Record<string, boolean>>({})

interface ParsedFailure {
  title: string
  assertion: string
  stack: string
  request: { method: string; url: string; status: number | null; body: any } | null
  response: any
}

const modal = ref<ParsedFailure | null>(null)

function shortMessage(msg: string): string {
  const first = msg.split('\n').find(l => l.trim() && !l.includes('::test_'))?.trim() ?? ''
  return first.replace(/^(Expected|Failed asserting)/, m => m)
}

function parseFailure(tc: TestCase, suiteName: string): ParsedFailure {
  const raw = tc.message ?? ''

  const assertionLines = raw.split('\n')
    .filter(l => l.trim() && !l.match(/^Tests\\/))
    .filter(l => !l.match(/^\/var\/www\//))
    .filter(l => !l.startsWith('{') && !l.startsWith('}') && !l.includes('"status"') && !l.includes('"errors"') && !l.includes('"message"') && !l.includes('occurred during'))
  const assertion = assertionLines.join('\n').trim()

  const stack = raw.split('\n').filter(l => l.match(/^\/var\/www\//)).join('\n')

  return {
    title: `${suiteName} › ${tc.name}`,
    assertion,
    stack,
    request: tc.http ? { method: tc.http.method, url: tc.http.url, status: tc.http.status, body: tc.http.body } : null,
    response: tc.http?.response ?? null,
  }
}

function openModal(tc: TestCase, suiteName: string) {
  modal.value = parseFailure(tc, suiteName)
}

onMounted(() => {
  axios.get('tests/list').then(res => {
    suites.value = res.data.data.suites
  }).finally(() => { loadingList.value = false })
})

function toggleSuite(name: string) {
  expanded.value[name] = !expanded.value[name]
}

function run(filter: string | null, label: string) {
  running.value    = true
  activeFilter.value = label
  results.value    = null
  resExpanded.value = {}

  axios.post('tests/run', { filter: filter ?? undefined }).then(res => {
    results.value = res.data.data
    results.value?.suites.forEach(s => {
      if (s.failures + s.errors > 0) resExpanded.value[s.name] = true
    })
  }).finally(() => { running.value = false })
}

function toggleRes(name: string) {
  resExpanded.value[name] = !resExpanded.value[name]
}

function suitePassed(s: TestSuite) { return s.failures + s.errors === 0 }

function statusCls(status: string): string {
  const map: Record<string, string> = {
    passed:  'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400',
    failed:  'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400',
    error:   'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400',
    skipped: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
  }
  return map[status] ?? 'bg-gray-100 text-gray-500'
}
</script>

<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="Tests" />

    <div class="flex gap-5 items-start">

      <!-- ── Left: test list ── -->
      <div class="w-72 shrink-0 space-y-3">

        <!-- Run all -->
        <button
          @click="run(null, 'All tests')"
          :disabled="running"
          class="flex w-full items-center justify-between rounded-xl border border-brand-300 bg-brand-50 px-4 py-3 text-sm font-semibold text-brand-700 transition hover:bg-brand-100 disabled:opacity-50 dark:border-brand-700 dark:bg-brand-500/10 dark:text-brand-400 dark:hover:bg-brand-500/20"
        >
          <span>Run all tests</span>
          <PlayCircleIcon class="h-4 w-4" />
        </button>

        <BaseLoading v-if="loadingList" />

        <!-- Groups -->
        <div v-for="(groupSuites, groupName) in groups" :key="groupName" class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">

          <!-- Group header -->
          <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2.5 dark:border-gray-800">
            <button @click="groupOpen[groupName] = !groupOpen[groupName]" class="flex flex-1 items-center gap-2 text-left">
              <span class="text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-white/80">{{ groupName }}</span>
              <span class="rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ groupSuites.length }}</span>
              <ChevronDownOutlineIcon :class="['ml-auto h-3.5 w-3.5 shrink-0 text-gray-400 transition-transform duration-200', groupOpen[groupName] ? 'rotate-180' : '']" />
            </button>
            <button @click.stop="run(groupSuites.map(s => s.filter).join('|'), groupName + ' tests')" :disabled="running" title="Run group"
              class="ml-2 shrink-0 rounded p-1 text-gray-400 transition hover:bg-brand-50 hover:text-brand-600 disabled:opacity-40 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
              <PlayCircleIcon class="h-4 w-4" />
            </button>
          </div>

          <!-- Suites inside group -->
          <div v-show="groupOpen[groupName]">
            <div v-for="suite in groupSuites" :key="suite.name" class="border-b border-gray-50 last:border-0 dark:border-gray-800/50">

              <!-- Suite header -->
              <div class="flex items-center justify-between px-3 py-2">
                <button @click="toggleSuite(suite.name)" class="flex flex-1 items-center gap-2 text-left">
                  <span class="text-sm text-gray-700 dark:text-gray-300">{{ suite.name }}</span>
                  <ChevronDownOutlineIcon :class="['ml-auto h-3 w-3 shrink-0 text-gray-400 transition-transform duration-200', expanded[suite.name] ? 'rotate-180' : '']" />
                </button>
                <button @click.stop="run(suite.filter, suite.name)" :disabled="running" title="Run suite"
                  class="ml-2 shrink-0 rounded p-1 text-gray-400 transition hover:bg-brand-50 hover:text-brand-600 disabled:opacity-40 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
                  <PlayCircleIcon class="h-3.5 w-3.5" />
                </button>
              </div>

              <!-- Test cases -->
              <div v-show="expanded[suite.name]" class="border-t border-gray-50 dark:border-gray-800/50">
                <div v-for="test in suite.tests" :key="test.filter"
                  class="group flex items-center justify-between gap-2 border-b border-gray-50 py-1.5 pl-6 pr-3 last:border-0 dark:border-gray-800/50">
                  <span class="text-xs text-gray-500 dark:text-gray-400">{{ test.name }}</span>
                  <button @click="run(test.filter, test.name)" :disabled="running" title="Run test"
                    class="shrink-0 rounded p-0.5 text-gray-300 opacity-0 transition group-hover:opacity-100 hover:bg-brand-50 hover:text-brand-600 disabled:opacity-40 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
                    <PlayCircleIcon class="h-3 w-3" />
                  </button>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- ── Right: results ── -->
      <div class="min-w-0 flex-1 space-y-4">

        <!-- Running indicator -->
        <div v-if="running" class="flex items-center gap-3 rounded-xl border border-brand-200 bg-brand-50 px-5 py-4 dark:border-brand-700 dark:bg-brand-500/10">
          <SpinnerIcon class="h-5 w-5 animate-spin text-brand-600 dark:text-brand-400" />
          <span class="text-sm font-medium text-brand-700 dark:text-brand-300">Running: {{ activeFilter }}…</span>
        </div>

        <!-- Empty state -->
        <div
          v-else-if="!results"
          class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 bg-white py-20 text-center dark:border-gray-800 dark:bg-white/[0.03]"
        >
          <svg class="mb-3 h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
          <p class="text-sm text-gray-400 dark:text-gray-500">Select a suite or test to run</p>
        </div>

        <template v-else>

          <!-- Summary -->
          <div class="grid grid-cols-4 gap-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-white/[0.03]">
              <p class="text-2xl font-bold text-gray-800 dark:text-white/90">{{ results.summary.total }}</p>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Total</p>
            </div>
            <div class="rounded-xl border border-success-200 bg-white p-4 text-center dark:border-success-800 dark:bg-white/[0.03]">
              <p class="text-2xl font-bold text-success-600 dark:text-success-400">{{ results.summary.passed }}</p>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Passed</p>
            </div>
            <div class="rounded-xl border border-error-200 bg-white p-4 text-center dark:border-error-800 dark:bg-white/[0.03]">
              <p class="text-2xl font-bold text-error-600 dark:text-error-400">{{ results.summary.failed }}</p>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Failed</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-white/[0.03]">
              <p class="text-2xl font-bold text-gray-800 dark:text-white/90">{{ results.summary.time }}s</p>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Time</p>
            </div>
          </div>

          <!-- Command & raw output -->
          <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5 dark:border-gray-800">
              <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Command</span>
              <span :class="['rounded px-2 py-0.5 text-xs font-medium', results.exit_code === 0 ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400' : 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400']">
                exit {{ results.exit_code }}
              </span>
            </div>
            <pre class="overflow-x-auto px-4 py-3 text-xs text-gray-600 dark:text-gray-400">{{ results.command }}</pre>

            <template v-if="results.output">
              <div class="border-t border-gray-100 px-4 py-2.5 dark:border-gray-800">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Output</span>
              </div>
              <pre class="max-h-60 overflow-y-auto px-4 pb-3 text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ results.output }}</pre>
            </template>

            <template v-if="results.error">
              <div class="border-t border-gray-100 px-4 py-2.5 dark:border-gray-800">
                <span class="text-xs font-semibold uppercase tracking-wide text-error-500">Stderr</span>
              </div>
              <pre class="max-h-60 overflow-y-auto px-4 pb-3 text-xs text-error-600 dark:text-error-400 whitespace-pre-wrap">{{ results.error }}</pre>
            </template>
          </div>

          <!-- Suite results -->
          <div class="space-y-3">
            <div
              v-for="suite in results.suites"
              :key="suite.name"
              :class="[
                'overflow-hidden rounded-xl border bg-white dark:bg-white/[0.03]',
                suitePassed(suite) ? 'border-gray-200 dark:border-gray-800' : 'border-error-200 dark:border-error-800'
              ]"
            >
              <button
                class="flex w-full items-center justify-between px-5 py-3.5 text-left"
                @click="toggleRes(suite.name)"
              >
                <div class="flex items-center gap-3">
                  <span
                    :class="[
                      'flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs',
                      suitePassed(suite)
                        ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400'
                        : 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400'
                    ]"
                  >{{ suitePassed(suite) ? '✓' : '✗' }}</span>
                  <span class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ suite.name }}</span>
                </div>
                <div class="flex items-center gap-4 text-xs text-gray-400 dark:text-gray-500">
                  <span>{{ suite.tests }} tests</span>
                  <span>{{ suite.time }}s</span>
                  <ChevronDownOutlineIcon :class="['h-4 w-4 transition-transform duration-200', resExpanded[suite.name] ? 'rotate-180' : '']" />
                </div>
              </button>

              <div v-show="resExpanded[suite.name]" class="border-t border-gray-100 dark:border-gray-800">
                <div
                  v-for="tc in suite.cases"
                  :key="tc.name"
                  class="border-b border-gray-50 px-5 py-2.5 last:border-0 dark:border-gray-800/50"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex flex-1 items-start gap-2.5">
                      <span :class="['shrink-0 rounded px-1.5 py-0.5 text-xs font-medium', statusCls(tc.status)]">
                        {{ tc.status }}
                      </span>
                      <div class="min-w-0">
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ tc.name }}</p>
                        <p v-if="tc.message" class="mt-0.5 truncate text-xs text-error-600 dark:text-error-400">
                          {{ shortMessage(tc.message) }}
                        </p>
                      </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                      <button
                        @click="openModal(tc, suite.name)"
                        class="rounded border border-gray-200 px-1.5 py-0.5 text-[10px] text-gray-500 hover:border-gray-400 hover:text-gray-700 dark:border-gray-700 dark:text-gray-400 dark:hover:border-gray-500 dark:hover:text-gray-200"
                      >Details</button>
                      <span class="text-xs text-gray-400">{{ tc.time }}s</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </template>
      </div>
    </div>
  </AdminLayout>

  <!-- Details modal -->
  <BaseModal v-if="modal" @close="modal = null">
    <template #body>
      <div class="relative flex w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900 mx-4" style="max-height:85vh">

        <!-- Header -->
        <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-5 py-3.5 dark:border-gray-800">
          <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ modal.title }}</p>
          <button @click="modal = null" class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800">
            <CloseIcon class="h-4 w-4" />
          </button>
        </div>

        <!-- Assertion error -->
        <div v-if="modal.assertion" class="shrink-0 border-b border-gray-100 bg-error-50 px-5 py-3 dark:border-gray-800 dark:bg-error-500/10">
          <p class="mb-1 text-[10px] font-bold uppercase tracking-wide text-error-400">Assertion</p>
          <p class="text-xs text-error-700 dark:text-error-300 whitespace-pre-wrap">{{ modal.assertion }}</p>
        </div>

        <!-- Two columns -->
        <div class="flex min-h-0 flex-1 divide-x divide-gray-100 dark:divide-gray-800 overflow-hidden">

          <!-- Left: Request -->
          <div class="flex w-1/2 shrink-0 flex-col overflow-hidden">
            <p class="shrink-0 border-b border-gray-100 px-4 py-2 text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:border-gray-800 dark:text-gray-500">Request</p>
            <div v-if="modal.request" class="flex min-h-0 flex-1 flex-col overflow-y-auto px-4 py-3 gap-2">
              <!-- Method + URL + Status -->
              <div class="flex flex-wrap items-center gap-2">
                <span class="rounded bg-brand-50 px-2 py-0.5 text-xs font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">{{ modal.request.method }}</span>
                <span :class="['rounded px-2 py-0.5 text-xs font-bold', (modal.request.status ?? 0) < 300 ? 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400' : 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400']">
                  {{ modal.request.status ?? '—' }}
                </span>
                <code class="break-all text-xs text-gray-600 dark:text-gray-400">{{ modal.request.url }}</code>
              </div>
              <!-- Body -->
              <div v-if="modal.request.body">
                <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Body</p>
                <pre class="rounded-lg bg-gray-50 px-3 py-2.5 text-xs text-gray-700 whitespace-pre-wrap dark:bg-gray-800 dark:text-gray-300">{{ JSON.stringify(modal.request.body, null, 2) }}</pre>
              </div>
              <p v-else class="text-xs text-gray-400 italic">No request body</p>
            </div>
            <p v-else class="px-4 py-3 text-xs text-gray-400 italic">No request captured</p>
          </div>

          <!-- Right: Response -->
          <div class="flex w-1/2 shrink-0 flex-col overflow-hidden">
            <p class="shrink-0 border-b border-gray-100 px-4 py-2 text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:border-gray-800 dark:text-gray-500">Response</p>
            <div v-if="modal.response" class="min-h-0 flex-1 overflow-y-auto px-4 py-3">
              <pre class="rounded-lg bg-gray-50 px-3 py-2.5 text-xs text-gray-700 whitespace-pre-wrap dark:bg-gray-800 dark:text-gray-300">{{ JSON.stringify(modal.response, null, 2) }}</pre>
            </div>
            <p v-else class="px-4 py-3 text-xs text-gray-400 italic">No response captured</p>
          </div>
        </div>

        <!-- Stack trace -->
        <div v-if="modal.stack" class="shrink-0 border-t border-gray-100 dark:border-gray-800">
          <pre class="max-h-28 overflow-y-auto px-5 py-3 text-[10px] leading-relaxed text-gray-400 whitespace-pre-wrap dark:text-gray-600">{{ modal.stack }}</pre>
        </div>

      </div>
    </template>
  </BaseModal>
</template>
