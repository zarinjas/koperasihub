<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { Bold, Italic, Underline as UnderlineIcon, List, ListOrdered, Quote, Link2, Undo2, Redo2, Heading2, Heading3 } from 'lucide-vue-next';
import { computed, watch } from 'vue';

const props = defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    modelValue: { type: String, default: '' },
    error: { type: String, default: '' },
    help: { type: String, default: '' },
    placeholder: { type: String, default: 'Taip di sini...' },
    required: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
        }),
        Underline,
        Link.configure({
            openOnClick: false,
            HTMLAttributes: { class: 'text-teal-700 underline underline-offset-2 hover:text-teal-800' },
        }),
        Placeholder.configure({ placeholder: props.placeholder }),
    ],
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

const isEditorReady = computed(() => editor.value);

const handleLinkClick = () => {
    if (!isEditorReady.value) return;
    const previousUrl = editor.value.getAttributes('link').href;
    const url = window.prompt('Masukkan URL:', previousUrl || 'https://');
    if (url === null) return;
    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }
    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
};

watch(() => props.modelValue, (newVal) => {
    if (isEditorReady.value && newVal !== editor.value.getHTML()) {
        editor.value.commands.setContent(newVal, false);
    }
});
</script>

<template>
    <div class="space-y-2">
        <label :for="id" class="text-sm font-medium text-slate-800">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <div
            class="overflow-hidden rounded-lg border bg-white shadow-sm transition"
            :class="error ? 'border-red-500' : 'border-slate-300 focus-within:border-teal-700 focus-within:ring-2 focus-within:ring-teal-700/20'"
        >
            <div class="flex flex-wrap items-center gap-0.5 border-b border-slate-200 bg-slate-50 px-2 py-1.5">
                <button type="button" title="Tebal"
                    class="rounded-md p-1.5 text-slate-600 transition hover:bg-slate-200 disabled:opacity-30"
                    :class="{ 'bg-slate-200 text-teal-800': editor?.isActive('bold') }"
                    :disabled="!isEditorReady"
                    @click="editor?.chain().focus().toggleBold().run()">
                    <Bold class="h-4 w-4" />
                </button>
                <button type="button" title="Italic"
                    class="rounded-md p-1.5 text-slate-600 transition hover:bg-slate-200 disabled:opacity-30"
                    :class="{ 'bg-slate-200 text-teal-800': editor?.isActive('italic') }"
                    :disabled="!isEditorReady"
                    @click="editor?.chain().focus().toggleItalic().run()">
                    <Italic class="h-4 w-4" />
                </button>
                <button type="button" title="Garis Bawah"
                    class="rounded-md p-1.5 text-slate-600 transition hover:bg-slate-200 disabled:opacity-30"
                    :class="{ 'bg-slate-200 text-teal-800': editor?.isActive('underline') }"
                    :disabled="!isEditorReady"
                    @click="editor?.chain().focus().toggleUnderline().run()">
                    <UnderlineIcon class="h-4 w-4" />
                </button>

                <span class="mx-1 h-5 w-px bg-slate-300"></span>

                <button type="button" title="Tajuk 2"
                    class="rounded-md p-1.5 text-slate-600 transition hover:bg-slate-200 disabled:opacity-30"
                    :class="{ 'bg-slate-200 text-teal-800': editor?.isActive('heading', { level: 2 }) }"
                    :disabled="!isEditorReady"
                    @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()">
                    <Heading2 class="h-4 w-4" />
                </button>
                <button type="button" title="Tajuk 3"
                    class="rounded-md p-1.5 text-slate-600 transition hover:bg-slate-200 disabled:opacity-30"
                    :class="{ 'bg-slate-200 text-teal-800': editor?.isActive('heading', { level: 3 }) }"
                    :disabled="!isEditorReady"
                    @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()">
                    <Heading3 class="h-4 w-4" />
                </button>

                <span class="mx-1 h-5 w-px bg-slate-300"></span>

                <button type="button" title="Senarai Berbullet"
                    class="rounded-md p-1.5 text-slate-600 transition hover:bg-slate-200 disabled:opacity-30"
                    :class="{ 'bg-slate-200 text-teal-800': editor?.isActive('bulletList') }"
                    :disabled="!isEditorReady"
                    @click="editor?.chain().focus().toggleBulletList().run()">
                    <List class="h-4 w-4" />
                </button>
                <button type="button" title="Senarai Bernombor"
                    class="rounded-md p-1.5 text-slate-600 transition hover:bg-slate-200 disabled:opacity-30"
                    :class="{ 'bg-slate-200 text-teal-800': editor?.isActive('orderedList') }"
                    :disabled="!isEditorReady"
                    @click="editor?.chain().focus().toggleOrderedList().run()">
                    <ListOrdered class="h-4 w-4" />
                </button>

                <span class="mx-1 h-5 w-px bg-slate-300"></span>

                <button type="button" title="Petikan"
                    class="rounded-md p-1.5 text-slate-600 transition hover:bg-slate-200 disabled:opacity-30"
                    :class="{ 'bg-slate-200 text-teal-800': editor?.isActive('blockquote') }"
                    :disabled="!isEditorReady"
                    @click="editor?.chain().focus().toggleBlockquote().run()">
                    <Quote class="h-4 w-4" />
                </button>
                <button type="button" title="Pautan"
                    class="rounded-md p-1.5 text-slate-600 transition hover:bg-slate-200 disabled:opacity-30"
                    :class="{ 'bg-slate-200 text-teal-800': editor?.isActive('link') }"
                    :disabled="!isEditorReady"
                    @click="handleLinkClick">
                    <Link2 class="h-4 w-4" />
                </button>

                <span class="mx-1 h-5 w-px bg-slate-300"></span>

                <button type="button" title="Buat asal"
                    class="rounded-md p-1.5 text-slate-600 transition hover:bg-slate-200 disabled:opacity-30"
                    :disabled="!isEditorReady || !editor?.can().chain().focus().undo().run()"
                    @click="editor?.chain().focus().undo().run()">
                    <Undo2 class="h-4 w-4" />
                </button>
                <button type="button" title="Buat semula"
                    class="rounded-md p-1.5 text-slate-600 transition hover:bg-slate-200 disabled:opacity-30"
                    :disabled="!isEditorReady || !editor?.can().chain().focus().redo().run()"
                    @click="editor?.chain().focus().redo().run()">
                    <Redo2 class="h-4 w-4" />
                </button>
            </div>
            <div class="tiptap-editor min-h-[160px] px-3 py-2 text-sm text-slate-950">
                <EditorContent :id="id" :editor="editor" />
            </div>
        </div>
        <p v-if="help" class="text-xs leading-5 text-slate-500">{{ help }}</p>
        <p v-if="error" class="text-sm text-red-700">{{ error }}</p>
    </div>
