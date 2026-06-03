<script setup>
import { ref } from 'vue';
import { Eye, EyeOff } from 'lucide-vue-next';

defineProps({
    id: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: 'Kata laluan',
    },
    modelValue: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    autocomplete: {
        type: String,
        default: 'current-password',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const showPassword = ref(false);
</script>

<template>
    <div class="w-full space-y-2">
        <label :for="id" class="text-sm font-medium text-slate-800">{{ label }}</label>
        <div class="relative">
            <input
                :id="id"
                :type="showPassword ? 'text' : 'password'"
                :value="modelValue"
                :autocomplete="autocomplete"
                :disabled="disabled"
                class="h-11 w-full min-w-0 rounded-lg border border-slate-300 bg-white pl-3 pr-10 text-sm text-slate-950 shadow-sm transition focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500"
                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': error }"
                :aria-invalid="Boolean(error)"
                @input="$emit('update:modelValue', $event.target.value)"
            />
            <button
                type="button"
                class="absolute right-0 top-0 flex h-11 w-10 items-center justify-center text-slate-400 hover:text-slate-600"
                tabindex="-1"
                @click="showPassword = !showPassword"
            >
                <EyeOff v-if="showPassword" class="h-4 w-4" />
                <Eye v-else class="h-4 w-4" />
            </button>
        </div>
        <p v-if="error" class="text-sm text-red-700">{{ error }}</p>
    </div>
</template>
