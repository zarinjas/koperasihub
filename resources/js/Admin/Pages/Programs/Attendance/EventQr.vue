<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import QRCode from 'qrcode';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    program: { type: Object, required: true },
    checkInUrl: { type: String, required: true },
});

const qrDataUrl = ref(null);
const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);

onMounted(async () => {
    try {
        qrDataUrl.value = await QRCode.toDataURL(props.checkInUrl, {
            width: 400,
            margin: 2,
            color: { dark: '#0F172A', light: '#FFFFFF' },
        });
    } catch {
        qrDataUrl.value = null;
    }
});

const downloadQr = () => {
    if (!qrDataUrl.value) return;
    const link = document.createElement('a');
    link.download = `qr-${props.program.slug || props.program.id}.png`;
    link.href = qrDataUrl.value;
    link.click();
};
</script>

<template>
    <Head title="QR Acara" />

    <AdminLayout>
        <section class="mx-auto max-w-2xl space-y-6">
            <PageHeader title="QR Code Acara" :description="`Imbas untuk daftar masuk: ${program.title}`">
                <template #actions>
                    <Button variant="outline" :as="Link" :href="`/admin/programs/${program.id}/attendance`">
                        Kembali
                    </Button>
                </template>
            </PageHeader>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="text-center">
                    <h2 class="text-xl font-semibold">{{ program.title }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ program.start_date_human }}
                        <span v-if="program.location"> &middot; {{ program.location }}</span>
                    </p>
                    <div class="mt-2">
                        <StatusBadge :status="program.status" />
                    </div>
                </div>

                <div class="mt-6 flex flex-col items-center gap-6">
                    <div v-if="qrDataUrl" class="rounded-2xl border-2 border-slate-200 p-4">
                        <img :src="qrDataUrl" alt="QR Code untuk daftar masuk" class="h-80 w-80" />
                    </div>
                    <div v-else class="flex h-80 w-80 items-center justify-center rounded-2xl border-2 border-dashed border-slate-300">
                        <p class="text-sm text-slate-400">Sedang menjana QR...</p>
                    </div>

                    <div class="text-center">
                        <p class="text-sm text-slate-600">Ahli boleh imbas QR ini untuk daftar masuk sendiri.</p>
                        <p class="mt-1 text-xs text-slate-400">Paparkan pada skrin atau cetak untuk kegunaan fizikal.</p>
                    </div>

                    <Button @click="downloadQr" :disabled="!qrDataUrl">
                        <Download class="mr-2 h-4 w-4" />
                        Muat Turun QR
                    </Button>
                </div>
            </section>
        </section>
    </AdminLayout>
</template>