<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, FileText } from 'lucide-vue-next';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import { Button } from '@/Shared/Components/ui/button';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';

defineProps({
    application: { type: Object, required: true },
});

const cancelApp = (id) => {
    router.post('/member/ansuran/applications/' + id + '/cancel', {}, { preserveScroll: true });
};
</script>

<template>
    <MemberLayout>
        <Head :title="'Permohonan ' + application.application_no" />
        <section class="space-y-6">
            <Button variant="ghost" size="sm" @click="window.history.back()"><ArrowLeft class="mr-1 h-4 w-4" /> Kembali</Button>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-slate-950">{{ application.application_no }}</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ application.product.name }}</p>
                </div>
                <StatusBadge :status="application.status" :label="application.status_label" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                                <FileText class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-950">Maklumat Pembiayaan</h2>
                                <p class="text-sm text-slate-600">Butiran pembiayaan ansuran anda.</p>
                            </div>
                        </div>
                        <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                            <div><dt class="text-slate-500">Produk</dt><dd class="mt-0.5 font-medium text-slate-950">{{ application.product.name }}</dd></div>
                            <div><dt class="text-slate-500">Varian</dt><dd class="mt-0.5 font-medium text-slate-950">{{ application.variant.name }}</dd></div>
                            <div><dt class="text-slate-500">Harga Penuh</dt><dd class="mt-0.5 font-medium text-slate-950">RM {{ Number(application.financial.full_price).toFixed(2) }}</dd></div>
                            <div><dt class="text-slate-500">Bayaran Pendahuluan</dt><dd class="mt-0.5 font-medium text-slate-950">RM {{ Number(application.financial.down_payment).toFixed(2) }}</dd></div>
                            <div><dt class="text-slate-500">Kadar Keuntungan</dt><dd class="mt-0.5 font-medium text-slate-950">{{ Number(application.financial.interest_rate_percent).toFixed(2) }}%</dd></div>
                            <div><dt class="text-slate-500">Tempoh</dt><dd class="mt-0.5 font-medium text-slate-950">{{ application.financial.tenure_months }} Bulan</dd></div>
                            <div><dt class="text-slate-500">Bayaran Bulanan</dt><dd class="mt-0.5 text-base font-bold text-teal-700">RM {{ Number(application.financial.monthly_amount).toFixed(2) }}</dd></div>
                            <div><dt class="text-slate-500">Jumlah Perlu Dibayar</dt><dd class="mt-0.5 font-medium text-slate-950">RM {{ Number(application.financial.total_payable).toFixed(2) }}</dd></div>
                        </dl>
                    </div>

                    <div v-if="application.guarantors.length > 0" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                                <FileText class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-950">Penjamin</h2>
                                <p class="text-sm text-slate-600">Senarai penjamin permohonan ini.</p>
                            </div>
                        </div>
                        <div class="mt-4 space-y-2">
                            <div v-for="g in application.guarantors" :key="g.name" class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3">
                                <span class="text-sm font-medium text-slate-950">{{ g.name }}</span>
                                <StatusBadge :status="g.status" :label="g.status_label" />
                            </div>
                        </div>
                    </div>

                    <div v-if="application.payments.length > 0" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                                <FileText class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-950">Jadual Bayaran</h2>
                                <p class="text-sm text-slate-600">Jadual pembayaran bulanan anda.</p>
                            </div>
                        </div>
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 text-left text-xs font-medium uppercase tracking-wide text-slate-400">
                                        <th class="pb-2 pr-4">Bulan</th><th class="pb-2 pr-4">Jumlah</th><th class="pb-2 pr-4">Tarikh Akhir</th><th class="pb-2 pr-4">Dibayar</th><th class="pb-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in application.payments" :key="p.month_number" class="border-b border-slate-100">
                                        <td class="py-2.5 pr-4 text-slate-950">Ke-{{ p.month_number }}</td>
                                        <td class="py-2.5 pr-4 text-slate-950">RM {{ Number(p.amount).toFixed(2) }}</td>
                                        <td class="py-2.5 pr-4 text-slate-500">{{ p.due_date }}</td>
                                        <td class="py-2.5 pr-4 text-slate-950">RM {{ Number(p.paid_amount).toFixed(2) }}</td>
                                        <td class="py-2.5">
                                            <span
                                                class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold whitespace-nowrap"
                                                :class="{
                                                    'border-emerald-200 bg-emerald-50 text-emerald-700': p.status === 'paid',
                                                    'border-amber-200 bg-amber-50 text-amber-700': p.status === 'partial',
                                                    'border-red-200 bg-red-50 text-red-700': p.status === 'overdue',
                                                    'border-slate-200 bg-slate-100 text-slate-700': !['paid', 'partial', 'overdue'].includes(p.status),
                                                }"
                                            >{{ p.status_label }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="application.histories.length > 0" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-700">
                                <FileText class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-950">Sejarah</h2>
                                <p class="text-sm text-slate-600">Rekod perubahan status permohonan.</p>
                            </div>
                        </div>
                        <div class="mt-4 space-y-0">
                            <div v-for="h in application.histories" :key="h.created_at + h.action" class="flex gap-3 text-sm">
                                <div class="flex flex-col items-center">
                                    <div class="h-2.5 w-2.5 rounded-full bg-slate-300" />
                                    <div class="h-full w-px bg-slate-200" />
                                </div>
                                <div class="pb-5">
                                    <p class="font-medium text-slate-950">{{ h.action }}</p>
                                    <p class="mt-0.5 text-sm text-slate-400">{{ h.created_at }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                                <FileText class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-950">Tindakan</h2>
                                <p class="text-sm text-slate-600">Tindakan yang tersedia.</p>
                            </div>
                        </div>
                        <div class="mt-5 space-y-3">
                            <Link v-if="application.status === 'agreement_generated'" :href="'/member/ansuran/applications/' + application.id + '/sign'">
                                <Button class="w-full"><FileText class="mr-1 h-4 w-4" /> Tandatangani Perjanjian</Button>
                            </Link>
                            <Button v-if="!['completed', 'rejected', 'cancelled'].includes(application.status)" variant="outline" class="w-full" @click="cancelApp(application.id)">Batal Permohonan</Button>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                                <FileText class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-950">Kaedah Penerimaan</h2>
                                <p class="text-sm text-slate-600">Butiran penerimaan produk.</p>
                            </div>
                        </div>
                        <div class="mt-4 space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">Kaedah</span><span class="font-medium text-slate-950">{{ application.delivery_method === 'pickup' ? 'Ambil Sendiri' : 'Penghantaran' }}</span></div>
                            <div v-if="application.delivery_address" class="flex justify-between"><span class="text-slate-500">Alamat</span><span class="text-right text-slate-950">{{ application.delivery_address }}</span></div>
                            <div v-if="application.delivery_tracking_no" class="flex justify-between"><span class="text-slate-500">Tracking</span><span class="font-mono text-slate-950">{{ application.delivery_tracking_no }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </MemberLayout>
</template>