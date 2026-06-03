<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Download, Printer } from 'lucide-vue-next';
import { toJpeg } from 'html-to-image';
import jsPDF from 'jspdf';

const props = defineProps({
    application: { type: Object, required: true },
    member: { type: Object, required: true },
    cooperative: { type: Object, default: null },
    guarantors: { type: Array, default: () => [] },
});

const downloading = ref(false);

const downloadPdf = async () => {
    const el = document.querySelector('.print-area');
    if (!el) return;
    downloading.value = true;
    try {
        const dataUrl = await toJpeg(el, { quality: 0.95, pixelRatio: 2 });
        const img = new Image();
        img.src = dataUrl;
        await new Promise((resolve) => { img.onload = resolve; });
        const imgWidth = 210;
        const pageHeight = 297;
        const imgHeight = (img.height * imgWidth) / img.width;
        const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        let heightLeft = imgHeight;
        let position = 0;
        pdf.addImage(dataUrl, 'JPEG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;
        while (heightLeft > 0) {
            position -= pageHeight;
            pdf.addPage();
            pdf.addImage(dataUrl, 'JPEG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
        }
        const filename = `borang-${(props.application.product_name || 'permohonan').toLowerCase().replace(/\s+/g, '-')}.pdf`;
        pdf.save(filename);
    } catch {
        window.print();
    }
    downloading.value = false;
};
</script>

<template>
    <Head title="Cetakan Permohonan" />
    <div class="min-h-screen bg-slate-100 p-4 print:bg-white print:p-0">
        <div class="mx-auto max-w-3xl space-y-4">

            <!-- Toolbar (hidden on print) -->
            <div class="no-print flex items-center justify-between">
                <Link :href="`/member/financing/applications/${application.id}`"
                    class="text-sm text-teal-700 hover:underline">
                    &larr; Kembali
                </Link>
                <div class="flex items-center gap-2">
                    <button type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50"
                        :disabled="downloading"
                        @click="downloadPdf">
                        <Download class="h-4 w-4" />
                        {{ downloading ? 'Sedang menjana...' : 'Muat Turun PDF' }}
                    </button>
                    <button type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                        @click="window.print()">
                        <Printer class="h-4 w-4" />
                        Cetak
                    </button>
                </div>
            </div>

            <!-- Print area -->
            <div class="rounded-lg bg-white p-6 shadow print:shadow-none" style="min-height: 257mm; max-width: 210mm; padding: 12mm;">
                <!-- Header -->
                <div class="mb-5 border-b pb-3 text-center">
                    <img v-if="cooperative?.logo_url" :src="cooperative.logo_url" class="mx-auto mb-2 h-14 object-contain" :alt="cooperative.name" />
                    <h1 class="text-base font-bold uppercase">{{ cooperative?.name || 'Koperasi' }}</h1>
                    <p class="mt-1 text-sm font-semibold">BORANG PERMOHONAN PEMBIAYAAN</p>
                    <p class="text-sm">{{ application.product_name }}</p>
                    <p v-if="cooperative?.registration_no" class="text-xs text-slate-500">No. Pendaftaran: {{ cooperative.registration_no }}</p>
                    <p class="mt-1 text-sm">No. Rujukan: <strong>{{ application.reference_no }}</strong></p>
                    <p class="text-xs text-slate-400">Tarikh: {{ application.submitted_at || new Date().toLocaleDateString('ms-MY') }}</p>
                </div>

                <!-- ─── MAKLUMAT AHLI ─── -->
                <div class="mb-5">
                    <h2 class="mb-2 border-b pb-1 text-sm font-bold uppercase text-slate-700">Maklumat Ahli</h2>
                    <table class="w-full text-xs">
                        <tr><td class="w-40 py-1 font-medium text-slate-500">Nama</td><td class="py-1">{{ member.full_name }}</td></tr>
                        <tr><td class="py-1 font-medium text-slate-500">No. Ahli</td><td class="py-1">{{ member.member_no }}</td></tr>
                        <tr v-if="member.identity_no"><td class="py-1 font-medium text-slate-500">No. KP</td><td class="py-1">{{ member.identity_no }}</td></tr>
                        <tr v-if="member.phone"><td class="py-1 font-medium text-slate-500">Telefon</td><td class="py-1">{{ member.phone }}</td></tr>
                        <tr v-if="member.email"><td class="py-1 font-medium text-slate-500">E-mel</td><td class="py-1">{{ member.email }}</td></tr>
                        <tr v-if="member.position"><td class="py-1 font-medium text-slate-500">Jawatan</td><td class="py-1">{{ member.position }}</td></tr>
                    </table>
                </div>

                <!-- ─── MAKLUMAT PERMOHONAN ─── -->
                <div class="mb-5">
                    <h2 class="mb-2 border-b pb-1 text-sm font-bold uppercase text-slate-700">Maklumat Permohonan</h2>
                    <table class="w-full text-xs">
                        <tr><td class="w-40 py-1 font-medium text-slate-500">Kategori</td><td class="py-1">{{ application.category_name }}</td></tr>
                        <tr><td class="py-1 font-medium text-slate-500">Produk</td><td class="py-1">{{ application.product_name }}</td></tr>
                        <tr><td class="py-1 font-medium text-slate-500">Jumlah Dipohon</td><td class="py-1">{{ application.amount_requested }}</td></tr>
                        <tr><td class="py-1 font-medium text-slate-500">Tempoh</td><td class="py-1">{{ application.tenure_months }} bulan</td></tr>
                        <tr v-if="application.purpose"><td class="py-1 font-medium text-slate-500">Tujuan</td><td class="py-1">{{ application.purpose }}</td></tr>
                        <tr v-if="application.monthly_income"><td class="py-1 font-medium text-slate-500">Pendapatan Bulanan</td><td class="py-1">{{ application.monthly_income }}</td></tr>
                        <tr v-if="application.monthly_commitment"><td class="py-1 font-medium text-slate-500">Komitmen Bulanan</td><td class="py-1">{{ application.monthly_commitment }}</td></tr>
                    </table>
                </div>

                <!-- ─── SEKSYEN BORANG DINAMIK ─── -->
                <div v-for="section in application.custom_sections" :key="section.title" class="mb-5">
                    <h2 class="mb-2 border-b pb-1 text-sm font-bold uppercase text-slate-700">{{ section.title }}</h2>
                    <table class="w-full text-xs">
                        <tr v-for="field in section.fields" :key="field.field_key">
                            <td class="w-40 py-1 font-medium text-slate-500 align-top">{{ field.label }}</td>
                            <td class="py-1 whitespace-pre-wrap">{{ field.value ?? '-' }}</td>
                        </tr>
                    </table>
                </div>

                <!-- ─── DOKUMEN PERMOHONAN ─── -->
                <div v-if="application.generated_documents?.length" class="mb-5">
                    <h2 class="mb-2 border-b pb-1 text-sm font-bold uppercase text-slate-700">Dokumen Permohonan</h2>
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-1 pr-2 text-left font-semibold text-slate-600">Dokumen</th>
                                <th class="py-1 text-left font-semibold text-slate-600">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="doc in application.generated_documents" :key="doc.id">
                                <td class="py-1.5 pr-2 font-medium text-slate-700">{{ doc.name || doc.code }}</td>
                                <td class="py-1.5">
                                    <span v-if="doc.uploaded_download_url" class="text-green-700 text-xs">&#10003; Disertakan</span>
                                    <span v-else-if="doc.download_url" class="text-blue-700 text-xs">&#10003; Dijana</span>
                                    <span v-else class="text-slate-400">&#8212;</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ─── PENJAMIN ─── -->
                <div v-if="guarantors.length" class="mb-5">
                    <h2 class="mb-2 border-b pb-1 text-sm font-bold uppercase text-slate-700">Penjamin</h2>
                    <div v-for="(g, idx) in guarantors" :key="g.name" class="mb-4">
                        <p class="text-xs font-semibold text-slate-600 mb-1">Penjamin {{ idx + 1 }}</p>
                        <table class="w-full text-xs">
                            <tr><td class="w-40 py-1 font-medium text-slate-500">Nama</td><td class="py-1">{{ g.name }}</td></tr>
                            <tr><td class="py-1 font-medium text-slate-500">No. Anggota</td><td class="py-1">{{ g.member_no }}</td></tr>
                            <tr><td class="py-1 font-medium text-slate-500">No. Kad Pengenalan</td><td class="py-1">{{ g.identity_no || '-' }}</td></tr>
                            <tr><td class="py-1 font-medium text-slate-500">Telefon</td><td class="py-1">{{ g.phone || '-' }}</td></tr>
                            <tr><td class="py-1 font-medium text-slate-500">Pekerjaan</td><td class="py-1">{{ g.position || '-' }}</td></tr>
                            <tr><td class="py-1 font-medium text-slate-500">Alamat</td><td class="py-1">{{ g.address || '-' }}</td></tr>
                            <tr><td class="py-1 font-medium text-slate-500">Status</td><td class="py-1">{{ g.status_label }}</td></tr>
                        </table>
                        <div v-if="g.signature_data_url" class="mt-3 border-t border-slate-200 pt-2">
                            <p class="text-xs font-medium text-slate-600 mb-1">Tandatangan Penjamin</p>
                            <img :src="g.signature_data_url" alt="Tandatangan penjamin" class="max-h-16 object-contain" />
                            <p class="mt-1 text-[10px] text-slate-400">Tarikh: {{ g.responded_at || '___________' }}</p>
                        </div>
                    </div>
                </div>

                <!-- ─── TANDATANGAN PEMOHON ─── -->
                <div class="mt-8 text-xs">
                    <div class="max-w-xs">
                        <p class="font-medium text-slate-600">Tandatangan Pemohon</p>
                        <div class="mt-4">
                            <img v-if="member.digital_signature" :src="member.digital_signature" alt="Tandatangan pemohon" class="max-h-16 object-contain" />
                            <div v-else class="mt-6 border-t border-slate-400 pt-1">
                                <p class="text-slate-400">(Tandatangan)</p>
                            </div>
                        </div>
                        <div class="mt-2">
                            <p>{{ member.full_name }}</p>
                            <p class="text-slate-400">Tarikh: {{ application.submitted_at || '___________' }}</p>
                        </div>
                    </div>
                    <div class="mt-8 max-w-xs">
                        <p class="font-medium text-slate-600">Diterima Oleh</p>
                        <div class="mt-6 border-t border-slate-400 pt-1">
                            <p class="text-slate-400">Nama: ___________</p>
                            <p class="text-slate-400">Tarikh: ___________</p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-8 border-t pt-3 text-center text-xs text-slate-400">
                    <p v-if="cooperative?.name" class="font-semibold">{{ cooperative.name }}</p>
                    <p v-if="cooperative?.phone || cooperative?.email">{{ cooperative.phone }} &middot; {{ cooperative.email }}</p>
                    <p class="mt-1">Dijana oleh sistem pada {{ application.print_generated_at }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    .no-print { display: none !important; }
}
@page {
    margin: 15mm;
}
</style>
