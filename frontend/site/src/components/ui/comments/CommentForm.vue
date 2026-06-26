<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { normalizeCommentBody } from '@/utils/comment_body'

const props = defineProps<{
    loading?: boolean
    reply_to?: string | null
    max_len: number
}>()

const emit = defineEmits<{
    (e: 'submit', body: string): void
    (e: 'cancel'): void
}>()

const editor = ref<HTMLDivElement | null>(null)
const show_emoji = ref(false)
const emoji_btn = ref<HTMLButtonElement | null>(null)
const char_count = ref(0)
const error = ref('')

const EMOJIS = [
    '😀','😂','😍','🥰','😎','😢','😡','🤔','👍','👎',
    '❤️','🔥','🎉','✅','❌','😅','🙏','💪','🤣','😏',
    '😱','🤯','😴','🥳','😇','🤗','😤','🙄','😒','💀',
    '👀','💯','🚀','✨','💡','🎯','🤝','👋','🫶','💬',
]

function exec(cmd: string, value?: string) {
    editor.value?.focus()
    // Force tag output (<b>, <s>, ...) instead of styled spans in Firefox
    document.execCommand('styleWithCSS', false, 'false')
    document.execCommand(cmd, false, value)
}

function insertEmoji(emoji: string) {
    editor.value?.focus()
    document.execCommand('insertText', false, emoji)
    show_emoji.value = false
    updateCount()
}

function updateCount() {
    const text = editor.value?.textContent?.trim() ?? ''
    char_count.value = text.length
    if (char_count.value > props.max_len) {
        error.value = `Maximum ${props.max_len} characters`
    } else {
        error.value = ''
    }
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter') {
        e.preventDefault()
        document.execCommand('insertLineBreak')
        updateCount()
    }
}

function onSubmit() {
    const text = editor.value?.textContent?.trim() ?? ''
    const body = normalizeCommentBody(editor.value?.innerHTML?.trim() ?? '')

    if (!text || text.length < 1) {
        error.value = 'Comment cannot be empty'
        return
    }
    if (text.length > props.max_len) {
        error.value = `Maximum ${props.max_len} characters`
        return
    }
    if (!body || body === '<br>') return

    error.value = ''
    emit('submit', body)
    if (editor.value) editor.value.innerHTML = ''
    char_count.value = 0
}

function onClickOutside(e: MouseEvent) {
    if (show_emoji.value && !emoji_btn.value?.contains(e.target as Node)) {
        show_emoji.value = false
    }
}

onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))
</script>

<template>
    <div class="cf-wrap">
        <div v-if="reply_to" class="cf-reply-header">
            <span>Reply to <strong>{{ reply_to }}</strong></span>
            <button type="button" class="cf-reply-header-close" @click="$emit('cancel')">×</button>
        </div>
        <div class="cf-editor-wrap">
            <div
                ref="editor"
                contenteditable="true"
                class="cf-editor"
                :data-placeholder="reply_to ? 'Write a reply...' : 'Write a comment...'"
                @input="updateCount"
                @keydown="onKeydown"
            />
            <button ref="emoji_btn" class="cf-emoji-btn" type="button" @click.stop="show_emoji = !show_emoji" title="Emoji">
                <span>🙂</span>
            </button>
            <div v-if="show_emoji" class="cf-emoji-picker" @click.stop>
                <button
                    v-for="emoji in EMOJIS"
                    :key="emoji"
                    type="button"
                    class="cf-emoji-item"
                    @click="insertEmoji(emoji)"
                >{{ emoji }}</button>
            </div>
        </div>

        <div class="cf-toolbar">
            <div class="cf-toolbar-left">
                <button type="button" class="cf-tool" @click="exec('bold')" title="Bold"><b>B</b></button>
                <button type="button" class="cf-tool cf-tool--i" @click="exec('italic')" title="Italic"><i>I</i></button>
                <button type="button" class="cf-tool cf-tool--u" @click="exec('underline')" title="Underline"><u>U</u></button>
                <button type="button" class="cf-tool cf-tool--s" @click="exec('strikeThrough')" title="Strikethrough"><s>S</s></button>
            </div>
            <div class="cf-toolbar-right">
                <span class="cf-counter" :class="{ 'cf-counter--warn': char_count > props.max_len - 100, 'cf-counter--over': char_count > props.max_len }">
                    {{ char_count }}/{{ props.max_len }}
                </span>
                <button type="button" class="cf-submit" :disabled="loading || char_count > props.max_len" @click="onSubmit">
                    <span v-if="loading" class="cf-spinner"></span>
                    <span v-else>{{ reply_to ? 'Reply' : 'Submit' }}</span>
                </button>
            </div>
        </div>
        <div v-if="error" class="cf-error">{{ error }}</div>
    </div>
