<script setup>
import { usePage } from '@inertiajs/vue3';
import { watch, ref, onMounted } from 'vue';
import { CircleCheck, CircleAlert, X } from 'lucide-vue-next';

const page = usePage();

const visible = ref(false);
const message = ref('');
const type = ref('success');
let timer = null;

function show(msg, msgType) {
    if (timer) clearTimeout(timer);
    message.value = msg;
    type.value = msgType;
    visible.value = true;
    timer = setTimeout(() => {
        visible.value = false;
    }, 5000);
}

function dismiss() {
    visible.value = false;
    if (timer) clearTimeout(timer);
}

function checkFlash() {
    const status = page.props.flash?.status;
    const error = page.props.flash?.error;
    if (status) show(status, 'success');
    else if (error) show(error, 'error');
}

onMounted(() => {
    checkFlash();
});

watch(
    () => [page.props.flash?.status, page.props.flash?.error],
    () => {
        checkFlash();
    },
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-400 ease-out"
            enter-from-class="-translate-y-4 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-4 opacity-0"
        >
            <div
                v-if="visible"
                class="fixed top-24 left-1/2 z-50 flex -translate-x-1/2 max-w-md items-start gap-3 rounded-xl border bg-white p-4 shadow-xl"
                :class="type === 'success'
                    ? 'border-emerald-200 text-emerald-900'
                    : 'border-red-200 text-red-900'"
            >
                <CircleCheck v-if="type === 'success'" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                <CircleAlert v-else class="mt-0.5 h-5 w-5 shrink-0 text-red-600" />
                <p class="flex-1 text-sm font-medium leading-relaxed">{{ message }}</p>
                <button
                    class="-mr-1 -mt-1 rounded-lg p-1 transition-colors hover:bg-slate-100"
                    @click="dismiss"
                >
                    <X class="h-4 w-4 text-slate-400" />
                </button>
            </div>
        </Transition>
    </Teleport>
</template>