</template>

<style scoped>
.tiptap-editor :deep(.ProseMirror) {
    outline: none;
    min-height: 140px;
}
.tiptap-editor :deep(.ProseMirror p) {
    margin: 0;
    line-height: 1.6;
}
.tiptap-editor :deep(.ProseMirror p.is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
    color: #94a3b8;
}
.tiptap-editor :deep(.ProseMirror h2) {
    font-size: 1.125rem;
    font-weight: 700;
    margin-top: 0.75rem;
    margin-bottom: 0.5rem;
    color: #1e293b;
}
.tiptap-editor :deep(.ProseMirror h3) {
    font-size: 1rem;
    font-weight: 600;
    margin-top: 0.5rem;
    margin-bottom: 0.25rem;
    color: #1e293b;
}
.tiptap-editor :deep(.ProseMirror ul) {
    list-style-type: disc;
    padding-left: 1.5rem;
    margin: 0.25rem 0;
}
.tiptap-editor :deep(.ProseMirror ol) {
    list-style-type: decimal;
    padding-left: 1.5rem;
    margin: 0.25rem 0;
}
.tiptap-editor :deep(.ProseMirror li) {
    margin: 0.125rem 0;
}
.tiptap-editor :deep(.ProseMirror blockquote) {
    border-left: 3px solid #0f766e;
    padding-left: 0.75rem;
    margin: 0.5rem 0;
    color: #475569;
    font-style: italic;
}
.tiptap-editor :deep(.ProseMirror a) {
    color: #0f766e;
    text-decoration: underline;
    text-underline-offset: 2px;
}
.tiptap-editor :deep(.ProseMirror a:hover) {
    color: #115e59;
}
.tiptap-editor :deep(.ProseMirror code) {
    background: #f1f5f9;
    border-radius: 0.25rem;
    padding: 0.125rem 0.375rem;
    font-size: 0.8125rem;
}
.tiptap-editor :deep(.ProseMirror pre) {
    background: #1e293b;
    color: #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    margin: 0.5rem 0;
    overflow-x: auto;
}
.tiptap-editor :deep(.ProseMirror hr) {
    border: none;
    border-top: 1px solid #e2e8f0;
    margin: 1rem 0;
}
</style>
