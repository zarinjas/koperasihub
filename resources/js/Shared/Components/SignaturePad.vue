<script setup>
import { onMounted, ref, watch } from 'vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const canvasRef = ref(null);
const drawing = ref(false);
const hasDrawn = ref(false);

const resizeCanvas = () => {
    const canvas = canvasRef.value;

    if (!canvas) {
        return;
    }

    const ratio = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;

    const context = canvas.getContext('2d');
    context.scale(ratio, ratio);
    context.lineCap = 'round';
    context.lineJoin = 'round';
    context.lineWidth = 2;
    context.strokeStyle = '#0f172a';
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, rect.width, rect.height);

    if (props.modelValue) {
        const image = new Image();
        image.onload = () => {
            context.drawImage(image, 0, 0, rect.width, rect.height);
        };
        image.src = props.modelValue;
        hasDrawn.value = true;
    }
};

const position = (event) => {
    const canvas = canvasRef.value;
    const rect = canvas.getBoundingClientRect();
    return {
        x: event.clientX - rect.left,
        y: event.clientY - rect.top,
    };
};

const start = (event) => {
    drawing.value = true;
    hasDrawn.value = true;
    const context = canvasRef.value.getContext('2d');
    const point = position(event);
    context.beginPath();
    context.moveTo(point.x, point.y);
};

const move = (event) => {
    if (!drawing.value) {
        return;
    }

    const context = canvasRef.value.getContext('2d');
    const point = position(event);
    context.lineTo(point.x, point.y);
    context.stroke();
    emit('update:modelValue', canvasRef.value.toDataURL('image/png'));
};

const stop = () => {
    drawing.value = false;
};

const clear = () => {
    emit('update:modelValue', '');
    hasDrawn.value = false;
    resizeCanvas();
};

onMounted(() => {
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);
});

watch(() => props.modelValue, (value) => {
    if (!value && hasDrawn.value) {
        resizeCanvas();
    }
});
</script>

<template>
    <div class="space-y-3">
        <div class="overflow-hidden rounded-[1.5rem] border border-slate-300 bg-white">
            <canvas
                ref="canvasRef"
                class="h-40 w-full touch-none"
                @pointerdown="start"
                @pointermove="move"
                @pointerup="stop"
                @pointerleave="stop"
            />
        </div>

        <div class="flex items-center justify-between gap-3">
            <p class="text-xs text-slate-500">Lukis tandatangan anda menggunakan tetikus atau sentuhan.</p>
            <Button type="button" variant="outline" @click="clear">Kosongkan</Button>
        </div>

        <p v-if="error" class="text-sm text-red-700">{{ error }}</p>
    </div>
</template>