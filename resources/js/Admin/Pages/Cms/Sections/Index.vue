<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, FilePlus2, Layers3, PencilLine, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import CmsSectionFields from '@/Admin/Components/CmsSectionFields.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import EmptyState from '@/Shared/Components/EmptyState.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import ToggleSwitch from '@/Shared/Components/Form/ToggleSwitch.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    pageRecord: { type: Object, required: true },
    sections: { type: Array, required: true },
    sectionDefinitions: { type: Array, required: true },
    selectedSectionId: { type: Number, default: null },
});

const cloneValue = (value) => {
    if (value === undefined || value === null) {
        return {};
    }

    return JSON.parse(JSON.stringify(value));
};

const sanitiseSectionData = (data = {}) => {
    const nextData = { ...(data || {}) };
    const isBrowserFile = (value) => typeof File !== 'undefined' && value instanceof File;

    delete nextData.image_url;

    Object.keys(nextData).forEach((key) => {
        if (Array.isArray(nextData[key])) {
            nextData[key] = nextData[key].map((item) => (
                item && typeof item === 'object' && !isBrowserFile(item)
                    ? sanitiseSectionData(item)
                    : item
            ));
        } else if (nextData[key] && typeof nextData[key] === 'object' && !isBrowserFile(nextData[key])) {
            nextData[key] = sanitiseSectionData(nextData[key]);
        }
    });

    return nextData;
};

const sectionDefinitions = computed(() => Array.isArray(props.sectionDefinitions) ? props.sectionDefinitions : []);
const sections = computed(() => Array.isArray(props.sections) ? props.sections : []);
const definitionsByType = computed(() => Object.fromEntries(sectionDefinitions.value.map((definition) => [definition.type, definition])));
const sectionTypeOptions = computed(() => sectionDefinitions.value.map((definition) => ({ value: definition.type, label: definition.label })));
const selectedSectionId = ref(props.selectedSectionId || null);
const deleteSectionId = ref(null);
const deleteLoading = ref(false);
const localStatus = ref('');
const localError = ref('');

const createForm = useForm({
    type: sectionDefinitions.value[0]?.type || 'hero',
    name: '',
    is_active: true,
    data: cloneValue(sectionDefinitions.value[0]?.defaults?.data || {}),
    settings: cloneValue(sectionDefinitions.value[0]?.defaults?.settings || {}),
});

const updateForm = useForm({
    type: '',
    name: '',
    is_active: true,
    data: {},
    settings: {},
});

const selectedSection = computed(() => sections.value.find((section) => section.id === selectedSectionId.value) || null);
const selectedDefinition = computed(() => selectedSection.value ? definitionsByType.value[selectedSection.value.type] : null);
const createDefinition = computed(() => definitionsByType.value[createForm.type] || sectionDefinitions.value[0] || null);

const hydrateUpdateForm = (section) => {
    updateForm.type = section.type;
    updateForm.name = section.name || '';
    updateForm.is_active = Boolean(section.is_active);
    updateForm.data = cloneValue(section.data || {});
    updateForm.settings = cloneValue(section.settings || {});
    updateForm.clearErrors();
};

watch(() => createForm.type, (type) => {
    const definition = definitionsByType.value[type];

    if (!definition) {
        return;
    }

    createForm.name = definition.name_default;
    createForm.data = cloneValue(definition.defaults?.data || {});
    createForm.settings = cloneValue(definition.defaults?.settings || {});
}, { immediate: true });

watch(selectedSection, (section) => {
    if (!section) {
        return;
    }

    hydrateUpdateForm(section);
}, { immediate: true });

watch(() => props.selectedSectionId, (id) => {
    selectedSectionId.value = id || null;
});

watch(sections, (items) => {
    if (!items.length || !selectedSectionId.value) {
        selectedSectionId.value = null;

        return;
    }

    if (!items.some((section) => section.id === selectedSectionId.value)) {
        selectedSectionId.value = null;
    }
}, { immediate: true });

const submitCreate = () => {
    localStatus.value = '';
    localError.value = '';

    createForm.post(`/admin/cms/pages/${props.pageRecord.id}/sections`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            createForm.clearErrors();
            localStatus.value = 'Seksyen berjaya ditambah.';
        },
        onError: () => {
            localError.value = 'Sila semak medan yang bertanda merah sebelum simpan.';
        },
    });
};

