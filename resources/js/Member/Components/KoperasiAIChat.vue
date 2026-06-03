<script setup>
import { nextTick, ref, watch } from 'vue';
import { Send, Sparkles, X } from 'lucide-vue-next';
import { useStorage } from '@vueuse/core';

const isOpen = useStorage('koperasi-ai-chat-open', false);
const messages = useStorage('koperasi-ai-chat-messages', []);
const dismissed = useStorage('koperasi-ai-chat-dismissed', false);
const inputText = ref('');
const loading = ref(false);
const chatRef = ref(null);

const suggestedPrompts = [
    'Macam mana mohon pembiayaan?',
    'Apa syarat keahlian?',
    'Dividen dibayar bila?',
    'Dokumen apa diperlukan?',
];

const sendMessage = async (text) => {
    const msg = text.trim();
    if (!msg || loading.value) return;

    messages.value = [...messages.value, { role: 'user', content: msg }];
    inputText.value = '';
    loading.value = true;

    try {
        const res = await fetch('/member/koperasi-ai-chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ message: msg }),
        });

        const data = await res.json();
        messages.value = [...messages.value, { role: 'assistant', content: data.reply }];
    } catch {
        messages.value = [...messages.value, { role: 'assistant', content: 'Maaf, berlaku masalah teknikal. Sila cuba sebentar lagi.' }];
    } finally {
        loading.value = false;
    }
};

const selectPrompt = (prompt) => {
    sendMessage(prompt);
};

watch(messages, async () => {
    await nextTick();
    if (chatRef.value) {
        chatRef.value.scrollTop = chatRef.value.scrollHeight;
    }
}, { deep: true });

const formatContent = (text) => {
    return text.split('\n').map((line, i) => {
        const trimmed = line.trim();
        if (!trimmed) return null;
        if (/^\d+\.\s/.test(trimmed)) {
            const parts = trimmed.match(/^(\d+\.\s)(.*)/);
            if (parts) {
                return { type: 'list-item', num: parts[1], text: parts[2] };
            }
        }
        if (/^Berdasarkan dokumen/i.test(trimmed)) {
            return { type: 'header', text: trimmed };
        }
        return { type: 'text', text: trimmed };
    }).filter(Boolean);
};

const toggleOpen = () => {
    isOpen.value = !isOpen.value;
};
</script>

