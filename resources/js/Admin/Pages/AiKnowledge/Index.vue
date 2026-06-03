<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { Brain, FileText, LoaderCircle, Plus, Trash2, Upload } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    documents: { type: Array, default: () => [] },
});

const page = usePage();
const uploading = ref(false);
const deleting = ref(null);

const flash = computed(() => page.props.flash?.status);

const handleUpload = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    uploading.value = true;
    const form = new FormData();
    form.append('document', file);

    try {
        await router.post('/admin/ai-knowledge', form, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => { uploading.value = false; },
        });
    } catch {
        uploading.value = false;
    }

    e.target.value = '';
};

const confirmDelete = (name) => {
    if (confirm(`Padam dokumen "${name}"? Tindakan ini tidak boleh dibatalkan.`)) {
        deleting.value = name;
        router.post(`/admin/ai-knowledge/${encodeURIComponent(name)}`, { _method: 'DELETE' }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => { deleting.value = null; },
        });
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ms-MY', {
        day: 'numeric', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Pengetahuan AI" />

    <AdminLayout>
        <PageHeader
            title="Pengetahuan AI"
            subtitle="Urus dokumen rujukan untuk Koperasi AI Chat"
        >
            <template #icon>
                <Brain class="h-5 w-5" />
            </template>
        </PageHeader>

        <div class="mx-auto max-w-4xl space-y-6">
            <div v-if="flash" class="rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200">
                {{ flash }}
            </div>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
                        <Upload class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Muat Naik Dokumen PDF</h2>
                        <p class="text-xs text-slate-500">PDF sehingga 10MB. Teks akan diekstrak untuk rujukan AI.</p>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="relative flex cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-8 transition hover:border-teal-400 hover:bg-teal-50/30">
                        <input
                            type="file"
                            accept=".pdf,application/pdf"
                            class="sr-only"
                            :disabled="uploading"
                            @change="handleUpload"
                        />
                        <div class="flex flex-col items-center gap-2 text-center">
                            <LoaderCircle v-if="uploading" class="h-8 w-8 animate-spin text-teal-500" />
                            <Upload v-else class="h-8 w-8 text-slate-400" />
                            <div>
                                <p class="text-sm font-medium text-slate-600">
                                    <span v-if="uploading">Sedang dimuat naik...</span>
                                    <span v-else>Klik atau seret PDF ke sini</span>
                                </p>
                                <p class="mt-0.5 text-xs text-slate-400">Format PDF sahaja</p>
                            </div>
                        </div>
                    </label>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Senarai Dokumen</h2>
                </div>

                <div v-if="documents.length" class="divide-y divide-slate-100">
                    <div v-for="doc in documents" :key="doc.name" class="flex items-center gap-4 px-6 py-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                            <FileText class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-900">{{ doc.name }}</p>
                            <p class="text-xs text-slate-400">
                                {{ doc.chunk_count }} cebisan teks &middot; {{ formatDate(doc.last_uploaded) }}
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="shrink-0 text-red-500 hover:text-red-600"
                            :disabled="deleting === doc.name"
                            @click="confirmDelete(doc.name)"
                        >
                            <Trash2 v-if="deleting !== doc.name" class="h-4 w-4" />
                            <LoaderCircle v-else class="h-4 w-4 animate-spin" />
                        </Button>
                    </div>
                </div>

                <EmptyState
                    v-else
                    class="px-6 py-10"
                    title="Tiada Dokumen"
                    description="Muat naik dokumen PDF untuk membolehkan AI Chat menjawab soalan ahli."
                >
                    <template #icon>
                        <Brain class="h-10 w-10 text-slate-300" />
                    </template>
                </EmptyState>
            </section>
        </div>
    </AdminLayout>
</template>
