<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Printer } from 'lucide-vue-next';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    pack: { type: Object, required: true },
});

const printPage = () => window.print();
</script>

<template>
    <div class="min-h-screen bg-slate-100 px-4 py-6 text-slate-900 print:bg-white print:px-0 print:py-0">
        <div class="mx-auto max-w-5xl space-y-6 print:max-w-none print:space-y-0">
            <div class="flex flex-wrap justify-between gap-3 print:hidden">
                <Button :as="Link" :href="pack.back_url" variant="outline">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Kembali
                </Button>
                <Button type="button" @click="printPage">
                    <Printer class="mr-2 h-4 w-4" />
                    Cetak / Simpan PDF
                </Button>
            </div>

            <article class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm print:rounded-none print:border-0 print:p-0 print:shadow-none">
                <header class="flex items-start justify-between gap-6 border-b border-slate-200 pb-6">
                    <div class="flex items-start gap-4">
                        <img v-if="pack.cooperative.logo_url" :src="pack.cooperative.logo_url" :alt="pack.cooperative.name" class="h-16 w-16 object-contain" />
                        <div class="space-y-1">
                            <h1 class="text-xl font-semibold">{{ pack.cooperative.name }}</h1>
                            <p v-if="pack.cooperative.registration_no" class="text-sm text-slate-600">No. Pendaftaran: {{ pack.cooperative.registration_no }}</p>
                            <p v-if="pack.cooperative.address" class="text-sm text-slate-600">{{ pack.cooperative.address }}</p>
                            <p class="text-sm text-slate-600">
                                <span v-if="pack.cooperative.phone">{{ pack.cooperative.phone }}</span>
                                <span v-if="pack.cooperative.phone && pack.cooperative.email"> · </span>
                                <span v-if="pack.cooperative.email">{{ pack.cooperative.email }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="text-right text-sm">
                        <p class="font-semibold text-slate-950">Borang Permohonan Pembiayaan</p>
                        <p>No. Rujukan: {{ pack.application.reference_no }}</p>
                        <p>Status: {{ pack.application.status_label }}</p>
                        <p>Tarikh Cetak: {{ pack.application.print_generated_at }}</p>
                    </div>
                </header>

                <section class="mt-6 grid gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Maklumat Pemohon</h2>
                        <p>Nama: {{ pack.member.full_name || '-' }}</p>
                        <p>No. Ahli: {{ pack.member.member_no || '-' }}</p>
                        <p>No. Kad Pengenalan: {{ pack.member.identity_no || '-' }}</p>
                        <p>Telefon: {{ pack.member.phone || '-' }}</p>
                        <p>Email: {{ pack.member.email || '-' }}</p>
                        <p>Pekerjaan: {{ pack.member.occupation || '-' }}</p>
                        <p>Majikan: {{ pack.member.employer_name || '-' }}</p>
                    </div>
                    <div class="space-y-2">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Maklumat Pembiayaan</h2>
                        <p>Produk: {{ pack.application.product_name || '-' }}</p>
                        <p>Kategori: {{ pack.application.category_name || '-' }}</p>
                        <p>Unit: {{ pack.application.unit_name || '-' }}</p>
                        <p>Amaun Dimohon: {{ pack.application.amount_requested }}</p>
                        <p>Tempoh: {{ pack.application.tenure_months }} bulan</p>
                        <p>Pendapatan Bulanan: {{ pack.application.monthly_income }}</p>
                        <p>Komitmen Bulanan: {{ pack.application.monthly_commitment }}</p>
                    </div>
                </section>

                <section class="mt-6 space-y-2">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Tujuan dan Catatan</h2>
                    <div class="rounded-2xl border border-slate-200 p-4 whitespace-pre-line text-sm leading-7">{{ pack.application.purpose || '-' }}</div>
                    <div class="rounded-2xl border border-slate-200 p-4 whitespace-pre-line text-sm leading-7">{{ pack.application.employment_notes || 'Tiada catatan pekerjaan direkodkan.' }}</div>
                </section>

                <section class="mt-6 grid gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Pasangan / Waris</h2>
                        <div class="rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                            Ruang ini disediakan untuk maklumat pasangan / waris sekiranya diperlukan oleh koperasi.
                        </div>
                    </div>
                    <div class="space-y-2">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Penjamin</h2>
                        <div v-if="pack.guarantors.length" class="space-y-3">
                            <div v-for="guarantor in pack.guarantors" :key="`${guarantor.name}-${guarantor.member_no}`" class="rounded-2xl border border-slate-200 p-4 text-sm">
                                <p>Nama: {{ guarantor.name || '-' }}</p>
                                <p>No. Ahli: {{ guarantor.member_no || '-' }}</p>
                                <p>Status Persetujuan: {{ guarantor.status_label || '-' }}</p>
                                <p class="whitespace-pre-line">{{ guarantor.consent_text || 'Ruang tandatangan penjamin.' }}</p>
                            </div>
                        </div>
                        <div v-else class="rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                            Produk ini tidak memerlukan penjamin.
                        </div>
                    </div>
                </section>

                <section class="mt-6">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Senarai Semak Dokumen</h2>
                    <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-slate-600">Dokumen</th>
                                    <th class="px-4 py-3 text-left font-medium text-slate-600">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <tr v-for="document in pack.required_documents" :key="document">
                                    <td class="px-4 py-3">{{ document }}</td>
                                    <td class="px-4 py-3">{{ pack.documents.some((item) => item.label === document) ? 'Disertakan' : 'Belum disahkan' }}</td>
                                </tr>
                                <tr v-if="!pack.required_documents.length">
                                    <td colspan="2" class="px-4 py-3 text-slate-600">Tiada dokumen wajib ditetapkan untuk produk ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="mt-6 grid gap-6 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Pengakuan Pemohon</h2>
                        <div class="mt-4 min-h-28 border-b border-slate-300"></div>
                        <p class="mt-3 text-sm text-slate-600">Tandatangan Pemohon</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Pengakuan Penjamin</h2>
                        <div class="mt-4 min-h-28 border-b border-slate-300"></div>
                        <p class="mt-3 text-sm text-slate-600">Tandatangan Penjamin / Penjamin Utama</p>
                    </div>
                </section>

                <section class="mt-6 grid gap-6 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Ulasan Unit / Pejabat</h2>
                        <div class="mt-4 min-h-40"></div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Cop Jabatan / Pejabat</h2>
                        <div class="mt-4 min-h-40 rounded-2xl border border-dashed border-slate-300"></div>
                    </div>
                </section>
            </article>
        </div>
    </div>
</template>