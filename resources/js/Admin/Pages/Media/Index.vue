<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ImagePlus, Trash2 } from 'lucide-vue-next';
import { reactive, ref } from 'vue';
import AdminFilterBar from '@/Admin/Components/AdminFilterBar.vue';
import AdminSearchInput from '@/Admin/Components/AdminSearchInput.vue';
import AdminSelectFilter from '@/Admin/Components/AdminSelectFilter.vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import FileUploader from '@/Shared/Components/FileUploader.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    filters: { type: Object, required: true },
    mediaFiles: { type: Object, required: true },
    collectionOptions: { type: Array, required: true },
    visibilityOptions: { type: Array, required: true },
    canUpload: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
});
const filters = reactive({
    search: props.filters.search || '',
    collection: props.filters.collection || '',
});

const uploadForm = useForm({
    file: null,
    collection: 'general',
    alt_text: '',
    caption: '',
    visibility: 'public',
});

const deletingId = ref(null);
const dialogOpen = ref(false);

const applyFilters = () => {
    router.get('/admin/media', filters, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filters.search = '';
    filters.collection = '';
    applyFilters();
};

const submitUpload = () => {
    uploadForm.post('/admin/media', {
        forceFormData: true,
        onSuccess: () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            uploadForm.reset();
        },
        onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    });
};

const askDelete = (id) => {
    deletingId.value = id;
    dialogOpen.value = true;
};

const deleteMedia = () => {
    if (!deletingId.value) return;

    router.post(`/admin/media/${deletingId.value}`, { _method: 'DELETE' }, {
        preserveScroll: true,
        onFinish: () => {
            dialogOpen.value = false;
            deletingId.value = null;
        },
    });
};
</script>

<template>
    <Head title="Media" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                title="Media"
                description="Simpan imej dan aset visual untuk digunakan pada laman awam, tetapan jenama, banner dan ikon."
            />

            <FormSection
                v-if="canUpload"
                title="Muat Naik Media"
                description="Gunakan hanya fail yang sesuai untuk laman awam. Media private lanjutan boleh diperluas pada fasa seterusnya."
                :columns="2"
            >
                <div class="md:col-span-2">
                    <FileUploader
                        id="media-file"
                        v-model="uploadForm.file"
                        label="Fail media"
                        accept=".jpg,.jpeg,.png,.webp,.gif,.svg"
                        helper-text="Format disokong: JPG, PNG, WEBP, GIF, SVG. Saiz maksimum 5MB. Foundation fasa ini menyimpan media awam untuk laman web."
                        :error="uploadForm.errors.file"
                    />
                </div>
                <SelectInput id="media-collection" v-model="uploadForm.collection" label="Koleksi" :options="collectionOptions.slice(1)" :error="uploadForm.errors.collection" />
                <SelectInput id="media-visibility" v-model="uploadForm.visibility" label="Tahap akses" :options="visibilityOptions" :error="uploadForm.errors.visibility" />
                <TextInput id="media-alt-text" v-model="uploadForm.alt_text" label="Alt text" :error="uploadForm.errors.alt_text" />
                <div />
                <div class="md:col-span-2">
                    <TextareaInput id="media-caption" v-model="uploadForm.caption" label="Caption" :error="uploadForm.errors.caption" />
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <Button type="button" :disabled="uploadForm.processing" @click="submitUpload">
                        <ImagePlus class="mr-2 h-4 w-4" />
                        {{ uploadForm.processing ? 'Memuat naik...' : 'Muat Naik Media' }}
                    </Button>
                </div>
            </FormSection>

            <AdminFilterBar>
                <AdminSearchInput id="media-search-filter" v-model="filters.search" placeholder="Cari nama fail atau alt text" />
                <AdminSelectFilter id="collection-filter" v-model="filters.collection" label="Koleksi" :options="collectionOptions" />
                <template #actions>
                    <Button type="button" variant="outline" class="h-11" @click="resetFilters">Set Semula</Button>
                    <Button type="button" class="h-11" @click="applyFilters">Tapis</Button>
                </template>
            </AdminFilterBar>

            <EmptyState
                v-if="mediaFiles.data.length === 0"
                title="Tiada media ditemui."
                description="Muat naik aset pertama untuk digunakan dalam kandungan CMS atau tetapan jenama."
            />

            <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="item in mediaFiles.data"
                    :key="item.id"
                    class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="aspect-[16/10] bg-slate-100">
                        <img v-if="item.url" :src="item.url" :alt="item.alt_text || item.original_name" class="h-full w-full object-cover" />
                    </div>

                    <div class="space-y-4 p-5">
                        <div class="space-y-1">
                            <div class="flex items-start justify-between gap-3">
                                <p class="line-clamp-2 text-sm font-semibold text-slate-950">{{ item.original_name }}</p>
                                <StatusBadge :status="item.visibility" :label="item.visibility === 'public' ? 'Public' : 'Admin sahaja'" />
                            </div>
                            <p class="text-xs text-slate-500">{{ item.collection || 'Umum' }} · {{ item.size_label }}</p>
                        </div>

                        <p v-if="item.alt_text" class="text-sm leading-6 text-slate-600">{{ item.alt_text }}</p>
                        <p v-if="item.caption" class="text-sm leading-6 text-slate-500">{{ item.caption }}</p>

                        <div class="flex flex-wrap items-center gap-2">
                            <Button :as="Link" :href="item.url" target="_blank" rel="noopener noreferrer" variant="outline">Lihat</Button>
                            <Button v-if="canDelete" type="button" variant="destructive" @click="askDelete(item.id)">
                                <Trash2 class="mr-2 h-4 w-4" />
                                Padam
                            </Button>
                        </div>
                    </div>
                </article>
            </div>

            <div v-if="mediaFiles.links?.length > 3" class="flex flex-wrap gap-2">
                <Button
                    v-for="link in mediaFiles.links"
                    :key="`${link.label}-${link.url}`"
                    :as="link.url ? Link : 'button'"
                    :href="link.url || undefined"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                    v-html="link.label"
                />
            </div>
        </section>

        <ConfirmDialog
            :open="dialogOpen"
            title="Padam media"
            description="Media ini akan dipadam daripada pustaka. Pastikan ia tidak lagi digunakan pada kandungan awam sebelum meneruskan."
            confirm-label="Padam"
            :loading="false"
            @cancel="dialogOpen = false"
            @confirm="deleteMedia"
        />
    </AdminLayout>
</template>