<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Eye, EyeOff, FilePlus2, GripVertical, PencilLine, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import CmsSectionFields from '@/Admin/Components/CmsSectionFields.vue';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';
import DataTable from '@/Shared/Components/DataTable.vue';
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

const page = usePage();
const statusMessage = computed(() => page.props.flash?.status);
const columns = [
    { key: 'sort_order', label: 'Susunan' },
    { key: 'name', label: 'Seksyen' },
    { key: 'is_active', label: 'Status' },
    { key: 'updated_at', label: 'Dikemas kini' },
    { key: 'actions', label: 'Tindakan' },
];

const definitionsByType = computed(() => Object.fromEntries(props.sectionDefinitions.map((definition) => [definition.type, definition])));
const sectionTypeOptions = computed(() => props.sectionDefinitions.map((definition) => ({ value: definition.type, label: definition.label })));
const selectedSectionId = ref(props.selectedSectionId || props.sections[0]?.id || null);
const deleteSectionId = ref(null);
const deleteLoading = ref(false);

const createForm = useForm({
    type: props.sectionDefinitions[0]?.type || 'hero',
    name: '',
    is_active: true,
    data: props.sectionDefinitions[0]?.defaults?.data || {},
    settings: props.sectionDefinitions[0]?.defaults?.settings || {},
});

const updateForm = useForm({
    type: '',
    name: '',
    sort_order: 0,
    is_active: true,
    data: {},
    settings: {},
});

const selectedSection = computed(() => props.sections.find((section) => section.id === selectedSectionId.value) || null);
const selectedDefinition = computed(() => selectedSection.value ? definitionsByType.value[selectedSection.value.type] : null);
const createDefinition = computed(() => definitionsByType.value[createForm.type] || props.sectionDefinitions[0] || null);

watch(() => createForm.type, (type) => {
    const definition = definitionsByType.value[type];

    if (!definition) {
        return;
    }

    createForm.name = definition.name_default;
    createForm.data = structuredClone(definition.defaults.data);
    createForm.settings = structuredClone(definition.defaults.settings);
}, { immediate: true });

watch(selectedSection, (section) => {
    if (!section) {
        return;
    }

    updateForm.defaults({
        type: section.type,
        name: section.name || '',
        sort_order: section.sort_order,
        is_active: Boolean(section.is_active),
        data: structuredClone(section.data || {}),
        settings: structuredClone(section.settings || {}),
    });
    updateForm.reset();
    updateForm.clearErrors();
}, { immediate: true });

const submitCreate = () => {
    createForm.post(`/admin/cms/pages/${props.pageRecord.id}/sections`, {
        preserveScroll: true,
    });
};

const submitUpdate = () => {
    if (!selectedSection.value) {
        return;
    }

    updateForm.patch(`/admin/page-sections/${selectedSection.value.id}`, {
        preserveScroll: true,
    });
};

const moveSection = (sectionId, direction) => {
    const items = props.sections.map((section) => ({ id: section.id, sort_order: section.sort_order }));
    const index = items.findIndex((item) => item.id === sectionId);

    if (index === -1) {
        return;
    }

    const targetIndex = direction === 'up' ? index - 1 : index + 1;

    if (targetIndex < 0 || targetIndex >= items.length) {
        return;
    }

    [items[index], items[targetIndex]] = [items[targetIndex], items[index]];

    router.post(`/admin/cms/pages/${props.pageRecord.id}/sections/reorder`, {
        sections: items.map((item, currentIndex) => ({
            id: item.id,
            sort_order: currentIndex + 1,
        })),
    }, {
        preserveScroll: true,
    });
};

const toggleSection = (section) => {
    router.patch(`/admin/page-sections/${section.id}`, {
        type: section.type,
        name: section.name,
        sort_order: section.sort_order,
        is_active: !section.is_active,
        data: section.data,
        settings: section.settings,
    }, {
        preserveScroll: true,
    });
};

const confirmDelete = (sectionId) => {
    deleteSectionId.value = sectionId;
};

