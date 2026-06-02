<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, FileText, Truck, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    application: { type: Object, required: true },
    templates: { type: Array, default: () => [] },
    deliveryStatuses: { type: Array, default: () => [] },
});

const approveDialog = ref(false);
const rejectDialog = ref(false);
const agreementDialog = ref(false);
const deliveryDialog = ref(false);
const paymentDialog = ref(false);

const approveForm = useForm({ notes: '' });
const rejectForm = useForm({ reason: '' });
const cancelForm = useForm({ reason: '' });
const agreementForm = useForm({ template_id: '' });
const deliveryForm = useForm({ delivery_status: '', delivery_tracking_no: '' });
const paymentForm = useForm({ payment_id: '', paid_amount: 0, payment_method: '', reference_no: '' });

const markUnderReview = (id) => router.post('/admin/ansuran/applications/' + id + '/in-review', {}, { preserveScroll: true });
const doApprove = (id) => { approveForm.post('/admin/ansuran/applications/' + id + '/approve', { preserveScroll: true, onSuccess: () => approveDialog.value = false }); };
const doReject = (id) => { rejectForm.post('/admin/ansuran/applications/' + id + '/reject', { preserveScroll: true, onSuccess: () => rejectDialog.value = false }); };
const doCancel = (id) => { cancelForm.post('/admin/ansuran/applications/' + id + '/cancel', { preserveScroll: true }); };
const doAgreement = (id) => { agreementForm.post('/admin/ansuran/applications/' + id + '/generate-agreement', { preserveScroll: true, onSuccess: () => agreementDialog.value = false }); };
const doDelivery = (id) => { deliveryForm.post('/admin/ansuran/applications/' + id + '/delivery', { preserveScroll: true, onSuccess: () => deliveryDialog.value = false }); };
const doSchedule = (id) => router.post('/admin/ansuran/applications/' + id + '/generate-schedule', {}, { preserveScroll: true });
const doPayment = (id) => { paymentForm.post('/admin/ansuran/applications/' + id + '/record-payment', { preserveScroll: true, onSuccess: () => paymentDialog.value = false }); };

const templateOptions = computed(() => [
    { value: '', label: 'Pilih template' },
    ...props.templates.map((t) => ({ value: String(t.id), label: t.name })),
]);

const deliveryStatusOptions = computed(() => [
    { value: '', label: 'Pilih status' },
    ...props.deliveryStatuses.map((ds) => ({ value: ds.value, label: ds.label })),
]);

const paymentOptions = computed(() => {
    return (props.application.payments || []).map((p) => ({
        value: String(p.id),
        label: `Bulan Ke-${p.month_number} - RM ${Number(p.amount).toFixed(2)}`,
    }));
});
</script>

