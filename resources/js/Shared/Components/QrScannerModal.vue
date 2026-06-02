<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { Html5Qrcode } from 'html5-qrcode';
import { X } from 'lucide-vue-next';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Imbas Kod QR' },
});

const emit = defineEmits(['close', 'scanned']);

const scannerRef = ref(null);
const statusText = ref('Menunggu kamera...');
const isScanning = ref(false);
let html5QrCode = null;

onMounted(() => {
    if (props.open) {
        startScanner();
    }
});

onUnmounted(() => {
    stopScanner();
});

function startScanner() {
    if (!scannerRef.value) return;

    statusText.value = 'Mengakses kamera...';
    isScanning.value = true;

    html5QrCode = new Html5Qrcode('qr-scanner-view');

    html5QrCode.start(
        { facingMode: 'environment' },
        {
            fps: 10,
            qrbox: { width: 250, height: 250 },
        },
        onScanSuccess,
        () => {},
    ).then(() => {
        statusText.value = 'Arahkan kamera ke kod QR';
    }).catch(() => {
        statusText.value = 'Kamera tidak tersedia. Sila semak kebenaran kamera.';
        isScanning.value = false;
    });
}

function stopScanner() {
    if (html5QrCode) {
        html5QrCode.stop().catch(() => {});
        html5QrCode.clear().catch(() => {});
        html5QrCode = null;
    }
    isScanning.value = false;
}

function onScanSuccess(decodedText) {
    stopScanner();
    emit('scanned', decodedText);
}

function close() {
    stopScanner();
    emit('close');
}
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4">
            <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-slate-900">{{ title }}</h3>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600" @click="close">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="p-5">
                    <div class="relative mx-auto aspect-square max-w-sm overflow-hidden rounded-xl bg-slate-900">
                        <div id="qr-scanner-view" ref="scannerRef" class="h-full w-full"></div>
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="h-56 w-56 rounded-lg border-2 border-white/50"></div>
                        </div>
                    </div>

                    <p class="mt-3 text-center text-sm" :class="isScanning ? 'text-slate-500' : 'text-red-500'">
                        {{ statusText }}
                    </p>
                </div>

                <div class="flex justify-end border-t border-slate-200 px-5 py-4">
                    <Button type="button" variant="outline" @click="close">Batal</Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