const deleteSection = () => {
    if (!deleteSectionId.value) {
        return;
    }

    deleteLoading.value = true;

    router.delete(`/admin/page-sections/${deleteSectionId.value}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteLoading.value = false;
            deleteSectionId.value = null;
        },
    });
};

const cancelUpdate = () => router.get('/admin/cms/pages');
</script>

<template>
    <Head :title="`Seksyen ${pageRecord.title}`" />

    <AdminLayout>
        <section class="space-y-6">
            <PageHeader
                :title="`Seksyen Halaman: ${pageRecord.title}`"
                description="Tambah, susun semula dan kemas kini seksyen mengikut struktur CMS yang terkawal."
            >
                <template #actions>
                    <StatusBadge :status="pageRecord.status" />
                    <Button :as="Link" :href="`/admin/cms/pages/${pageRecord.id}/edit`" variant="outline">
                        <PencilLine class="mr-2 h-4 w-4" />
                        Edit Halaman
                    </Button>
                </template>
            </PageHeader>

            <div v-if="statusMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                {{ statusMessage }}
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
                <div class="space-y-6">
                    <FormSection title="Tambah Seksyen" description="Pilih jenis seksyen yang telah ditetapkan. Admin tidak boleh menambah CSS atau JavaScript bebas.">
                        <SelectInput id="section-type" v-model="createForm.type" label="Jenis seksyen" :options="sectionTypeOptions" :error="createForm.errors.type" />
                        <TextInput id="section-name" v-model="createForm.name" label="Nama seksyen" :error="createForm.errors.name" />
                        <ToggleSwitch id="create-section-active" v-model="createForm.is_active" label="Papar seksyen ini" description="Jika dimatikan, seksyen disimpan tetapi tidak dipaparkan pada laman awam." />
                        <CmsSectionFields
                            v-if="createDefinition"
                            :fields="createDefinition.data_fields"
                            :model="createForm.data"
                            :errors="createForm.errors"
                            prefix="data"
                        />
                        <CmsSectionFields
                            v-if="createDefinition"
                            :fields="createDefinition.settings_fields"
                            :model="createForm.settings"
                            :errors="createForm.errors"
                            prefix="settings"
                        />
                    </FormSection>

                    <div class="flex justify-end">
                        <Button type="button" :disabled="createForm.processing" @click="submitCreate">
                            <FilePlus2 class="mr-2 h-4 w-4" />
                            {{ createForm.processing ? 'Menyimpan...' : 'Tambah Seksyen' }}
                        </Button>
                    </div>
                </div>

                <div class="space-y-6">
                    <EmptyState
                        v-if="sections.length === 0"
                        title="Belum ada seksyen."
                        description="Tambah seksyen pertama untuk mula membina halaman ini."
                        compact
                    />

                    <DataTable v-else :columns="columns" :rows="sections">
                        <template #cell-sort_order="{ row }">
                            <div class="flex items-center gap-2">
                                <GripVertical class="h-4 w-4 text-slate-400" />
                                {{ row.sort_order }}
                            </div>
                        </template>

                        <template #cell-name="{ row }">
                            <div class="space-y-1">
                                <p class="font-semibold text-slate-950">{{ row.name || row.type_label }}</p>
                                <p class="text-xs text-slate-500">{{ row.type_label }} · {{ row.type }}</p>
                            </div>
                        </template>

                        <template #cell-is_active="{ row }">
                            <StatusBadge :status="row.is_active ? 'active' : 'inactive'" />
                        </template>

                        <template #cell-updated_at="{ row }">
                            <span class="text-sm text-slate-600">{{ row.updated_at }}</span>
                        </template>

                        <template #cell-actions="{ row }">
                            <div class="flex flex-wrap gap-2">
                                <Button type="button" variant="outline" @click="selectedSectionId = row.id">Edit</Button>
                                <Button type="button" variant="ghost" @click="moveSection(row.id, 'up')">
                                    <ArrowUp class="h-4 w-4" />
                                </Button>
                                <Button type="button" variant="ghost" @click="moveSection(row.id, 'down')">
                                    <ArrowDown class="h-4 w-4" />
                                </Button>
                                <Button type="button" variant="ghost" @click="toggleSection(row)">
                                    <component :is="row.is_active ? EyeOff : Eye" class="h-4 w-4" />
                                </Button>
                                <Button type="button" variant="ghost" class="text-red-600 hover:bg-red-50 hover:text-red-700" @click="confirmDelete(row.id)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </template>
                    </DataTable>

                    <FormSection
                        v-if="selectedSection && selectedDefinition"
                        :title="`Edit Seksyen: ${selectedSection.name || selectedSection.type_label}`"
                        :description="selectedDefinition.description"
                    >
                        <div v-if="selectedSection.unknown_data_keys.length || selectedSection.unknown_settings_keys.length" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                            Medan tambahan yang tidak dikenali akan dikekalkan semasa simpan.
                            <span v-if="selectedSection.unknown_data_keys.length"> Data: {{ selectedSection.unknown_data_keys.join(', ') }}.</span>
                            <span v-if="selectedSection.unknown_settings_keys.length"> Tetapan: {{ selectedSection.unknown_settings_keys.join(', ') }}.</span>
                        </div>

                        <TextInput id="update-section-name" v-model="updateForm.name" label="Nama seksyen" :error="updateForm.errors.name" />
                        <TextInput id="update-section-order" v-model="updateForm.sort_order" label="Susunan" type="number" :error="updateForm.errors.sort_order" />
                        <ToggleSwitch id="update-section-active" v-model="updateForm.is_active" label="Papar seksyen ini" />

                        <CmsSectionFields
                            :fields="selectedDefinition.data_fields"
                            :model="updateForm.data"
                            :errors="updateForm.errors"
                            prefix="data"
                        />
                        <CmsSectionFields
                            :fields="selectedDefinition.settings_fields"
                            :model="updateForm.settings"
                            :errors="updateForm.errors"
                            prefix="settings"
                        />
                    </FormSection>

                    <div v-if="selectedSection" class="flex flex-col gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:justify-end">
                        <Button type="button" variant="outline" @click="cancelUpdate">Kembali ke Halaman</Button>
                        <Button type="button" :disabled="updateForm.processing" @click="submitUpdate">
                            {{ updateForm.processing ? 'Menyimpan...' : 'Simpan Seksyen' }}
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <ConfirmDialog
            :open="Boolean(deleteSectionId)"
            title="Padam seksyen?"
            description="Tindakan ini akan memadam seksyen daripada halaman ini. Pastikan anda benar-benar mahu meneruskan."
            confirm-label="Padam Seksyen"
            :loading="deleteLoading"
            @cancel="deleteSectionId = null"
            @confirm="deleteSection"
        />
    </AdminLayout>
</template>
