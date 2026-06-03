<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import PublicLayout from '@/Public/Layouts/PublicLayout.vue';
import FileUploader from '@/Shared/Components/FileUploader.vue';
import FormDocumentHeader from '@/Shared/Components/FormDocumentHeader.vue';
import FormSection from '@/Shared/Components/FormSection.vue';
import SignaturePad from '@/Shared/Components/SignaturePad.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import StatusBadge from '@/Shared/Components/StatusBadge.vue';
import { Button } from '@/Shared/Components/ui/button';
import { useAutofill } from '@/Shared/Composables/useAutofill';
import { getFieldTypeConfig, MEMBER_FIELD_MAP } from '@/Admin/Helpers/formFieldTypes';

const props = defineProps({
    formRecord: { type: Object, required: true },
    autofillData: { type: Object, default: () => ({}) },
});

const { tryFill, isAutofilled, autofillData } = useAutofill(props);

const MY_STATES = [
    'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan',
    'Pahang', 'Perak', 'Perlis', 'Pulau Pinang', 'Sabah',
    'Sarawak', 'Selangor', 'Terengganu',
    'W.P. Kuala Lumpur', 'W.P. Labuan', 'W.P. Putrajaya',
];

const form = useForm({
    submitted_by_name: '',
    submitted_by_email: '',
    answers: {},
    files: {},
});

for (const section of props.formRecord.sections) {
    for (const field of section.fields) {
        if (!['online_and_print', 'online_only'].includes(field.display_mode)) {
            continue;
        }

        if (field.type === 'checkbox') {
            form.answers[field.field_key] = [];
        } else if (field.type === 'address_my' || field.type === 'member_address') {
            form.answers[field.field_key] = { line1: '', line2: '', postcode: '', city: '', state: '' };
        } else {
            form.answers[field.field_key] = '';
        }
        form.files[field.field_key] = null;
    }
}

function getAddressValue(fieldKey) {
    const v = form.answers[fieldKey];
    if (v && typeof v === 'object') return v;
    return { line1: '', line2: '', postcode: '', city: '', state: '' };
}

function setAddressSubField(fieldKey, key, value) {
    form.answers[fieldKey] = { ...getAddressValue(fieldKey), [key]: value };
}

function composeAddressFromAutofill() {
    const ad = props.autofillData;
    return {
        line1: ad.address_line_1 || ad.address || '',
        line2: ad.address_line_2 || '',
        postcode: ad.postcode || '',
        city: ad.city || '',
        state: ad.state || '',
    };
}

onMounted(() => {
    if (props.formRecord.visibility === 'public' && props.autofillData.full_name) {
        form.submitted_by_name = props.autofillData.full_name;
    }
    if (props.formRecord.visibility === 'public' && props.autofillData.email) {
        form.submitted_by_email = props.autofillData.email;
    }

    for (const section of props.formRecord.sections) {
        for (const field of section.fields) {
            if (!['online_and_print', 'online_only'].includes(field.display_mode)) {
                continue;
            }

            if (field.type === 'file' || field.type === 'signature' || field.type === 'agreement_checkbox'
                || field.type === 'note' || field.type === 'instruction_text' || field.type === 'office_use_box') {
                continue;
            }

            // Handle member_address (compound autofill)
            if (field.type === 'member_address') {
                const addr = composeAddressFromAutofill();
                if (addr.line1 || addr.postcode) {
                    form.answers[field.field_key] = addr;
                    autofilledFields.value.add(field.field_key);
                }
                continue;
            }

            // Try filling by field_key first
            const filled = tryFill(form.answers, field.field_key);

            // If not filled, try member autofill type mapping
            if (!filled) {
                const config = getFieldTypeConfig(field.type);
                if (config?.isMemberAutofill) {
                    const mappedKey = MEMBER_FIELD_MAP[field.type];
                    if (mappedKey && autofillData[mappedKey]) {
                        tryFill(form.answers, field.field_key, () => autofillData[mappedKey]);
                    }
                }
            }
        }
    }
});

const toggleCheckbox = (fieldKey, option) => {
    const current = form.answers[fieldKey] || [];
    if (current.includes(option)) {
        form.answers[fieldKey] = current.filter((item) => item !== option);
        return;
    }

    form.answers[fieldKey] = [...current, option];
};

const submit = () => form.post(`/forms/${props.formRecord.slug}`, {
    forceFormData: true,
    onSuccess: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
});

