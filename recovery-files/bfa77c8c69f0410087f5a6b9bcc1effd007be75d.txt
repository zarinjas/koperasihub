<script setup>
import { Head, router } from '@inertiajs/vue3';
import { Copy, Download, Gift, Link2, QrCode, Share2, Sparkles, UsersRound } from 'lucide-vue-next';
import QRCode from 'qrcode';
import { onMounted, ref } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    commissions: { type: Object, required: true },
    stats: { type: Object, required: true },
    referral_code: { type: String, default: null },
    referral_link: { type: String, default: null },
});

const copied = ref(false);
const linkCopied = ref(false);
const qrDataUrl = ref(null);
const showShareSection = ref(true);

const formatCurrency = (amount) => {
    return 'RM' + Number(amount).toFixed(2);
};

const statusVariant = (status) => {
    return {
        pending: 'warning',
        approved: 'success',
        paid: 'info',
        cancelled: 'danger',
    }[status] || 'secondary';
};

const statusLabel = (status) => {
    return {
        pending: 'Tertunda',
        approved: 'Diluluskan',
        paid: 'Dibayar',
        cancelled: 'Dibatalkan',
    }[status] || status;
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ms-MY', { year: 'numeric', month: 'short', day: 'numeric' });
};

const generateQrCode = async () => {
    if (!props.referral_link) return;
    try {
        qrDataUrl.value = await QRCode.toDataURL(props.referral_link, {
            width: 300,
            margin: 2,
            color: { dark: '#0F766E', light: '#FFFFFF' },
        });
    } catch {
        // ignore
    }
};

const copyCode = async () => {
    if (!props.referral_code) return;
    try {
        await navigator.clipboard.writeText(props.referral_code);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    } catch {
        // ignore
    }
};

const copyLink = async () => {
    if (!props.referral_link) return;
    try {
        await navigator.clipboard.writeText(props.referral_link);
        linkCopied.value = true;
        setTimeout(() => { linkCopied.value = false; }, 2000);
    } catch {
        // ignore
    }
};

const downloadQr = () => {
    if (!qrDataUrl.value) return;
    const a = document.createElement('a');
    a.href = qrDataUrl.value;
    a.download = `rujukan-${props.referral_code || 'saya'}.png`;
    a.click();
};

const shareWhatsApp = () => {
    if (!props.referral_link) return;
    const text = encodeURIComponent(
        'Jom sertai koperasi saya! Gunakan pautan di bawah untuk daftar:\n\n' + props.referral_link
    );
    window.open(`https://wa.me/?text=${text}`, '_blank');
};

const generateCode = () => {
    router.post('/member/referrals/generate', {}, {
        preserveScroll: true,
    });
};

onMounted(() => {
    generateQrCode();
});
</script>