</template>

<style scoped>
.cf-wrap {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: visible;
    background: #fff;
}

.cf-reply-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 7px 12px;
    background: #f0fbf7;
    border-bottom: 1px solid #c8edd9;
    font-size: 13px;
    color: #555;
    border-radius: 8px 8px 0 0;
}

.cf-reply-header strong { color: #17b978; }

.cf-reply-header-close {
    background: none;
    border: none;
    font-size: 18px;
    line-height: 1;
    color: #aaa;
    cursor: pointer;
    padding: 0 2px;
    transition: color 0.15s;
}

.cf-reply-header-close:hover { color: #333; }

.cf-editor-wrap {
    position: relative;
}

.cf-editor {
    min-height: 90px;
    max-height: 300px;
    overflow-y: auto;
    padding: 12px 44px 12px 14px;
    font-size: 14px;
    color: #333;
    outline: none;
    line-height: 1.5;
}

.cf-editor:empty::before {
    content: attr(data-placeholder);
    color: #aaa;
    pointer-events: none;
}

.cf-emoji-btn {
    position: absolute;
    top: 8px;
    right: 10px;
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    line-height: 1;
    padding: 2px;
    opacity: 0.6;
    transition: opacity 0.15s;
}

.cf-emoji-btn:hover { opacity: 1; }

.cf-emoji-picker {
    position: absolute;
    top: 36px;
    right: 0;
    z-index: 100;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 10px;
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 4px;
    box-shadow: 0 6px 24px rgba(0,0,0,0.12);
    width: 280px;
}

.cf-emoji-item {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    padding: 3px;
    border-radius: 4px;
    line-height: 1;
    transition: background 0.1s;
}

.cf-emoji-item:hover { background: #f3f3f3; }

.cf-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    border-top: 1px solid #f0f0f0;
    background: #fafafa;
}

.cf-toolbar-left {
    display: flex;
    gap: 2px;
}

.cf-toolbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.cf-tool {
    background: none;
    border: none;
    width: 30px;
    height: 30px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    color: #555;
    transition: background 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cf-tool:hover { background: #ebebeb; }
.cf-tool--i i { font-style: italic; }
.cf-tool--u u { text-decoration: underline; }
.cf-tool--s s { text-decoration: line-through; }

.cf-cancel {
    background: none;
    border: none;
    font-size: 13px;
    color: #888;
    cursor: pointer;
    padding: 0 4px;
}

.cf-cancel:hover { color: #333; }

.cf-submit {
    background: #17b978;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 18px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
    min-width: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cf-submit:hover:not(:disabled) { background: #13a368; }
.cf-submit:disabled { background: #aaa; cursor: default; }

.cf-counter {
    font-size: 12px;
    color: #bbb;
    white-space: nowrap;
    transition: color 0.15s;
}

.cf-counter--warn { color: #f39c12; }
.cf-counter--over { color: #e74c3c; font-weight: 600; }

.cf-error {
    padding: 6px 14px 8px;
    font-size: 12px;
    color: #e74c3c;
}

.cf-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255,255,255,0.4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: cf-spin 0.7s linear infinite;
    display: inline-block;
}

@keyframes cf-spin { to { transform: rotate(360deg); } }
</style>