const submitUpdate = () => {
    if (!selectedSection.value) {
        return;
    }

    localStatus.value = '';
    localError.value = '';

    updateForm.patch(`/admin/page-sections/${selectedSection.value.id}`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            localStatus.value = 'Seksyen berjaya disimpan.';
        },
        onError: () => {
            localError.value = 'Sila semak medan yang bertanda merah sebelum simpan.';
        },
    });
};

const toggleSection = (section) => {
    router.post(`/admin/page-sections/${section.id}`, {
        _method: 'patch',
        type: section.type,
        name: section.name,
        is_active: !section.is_active,
        data: sanitiseSectionData(section.data),
        settings: section.settings,
    }, {
        preserveScroll: true,
        forceFormData: true,
    });
};

const sectionEditUrl = (section) => `/admin/page-sections/${section.id}/edit`;

const selectSection = (section) => {
    selectedSectionId.value = section.id;
    hydrateUpdateForm(section);
};

const deleteSection = () => {
    if (!deleteSectionId.value) {
        return;
    }

    deleteLoading.value = true;

    router.post(`/admin/page-sections/${deleteSectionId.value}`, { _method: 'DELETE' }, {
        preserveScroll: true,
        onFinish: () => {
            deleteLoading.value = false;
            deleteSectionId.value = null;
        },
    });
};
</script>

