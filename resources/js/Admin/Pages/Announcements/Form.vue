<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Eye } from 'lucide-vue-next';
import { computed } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import FormActions from '@/Shared/Components/FormActions.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import ToggleSwitch from '@/Shared/Components/Form/ToggleSwitch.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    mode: { type: String, required: true },
    announcementRecord: { type: Object, default: null },
    statusOptions: { type: Array, required: true },
    audienceOptions: { type: Array, required: true },
});

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);
const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
    title: props.announcementRecord?.title || '',
    slug: props.announcementRecord?.slug || '',
    summary: props.announcementRecord?.summary || '',
    content: props.announcementRecord?.content || '',
    image_path: props.announcementRecord?.image_path || '',
    audience: props.announcementRecord?.audience || 'public',
    status: props.announcementRecord?.status || 'draft',
    is_pinned: Boolean(props.announcementRecord?.is_pinned),
    published_at: props.announcementRecord?.published_at || '',
    expires_at: props.announcementRecord?.expires_at || '',
});

const submit = () => {
    if (isEdit.value) {
        form.patch(`/admin/announcements/${props.announcementRecord.id}`, {
            preserveScroll: true,
        });
        return;
    }

    form.post('/admin/announcements', {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get('/admin/announcements');
};
</script>

<template>
    <Head :title="isEdit ? `Edit ${announcementRecord.title}` : 'Tambah Pengumuman'" />

    <AdminLayout>
        <form class="space-y-6" @submit.prevent="submit">
            <PageHeader
                :title="isEdit ? 'Edit Pengumuman' : 'Tambah Pengumuman'"
                :description="isEdit ? 'Kemas kini kandungan, audiens, dan jadual siaran pengumuman.' : 'Sediakan hebahan baharu untuk paparan awam atau kegunaan dalaman.'"
            >
                <template #actions>
                    <StatusBadge v-if="announcementRecord" :status="announcementRecord.status" />
                    <Button v-if="announcementRecord" :as="Link" :href="announcementRecord.public_url" variant="outline">
                        <Eye class="mr-2 h-4 w-4" />
                        Lihat Halaman
                    </Button>
                </template>
            </PageHeader>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <FormSection title="Maklumat Pengumuman" description="Maklumat ini digunakan pada senarai dan halaman butiran." :columns="2">
                <TextInput id="announcement-title" v-model="form.title" label="Tajuk" :error="form.errors.title" />
                <TextInput id="announcement-slug" v-model="form.slug" label="Slug" :error="form.errors.slug" />
                <SelectInput id="announcement-audience" v-model="form.audience" label="Audiens" :options="audienceOptions" :error="form.errors.audience" />
                <SelectInput id="announcement-status" v-model="form.status" label="Status" :options="statusOptions" :error="form.errors.status" />
                <TextInput id="announcement-published-at" v-model="form.published_at" label="Tarikh terbit" type="datetime-local" :error="form.errors.published_at" />
                <TextInput id="announcement-expires-at" v-model="form.expires_at" label="Tarikh tamat" type="datetime-local" :error="form.errors.expires_at" />
                <div class="md:col-span-2">
                    <TextareaInput id="announcement-summary" v-model="form.summary" label="Ringkasan" :error="form.errors.summary" />
                </div>
                <div class="md:col-span-2">
                    <TextareaInput id="announcement-content" v-model="form.content" label="Kandungan" :rows="8" :error="form.errors.content" />
                </div>
            </FormSection>

            <FormSection title="Paparan" description="Gunakan pin untuk letakkan pengumuman penting di bahagian atas senarai." :columns="2">
                <TextInput id="announcement-image-path" v-model="form.image_path" label="Path imej" :error="form.errors.image_path" />
                <div class="md:col-span-2">
                    <ToggleSwitch id="announcement-pinned" v-model="form.is_pinned" label="Pin pengumuman" description="Pengumuman yang dipin akan dipaparkan dahulu pada senarai awam." />
                </div>
            </FormSection>

            <FormActions :submit-label="isEdit ? 'Simpan Pengumuman' : 'Cipta Pengumuman'" :submitting="form.processing" @cancel="cancel" />
        </form>
    </AdminLayout>
</template>
