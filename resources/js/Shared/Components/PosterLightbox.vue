<script setup>
import { onMounted, onUnmounted, watch } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    poster: { type: Object, required: true },
});

const emit = defineEmits(['close']);

function onKeydown(e) {
    if (e.key === 'Escape') emit('close');
}

onMounted(() => {
    document.addEventListener('keydown', onKeydown);
    document.body.style.overflow = 'hidden';
});

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
            @click.self="emit('close')"
        >
            <button
                class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20"
                @click="emit('close')"
            >
                <X class="h-5 w-5" />
            </button>

            <div class="relative max-h-[90vh] max-w-[90vw]">
                <img
                    :src="poster.image_url"
                    :alt="poster.alt_text || poster.title"
                    class="max-h-[85vh] w-auto rounded-2xl object-contain shadow-2xl"
                />
                <p v-if="poster.title" class="mt-3 text-center text-sm text-white/80">
                    {{ poster.title }}
                </p>
            </div>
        </div>
    </Teleport>
</template>