const isInputVisible = (field) => ['online_and_print', 'online_only'].includes(field.display_mode);
</script>

<template>
    <Head :title="formRecord.title" />

    <PublicLayout>
        <section class="bg-gradient-to-br from-emerald-50 via-white to-blue-50 py-12">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center gap-3">
                    <StatusBadge :status="formRecord.visibility" :label="formRecord.visibility_label" />
                    <span class="text-sm text-slate-500">{{ formRecord.category_name || 'Borang Online' }}</span>
                </div>

                <FormDocumentHeader v-if="formRecord.show_document_header" :form-record="formRecord" />

                <div v-else class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h1 class="text-2xl font-semibold text-slate-950">{{ formRecord.title }}</h1>
                    <p v-if="formRecord.description" class="mt-3 text-sm leading-6 text-slate-600">{{ formRecord.description }}</p>
                </div>

                <div v-if="formRecord.submission_method === 'requires_stamped_upload'" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                    <p class="font-semibold">Perhatian: Borang ini memerlukan cop dan tandatangan</p>
                    <p class="mt-1">{{ formRecord.stamped_upload_instructions }}</p>
                </div>

                <form class="space-y-6" @submit.prevent="submit">
                    <FormSection
                        v-if="formRecord.visibility === 'public'"
                        title="Maklumat Penghantar"
                        description="Maklumat asas ini digunakan untuk rujukan submission."
                        :columns="2"
                    >
                        <TextInput id="submitter-name" v-model="form.submitted_by_name" label="Nama penuh" :error="form.errors.submitted_by_name" />
                        <TextInput id="submitter-email" v-model="form.submitted_by_email" type="email" label="Emel" :error="form.errors.submitted_by_email" />
                    </FormSection>

                    <FormSection
                        v-for="section in formRecord.sections"
                        :key="section.id"
                        :title="section.title"
                        :description="section.description"
                    >
                        <template v-for="field in section.fields" :key="field.id">
                            <div v-if="!isInputVisible(field)" class="hidden" />

                            <div v-else-if="field.type === 'note'" class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4 text-sm leading-7 text-slate-700">
                                <p class="font-semibold text-slate-900">{{ field.label }}</p>
                                <p v-if="field.help_text" class="mt-2">{{ field.help_text }}</p>
                            </div>

                            <div v-else-if="field.type === 'instruction_text'" class="rounded-[1.5rem] border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-900">
                                <p class="font-semibold">{{ field.label }}</p>
                                <p class="mt-2">{{ field.help_text }}</p>
                            </div>

                            <div v-else-if="field.type === 'office_use_box'" class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-900">{{ field.label }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-500">{{ field.help_text || 'Ruangan ini disediakan untuk kegunaan pejabat.' }}</p>
                            </div>

                            <!-- Address field: manual fill -->
                            <div v-else-if="field.type === 'address_my'" class="space-y-3">
                                <p class="text-sm font-medium text-slate-800">
                                    {{ field.label }}<span v-if="field.is_required" class="text-red-500">*</span>
                                </p>
                                <div class="space-y-2">
                                    <input
                                        :value="getAddressValue(field.field_key).line1"
                                        type="text" placeholder="Nombor & Nama Jalan / Taman"
                                        class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm"
                                        @input="setAddressSubField(field.field_key, 'line1', $event.target.value)"
                                    />
                                    <input
                                        :value="getAddressValue(field.field_key).line2"
                                        type="text" placeholder="Kawasan / Pekan (pilihan)"
                                        class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm"
                                        @input="setAddressSubField(field.field_key, 'line2', $event.target.value)"
                                    />
                                    <div class="grid grid-cols-2 gap-2">
                                        <input
                                            :value="getAddressValue(field.field_key).postcode"
                                            type="text" placeholder="Poskod" maxlength="5"
                                            class="h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm"
                                            @input="setAddressSubField(field.field_key, 'postcode', $event.target.value)"
                                        />
                                        <input
                                            :value="getAddressValue(field.field_key).city"
                                            type="text" placeholder="Bandar"
                                            class="h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm"
                                            @input="setAddressSubField(field.field_key, 'city', $event.target.value)"
                                        />
                                    </div>
                                    <select
                                        :value="getAddressValue(field.field_key).state"
                                        class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm"
                                        @change="setAddressSubField(field.field_key, 'state', $event.target.value)"
                                    >
                                        <option value="" disabled>-- Pilih Negeri --</option>
                                        <option v-for="st in MY_STATES" :key="st" :value="st">{{ st }}</option>
                                    </select>
                                </div>
                                <p v-if="form.errors[`answers.${field.field_key}`]" class="text-sm text-red-700">{{ form.errors[`answers.${field.field_key}`] }}</p>
                            </div>

                            <!-- Member address autofill: disabled multi-field -->
                            <div v-else-if="field.type === 'member_address'" class="space-y-3">
                                <p class="text-sm font-medium text-slate-800">
                                    {{ field.label }}<span v-if="field.is_required" class="text-red-500">*</span>
                                </p>
                                <div class="relative space-y-2">
                                    <input
                                        :value="getAddressValue(field.field_key).line1"
                                        type="text" placeholder="Nombor & Nama Jalan / Taman" disabled
                                        class="h-11 w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-3 text-sm text-slate-600"
                                    />
                                    <input
                                        :value="getAddressValue(field.field_key).line2"
                                        type="text" placeholder="Kawasan / Pekan (pilihan)" disabled
                                        class="h-11 w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-3 text-sm text-slate-600"
                                    />
                                    <div class="grid grid-cols-2 gap-2">
                                        <input
                                            :value="getAddressValue(field.field_key).postcode"
                                            type="text" placeholder="Poskod" disabled
                                            class="h-11 cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-3 text-sm text-slate-600"
                                        />
                                        <input
                                            :value="getAddressValue(field.field_key).city"
                                            type="text" placeholder="Bandar" disabled
                                            class="h-11 cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-3 text-sm text-slate-600"
                                        />
                                    </div>
                                    <input
                                        :value="getAddressValue(field.field_key).state"
                                        type="text" placeholder="Negeri" disabled
                                        class="h-11 w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-3 text-sm text-slate-600"
                                    />
                                    <span class="absolute right-3 top-1 rounded bg-purple-50 px-1.5 py-0.5 text-[10px] font-medium text-purple-600">Auto</span>
                                </div>
                                <p v-if="form.errors[`answers.${field.field_key}`]" class="text-sm text-red-700">{{ form.errors[`answers.${field.field_key}`] }}</p>
                            </div>

                            <!-- Member autofill: disabled readonly input -->
                            <div v-else-if="getFieldTypeConfig(field.type)?.isMemberAutofill" class="space-y-1">
                                <label :for="field.field_key" class="text-sm font-medium text-slate-800">
                                    {{ field.label }}
                                    <span v-if="field.is_required" class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        :id="field.field_key"
                                        :value="form.answers[field.field_key]"
                                        disabled
                                        class="h-11 w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-3 text-sm text-slate-600"
                                    />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 rounded bg-purple-50 px-1.5 py-0.5 text-[10px] font-medium text-purple-600">Auto</span>
                                </div>
                                <p v-if="form.errors[`answers.${field.field_key}`]" class="text-sm text-red-700">{{ form.errors[`answers.${field.field_key}`] }}</p>
                            </div>

                            <div v-else-if="['short_text', 'email', 'phone', 'identity_no', 'number', 'currency', 'date'].includes(field.type)" class="space-y-1">
                                <TextInput
                                    :id="field.field_key"
                                    v-model="form.answers[field.field_key]"
                                    :label="`${field.label}${field.is_required ? ' *' : ''}`"
                                    :type="field.type === 'email' ? 'email' : field.type === 'date' ? 'date' : field.type === 'number' || field.type === 'currency' ? 'number' : 'text'"
                                    :error="form.errors[`answers.${field.field_key}`]"
                                />
                                <p v-if="isAutofilled(field.field_key)" class="text-xs text-blue-600 font-medium">Auto-isi</p>
                            </div>

                            <div v-else-if="field.type === 'long_text'" class="space-y-1">
                                <TextareaInput
                                    :id="field.field_key"
                                    v-model="form.answers[field.field_key]"
                                    :label="`${field.label}${field.is_required ? ' *' : ''}`"
                                    :help="field.help_text"
                                    :error="form.errors[`answers.${field.field_key}`]"
                                />
                                <p v-if="isAutofilled(field.field_key)" class="text-xs text-blue-600 font-medium">Auto-isi</p>
                            </div>

                            <div v-else-if="field.type === 'select'" class="space-y-2">
                                <label :for="field.field_key" class="text-sm font-medium text-slate-800">{{ field.label }}<span v-if="field.is_required"> *</span></label>
                                <select
                                    :id="field.field_key"
                                    v-model="form.answers[field.field_key]"
                                    class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-950 shadow-sm"
                                >
                                    <option value="">Pilih pilihan</option>
                                    <option v-for="option in field.options" :key="option" :value="option">{{ option }}</option>
                                </select>
                                <p v-if="isAutofilled(field.field_key)" class="text-xs text-blue-600 font-medium">Auto-isi</p>
                                <p v-if="form.errors[`answers.${field.field_key}`]" class="text-sm text-red-700">{{ form.errors[`answers.${field.field_key}`] }}</p>
                            </div>

                            <div v-else-if="['radio', 'yes_no'].includes(field.type)" class="space-y-3">
                                <p class="text-sm font-medium text-slate-800">{{ field.label }}<span v-if="field.is_required"> *</span></p>
                                <div class="flex flex-wrap gap-3">
                                    <label
                                        v-for="option in field.type === 'yes_no' ? ['yes', 'no'] : field.options"
                                        :key="option"
                                        class="flex items-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700"
                                    >
                                        <input v-model="form.answers[field.field_key]" type="radio" :value="option" />
                                        {{ field.type === 'yes_no' ? (option === 'yes' ? 'Ya' : 'Tidak') : option }}
                                    </label>
                                </div>
                                <p v-if="isAutofilled(field.field_key)" class="text-xs text-blue-600 font-medium">Auto-isi</p>
                                <p v-if="form.errors[`answers.${field.field_key}`]" class="text-sm text-red-700">{{ form.errors[`answers.${field.field_key}`] }}</p>
                            </div>

                            <div v-else-if="field.type === 'checkbox'" class="space-y-3">
                                <p class="text-sm font-medium text-slate-800">{{ field.label }}<span v-if="field.is_required"> *</span></p>
                                <div class="flex flex-col gap-2">
                                    <label
                                        v-for="option in field.options"
                                        :key="option"
                                        class="flex items-center gap-3 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="form.answers[field.field_key]?.includes(option)"
                                            @change="toggleCheckbox(field.field_key, option)"
                                        />
                                        {{ option }}
                                    </label>
                                </div>
                                <p v-if="form.errors[`answers.${field.field_key}`]" class="text-sm text-red-700">{{ form.errors[`answers.${field.field_key}`] }}</p>
                            </div>

                            <FileUploader
                                v-else-if="field.type === 'file'"
                                :id="field.field_key"
                                v-model="form.files[field.field_key]"
                                :label="`${field.label}${field.is_required ? ' *' : ''}`"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                helper-text="Format disokong: PDF, JPG, JPEG, PNG, WEBP. Saiz maksimum 5MB."
                                :error="form.errors[`files.${field.field_key}`]"
                            />

                            <div v-else-if="field.type === 'signature'" class="space-y-2">
                                <label class="text-sm font-medium text-slate-800">{{ field.label }}<span v-if="field.is_required"> *</span></label>
                                <SignaturePad v-model="form.answers[field.field_key]" :error="form.errors[`answers.${field.field_key}`]" />
                            </div>

                            <div v-else-if="field.type === 'agreement_checkbox'" class="rounded-[1.5rem] border border-slate-200 bg-white p-4">
                                <label class="flex items-start gap-3">
                                    <input v-model="form.answers[field.field_key]" type="checkbox" class="mt-1" />
                                    <div class="space-y-2">
                                        <p class="text-sm font-semibold text-slate-950">{{ field.label }}<span v-if="field.is_required"> *</span></p>
                                        <p class="text-sm leading-6 text-slate-600">{{ field.help_text }}</p>
                                    </div>
                                </label>
                                <p v-if="form.errors[`answers.${field.field_key}`]" class="mt-2 text-sm text-red-700">{{ form.errors[`answers.${field.field_key}`] }}</p>
                            </div>
                        </template>
                    </FormSection>

                    <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-950">Hantar borang</h2>
                                <p v-if="formRecord.submission_method === 'requires_stamped_upload'" class="text-sm leading-6 text-slate-600">
                                    Selepas menghantar, anda perlu mencetak borang yang dilengkapkan, mendapatkan cop dan tandatangan, kemudian muat naik semula borang tersebut.
                                </p>
                                <p v-else class="text-sm leading-6 text-slate-600">Sila semak semula maklumat yang diisi sebelum menghantar borang ini.</p>
                            </div>
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Menghantar...' : (formRecord.submission_method === 'requires_stamped_upload' ? 'Teruskan' : 'Hantar Borang') }}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </PublicLayout>
</template>