<template>
    <Head title="Rujukan Saya" />

    <MemberLayout>
        <div class="space-y-6">
            <PageHeader
                title="Rujukan Saya"
                description="Kongsikan kod rujukan anda dengan rakan-rakan dan dapatkan komisyen untuk setiap ahli baru yang berjaya diterima masuk."
            >
                <template #actions>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                        <UsersRound class="h-5 w-5" />
                    </span>
                </template>
            </PageHeader>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Jumlah Rujukan</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ stats.total_referrals }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Komisyen Diperoleh</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-600">{{ formatCurrency(stats.total_earned) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Komisyen Dibayar</p>
                    <p class="mt-1 text-2xl font-semibold text-blue-600">{{ formatCurrency(stats.total_paid) }}</p>
                </div>
            </div>

            <div v-if="referral_code" class="rounded-2xl border border-teal-100 bg-white p-6 shadow-sm">
                <div class="grid gap-8 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                                <Share2 class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-950">Kongsi & Dapatkan Komisyen</h2>
                                <p class="text-sm text-slate-500">Kongsikan pautan atau kod rujukan anda. Dapatkan komisyen untuk setiap ahli baru yang berjaya diterima.</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <Link2 class="h-4 w-4 shrink-0 text-slate-400" />
                                <code class="flex-1 truncate text-sm text-slate-700">{{ referral_link }}</code>
                                <Button variant="outline" size="sm" class="shrink-0" @click="copyLink">
                                    <Copy class="mr-1.5 h-3.5 w-3.5" />
                                    {{ linkCopied ? 'Disalin!' : 'Salin' }}
                                </Button>
                            </div>

                            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <QrCode class="h-4 w-4 shrink-0 text-slate-400" />
                                <span class="flex-1 text-sm font-mono font-bold tracking-wider text-slate-700">{{ referral_code }}</span>
                                <Button variant="outline" size="sm" class="shrink-0" @click="copyCode">
                                    <Copy class="mr-1.5 h-3.5 w-3.5" />
                                    {{ copied ? 'Disalin!' : 'Salin' }}
                                </Button>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Button @click="shareWhatsApp">
                                <Share2 class="mr-1.5 h-4 w-4" />
                                Kongsi di WhatsApp
                            </Button>
                            <Button variant="outline" @click="downloadQr" :disabled="!qrDataUrl">
                                <Download class="mr-1.5 h-4 w-4" />
                                Muat Turun QR
                            </Button>
                        </div>
                    </div>

                    <div class="flex justify-center">
                        <div v-if="qrDataUrl" class="rounded-2xl border-2 border-teal-100 bg-white p-2">
                            <img :src="qrDataUrl" alt="Kod QR Rujukan" class="h-40 w-40" />
                        </div>
                        <div v-else class="flex h-44 w-44 items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50">
                            <QrCode class="h-8 w-8 text-slate-300" />
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!referral_code" class="rounded-2xl border border-amber-100 bg-gradient-to-r from-amber-50 to-orange-50 p-6 shadow-sm">
                <div class="flex flex-col items-center gap-4 text-center sm:flex-row sm:text-left">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <Sparkles class="h-6 w-6" />
                    </span>
                    <div class="flex-1">
                        <h2 class="text-lg font-semibold text-amber-900">Kod Rujukan Belum Dijana</h2>
                        <p class="text-sm text-amber-700">Jana kod rujukan anda untuk mula berkongsi dengan rakan-rakan dan dapatkan komisyen.</p>
                    </div>
                    <Button @click="generateCode" class="shrink-0">
                        <Sparkles class="mr-1.5 h-4 w-4" />
                        Jana Kod Rujukan
                    </Button>
                </div>
            </div>

            <EmptyState
                v-if="commissions.data.length === 0"
                title="Belum ada rujukan."
                description="Kongsikan kod rujukan anda untuk mula memperkenalkan rakan-rakan ke koperasi."
            >
                <template #icon>
                    <Gift class="h-12 w-12 text-slate-300" />
                </template>
            </EmptyState>

            <div v-else class="space-y-4">
                <h2 class="text-lg font-semibold">Sejarah Rujukan</h2>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <table class="w-full text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium text-slate-500">Ahli Dirujuk</th>
                                <th class="px-4 py-3 font-medium text-slate-500">Permohonan</th>
                                <th class="px-4 py-3 font-medium text-slate-500">Amaun</th>
                                <th class="px-4 py-3 font-medium text-slate-500">Status</th>
                                <th class="px-4 py-3 font-medium text-slate-500">Tarikh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="commission in commissions.data" :key="commission.id" class="border-t border-slate-100">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-950">{{ commission.referred_member.full_name }}</p>
                                    <p class="text-xs text-slate-500">{{ commission.referred_member.member_no }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-slate-700">{{ commission.application_no }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-950">{{ formatCurrency(commission.commission_amount) }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <StatusBadge :variant="statusVariant(commission.status)">
                                        {{ statusLabel(commission.status) }}
                                    </StatusBadge>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-slate-600">{{ formatDate(commission.created_at) }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="commissions.data.length > 0" class="mt-6 flex items-center justify-between text-sm text-slate-600">
                    <span>{{ commissions.total }} rekod</span>
                    <div class="flex items-center gap-2">
                        <template v-for="link in commissions.links" :key="link.url">
                            <Button
                                v-if="link.url"
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                as="a"
                                :href="link.url"
                            >
                                <span v-html="link.label" />
                            </Button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </MemberLayout>
</template>