<template>
    <Head :title="`Seksyen ${pageRecord.title}`" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                :title="`Seksyen Halaman: ${pageRecord.title}`"
                description="Urus kandungan homepage atau landing page melalui seksyen yang tersusun. Setiap seksyen boleh diedit terus tanpa editor kompleks."
            >
                <template #actions>
                    <StatusBadge :status="pageRecord.status" />
                    <Button :as="Link" :href="`/admin/cms/pages/${pageRecord.id}/edit`" variant="outline">
                        <PencilLine class="mr-2 h-4 w-4" />
                        Edit Halaman
                    </Button>
                </template>
            </PageHeader>

            <div v-if="localStatus" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ localStatus }}
            </div>
            <div v-if="localError" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800">
                {{ localError }}
            </div>

            <div class="grid gap-6 lg:grid-cols-[0.92fr,1.08fr]">
                <div class="space-y-6">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-slate-950">{{ pageRecord.title }}</p>
                                <p class="text-sm text-slate-500">/{{ pageRecord.slug }}</p>
                            </div>
                            <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                <Layers3 class="h-3.5 w-3.5" />
                                {{ sections.length }} seksyen
                            </div>
                        </div>
                        <p class="mt-4 text-sm leading-6 text-slate-600">
                            Pilih seksyen di bawah untuk mengubah teks, butang, imej, dan tetapan paparan bagi halaman ini.
                        </p>
                    </div>

                    <div v-if="sections.length === 0" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <EmptyState
                            title="Belum ada seksyen."
                            description="Tambah seksyen pertama di panel sebelah kanan untuk mula membina halaman ini."
                            compact
                        />
                    </div>

                    <div v-else class="space-y-4">
                        <article
                            v-for="section in sections"
                            :key="section.id"
                            class="rounded-3xl border bg-white p-5 shadow-sm transition hover:border-teal-200 hover:shadow-md"
                            :class="selectedSectionId === section.id ? 'border-teal-300 ring-2 ring-teal-100' : 'border-slate-200'"
                        >
                            <div class="flex flex-col gap-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-base font-semibold text-slate-950">{{ section.name || section.type_label }}</p>
                                            <StatusBadge :status="section.is_active ? 'active' : 'inactive'" />
                                        </div>
                                        <p class="text-sm text-slate-500">{{ section.type_label }}</p>
                                    </div>
                                    <a
                                        :href="sectionEditUrl(section)"
                                        class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-600 focus-visible:ring-offset-2"
                                        @click="selectSection(section)"
                                    >
                                        Edit Kandungan
                                    </a>
                                </div>

                                <div
                                    v-if="section.unknown_data_keys.length || section.unknown_settings_keys.length"
                                    class="rounded-2xl border border-amber-200 bg-amber-50 p-3 text-sm leading-6 text-amber-900"
                                >
                                    Ada data tambahan yang akan dikekalkan semasa simpan.
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Button type="button" variant="outline" @click.stop="toggleSection(section)">
                                        <component :is="section.is_active ? EyeOff : Eye" class="mr-2 h-4 w-4" />
                                        {{ section.is_active ? 'Sembunyi' : 'Papar' }}
                                    </Button>
                                    <Button type="button" variant="destructive" @click.stop="deleteSectionId = section.id">
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Padam
                                    </Button>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <div id="edit-section" class="space-y-6 scroll-mt-6">
                    <form
                        v-if="selectedSection && selectedDefinition"
                        class="space-y-4"
                        novalidate
                        @submit.prevent="submitUpdate"
                    >
                        <FormSection
                            :title="`Edit Seksyen: ${selectedSection.name || selectedSection.type_label}`"
                            :description="selectedDefinition.description"
                        >
                            <TextInput id="update-section-name" v-model="updateForm.name" label="Nama seksyen" :error="updateForm.errors.name" />
                            <ToggleSwitch
                                id="update-section-active"
                                v-model="updateForm.is_active"
                                label="Papar seksyen ini"
                                description="Matikan pilihan ini jika anda mahu sembunyikan seksyen tanpa memadam kandungan."
                            />

                            <CmsSectionFields
                                :fields="selectedDefinition.data_fields"
                                v-model:model="updateForm.data"
                                :errors="updateForm.errors"
                                prefix="data"
                                id-prefix="update-data"
                            />
                            <CmsSectionFields
                                :fields="selectedDefinition.settings_fields"
                                v-model:model="updateForm.settings"
                                :errors="updateForm.errors"
                                prefix="settings"
                                id-prefix="update-settings"
                            />
                        </FormSection>

                        <div class="flex flex-col gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-600 focus-visible:ring-offset-2"
                                @click="selectedSectionId = null"
                            >
                                Tutup Pilihan
                            </button>
                            <button
                                type="submit"
                                :disabled="updateForm.processing"
                                class="inline-flex h-10 items-center justify-center rounded-lg bg-teal-700 px-4 text-sm font-medium text-white shadow-sm transition-colors hover:bg-teal-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-600 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
                            >
                                {{ updateForm.processing ? 'Menyimpan...' : 'Simpan Seksyen' }}
                            </button>
                        </div>
                    </form>

                    <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white p-6 text-sm leading-6 text-slate-600">
                        Pilih butang <span class="font-semibold text-slate-900">Edit Kandungan</span> pada mana-mana seksyen untuk membuka borang edit di sini.
                    </div>

                    <form class="space-y-4" novalidate @submit.prevent="submitCreate">
                        <FormSection title="Tambah Seksyen Baharu" description="Pilih jenis seksyen yang tersedia dalam sistem. Selepas ditambah, kandungan boleh terus diedit di bawah.">
                            <SelectInput id="section-type" v-model="createForm.type" label="Jenis seksyen" :options="sectionTypeOptions" :error="createForm.errors.type" />
                            <TextInput id="section-name" v-model="createForm.name" label="Nama seksyen" :error="createForm.errors.name" />
                            <ToggleSwitch
                                id="create-section-active"
                                v-model="createForm.is_active"
                                label="Papar seksyen ini"
                                description="Jika dimatikan, seksyen disimpan tetapi tidak dipaparkan pada laman awam."
                            />

                            <CmsSectionFields
                                v-if="createDefinition"
                                :fields="createDefinition.data_fields"
                                v-model:model="createForm.data"
                                :errors="createForm.errors"
                                prefix="data"
                                id-prefix="create-data"
                            />
                            <CmsSectionFields
                                v-if="createDefinition"
                                :fields="createDefinition.settings_fields"
                                v-model:model="createForm.settings"
                                :errors="createForm.errors"
                                prefix="settings"
                                id-prefix="create-settings"
                            />
                        </FormSection>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="inline-flex h-10 items-center justify-center rounded-lg bg-teal-700 px-4 text-sm font-medium text-white shadow-sm transition-colors hover:bg-teal-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-600 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
                            >
                                <FilePlus2 class="mr-2 h-4 w-4" />
                                {{ createForm.processing ? 'Menyimpan...' : 'Tambah Seksyen' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <ConfirmDialog
            :open="Boolean(deleteSectionId)"
            title="Padam seksyen?"
            description="Tindakan ini akan memadam seksyen daripada halaman ini. Kandungan seksyen ini tidak boleh dipulihkan selepas dipadam."
            confirm-label="Padam Seksyen"
            :loading="deleteLoading"
            @cancel="deleteSectionId = null"
            @confirm="deleteSection"
        />
    </AdminLayout>
</template>