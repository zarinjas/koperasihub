<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: [Number, String], default: null },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    error: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    min: { type: Number, default: null },
    max: { type: Number, default: null },
});

const emit = defineEmits(['update:modelValue']);

const focused = ref(false);
const localDisplay = ref('');

const formatNumber = (val) => {
    if (val === null || val === '' || val === undefined) return '';
    const num = typeof val === 'string' ? parseFloat(val.replace(/,/g, '')) : parseFloat(val);
    if (isNaN(num)) return '';
    if (num === 0 && val !== 0 && val !== '0') return '';
    return num.toLocaleString('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const unformat = (str) => {
    return str.replace(/[^0-9.]/g, '');
};

const displayValue = computed(() => {
    if (focused.value) {
        const raw = props.modelValue;
        if (raw === null || raw === '' || raw === undefined) return '';
        const num = typeof raw === 'string' ? parseFloat(raw.replace(/,/g, '')) : parseFloat(raw);
        if (isNaN(num)) return '';
        return num.toString();
    }
    return localDisplay.value || formatNumber(props.modelValue);
});

const onFocus = () => {
    focused.value = true;
};

const onBlur = () => {
    focused.value = false;
};

const onInput = (e) => {
    const raw = unformat(e.target.value);
    const num = parseFloat(raw);
    const finalVal = isNaN(num) ? null : num;
    emit('update:modelValue', finalVal);
    localDisplay.value = formatNumber(finalVal);
};

watch(() => props.modelValue, (val) => {
    if (val === null || val === '' || val === undefined) {
        localDisplay.value = '';
    }
}, { immediate: true });
</script>

<template>
    <div>
        <label v-if="label" class="text-sm font-medium text-slate-800">{{ label }}</label>
        <div class="relative mt-1" :class="{ 'mt-1.5': !label }">
            <span
                class="absolute left-0 top-0 flex h-full items-center pl-3 text-sm text-slate-400 pointer-events-none"
            >RM</span>
            <input
                :value="displayValue"
                @input="onInput"
                @focus="onFocus"
                @blur="onBlur"
                type="text"
                inputmode="decimal"
                :placeholder="placeholder || '0.00'"
                :disabled="disabled"
                class="h-11 w-full rounded-lg border bg-white pl-10 pr-3 text-sm shadow-sm focus:outline-none focus:ring-2 transition"
                :class="error
                    ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20'
                    : 'border-slate-300 focus:border-teal-700 focus:ring-teal-700/20'"
            />
        </div>
        <p v-if="error" class="mt-1 text-sm text-red-700">{{ error }}</p>
    </div>
</template>
