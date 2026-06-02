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
    modelValue: {
        type: String,
        default: '',
    },
    rows: {
        type: Number,
        default: 4,
    },
    error: {
        type: String,
        default: '',
    },
    help: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="space-y-2">
        <label :for="id" class="text-sm font-medium text-slate-800">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <textarea
            :id="id"
            :rows="rows"
            :value="modelValue"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm transition focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
            :aria-invalid="Boolean(error)"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <p v-if="help" class="text-xs leading-5 text-slate-500">{{ help }}</p>
        <p v-if="error" class="text-sm text-red-700">{{ error }}</p>
    </div>
</template>