<script setup>
defineProps({
    id: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    modelValue: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label :for="id" class="flex items-start justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
        <div>
            <p class="text-sm font-medium text-slate-900">{{ label }}</p>
            <p v-if="description" class="mt-1 text-xs leading-5 text-slate-500">{{ description }}</p>
        </div>
        <button
            :id="id"
            type="button"
            class="relative inline-flex h-6 w-11 items-center rounded-full transition"
            :class="modelValue ? 'bg-teal-700' : 'bg-slate-300'"
            @click="$emit('update:modelValue', !modelValue)"
        >
            <span
                class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition"
                :class="modelValue ? 'translate-x-5' : 'translate-x-1'"
            />
        </button>
    </label>
</template>