<template>
    <Teleport to="body">
        <div class="pointer-events-none fixed inset-0 z-40 lg:inset-auto lg:bottom-24 lg:right-6 lg:z-50">
            <div
                v-if="isOpen"
                class="pointer-events-auto flex h-full w-full flex-col bg-white/95 backdrop-blur-xl lg:h-[600px] lg:w-[400px] lg:rounded-2xl lg:shadow-2xl lg:ring-1 lg:ring-black/5"
                :class="isOpen ? 'lg:animate-in lg:slide-in-from-bottom-4 lg:fade-in' : ''"
            >
                <div class="flex shrink-0 items-center justify-between rounded-t-2xl bg-gradient-to-r from-teal-600 to-emerald-600 px-5 py-4 text-white">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/20">
                            <Sparkles class="h-5 w-5" />
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold">Koperasi AI Chat</p>
                                <span class="relative flex h-2 w-2">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-300 opacity-75" />
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-200" />
                                </span>
                            </div>
                            <p class="text-xs text-teal-100">Pembantu Pintar Koperasi</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="flex h-8 w-8 items-center justify-center rounded-lg transition hover:bg-white/20"
                        @click="isOpen = false"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div
                    ref="chatRef"
                    class="flex-1 space-y-4 overflow-y-auto px-4 py-5 scrollbar-thin"
                >
                    <template v-if="messages.length === 0">
                        <div class="flex flex-col items-center gap-3 pt-8 text-center">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-50 to-emerald-50">
                                <Sparkles class="h-7 w-7 text-teal-600" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Selamat datang!</p>
                                <p class="mt-1 text-xs text-slate-400">Tanya apa sahaja tentang koperasi</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2 pt-4">
                            <button
                                v-for="prompt in suggestedPrompts"
                                :key="prompt"
                                type="button"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-xs font-medium text-slate-600 shadow-sm transition hover:border-teal-300 hover:bg-teal-50/40 hover:text-teal-700"
                                @click="selectPrompt(prompt)"
                            >
                                {{ prompt }}
                            </button>
                        </div>
                    </template>

                    <template v-else>
                        <div v-for="(msg, idx) in messages" :key="idx">
                            <div v-if="msg.role === 'user'" class="flex justify-end">
                                <div class="max-w-[80%] rounded-2xl rounded-br-sm bg-gradient-to-br from-teal-500 to-emerald-500 px-4 py-2.5 text-sm text-white shadow-sm">
                                    {{ msg.content }}
                                </div>
                            </div>
                            <div v-else class="flex justify-start">
                                <div class="max-w-[90%] rounded-2xl rounded-bl-sm bg-slate-100 px-4 py-2.5 text-sm text-slate-800 shadow-sm">
                                    <template v-for="(part, pi) in formatContent(msg.content)" :key="pi">
                                        <p v-if="part.type === 'header'" class="mb-1 text-xs font-semibold text-slate-500">
                                            {{ part.text }}
                                        </p>
                                        <p v-else-if="part.type === 'list-item'" class="mb-1 leading-relaxed">
                                            <span class="font-medium text-teal-700">{{ part.num }}</span>{{ part.text }}
                                        </p>
                                        <p v-else-if="part.text" class="mb-1 leading-relaxed">
                                            {{ part.text }}
                                        </p>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div v-if="loading" class="flex justify-start">
                            <div class="flex items-center gap-1.5 rounded-2xl rounded-bl-sm bg-slate-100 px-4 py-3">
                                <span class="h-2 w-2 animate-bounce rounded-full bg-teal-500" style="animation-delay: 0ms" />
                                <span class="h-2 w-2 animate-bounce rounded-full bg-teal-500" style="animation-delay: 150ms" />
                                <span class="h-2 w-2 animate-bounce rounded-full bg-teal-500" style="animation-delay: 300ms" />
                            </div>
                        </div>
                    </template>
                </div>

                <div class="shrink-0 border-t border-slate-100 px-4 py-3">
                    <form class="flex items-center gap-2" @submit.prevent="sendMessage(inputText)">
                        <input
                            v-model="inputText"
                            type="text"
                            placeholder="Tanya soalan..."
                            class="flex-1 rounded-full border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-teal-400 focus:ring-2 focus:ring-teal-100"
                            :disabled="loading"
                        />
                        <button
                            type="submit"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-emerald-500 text-white shadow-sm transition hover:scale-105 hover:shadow-md disabled:opacity-50"
                            :disabled="!inputText.trim() || loading"
                        >
                            <Send class="h-4 w-4" />
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <Transition name="fab">
            <div
                v-if="!isOpen && !dismissed"
                role="button"
                aria-label="Buka AI Chat"
                class="pointer-events-auto fixed bottom-24 right-5 z-50 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/20 transition hover:shadow-indigo-500/40 active:scale-90 lg:bottom-7"
                @click="toggleOpen"
            >
                <svg viewBox="0 0 28 28" class="h-[30px] w-[30px]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2l3 7 7 3-7 3-3 7-3-7-7-3 7-3 3-7z" fill="currentColor" />
                </svg>
                <button
                    type="button"
                    aria-label="Tutup butang AI Chat"
                    class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white shadow transition hover:bg-red-600"
                    @click.stop="dismissed = true"
                >
                    <X class="h-3 w-3" />
                </button>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fab-enter-active {
    transition: all 0.2s ease-out;
}
.fab-leave-active {
    transition: all 0.15s ease-in;
}
.fab-enter-from,
.fab-leave-to {
    opacity: 0;
    transform: scale(0.8);
}
</style>
