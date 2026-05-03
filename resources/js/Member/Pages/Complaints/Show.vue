<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import MemberLayout from '@/Member/Layouts/MemberLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    complaint: { type: Object, required: true },
});
</script>

<template>
    <Head :title="complaint.ticket_no" />

    <MemberLayout>
        <section class="space-y-6">
            <PageHeader
                title="Butiran Aduan"
                description="Semak status aduan atau cadangan anda serta balasan daripada pihak admin."
            >
                <template #actions>
                    <Button :as="Link" href="/member/complaints" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <FormSection title="Maklumat Tiket" description="Ringkasan rekod yang telah dihantar." :columns="2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">No. tiket</p>
                        <p class="mt-1 text-sm font-semibold text-slate-950">{{ complaint.ticket_no }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kategori</p>
                        <p class="mt-1 text-sm text-slate-700">{{ complaint.category_label }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Status</p>
                        <div class="mt-2">
                            <StatusBadge :status="complaint.status" />
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Keutamaan</p>
                        <div class="mt-2">
                            <StatusBadge :status="complaint.priority" />
                        </div>
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

                <FormSection title="Status Semasa" description="Maklumat susulan untuk rujukan anda." :columns="1">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Dihantar pada</p>
                        <p class="mt-1 text-sm text-slate-700">{{ complaint.submitted_at }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Ditutup pada</p>
                        <p class="mt-1 text-sm text-slate-700">{{ complaint.closed_at || '-' }}</p>
                    </div>
                </FormSection>
            </div>

            <FormSection title="Balasan Admin" description="Hanya balasan yang boleh dilihat oleh ahli dipaparkan di sini." :columns="1">
                <div v-if="complaint.replies.length" class="space-y-3">
                    <article v-for="reply in complaint.replies" :key="reply.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="font-semibold text-slate-950">{{ reply.author_name }}</p>
                            <p class="text-xs font-medium text-slate-500">{{ reply.created_at }}</p>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ reply.message }}</p>
                    </article>
                </div>
                <EmptyState
                    v-else
                    title="Belum ada balasan."
                    description="Balasan daripada admin akan dipaparkan di sini apabila susulan dibuat."
                    compact
                />
            </FormSection>
        </section>
    </MemberLayout>
</template>
