<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Eye, Upload } from 'lucide-vue-next';
import { computed } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import { useAutoSlug } from '@/Shared/Composables/useAutoSlug.js';
import FormActions from '@/Shared/Components/FormActions.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import RichTextEditor from '@/Shared/Components/Form/RichTextEditor.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import ToggleSwitch from '@/Shared/Components/Form/ToggleSwitch.vue';

import PageHeader from '@/Shared/Components/PageHeader.vue';
import MemberSearchSelect from '@/Shared/Components/MemberSearchSelect.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    mode: { type: String, required: true },
    announcementRecord: { type: Object, default: null },
    audienceOptions: { type: Array, required: true },
    memberSearchUrl: { type: String, default: '/admin/members/search' },
    selectedMembers: { type: Array, default: () => [] },
});

const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
    title: props.announcementRecord?.title || '',
    slug: props.announcementRecord?.slug || '',
    summary: props.announcementRecord?.summary || '',
    content: props.announcementRecord?.content || '',
    image: null,
    audience: props.announcementRecord?.audience || 'public',
    is_pinned: Boolean(props.announcementRecord?.is_pinned),
    send_notification: Boolean(props.announcementRecord?.send_notification),
    send_email: Boolean(props.announcementRecord?.send_email),
    recipient_type: props.selectedMembers.length > 0 ? 'specific' : 'all',
    specific_member_ids: props.selectedMembers.map((m) => m.id),
});

useAutoSlug(() => form.title, form, 'slug');

const submit = () => {
    if (form.recipient_type === 'all') {
        form.specific_member_ids = [];
    }

    const cb = {
        onSuccess: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
        onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    };

    if (isEdit.value) {
        form.patch(`/admin/announcements/${props.announcementRecord.id}`, { forceFormData: true, ...cb });
        return;
    }

    form.post('/admin/announcements', { forceFormData: true, ...cb });
};

const cancel = () => {
    router.get('/admin/announcements');
};

const showNotificationOptions = computed(() =>
    form.audience === 'members' || form.audience === 'admins',
);
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
                    <Button v-if="announcementRecord && announcementRecord.audience === 'public'" :as="Link" :href="announcementRecord.public_url" variant="outline">
                        <Eye class="mr-2 h-4 w-4" />
                        Lihat Halaman
                    </Button>
                </template>
            </PageHeader>

            <FormSection title="Maklumat Pengumuman" description="Maklumat ini digunakan pada senarai dan halaman butiran.">
                <TextInput id="announcement-title" v-model="form.title" label="Tajuk" :error="form.errors.title" />
                <SelectInput id="announcement-audience" v-model="form.audience" label="Audiens" :options="audienceOptions" :error="form.errors.audience" />
                <div class="md:col-span-2">
                    <TextareaInput id="announcement-summary" v-model="form.summary" label="Ringkasan" :error="form.errors.summary" />
                </div>
                <div class="md:col-span-2">
                    <RichTextEditor id="announcement-content" v-model="form.content" label="Kandungan" :error="form.errors.content" />
                </div>
            </FormSection>

            <FormSection title="Paparan" description="Gunakan pin untuk letakkan pengumuman penting di bahagian atas senarai." :columns="2">
                <div class="space-y-3">
                    <label class="text-sm font-medium text-slate-800">Imej</label>
                    <label
                        for="announcement-image"
                        class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center transition hover:border-teal-300 hover:bg-teal-50/40"
                    >
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-teal-700 shadow-sm">
                            <Upload class="h-5 w-5" />
                        </span>
                        <div class="space-y-1">
                            <p v-if="form.image" class="text-sm font-medium text-slate-900">{{ form.image.name }}</p>
                            <p v-else class="text-sm font-medium text-slate-900">Pilih fail imej untuk dimuat naik</p>
                            <p class="text-xs leading-5 text-slate-500">JPEG, PNG, JPG atau WebP. Maks 5MB.</p>
                        </div>
                    </label>
                    <input
                        id="announcement-image"
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                        type="file"
                        class="hidden"
                        @change="(e) => { const file = e.target.files?.[0]; if (file) form.image = file; }"
                    />
                    <img
                        v-if="announcementRecord?.image_url && !form.image"
                        :src="announcementRecord.image_url"
                        class="h-40 rounded-2xl border border-slate-200 object-cover shadow-sm"
                        alt="Imej semasa"
                    />
                    <p v-if="form.errors.image" class="text-sm text-red-700">{{ form.errors.image }}</p>
                </div>
                <div class="md:col-span-2">
                    <ToggleSwitch id="announcement-pinned" v-model="form.is_pinned" label="Pin pengumuman" description="Pengumuman yang dipin akan dipaparkan dahulu pada senarai awam." />
                </div>
            </FormSection>

            <FormSection
                v-if="showNotificationOptions"
                title="Penghantaran Notifikasi"
                description="Hantar notifikasi dalam sistem dan/atau emel kepada ahli apabila pengumuman diterbitkan."
                :columns="2"
            >
                <div class="md:col-span-2">
                    <ToggleSwitch
                        id="announcement-send-notification"
                        v-model="form.send_notification"
                        label="Hantar notifikasi dalam sistem"
                        description="Ahli akan menerima notifikasi di portal apabila pengumuman diterbitkan."
                    />
                </div>

                <div class="md:col-span-2">
                    <ToggleSwitch
                        id="announcement-send-email"
                        v-model="form.send_email"
                        label="Hantar emel"
                        description="Ahli akan menerima emel pengumuman."
                    />
                </div>

                <div v-if="form.send_notification && form.audience === 'members'" class="md:col-span-2 space-y-3">
                    <label class="text-sm font-medium text-slate-700">Penerima</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input
                                type="radio"
                                name="recipient_type"
                                value="all"
                                class="h-4 w-4 text-teal-700"
                                :checked="form.recipient_type === 'all'"
                                @change="form.recipient_type = 'all'"
                            />
                            Semua ahli
                        </label>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input
                                type="radio"
                                name="recipient_type"
                                value="specific"
                                class="h-4 w-4 text-teal-700"
                                :checked="form.recipient_type === 'specific'"
                                @change="form.recipient_type = 'specific'"
                            />
                            Pilih ahli tertentu
                        </label>
                    </div>

                    <div v-if="form.recipient_type === 'specific'">
                        <MemberSearchSelect
                            :search-url="memberSearchUrl"
                            v-model="form.specific_member_ids"
                            :selected-members="selectedMembers"
                        />
                        <p v-if="form.errors.specific_member_ids" class="mt-1 text-xs text-red-600">
                            {{ form.errors.specific_member_ids }}
                        </p>
                    </div>
                </div>
            </FormSection>

            <FormActions :submit-label="isEdit ? 'Simpan Pengumuman' : 'Cipta Pengumuman'" :submitting="form.processing" @cancel="cancel" />
        </form>
    </AdminLayout>
</template>