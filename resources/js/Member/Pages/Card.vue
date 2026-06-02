<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Download, ImageDown, Link2, Smartphone, Share2, WalletCards } from 'lucide-vue-next';
import { ref } from 'vue';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import MemberDigitalCard from '@/Shared/Components/MemberDigitalCard.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import { copyText, downloadMemberCardJpg, downloadMemberCardPdf, shareLink } from '@/Shared/lib/memberCardActions';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    card: {
        type: Object,
        required: true,
    },
});

const cardCaptureRef = ref(null);
const statusMessage = ref(null);
const errorMessage = ref(null);
const isDownloadingJpg = ref(false);
const isDownloadingPdf = ref(false);
const isSharing = ref(false);
const walletDialogOpen = ref(false);
const walletDialogTitle = ref('Tambah ke Wallet');

const setMessage = (message) => {
    statusMessage.value = message;
    errorMessage.value = null;
};

const setError = (message) => {
    errorMessage.value = message;
    statusMessage.value = null;
};

const guardCardReady = () => {
        if (!props.card.readiness?.has_profile_photo) {
            setError('Muat naik gambar profil untuk mengaktifkan fungsi muat turun dan perkongsian kad. Sila pergi ke halaman profil untuk muat naik.');

        return false;
    }

    return true;
};

const fileBaseName = () => `kad-${props.card.member_no || 'ahli'}`;

const downloadJpg = async () => {
    if (!guardCardReady() || !cardCaptureRef.value) {
        return;
    }

    isDownloadingJpg.value = true;

    try {
        await downloadMemberCardJpg(cardCaptureRef.value, fileBaseName());
        setMessage('Kad ahli dalam format JPG berjaya dimuat turun.');
    } catch {
        setError('Muat turun JPG tidak berjaya. Sila cuba lagi.');
    } finally {
        isDownloadingJpg.value = false;
    }
};

const downloadPdf = async () => {
    if (!guardCardReady() || !cardCaptureRef.value) {
        return;
    }

    isDownloadingPdf.value = true;

    try {
        await downloadMemberCardPdf(cardCaptureRef.value, fileBaseName());
        setMessage('Kad ahli dalam format PDF berjaya dimuat turun.');
    } catch {
        setError('Muat turun PDF tidak berjaya. Sila cuba lagi.');
    } finally {
        isDownloadingPdf.value = false;
    }
};

const shareCard = async () => {
    if (!guardCardReady()) {
        return;
    }

    isSharing.value = true;

    try {
        const result = await shareLink({
            title: 'Kad Keahlian Digital',
            text: 'Semak pengesahan keahlian melalui pautan ini.',
            url: props.card.verification_url,
        });

        setMessage(result === 'shared'
            ? 'Pautan kad berjaya dikongsi.'
            : 'Pautan kad disalin ke papan klip.');
    } catch {
        setError('Fungsi kongsi tidak berjaya. Sila cuba lagi.');
    } finally {
        isSharing.value = false;
    }
};

const copyLink = async () => {
    if (!guardCardReady()) {
        return;
    }

    try {
        await copyText(props.card.verification_url);
        setMessage('Pautan verifikasi berjaya disalin.');
    } catch {
        setError('Pautan verifikasi tidak dapat disalin.');
    }
};

const openWalletDialog = (label) => {
    walletDialogTitle.value = label;
    walletDialogOpen.value = true;
};
</script>

<template>
    <Head title="Kad Keahlian Digital" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader
                title="Kad Keahlian Digital"
                description="Paparan kad ahli digital anda untuk semakan, muat turun, dan perkongsian pautan verifikasi."
            />

            <div v-if="card.readiness.notice" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">
                {{ card.readiness.notice }}
                <Link href="/member/profile?edit=1" class="ml-2 inline-flex items-center gap-1 underline font-semibold">
                    Muat Naik Sekarang
                </Link>
            </div>
            <div v-else-if="card.readiness.is_limited" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800">
                Kad ini dipaparkan dalam mod terhad kerana status keahlian anda tidak aktif.
            </div>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>
            <div v-if="errorMessage" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800">
                {{ errorMessage }}
            </div>

            <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div ref="cardCaptureRef" class="mx-auto max-w-[440px]">
                        <MemberDigitalCard
                            :cooperative="$page.props.appSettings.cooperative"
                            :card="card"
                            size="large"
                        />
                    </div>
                </div>

                <div class="space-y-6">
                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-950">Tindakan Kad</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Gunakan pautan verifikasi awam untuk tujuan semakan. Data peribadi sensitif tidak dikongsi melalui halaman ini.
                        </p>

                        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-2">
                            <Button :disabled="!card.readiness.is_ready || isDownloadingJpg" @click="downloadJpg">
                                <ImageDown class="mr-2 h-4 w-4" />
                                {{ isDownloadingJpg ? 'Memproses...' : 'Muat Turun JPG' }}
                            </Button>
                            <Button variant="outline" :disabled="!card.readiness.is_ready || isDownloadingPdf" @click="downloadPdf">
                                <Download class="mr-2 h-4 w-4" />
                                {{ isDownloadingPdf ? 'Memproses...' : 'Muat Turun PDF' }}
                            </Button>
                            <Button variant="outline" :disabled="!card.readiness.is_ready || isSharing" @click="shareCard">
                                <Share2 class="mr-2 h-4 w-4" />
                                {{ isSharing ? 'Memproses...' : 'Kongsi Kad' }}
                            </Button>
                            <Button variant="outline" :disabled="!card.readiness.is_ready" @click="copyLink">
                                <Link2 class="mr-2 h-4 w-4" />
                                Salin Pautan
                            </Button>
                            <Button variant="outline" @click="openWalletDialog('Tambah ke Apple Wallet')">
                                <WalletCards class="mr-2 h-4 w-4" />
                                Tambah ke Apple Wallet
                            </Button>
                            <Button variant="outline" @click="openWalletDialog('Tambah ke Google Wallet')">
                                <Smartphone class="mr-2 h-4 w-4" />
                                Tambah ke Google Wallet
                            </Button>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-950">Ringkasan Kad</h2>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Nama ahli</p>
                                <p class="mt-2 text-sm font-semibold text-slate-950">{{ card.full_name }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. ahli</p>
                                <p class="mt-2 text-sm font-semibold text-slate-950">{{ card.member_no }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Status keahlian</p>
                                <p class="mt-2 text-sm font-semibold text-slate-950">{{ card.membership_status_label }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh sertai</p>
                                <p class="mt-2 text-sm font-semibold text-slate-950">{{ card.joined_at || '-' }}</p>
                            </div>
                        </div>
                        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
                            Pautan verifikasi akan membuka paparan awam yang selamat tanpa nombor kad pengenalan, alamat, telefon, e-mel, atau dokumen peribadi.
                        </div>
                    </section>
                </div>
            </div>
        </section>

        <ConfirmDialog
            :open="walletDialogOpen"
            :title="walletDialogTitle"
            description="Ciri ini akan tersedia dalam versi aplikasi mudah alih."
            confirm-label="Faham"
            cancel-label="Tutup"
            variant="default"
            @cancel="walletDialogOpen = false"
            @confirm="walletDialogOpen = false"
        />
    </MemberLayout>
</template>