<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import BaseBtn from '@/components/ui/base/BaseBtn.vue'

const model = defineModel<string>({
    required: true,
})

const props = withDefaults(defineProps<{
    submit_label: string
    loading?: boolean
    placeholder?: string
    max_len?: number
}>(), {
    placeholder: 'Write a comment...',
    max_len: 1000,
})

const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'cancel'): void
}>()

const editor = ref<HTMLDivElement | null>(null)
const emoji_btn = ref<HTMLButtonElement | null>(null)
const show_emoji = ref(false)
const char_count = ref(0)
const error = ref('')

const EMOJIS = [
    '😀','😂','😍','🥰','😎','😢','😡','🤔','👍','👎',
    '❤️','🔥','🎉','✅','❌','😅','🙏','💪','🤣','😏',
    '😱','🤯','😴','🥳','😇','🤗','😤','🙄','😒','💀',
    '👀','💯','🚀','✨','💡','🎯','🤝','👋','🫶','💬',
]

const TOOLS: [string, string, string][] = [
    [
        'bold',
        'Bold',
        'b',
    ],
    [
        'italic',
        'Italic',
        'i',
    ],
    [
        'underline',
        'Underline',
        'u',
    ],
    [
        'strikeThrough',
        'Strikethrough',
        's',
    ],
]

/**
 * execCommand('strikeThrough') inserts <strike>, but the backend sanitizer
 * only allows <b><i><u><s><a><br> — normalize before syncing to the model.
 */
function normalizeBody(html: string): string {
    return html
        .replace(/<strike>/g, '<s>')
        .replace(/<\/strike>/g, '</s>')
}

function exec(cmd: string) {
    editor.value?.focus()
    // Force tag output (<b>, <s>, ...) instead of styled spans in Firefox
    document.execCommand('styleWithCSS', false, 'false')
    document.execCommand(cmd, false)
    onInput()
}

function insertEmoji(emoji: string) {
    editor.value?.focus()
    document.execCommand('insertText', false, emoji)
    show_emoji.value = false
    onInput()
}

function updateCount() {
    const text = editor.value?.textContent?.trim() ?? ''
    char_count.value = text.length
    error.value = char_count.value > props.max_len ? `Maximum ${props.max_len} characters` : ''
}

function onInput() {
    model.value = normalizeBody(editor.value?.innerHTML ?? '')
    updateCount()
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter') {
        e.preventDefault()
        document.execCommand('insertLineBreak')
        onInput()
    }
}

function onSubmit() {
    const text = editor.value?.textContent?.trim() ?? ''

    if (!text) {
        error.value = 'Comment cannot be empty'
        return
    }
    if (text.length > props.max_len) {
        error.value = `Maximum ${props.max_len} characters`
        return
    }

    error.value = ''
    emit('submit')
}

function onClickOutside(e: MouseEvent) {
    if (show_emoji.value && !emoji_btn.value?.contains(e.target as Node)) {
        show_emoji.value = false
    }
}

watch(model, (val) => {
    // Compare normalized so the editor is not rewritten (losing the cursor)
    // when the only difference is <strike> vs <s>
    if (editor.value && normalizeBody(editor.value.innerHTML) !== (val ?? '')) {
        editor.value.innerHTML = val ?? ''
        updateCount()
    }
})

onMounted(() => {
    if (editor.value) editor.value.innerHTML = model.value ?? ''
    updateCount()
    document.addEventListener('click', onClickOutside)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside)
})
</script>

<template>
    <div class="rounded-lg border border-gray-300 bg-transparent shadow-theme-xs dark:border-gray-700 dark:bg-gray-900">
        <!-- Editor -->
        <div class="relative">
            <div
                ref="editor"
                contenteditable="true"
                class="cbf-editor min-h-[70px] max-h-[300px] overflow-y-auto px-4 py-2.5 pr-10 text-sm text-gray-800 outline-none dark:text-white/90"
                :data-placeholder="placeholder"
                @input="onInput"
                @keydown="onKeydown"
            />
            <button
                ref="emoji_btn"
                type="button"
                class="absolute top-2 right-2.5 text-lg leading-none opacity-60 hover:opacity-100 transition-opacity"
                title="Emoji"
                @click.stop="show_emoji = !show_emoji"
            >🙂</button>
            <div
                v-if="show_emoji"
                class="absolute top-9 right-0 z-50 grid w-[280px] grid-cols-8 gap-1 rounded-xl border border-gray-200 bg-white p-2.5 shadow-theme-lg dark:border-gray-700 dark:bg-gray-900"
                @click.stop
            >
                <button
                    v-for="emoji in EMOJIS"
                    :key="emoji"
                    type="button"
                    class="rounded p-1 text-lg leading-none hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    @click="insertEmoji(emoji)"
                >{{ emoji }}</button>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="flex items-center justify-between border-t border-gray-100 px-3 py-2 dark:border-gray-800">
            <div class="flex gap-0.5">
                <button
                    v-for="t in TOOLS"
                    :key="t[0]"
                    type="button"
                    class="flex h-7 w-7 items-center justify-center rounded text-sm text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors"
                    :title="t[1]"
                    @click="exec(t[0])"
                >
                    <component :is="t[2]">{{ t[2].toUpperCase() }}</component>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <span
                    class="text-xs whitespace-nowrap"
                    :class="char_count > max_len
                        ? 'font-semibold text-red-500'
                        : (char_count > max_len - 100 ? 'text-yellow-500' : 'text-gray-400')"
                >{{ char_count }}/{{ max_len }}</span>
                <BaseBtn
                    color="secondary"
                    base_class="px-3 py-1.5 text-xs font-medium transition rounded-lg"
                    @click="emit('cancel')"
                >Cancel</BaseBtn>
                <BaseBtn
                    :loading="loading"
                    base_class="px-4 py-1.5 text-xs font-medium transition rounded-lg"
                    @click="onSubmit"
                >{{ submit_label }}</BaseBtn>
            </div>
        </div>

        <div v-if="error" class="px-4 pb-2 text-xs text-red-500">{{ error }}</div>
    </div>
</template>

<style scoped>
.cbf-editor:empty::before {
    content: attr(data-placeholder);
    color: #9ca3af;
    pointer-events: none;
}
</style>
