<script setup>
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: 'Tandatangan Digital' },
    error: { type: String, default: '' },
    height: { type: Number, default: 150 },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const canvasRef = ref(null);
const hasSignature = ref(false);
let ctx = null;
let drawing = false;
let points = [];

const initCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    const rect = canvas.parentElement.getBoundingClientRect();
    canvas.width = Math.min(rect.width - 4, 600);
    canvas.height = props.height;

    ctx = canvas.getContext('2d');
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#1e293b';

    if (props.modelValue) {
        loadSignature(props.modelValue);
    }
};

const getPos = (e) => {
    const rect = canvasRef.value.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    return {
        x: (clientX - rect.left) * (canvasRef.value.width / rect.width),
        y: (clientY - rect.top) * (canvasRef.value.height / rect.height),
    };
};

const startDraw = (e) => {
    if (props.disabled) return;
    e.preventDefault();
    drawing = true;
    points = [getPos(e)];
    ctx.beginPath();
    const p = points[0];
    ctx.moveTo(p.x, p.y);
};

const draw = (e) => {
    if (!drawing || props.disabled) return;
    e.preventDefault();
    const p = getPos(e);
    points.push(p);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
};

const endDraw = () => {
    if (!drawing) return;
    drawing = false;
    hasSignature.value = points.length > 1;
    emit('update:modelValue', toDataUrl());
};

const clear = () => {
    if (props.disabled) return;
    ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
    hasSignature.value = false;
    points = [];
    emit('update:modelValue', '');
};

const toDataUrl = () => {
    return canvasRef.value ? canvasRef.value.toDataURL('image/png') : '';
};

const loadSignature = (dataUrl) => {
    const img = new Image();
    img.onload = () => {
        ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
        ctx.drawImage(img, 0, 0, canvasRef.value.width, canvasRef.value.height);
        hasSignature.value = true;
    };
    img.src = dataUrl;
};

watch(() => props.modelValue, (val) => {
    if (val && canvasRef.value && ctx) {
        loadSignature(val);
    }
});

onMounted(() => {
    initCanvas();
});
</script>

<template>
    <div class="space-y-2">
        <label class="text-sm font-medium text-slate-800">{{ label }}</label>
        <div
            class="relative rounded-xl border-2 border-dashed border-slate-300 bg-white transition focus-within:border-teal-600"
            :class="{ 'border-red-400': error, 'opacity-60': disabled }"
        >
            <canvas
                ref="canvasRef"
                class="block w-full touch-none cursor-crosshair rounded-xl"
                @mousedown="startDraw"
                @mousemove="draw"
                @mouseup="endDraw"
                @mouseleave="endDraw"
                @touchstart="startDraw"
                @touchmove="draw"
                @touchend="endDraw"
            />
            <div
                v-if="!hasSignature && !disabled"
                class="pointer-events-none absolute inset-0 flex items-center justify-center"
            >
                <span class="text-sm text-slate-400">Tandatangan di sini</span>
            </div>
        </div>
        <div v-if="hasSignature && !disabled" class="flex justify-end">
            <button
                type="button"
                class="text-xs font-medium text-red-600 hover:text-red-800"
                @click="clear"
            >
                Padam
            </button>
        </div>
        <p v-if="error" class="text-sm text-red-700">{{ error }}</p>
    </div>
</template>