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
    products: { type: Array, required: true },
});

const deletingId = ref(null);
const deleteDialogOpen = ref(false);

const askDelete = (id) => {
    deletingId.value = id;
    deleteDialogOpen.value = true;
};

const deleteRecord = () => {
    if (!deletingId.value) return;
    router.post(`/admin/ansuran/products/${deletingId.value}`, { _method: 'DELETE' }, {
        preserveScroll: true,
        onFinish: () => { deleteDialogOpen.value = false; deletingId.value = null; },
    });
};

const priceRange = (product) => {
    if (!product.min_variant_price && !product.max_variant_price) return '-';
    if (product.min_variant_price === product.max_variant_price) {
        return `RM ${Number(product.min_variant_price).toFixed(2)}`;
    }
    return `RM ${Number(product.min_variant_price).toFixed(2)} - RM ${Number(product.max_variant_price).toFixed(2)}`;
};

const statusBadgeVariant = (status) => {
    if (status === 'aktif') return 'active';
    if (status === 'draf') return 'draft';
    return 'inactive';
};
</script>

<template>
    <AdminLayout>
        <Head title="Produk Ansuran Mudah" />
        <PageHeader title="Produk Ansuran Mudah" description="Urus produk ansuran mudah">
            <template #actions>
                <Link :href="'/admin/ansuran/products/create'">
                    <Button><Plus class="w-4 h-4 mr-1" /> Tambah Produk</Button>
                </Link>
            </template>
        </PageHeader>

        <div v-if="products.length > 0" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-200">
                <div v-for="product in products" :key="product.id" class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-4">
                        <img v-if="product.primary_image_url" :src="product.primary_image_url" class="w-16 h-16 rounded-lg object-cover" />
                        <div v-else class="w-16 h-16 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 text-xs">Tiada</div>
                        <div>
                            <div class="font-medium text-slate-900">{{ product.name }}</div>
                            <div class="text-sm text-slate-500">{{ product.category_name }} &middot; {{ product.variants_count }} varian &middot; {{ priceRange(product) }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <StatusBadge v-if="product.guarantor_count > 0" status="inactive" :label="product.guarantor_count + ' Penjamin'" />
                        <StatusBadge :status="statusBadgeVariant(product.status)" />
                        <Link :href="'/admin/ansuran/products/' + product.id + '/edit'">
                            <Button variant="ghost"><Pencil class="w-4 h-4" /></Button>
                        </Link>
                        <Button variant="ghost" @click="askDelete(product.id)">
                            <Trash2 class="w-4 h-4 text-red-500" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <EmptyState
            v-else
            title="Tiada Produk"
            description="Belum ada produk ansuran mudah."
            action-label="Tambah Produk"
            :action-href="'/admin/ansuran/products/create'"
        />

        <ConfirmDialog v-model:open="deleteDialogOpen" title="Padam Produk" description="Tindakan ini tidak boleh dibatalkan." @confirm="deleteRecord" />
    </AdminLayout>
</template>