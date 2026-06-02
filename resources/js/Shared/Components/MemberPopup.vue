<script setup>
import { router } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    popup: { type: Object, required: true },
});

const open = ref(true);

function dismiss() {
    open.value = false;
    router.post('/member/popup/dismiss', {}, {
        preserveState: true,
        preserveScroll: true,
        onError: () => {},
    });
}
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" @click="dismiss"></div>

            <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
                <button
                    type="button"
                    class="absolute right-3 top-3 z-20 rounded-lg p-1 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                    @click="dismiss"
                >
                    <X class="h-5 w-5" />
                </button>

                <img
                    v-if="popup.image_url"
                    :src="popup.image_url"
                    :alt="popup.title"
                    class="w-full object-cover"
                />

                <div class="space-y-3 px-6 pb-6 pt-4">
                    <h2 class="text-lg font-bold text-slate-950">{{ popup.title }}</h2>
                    <p class="text-sm leading-relaxed text-slate-600 whitespace-pre-line">{{ popup.content }}</p>

                    <a
                        v-if="popup.button_text && popup.button_url"
                        :href="popup.button_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-2 inline-flex items-center rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-teal-800"
                    >
                        {{ popup.button_text }}
                    </a>
                </div>
            </div>
        </div>
    </Teleport>
</template>
