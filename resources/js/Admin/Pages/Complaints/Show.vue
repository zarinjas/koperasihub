<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Send, Shield } from 'lucide-vue-next';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    complaint: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
    priorityOptions: { type: Array, required: true },
    categoryOptions: { type: Array, required: true },
    assigneeOptions: { type: Array, required: true },
    canReply: { type: Boolean, default: false },
    canClose: { type: Boolean, default: false },
});

const updateForm = useForm({
    status: props.complaint.status,
    priority: props.complaint.priority,
    assigned_to: props.complaint.assigned_to || '',
});

const replyForm = useForm({
    message: '',
    is_internal: false,
});

const saveComplaint = () => {
    updateForm.patch(`/admin/complaints/${props.complaint.id}`, {
        preserveScroll: true,
    });
};

const sendReply = () => {
    replyForm.post(`/admin/complaints/${props.complaint.id}/replies`, {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset('message', 'is_internal');
        },
    });
};
</script>

<template>
    <Head :title="complaint.ticket_no" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Butiran Aduan"
                description="Semak isu yang dilaporkan, tambah maklum balas, dan kemas kini status tindakan."
            >
                <template #actions>
                    <Button :as="Link" href="/admin/complaints" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <div class="space-y-6">
                    <FormSection title="Maklumat Tiket" description="Maklumat yang dihantar oleh ahli melalui portal." :columns="2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. tiket</p>
                            <p class="mt-1 text-sm font-semibold text-slate-950">{{ complaint.ticket_no }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kategori</p>
                            <p class="mt-1 text-sm text-slate-700">{{ complaint.category_label }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Nama ahli</p>
                            <p class="mt-1 text-sm text-slate-700">{{ complaint.member_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. telefon</p>
                            <p class="mt-1 text-sm text-slate-700">{{ complaint.member_phone || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">E-mel</p>
                            <p class="mt-1 text-sm text-slate-700">{{ complaint.member_email || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh dihantar</p>
                            <p class="mt-1 text-sm text-slate-700">{{ complaint.submitted_at }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tajuk</p>
                            <p class="mt-1 text-sm text-slate-700">{{ complaint.subject }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Mesej asal</p>
                            <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ complaint.message }}</p>
                        </div>
                    </FormSection>

                    <FormSection title="Balasan dan Catatan" description="Catatan dalaman hanya dilihat oleh admin." :columns="1">
                        <div v-if="complaint.replies.length" class="space-y-3">
                            <article v-for="reply in complaint.replies" :key="reply.id" class="rounded-2xl border p-4" :class="reply.is_internal ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-slate-50'">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-slate-950">{{ reply.author_name }}</p>
                                        <span v-if="reply.is_internal" class="inline-flex items-center rounded-full border border-amber-200 bg-white px-3 py-1 text-xs font-semibold text-amber-700">
                                            <Shield class="mr-1 h-3.5 w-3.5" />
                                            Nota dalaman
                                        </span>
                                    </div>
                                    <p class="text-xs font-medium text-slate-500">{{ reply.created_at }}</p>
                                </div>
                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ reply.message }}</p>
                            </article>
                        </div>
                        <p v-else class="text-sm text-slate-600">Belum ada balasan atau catatan untuk rekod ini.</p>
                    </FormSection>
                </div>

                <div class="space-y-6">
                    <FormSection title="Status Semasa" description="Kawalan tindakan untuk kemas kini status dan tugasan admin." :columns="1">
                        <div class="flex flex-wrap gap-2">
                            <StatusBadge :status="complaint.status" />
                            <StatusBadge :status="complaint.priority" />
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Pegawai ditugaskan</p>
                            <p class="mt-1 text-sm text-slate-700">{{ complaint.assigned_to_name || 'Belum ditetapkan' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tarikh tutup</p>
                            <p class="mt-1 text-sm text-slate-700">{{ complaint.closed_at || '-' }}</p>
                        </div>
                    </FormSection>

                    <form v-if="canClose" class="space-y-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="saveComplaint">
                        <h2 class="text-base font-semibold text-slate-950">Kemas Kini Aduan</h2>
                        <SelectInput id="complaint-status" v-model="updateForm.status" label="Status" :options="statusOptions" :error="updateForm.errors.status" />
                        <SelectInput id="complaint-priority" v-model="updateForm.priority" label="Keutamaan" :options="priorityOptions" :error="updateForm.errors.priority" />
                        <SelectInput id="complaint-assignee" v-model="updateForm.assigned_to" label="Pegawai ditugaskan" :options="assigneeOptions" :error="updateForm.errors.assigned_to" />
                        <Button type="submit" class="w-full" :disabled="updateForm.processing">
                            {{ updateForm.processing ? 'Menyimpan...' : 'Simpan Kemas Kini' }}
                        </Button>
                    </form>

                    <form v-if="canReply" class="space-y-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="sendReply">
                        <h2 class="text-base font-semibold text-slate-950">Tambah Balasan</h2>
                        <TextareaInput id="complaint-reply-message" v-model="replyForm.message" label="Mesej" :rows="5" :error="replyForm.errors.message" />
                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                            <input v-model="replyForm.is_internal" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-700/20" />
                            <span>Tandakan sebagai nota dalaman. Nota ini tidak akan dipaparkan kepada ahli.</span>
                        </label>
                        <Button type="submit" class="w-full" :disabled="replyForm.processing">
                            <Send class="mr-2 h-4 w-4" />
                            {{ replyForm.processing ? 'Menghantar...' : 'Simpan Balasan' }}
                        </Button>
                    </form>
                </div>
            </div>
        </section>
    </AdminLayout>
</template>