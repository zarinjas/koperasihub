<script setup>
import { Link } from '@inertiajs/vue3';
import { Inbox } from 'lucide-vue-next';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    actionLabel: {
        type: String,
        default: null,
    },
    actionHref: {
        type: String,
        default: null,
    },
    compact: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div
        class="rounded-3xl border border-dashed border-slate-300 bg-white text-center shadow-sm"
        :class="compact ? 'p-6' : 'p-10'"
    >
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
            <Inbox class="h-6 w-6" />
        </div>
        <h3 class="mt-4 text-lg font-semibold text-slate-950">{{ title }}</h3>
        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-600">{{ description }}</p>

        <div v-if="actionLabel && actionHref" class="mt-5">
            <Button :as="Link" :href="actionHref">{{ actionLabel }}</Button>
        </div>
    </div>
</template>
