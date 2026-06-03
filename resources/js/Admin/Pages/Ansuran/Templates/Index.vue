<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

defineProps({
    templates: { type: Array, required: true },
});

const deletingId = ref(null);
const deleteDialogOpen = ref(false);

const askDelete = (id) => {
    deletingId.value = id;
    deleteDialogOpen.value = true;
};

const deleteRecord = () => {
    if (!deletingId.value) return;
    router.post(`/admin/ansuran/templates/${deletingId.value}`, { _method: 'DELETE' }, {
        preserveScroll: true,
        onFinish: () => { deleteDialogOpen.value = false; deletingId.value = null; },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Template Perjanjian" />
        <PageHeader title="Template Perjanjian" description="Urus template perjanjian ansuran mudah">
            <template #actions>
                <Link :href="'/admin/ansuran/templates/create'">
                    <Button><Plus class="w-4 h-4 mr-1" /> Tambah Template</Button>
                </Link>
            </template>
        </PageHeader>

        <div v-if="templates.length > 0" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-200">
                <div v-for="t in templates" :key="t.id" class="flex items-center justify-between px-6 py-4">
                    <div>
                        <div class="font-medium text-slate-900">{{ t.name }}</div>
                        <div class="text-sm text-slate-500">{{ t.description || 'Tiada penerangan' }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <StatusBadge :status="t.is_active ? 'active' : 'inactive'" />
                        <Link :href="'/admin/ansuran/templates/' + t.id + '/edit'">
                            <Button variant="ghost"><Pencil class="w-4 h-4" /></Button>
                        </Link>
                        <Button variant="ghost" @click="askDelete(t.id)">
                            <Trash2 class="w-4 h-4 text-red-500" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <EmptyState
            v-else
            title="Tiada Template"
            description="Belum ada template perjanjian."
            action-label="Tambah Template"
            :action-href="'/admin/ansuran/templates/create'"
        />

        <ConfirmDialog v-model:open="deleteDialogOpen" title="Padam Template" description="Tindakan ini tidak boleh dibatalkan." @confirm="deleteRecord" />
    </AdminLayout>
</template>