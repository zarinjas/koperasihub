<script setup>
import { computed } from 'vue';
import { Upload } from 'lucide-vue-next';

const props = defineProps({
    id: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    accept: {
        type: String,
        default: '',
    },
    helperText: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    modelValue: {
        type: Object,
        default: null,
    },
    existingFile: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);

const selectedFileName = computed(() => props.modelValue?.name || '');

const onChange = (event) => {
    emit('update:modelValue', event.target.files?.[0] || null);
};
</script>

<template>
    <div class="space-y-3">
        <label :for="id" class="text-sm font-medium text-slate-800">{{ label }}</label>

        <label
            :for="id"
            class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center transition hover:border-teal-300 hover:bg-teal-50/40"
        >
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-teal-700 shadow-sm">
                <Upload class="h-5 w-5" />
            </span>
            <div class="space-y-1">
                <p class="text-sm font-medium text-slate-900">
                    {{ selectedFileName || 'Pilih fail untuk dimuat naik' }}
                </p>
                <p v-if="helperText" class="text-xs leading-5 text-slate-500">{{ helperText }}</p>
            </div>
        </label>

        <input
            :id="id"
            :accept="accept"
            type="file"
            class="hidden"
            @change="onChange"
        />

        <div
            v-if="existingFile?.name && !selectedFileName"
            class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600"
        >
            Fail semasa: <span class="font-medium text-slate-900">{{ existingFile.name }}</span>
        </div>

        <p v-if="error" class="text-sm text-red-700">{{ error }}</p>
    </div>
</template>
