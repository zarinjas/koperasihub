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
    categories: { type: Array, required: true },
});

const deletingId = ref(null);
const deleteDialogOpen = ref(false);

const askDelete = (id) => {
    deletingId.value = id;
    deleteDialogOpen.value = true;
};

const deleteRecord = () => {
    if (!deletingId.value) return;
    router.delete(`/admin/ansuran/categories/${deletingId.value}`, {
        preserveScroll: true,
        onFinish: () => { deleteDialogOpen.value = false; deletingId.value = null; },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Kategori Ansuran Mudah" />
        <PageHeader title="Kategori Ansuran Mudah" description="Urus kategori produk ansuran mudah">
            <template #actions>
                <Link :href="'/admin/ansuran/categories/create'">
                    <Button><Plus class="w-4 h-4 mr-1" /> Tambah Kategori</Button>
                </Link>
            </template>
        </PageHeader>

        <div v-if="categories.length > 0" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-200">
                <div v-for="category in categories" :key="category.id" class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-4">
                        <img v-if="category.image_url" :src="category.image_url" class="w-12 h-12 rounded-lg object-cover" />
                        <div v-else class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 text-xs">Tiada</div>
                        <div>
                            <div class="font-medium text-slate-900">{{ category.name }}</div>
                            <div class="text-sm text-slate-500">{{ category.products_count }} Produk</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <StatusBadge :status="category.is_active ? 'active' : 'inactive'" />
                        <Link :href="'/admin/ansuran/categories/' + category.id + '/edit'">
                            <Button variant="ghost"><Pencil class="w-4 h-4" /></Button>
                        </Link>
                        <Button variant="ghost" @click="askDelete(category.id)">
                            <Trash2 class="w-4 h-4 text-red-500" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <EmptyState
            v-else
            title="Tiada Kategori"
            description="Belum ada kategori produk ansuran mudah."
            action-label="Tambah Kategori"
            :action-href="'/admin/ansuran/categories/create'"
        />

        <ConfirmDialog v-model:open="deleteDialogOpen" title="Padam Kategori" description="Tindakan ini tidak boleh dibatalkan. Kategori yang dipadam akan hilang bersama produk di dalamnya." @confirm="deleteRecord" />
    </AdminLayout>
</template>