<template>
    <AdminLayout>
        <Head :title="'Permohonan ' + application.application_no" />
        <PageHeader :title="'Permohonan ' + application.application_no" :description="application.member?.name || ''">
            <template #actions>
                <Button variant="outline" @click="window.history.back()"><ArrowLeft class="w-4 h-4 mr-1" /> Kembali</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-2">
                <Button v-if="application.status === 'pending'" @click="markUnderReview(application.id)">Mula Semakan</Button>
                <Button v-if="['under_review'].includes(application.status)" @click="approveDialog = true"><CheckCircle class="w-4 h-4 mr-1" /> Luluskan</Button>
                <Button v-if="['pending', 'under_review'].includes(application.status)" variant="destructive" @click="rejectDialog = true"><XCircle class="w-4 h-4 mr-1" /> Tolak</Button>
                <Button v-if="application.status === 'approved'" @click="agreementDialog = true"><FileText class="w-4 h-4 mr-1" /> Jana Perjanjian</Button>
                <Button v-if="['signed', 'processing'].includes(application.status)" @click="deliveryDialog = true"><Truck class="w-4 h-4 mr-1" /> Kemaskini Penghantaran</Button>
                <Button v-if="application.status === 'signed'" variant="outline" @click="doSchedule(application.id)">Jana Jadual Bayaran</Button>
                <Button v-if="!['completed', 'rejected', 'cancelled'].includes(application.status)" variant="outline" @click="doCancel(application.id)">Batal</Button>
                <Button v-if="application.payments?.length > 0" variant="outline" @click="paymentDialog = true">Rekod Bayaran</Button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column (2/3) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Member Info -->
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-950 mb-4">Maklumat Ahli</h2>
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <dt class="text-slate-500">Nama</dt>
                            <dd class="text-slate-900">{{ application.member?.name || '-' }}</dd>
                            <dt class="text-slate-500">No Ahli</dt>
                            <dd class="text-slate-900">{{ application.member?.member_no || '-' }}</dd>
                            <dt class="text-slate-500">No KP</dt>
                            <dd class="text-slate-900">{{ application.member?.identity_no || '-' }}</dd>
                        </dl>
                    </div>

                    <!-- Product & Financing -->
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-950 mb-4">Butiran Produk & Pembiayaan</h2>
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <dt class="text-slate-500">Produk</dt>
                            <dd class="text-slate-900">{{ application.product?.name || '-' }}</dd>
                            <dt class="text-slate-500">Kategori</dt>
                            <dd class="text-slate-900">{{ application.product?.category_name || '-' }}</dd>
                            <dt class="text-slate-500">Varian</dt>
                            <dd class="text-slate-900">{{ application.variant?.name || '-' }}</dd>
                            <dt class="text-slate-500">Harga Penuh</dt>
                            <dd class="text-slate-900">RM {{ Number(application.financial?.full_price || 0).toFixed(2) }}</dd>
                            <dt class="text-slate-500">Bayaran Pendahuluan</dt>
                            <dd class="text-slate-900">RM {{ Number(application.financial?.down_payment || 0).toFixed(2) }}</dd>
                            <dt class="text-slate-500">Jumlah Pembiayaan</dt>
                            <dd class="text-slate-900">RM {{ Number(application.financial?.financed_amount || 0).toFixed(2) }}</dd>
                            <dt class="text-slate-500">Kadar Keuntungan</dt>
                            <dd class="text-slate-900">{{ Number(application.financial?.interest_rate_percent || 0).toFixed(2) }}%</dd>
                            <dt class="text-slate-500">Tempoh</dt>
                            <dd class="text-slate-900">{{ application.financial?.tenure_months || '-' }} Bulan</dd>
                            <dt class="text-slate-500">Bayaran Bulanan</dt>
                            <dd class="text-slate-900">RM {{ Number(application.financial?.monthly_amount || 0).toFixed(2) }}</dd>
                            <dt class="text-slate-500">Jumlah Perlu Dibayar</dt>
                            <dd class="text-slate-900">RM {{ Number(application.financial?.total_payable || 0).toFixed(2) }}</dd>
                        </dl>
                    </div>

                    <!-- Guarantors -->
                    <div v-if="application.guarantors?.length > 0" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-950 mb-4">Penjamin</h2>
                        <div class="space-y-2">
                            <div v-for="g in application.guarantors" :key="g.id" class="flex items-center justify-between p-3 border rounded-lg">
                                <span class="font-medium text-slate-900">{{ g.name }}</span>
                                <StatusBadge :status="g.status === 'accepted' ? 'approved' : g.status === 'rejected' ? 'rejected' : 'pending'" :label="g.status_label" />
                            </div>
                        </div>
                    </div>

                    <!-- Payments Table -->
                    <div v-if="application.payments?.length > 0" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-950 mb-4">Jadual Bayaran</h2>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left">
                                        <th class="pb-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Bulan</th>
                                        <th class="pb-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Jumlah</th>
                                        <th class="pb-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh Akhir</th>
                                        <th class="pb-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Dibayar</th>
                                        <th class="pb-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in application.payments" :key="p.id" class="border-b">
                                        <td class="py-2 text-slate-700">Ke-{{ p.month_number }}</td>
                                        <td class="py-2 text-slate-700">RM {{ Number(p.amount).toFixed(2) }}</td>
                                        <td class="py-2 text-slate-700">{{ p.due_date || '-' }}</td>
                                        <td class="py-2 text-slate-700">RM {{ Number(p.paid_amount || 0).toFixed(2) }}</td>
                                        <td class="py-2"><StatusBadge :status="p.status" :label="p.status_label" /></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Agreement -->
                    <div v-if="application.agreement_content" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-950 mb-4">Perjanjian</h2>
                        <div v-if="application.agreement_content" class="prose prose-sm max-w-none border rounded-lg p-4 bg-slate-50" v-html="application.agreement_content" />
                        <div v-if="application.signed_agreement_content" class="mt-4 prose prose-sm max-w-none border rounded-lg p-4 bg-slate-50" v-html="application.signed_agreement_content" />
                        <p v-if="application.signed_at" class="text-sm text-slate-500 mt-2">Ditandatangani pada {{ application.signed_at }}</p>
                    </div>

                    <!-- History -->
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-950 mb-4">Sejarah</h2>
                        <div class="space-y-3">
                            <div v-for="h in application.histories" :key="h.created_at + h.action" class="flex gap-3 text-sm">
                                <div class="w-1 bg-slate-200 rounded-full flex-shrink-0" />
                                <div>
                                    <div class="text-slate-900">{{ h.action }}</div>
                                    <div class="text-slate-400">{{ h.actor_name }} &middot; {{ h.created_at }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (1/3): Sidebar -->
                <div class="space-y-6">
                    <!-- Status Card -->
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-950 mb-4">Status</h2>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Status</span>
                                <StatusBadge :status="application.status" :label="application.status_label" />
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Kaedah</span>
                                <span class="text-slate-900">{{ application.delivery_method === 'pickup' ? 'Ambil Sendiri' : 'Penghantaran' }}</span>
                            </div>
                            <div v-if="application.delivery_address" class="flex items-start justify-between">
                                <span class="text-slate-500">Alamat</span>
                                <span class="text-right text-slate-900 max-w-[60%]">{{ application.delivery_address }}</span>
                            </div>
                            <div v-if="application.delivery_status" class="flex items-center justify-between">
                                <span class="text-slate-500">Penghantaran</span>
                                <StatusBadge :status="application.delivery_status" />
                            </div>
                            <div v-if="application.delivery_tracking_no" class="flex items-center justify-between">
                                <span class="text-slate-500">Tracking</span>
                                <span class="text-sm font-mono text-slate-900">{{ application.delivery_tracking_no }}</span>
                            </div>
                            <div v-if="application.rejection_reason" class="flex items-start justify-between">
                                <span class="text-slate-500">Sebab Tolak</span>
                                <span class="text-right text-red-600 max-w-[60%]">{{ application.rejection_reason }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar Action Buttons -->
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-3">
                        <Button v-if="application.status === 'signed'" variant="outline" class="w-full" @click="doSchedule(application.id)">Jana Jadual Bayaran</Button>
                        <Button v-if="application.payments?.length > 0" variant="outline" class="w-full" @click="paymentDialog = true">Rekod Bayaran</Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approve Modal -->
        <div v-if="approveDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-slate-950">Luluskan Permohonan</h3>
                <form @submit.prevent="doApprove(application.id)" class="mt-4 space-y-4">
                    <TextareaInput id="approve_notes" v-model="approveForm.notes" label="Nota (pilihan)" :rows="3" />
                    <div class="flex gap-2 justify-end">
                        <Button type="button" variant="outline" @click="approveDialog = false">Batal</Button>
                        <Button type="submit" :disabled="approveForm.processing">Luluskan</Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reject Modal -->
        <div v-if="rejectDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-slate-950">Tolak Permohonan</h3>
                <form @submit.prevent="doReject(application.id)" class="mt-4 space-y-4">
                    <TextareaInput id="reject_reason" v-model="rejectForm.reason" label="Sebab Penolakan" :rows="3" :error="rejectForm.errors.reason" required />
                    <div class="flex gap-2 justify-end">
                        <Button type="button" variant="outline" @click="rejectDialog = false">Batal</Button>
                        <Button type="submit" variant="destructive" :disabled="rejectForm.processing">Tolak</Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Agreement Modal -->
        <div v-if="agreementDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-slate-950">Jana Perjanjian</h3>
                <form @submit.prevent="doAgreement(application.id)" class="mt-4 space-y-4">
                    <SelectInput id="template_id" v-model="agreementForm.template_id" label="Template Perjanjian" :options="templateOptions" :error="agreementForm.errors.template_id" />
                    <div class="flex gap-2 justify-end">
                        <Button type="button" variant="outline" @click="agreementDialog = false">Batal</Button>
                        <Button type="submit" :disabled="agreementForm.processing">Jana</Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delivery Modal -->
        <div v-if="deliveryDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-slate-950">Kemaskini Penghantaran</h3>
                <form @submit.prevent="doDelivery(application.id)" class="mt-4 space-y-4">
                    <SelectInput id="delivery_status" v-model="deliveryForm.delivery_status" label="Status Penghantaran" :options="deliveryStatusOptions" :error="deliveryForm.errors.delivery_status" />
                    <TextInput id="delivery_tracking_no" v-model="deliveryForm.delivery_tracking_no" label="No Tracking (pilihan)" :error="deliveryForm.errors.delivery_tracking_no" />
                    <div class="flex gap-2 justify-end">
                        <Button type="button" variant="outline" @click="deliveryDialog = false">Batal</Button>
                        <Button type="submit" :disabled="deliveryForm.processing">Simpan</Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payment Modal -->
        <div v-if="paymentDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-slate-950">Rekod Bayaran</h3>
                <form @submit.prevent="doPayment(application.id)" class="mt-4 space-y-4">
                    <SelectInput id="payment_id" v-model="paymentForm.payment_id" label="Bulan" :options="paymentOptions" :error="paymentForm.errors.payment_id" />
                    <TextInput id="paid_amount" v-model.number="paymentForm.paid_amount" label="Jumlah Dibayar (RM)" type="number" :error="paymentForm.errors.paid_amount" />
                    <TextInput id="payment_method" v-model="paymentForm.payment_method" label="Kaedah Bayaran" :error="paymentForm.errors.payment_method" />
                    <TextInput id="reference_no" v-model="paymentForm.reference_no" label="No Rujukan" :error="paymentForm.errors.reference_no" />
                    <div class="flex gap-2 justify-end">
                        <Button type="button" variant="outline" @click="paymentDialog = false">Batal</Button>
                        <Button type="submit" :disabled="paymentForm.processing">Simpan</Button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>