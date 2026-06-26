<script setup lang="ts">
import ImagePlaceholderIcon from '@/icons/ImagePlaceholderIcon.vue'
import { ref, watch, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'
import DOMPurify, { type Config as DOMPurifyConfig } from 'dompurify'
import axios from '@/plugins/axios'

const props = defineProps<{
  modelValue: string | null
  disabled?: boolean
}>()

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

let _skipUpdate = false

const ALLOWED: DOMPurifyConfig = {
  ALLOWED_TAGS: ['p','br','strong','em','u','s','h2','h3','h4','ul','ol','li','blockquote','code','pre','a','hr','img'],
  ALLOWED_ATTR: ['href','target','rel','src','alt','width','height'],
  FORCE_BODY: true,
}

function sanitize(html: string): string {
  return String(DOMPurify.sanitize(html, ALLOWED))
}

const editor = useEditor({
  content: sanitize(props.modelValue || ''),
  editable: !props.disabled,
  extensions: [
    StarterKit.configure({ heading: { levels: [2, 3, 4] } }),
    Link.configure({
      openOnClick: false,
      HTMLAttributes: { rel: 'noopener noreferrer nofollow', target: '_blank' },
    }),
    Image.configure({ inline: false, allowBase64: false }),
  ],
  onUpdate({ editor }) {
    if (_skipUpdate) return
    emit('update:modelValue', sanitize(editor.getHTML()))
  },
})

watch(() => props.modelValue, (val) => {
  if (!editor.value) return
  const next = sanitize(val || '')
  if (editor.value.getHTML() !== next) {
    _skipUpdate = true
    editor.value.commands.setContent(next)
    _skipUpdate = false
  }
})

watch(() => props.disabled, (val) => editor.value?.setEditable(!val))
onBeforeUnmount(() => editor.value?.destroy())

// ── Link ──────────────────────────────────────────────────────────────────────

function setLink() {
  const url = window.prompt('URL')
  if (!url) return
  if (!url.startsWith('http://') && !url.startsWith('https://') && !url.startsWith('/')) return
  editor.value?.chain().focus().setLink({ href: url }).run()
}

// ── Image upload ──────────────────────────────────────────────────────────────

const imageInput = ref<HTMLInputElement | null>(null)
const uploading  = ref(false)

async function onImageFile(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  uploading.value = true
  try {
    const form = new FormData()
    form.append('image', file)
    const res = await axios.post('upload/image', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    editor.value?.chain().focus().setImage({ src: res.data.data.url }).run()
  } finally {
    uploading.value = false
    if (imageInput.value) imageInput.value.value = ''
  }
}
</script>

<template>
  <div
    class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900"
    :class="disabled ? 'opacity-60' : ''"
  >
    <input ref="imageInput" type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" @change="onImageFile" />

    <!-- Toolbar -->
    <div v-if="!disabled" class="flex flex-wrap items-center gap-0.5 border-b border-gray-200 dark:border-gray-700 px-2 py-1.5">

      <button type="button" @click="editor?.chain().focus().toggleBold().run()" :class="editor?.isActive('bold') ? 'btn-active' : 'btn-idle'" class="tb-btn" title="Bold">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M6 4h8a4 4 0 010 8H6zM6 12h9a4 4 0 010 8H6z"/></svg>
      </button>

      <button type="button" @click="editor?.chain().focus().toggleItalic().run()" :class="editor?.isActive('italic') ? 'btn-active' : 'btn-idle'" class="tb-btn" title="Italic">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>
      </button>

      <button type="button" @click="editor?.chain().focus().toggleStrike().run()" :class="editor?.isActive('strike') ? 'btn-active' : 'btn-idle'" class="tb-btn" title="Strikethrough">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 12h14M7 6a4 4 0 017.5 2M9 18a4 4 0 007.5-2"/></svg>
      </button>

      <span class="divider"></span>

      <button type="button" @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()" :class="editor?.isActive('heading', { level: 2 }) ? 'btn-active' : 'btn-idle'" class="tb-btn font-bold text-xs" title="Heading 2">H2</button>
      <button type="button" @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()" :class="editor?.isActive('heading', { level: 3 }) ? 'btn-active' : 'btn-idle'" class="tb-btn font-bold text-xs" title="Heading 3">H3</button>
      <button type="button" @click="editor?.chain().focus().toggleHeading({ level: 4 }).run()" :class="editor?.isActive('heading', { level: 4 }) ? 'btn-active' : 'btn-idle'" class="tb-btn font-bold text-xs" title="Heading 4">H4</button>

      <span class="divider"></span>

      <button type="button" @click="editor?.chain().focus().toggleBulletList().run()" :class="editor?.isActive('bulletList') ? 'btn-active' : 'btn-idle'" class="tb-btn" title="Bullet list">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="4" cy="6"  r="1.5" fill="currentColor" stroke="none"/>
          <circle cx="4" cy="12" r="1.5" fill="currentColor" stroke="none"/>
          <circle cx="4" cy="18" r="1.5" fill="currentColor" stroke="none"/>
          <path stroke-linecap="round" d="M8 6h12M8 12h12M8 18h12"/>
        </svg>
      </button>

      <button type="button" @click="editor?.chain().focus().toggleOrderedList().run()" :class="editor?.isActive('orderedList') ? 'btn-active' : 'btn-idle'" class="tb-btn" title="Numbered list">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" d="M10 6h11M10 12h11M10 18h11"/>
          <path fill="currentColor" stroke="none" d="M3 5h1.5v4H3V5zm0 6.5h2v.5l-2 2v.5h2v.5H3v-.5l2-2V12H3v-.5zm1 6H3v-.5h2v2H3v-.5h1V17z"/>
        </svg>
      </button>

      <span class="divider"></span>

      <button type="button" @click="editor?.chain().focus().toggleBlockquote().run()" :class="editor?.isActive('blockquote') ? 'btn-active' : 'btn-idle'" class="tb-btn" title="Blockquote">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179z"/></svg>
      </button>

      <button type="button" @click="editor?.chain().focus().toggleCodeBlock().run()" :class="editor?.isActive('codeBlock') ? 'btn-active' : 'btn-idle'" class="tb-btn" title="Code block">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
      </button>

      <button type="button" @click="editor?.chain().focus().setHorizontalRule().run()" class="tb-btn btn-idle" title="Horizontal rule">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 12h14"/></svg>
      </button>

      <span class="divider"></span>

      <button type="button" @click="setLink" :class="editor?.isActive('link') ? 'btn-active' : 'btn-idle'" class="tb-btn" title="Add link">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
      </button>

      <button v-if="editor?.isActive('link')" type="button" @click="editor?.chain().focus().unsetLink().run()" class="tb-btn btn-idle" title="Remove link">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12h-4M7 12H3m9-9v4m0 10v4"/></svg>
      </button>

      <button type="button" @click="imageInput?.click()" class="tb-btn" :class="uploading ? 'opacity-40 cursor-wait' : 'btn-idle'" title="Insert image">
        <ImagePlaceholderIcon class="w-4 h-4" />
      </button>

    </div>

    <EditorContent :editor="editor" class="editor-content px-4 py-3 min-h-[280px]" />
  </div>
</template>

<style scoped>
.tb-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  border-radius: 0.25rem;
  transition: background-color 0.15s, color 0.15s;
}
.btn-idle  { color: #6b7280; }
.btn-idle:hover { background-color: #f3f4f6; color: #374151; }
.btn-active { background-color: #eff6ff; color: #1d4ed8; }

:global(.dark) .btn-idle       { color: #9ca3af; }
:global(.dark) .btn-idle:hover { background-color: rgba(255,255,255,0.06); color: #e5e7eb; }
:global(.dark) .btn-active     { background-color: rgba(99,102,241,0.2); color: #818cf8; }

.divider {
  display: inline-block;
  width: 1px;
  height: 1.25rem;
  background-color: #e5e7eb;
  margin: 0 0.125rem;
}
:global(.dark) .divider { background-color: #374151; }

.editor-content :deep(.ProseMirror) {
  outline: none;
  min-height: 240px;
  color: #1f2937;
  font-size: 0.9rem;
  line-height: 1.7;
}
:global(.dark) .editor-content :deep(.ProseMirror) { color: rgba(255,255,255,0.88); }

.editor-content :deep(.ProseMirror h2) { font-size: 1.5rem;  font-weight: 700; line-height: 1.3;  margin: 1.25rem 0 0.5rem;  color: #111827; }
.editor-content :deep(.ProseMirror h3) { font-size: 1.2rem;  font-weight: 600; line-height: 1.35; margin: 1rem 0 0.4rem;     color: #111827; }
.editor-content :deep(.ProseMirror h4) { font-size: 1rem;    font-weight: 600; line-height: 1.4;  margin: 0.75rem 0 0.35rem; color: #1f2937; }
:global(.dark) .editor-content :deep(.ProseMirror h2),
:global(.dark) .editor-content :deep(.ProseMirror h3),
:global(.dark) .editor-content :deep(.ProseMirror h4) { color: rgba(255,255,255,0.92); }

.editor-content :deep(.ProseMirror p) { margin: 0.4rem 0; }

.editor-content :deep(.ProseMirror ul) { list-style: disc;    padding-left: 1.5rem; margin: 0.5rem 0; }
.editor-content :deep(.ProseMirror ol) { list-style: decimal; padding-left: 1.5rem; margin: 0.5rem 0; }
.editor-content :deep(.ProseMirror li) { margin: 0.15rem 0; }

.editor-content :deep(.ProseMirror blockquote) {
  border-left: 3px solid #d1d5db;
  padding-left: 1rem;
  color: #6b7280;
  margin: 0.75rem 0;
  font-style: italic;
}
:global(.dark) .editor-content :deep(.ProseMirror blockquote) { border-color: #4b5563; color: #9ca3af; }

.editor-content :deep(.ProseMirror code) { background: #f3f4f6; border-radius: 3px; padding: 0.1em 0.3em; font-size: 0.85em; font-family: monospace; }
.editor-content :deep(.ProseMirror pre)  { background: #f3f4f6; border-radius: 6px; padding: 0.75rem 1rem; overflow-x: auto; margin: 0.75rem 0; }
.editor-content :deep(.ProseMirror pre code) { background: none; padding: 0; }
:global(.dark) .editor-content :deep(.ProseMirror code),
:global(.dark) .editor-content :deep(.ProseMirror pre) { background: #1f2937; }

.editor-content :deep(.ProseMirror a) { color: #6366f1; text-decoration: underline; }

.editor-content :deep(.ProseMirror img) { max-width: 100%; border-radius: 6px; margin: 0.5rem 0; display: block; }

.editor-content :deep(.ProseMirror hr) { border: none; border-top: 1px solid #e5e7eb; margin: 1rem 0; }
:global(.dark) .editor-content :deep(.ProseMirror hr) { border-color: #4b5563; }

.editor-content :deep(.ProseMirror strong) { font-weight: 700; }
.editor-content :deep(.ProseMirror em)     { font-style: italic; }
.editor-content :deep(.ProseMirror s)      { text-decoration: line-through; }
</style>
