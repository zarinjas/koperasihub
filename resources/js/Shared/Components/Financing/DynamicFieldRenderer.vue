<script setup>
import { computed, ref, watch } from 'vue';
import { Download, AlertCircle, FileText } from 'lucide-vue-next';
import { parseOptions, parseSettings, getFieldFileUrl, getFieldLabel } from '@/Shared/Helpers/financingFormHelpers';
import SignaturePad from '@/Shared/Components/SignaturePad.vue';
import RepeaterTableField from '@/Shared/Components/Fields/RepeaterTableField.vue';

const props = defineProps({
  field: { type: Object, default: () => ({}) },
  value: { type: [String, Number, Array, Object, Boolean], default: null },
  mode: {
    type: String,
    default: 'builder-preview',
    validator: (v) => ['builder-preview', 'member-fill', 'product-preview', 'print', 'pdf'].includes(v),
  },
  errors: { type: [String, Object], default: '' },
  product: { type: Object, default: null },
});

const emit = defineEmits(['update:value', 'file-change']);

const settings = computed(() => parseSettings(props.field));
const fileUrl = computed(() => getFieldFileUrl(props.field));

const isAddressType = computed(() =>
  ['address_my', 'address_spouse', 'address_beneficiary'].includes(props.field.type)
);

const isContent = computed(() =>
  ['rich_text', 'image', 'pdf_document', 'note', 'instruction_text', 'document_checklist', 'signature_block'].includes(props.field.type)
);

const addressValue = computed(() => {
  if (!isAddressType.value) return null;
  const v = props.value;
  if (typeof v === 'object' && v !== null) return v;
  return { line1: '', line2: '', postcode: '', city: '', state: '' };
});

const isFinancingAmount = computed(() => props.field.type === 'financing_amount');
const isFinancingTenure = computed(() => props.field.type === 'financing_tenure');

const finMinAmount = computed(() => Number(props.product?.min_amount ?? 0));
const finMaxAmount = computed(() => Number(props.product?.max_amount ?? 9999999));
const finMinTenure = computed(() => Number(props.product?.min_tenure_months ?? 1));
const finMaxTenure = computed(() => Number(props.product?.max_tenure_months ?? 360));

const isInteractive = computed(() => props.mode === 'member-fill');
const isPreview = computed(() => props.mode === 'builder-preview' || props.mode === 'product-preview');
const isPrint = computed(() => props.mode === 'print' || props.mode === 'pdf');
const showErrors = computed(() => isInteractive.value && props.errors);

const signatureTimestamp = ref(null);

watch(() => props.value, (newVal) => {
    if (newVal && !signatureTimestamp.value) {
        signatureTimestamp.value = new Date();
    }
});

const checkboxArray = computed(() => Array.isArray(props.value) ? props.value : []);

function onInput(e) {
  emit('update:value', e.target.value);
}

function onNumberInput(e) {
  const v = e.target.value;
  emit('update:value', v === '' ? '' : Number(v));
}

function onCheckboxChange(optVal, checked) {
  const arr = [...checkboxArray.value];
  const idx = arr.indexOf(optVal);
  if (checked && idx === -1) arr.push(optVal);
  if (!checked && idx !== -1) arr.splice(idx, 1);
  emit('update:value', arr);
}

function onSelectChange(e) {
  emit('update:value', e.target.value);
}

function onRadioChange(val) {
  emit('update:value', val);
}

function onFileChange(e) {
  const f = e.target.files?.[0];
  if (f) emit('file-change', f);
}

function onAddressChange(suffix, value) {
  const current = addressValue.value || { line1: '', line2: '', postcode: '', city: '', state: '' };
  emit('update:value', { ...current, [suffix]: value });
}

function fmtPlaceholder(val) {
  if (val == null || val === 0) return '';
  return 'RM ' + Number(val).toLocaleString('en-MY');
}
</script>

<template>
  <div
    class="dynamic-field"
    :class="{
      'col-span-full': isContent || isAddressType || field.type === 'long_text' || field.type === 'repeater_table',
      'print-mode': isPrint,
      'preview-mode': isPreview,
      'interactive-mode': isInteractive,
    }"
  >
    <!-- ==================== CONTENT TYPES ==================== -->

    <!-- Rich Text -->
    <div v-if="field.type === 'rich_text' && settings.content" class="prose prose-slate prose-sm max-w-none" v-html="settings.content" />

    <!-- Image -->
    <div v-else-if="field.type === 'image' && fileUrl" class="rounded-lg border border-slate-100 p-3">
      <p v-if="field.label && isPreview" class="mb-2 text-xs font-medium text-slate-700">{{ field.label }}</p>
      <img v-if="fileUrl" :src="fileUrl" class="max-h-48 rounded-lg border object-contain" :alt="field.label" />
      <div v-else class="flex items-center justify-center rounded border border-dashed border-slate-300 bg-slate-50 px-3 py-8 text-xs text-slate-400">
        Imej (belum dimuat naik)
      </div>
    </div>

    <!-- PDF Document -->
    <div v-else-if="field.type === 'pdf_document' && fileUrl" class="rounded-lg border border-slate-100 p-3">
      <p v-if="field.label && isPreview" class="mb-2 text-xs font-medium text-slate-700">{{ field.label }}</p>
      <a :href="fileUrl" target="_blank"
        class="inline-flex items-center gap-1.5 rounded bg-red-50 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-100">
        <Download class="h-3.5 w-3.5" /> Muat Turun PDF
      </a>
    </div>

    <!-- Note -->
    <div v-else-if="field.type === 'note'" class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700 whitespace-pre-wrap">
      <p v-if="isInteractive || isPreview" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ getFieldLabel(field) || 'Nota' }}</p>
      {{ field.label }}
    </div>

    <!-- Instruction Text -->
    <div v-else-if="field.type === 'instruction_text'" class="rounded-lg border border-blue-200 bg-blue-50 p-3">
      <div class="flex items-start gap-2">
        <AlertCircle v-if="!isPrint" class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" />
        <div>
          <p class="text-xs font-medium text-blue-800 whitespace-pre-wrap">{{ field.label }}</p>
          <p v-if="field.help_text" class="mt-0.5 text-xs text-blue-700">{{ field.help_text }}</p>
        </div>
      </div>
    </div>

    <!-- Document Checklist -->
    <div v-else-if="field.type === 'document_checklist'" class="rounded-lg border border-slate-200 p-4">
      <table class="w-full border-collapse text-xs">
        <thead>
          <tr class="border border-slate-300 bg-slate-50">
            <th class="border border-slate-300 px-2 py-1 text-left font-semibold w-8">BIL</th>
            <th class="border border-slate-300 px-2 py-1 text-left font-semibold">PERKARA</th>
            <th class="border border-slate-300 px-2 py-1 text-center font-semibold w-24">SILA TANDAKAN (√)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!(settings.checklist_items?.length)">
            <td colspan="3" class="border border-slate-200 px-2 py-2 text-center text-slate-400">Belum ada item.</td>
          </tr>
          <tr v-for="(item, idx) in (settings.checklist_items ?? [])" :key="idx" class="border border-slate-200">
            <td class="border border-slate-200 px-2 py-1 text-center">{{ idx + 1 }}.</td>
            <td class="border border-slate-200 px-2 py-1">{{ item }}</td>
            <td class="border border-slate-200 px-2 py-1"></td>
          </tr>
        </tbody>
      </table>
      <div v-if="settings.checklist_notes?.length" class="mt-3 space-y-0.5 text-xs text-slate-600">
        <p class="font-medium">Nota :</p>
        <p v-for="(note, idx) in settings.checklist_notes" :key="idx">{{ idx + 1 }}. {{ note }}</p>
      </div>
    </div>

    <!-- Signature Block -->
    <div v-else-if="field.type === 'signature_block'" class="rounded-lg border border-slate-200 p-3">
      <div class="flex flex-col gap-6 sm:flex-row sm:justify-between text-xs text-slate-600">
        <div v-if="settings.enable_left !== false" class="w-full sm:w-44">
          <p class="font-medium">{{ settings.left_label || 'Tandatangan Pemohon' }}</p>
          <div class="mt-6 border-b border-slate-400"></div>
          <p class="mt-1">Nama: ____________</p>
          <p>Tarikh: ____________</p>
        </div>
        <div v-if="settings.enable_right !== false" class="w-full sm:w-44">
          <p class="font-medium">{{ settings.right_label || 'T/tangan Penerima Borang' }}</p>
          <div class="mt-6 border-b border-slate-400"></div>
          <p class="mt-1">Nama: ____________</p>
          <p>Tarikh: ____________</p>
        </div>
      </div>
    </div>

    <!-- Digital Signature (member-fill mode) -->
    <div v-else-if="field.type === 'digital_signature'">
      <template v-if="isInteractive">
        <SignaturePad
          :model-value="value"
          :label="field.label + (field.is_required ? ' *' : '')"
          :error="props.errors"
          @update:model-value="(val) => emit('update:value', val)"
        />
        <p v-if="value && signatureTimestamp" class="mt-1 text-xs text-slate-400">
          Ditandatangani pada {{ signatureTimestamp.toLocaleDateString('ms-MY', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
        </p>
      </template>
      <template v-else>
        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-center text-xs text-slate-400">
          <FileText class="mx-auto mb-1 h-4 w-4" />
          Tandatangan Digital
        </div>
      </template>
    </div>

    <!-- Repeater Table (member-fill mode) -->
    <div v-else-if="field.type === 'repeater_table'">
      <template v-if="isInteractive">
        <RepeaterTableField
          :model-value="value"
          :field="field"
          :error="props.errors"
          @update:model-value="(val) => emit('update:value', val)"
        />
      </template>
      <template v-else>
        <label v-if="field.label" class="mb-1 block text-xs font-medium text-slate-700">{{ field.label }}</label>
        <div class="rounded-lg border border-slate-200 p-3 text-xs text-slate-500">
          <FileText class="mr-1 inline h-3.5 w-3.5" />
          Jadual Berulang
        </div>
      </template>
    </div>

    <!-- ==================== ADDRESS TYPES ==================== -->

    <div v-else-if="isAddressType">
      <!-- Interactive mode -->
      <template v-if="isInteractive">
        <label class="block text-sm font-medium text-slate-800 mb-1.5">
          {{ field.label }}<span v-if="field.is_required" class="ml-0.5 text-red-500">*</span>
        </label>
        <div class="space-y-2">
          <input :value="addressValue?.line1 || ''" type="text" placeholder="Nombor & Nama Jalan / Taman"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
            :class="{ 'border-red-500': showErrors }"
            @input="onAddressChange('line1', $event.target.value)" />
          <input :value="addressValue?.line2 || ''" type="text" placeholder="Kawasan / Pekan (pilihan)"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
            @input="onAddressChange('line2', $event.target.value)" />
          <div class="grid grid-cols-2 gap-2">
            <input :value="addressValue?.postcode || ''" type="text" placeholder="Poskod" maxlength="5"
              class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
              @input="onAddressChange('postcode', $event.target.value)" />
            <input :value="addressValue?.city || ''" type="text" placeholder="Bandar"
              class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
              @input="onAddressChange('city', $event.target.value)" />
          </div>
          <select :value="addressValue?.state || ''"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
            @change="onAddressChange('state', $event.target.value)">
            <option value="" disabled>-- Pilih Negeri --</option>
            <option>Johor</option>
            <option>Kedah</option>
            <option>Kelantan</option>
            <option>Melaka</option>
            <option>Negeri Sembilan</option>
            <option>Pahang</option>
            <option>Perak</option>
            <option>Perlis</option>
            <option>Pulau Pinang</option>
            <option>Sabah</option>
            <option>Sarawak</option>
            <option>Selangor</option>
            <option>Terengganu</option>
            <option>W.P. Kuala Lumpur</option>
            <option>W.P. Labuan</option>
            <option>W.P. Putrajaya</option>
          </select>
        </div>
        <p v-if="showErrors" class="mt-1 text-sm text-red-600">{{ props.errors }}</p>
      </template>

      <!-- Preview/Print mode -->
      <template v-else>
        <label v-if="field.label" class="mb-1 block text-xs font-medium text-slate-700">
          {{ field.label }}
          <span v-if="field.is_required && isPreview" class="text-red-500">*</span>
        </label>
        <div class="space-y-1 rounded-lg border border-slate-200 bg-slate-50 p-3">
          <div v-if="addressValue?.line1" class="text-xs text-slate-600">{{ addressValue.line1 }}</div>
          <div v-if="addressValue?.line2" class="text-xs text-slate-600">{{ addressValue.line2 }}</div>
          <div v-if="addressValue?.postcode || addressValue?.city || addressValue?.state" class="text-xs text-slate-600">
            {{ [addressValue?.postcode, addressValue?.city, addressValue?.state].filter(Boolean).join(', ') }}
          </div>
          <div v-if="!(addressValue?.line1 || addressValue?.postcode)" class="text-xs text-slate-400 italic">
            <template v-if="isPreview">
              <input disabled placeholder="Nombor & Nama Jalan / Taman" class="mb-1 h-7 w-full rounded border border-slate-200 bg-white px-2 text-xs text-slate-400" />
              <div class="grid grid-cols-2 gap-1">
                <input disabled placeholder="Poskod" class="h-7 rounded border border-slate-200 bg-white px-2 text-xs text-slate-400" />
                <input disabled placeholder="Bandar" class="h-7 rounded border border-slate-200 bg-white px-2 text-xs text-slate-400" />
              </div>
              <input disabled placeholder="Negeri" class="mt-1 h-7 w-full rounded border border-slate-200 bg-white px-2 text-xs text-slate-400" />
            </template>
            <span v-else>—</span>
          </div>
        </div>
      </template>
    </div>

    <!-- ==================== INPUT TYPES ==================== -->

    <div v-else>
      <!-- Label -->
      <label v-if="field.label" class="mb-1.5 block text-sm font-medium text-slate-800">
        {{ field.label }}
        <span v-if="field.is_required" class="ml-0.5 text-red-500">*</span>
      </label>

      <!-- Long Text -->
      <textarea v-if="field.type === 'long_text'"
        :value="isInteractive ? value : ''"
        :placeholder="isPreview ? (field.placeholder || 'Isian...') : (field.placeholder || '')"
        rows="4"
        :readonly="!isInteractive"
        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 resize-none"
        :class="[!isInteractive ? 'bg-slate-50 text-slate-400' : '', showErrors ? 'border-red-500' : '']"
        @input="isInteractive ? onInput($event) : null"
      ></textarea>

      <!-- Select -->
      <select v-else-if="field.type === 'select'"
        :value="isInteractive ? value : ''"
        :disabled="!isInteractive"
        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
        :class="[!isInteractive ? 'bg-slate-50 text-slate-400' : '', showErrors ? 'border-red-500' : '']"
        @change="isInteractive ? onSelectChange($event) : null"
      >
        <option value="" disabled>Pilih...</option>
        <option v-for="opt in parseOptions(field)" :key="opt.value ?? opt" :value="opt.value ?? opt">
          {{ opt.label ?? opt }}
        </option>
      </select>

      <!-- Radio -->
      <div v-else-if="field.type === 'radio'" class="space-y-2">
        <label v-for="opt in parseOptions(field)" :key="opt.value ?? opt" class="flex items-center gap-2 text-sm">
          <input type="radio" :value="opt.value ?? opt"
            :checked="isInteractive ? value === (opt.value ?? opt) : false"
            :disabled="!isInteractive"
            class="h-4 w-4 accent-teal-700"
            @change="isInteractive ? onRadioChange(opt.value ?? opt) : null" />
          {{ opt.label ?? opt }}
        </label>
      </div>

      <!-- Checkbox -->
      <div v-else-if="field.type === 'checkbox'" class="space-y-2">
        <label v-for="opt in parseOptions(field)" :key="opt.value ?? opt" class="flex items-center gap-2 text-sm">
          <input type="checkbox" :value="opt.value ?? opt"
            :checked="checkboxArray.includes(opt.value ?? opt)"
            :disabled="!isInteractive"
            class="h-4 w-4 accent-teal-700"
            @change="isInteractive ? onCheckboxChange(opt.value ?? opt, $event.target.checked) : null" />
          {{ opt.label ?? opt }}
        </label>
      </div>

      <!-- Yes/No -->
      <div v-else-if="field.type === 'yes_no'" class="flex gap-4">
        <label class="flex items-center gap-2 text-sm">
          <input type="radio" value="ya"
            :checked="isInteractive ? value === 'ya' : false"
            :disabled="!isInteractive"
            class="h-4 w-4 accent-teal-700"
            @change="isInteractive ? onRadioChange('ya') : null" /> Ya
        </label>
        <label class="flex items-center gap-2 text-sm">
          <input type="radio" value="tidak"
            :checked="isInteractive ? value === 'tidak' : false"
            :disabled="!isInteractive"
            class="h-4 w-4 accent-teal-700"
            @change="isInteractive ? onRadioChange('tidak') : null" /> Tidak
        </label>
      </div>

      <!-- File Upload -->
      <div v-else-if="field.type === 'file'" class="space-y-1.5">
        <template v-if="isInteractive">
          <input type="file"
            class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-teal-700 hover:file:bg-teal-100"
            @change="onFileChange" />
          <p v-if="field.help_text" class="text-xs text-slate-500">{{ field.help_text }}</p>
          <p v-if="showErrors" class="text-sm text-red-600">{{ props.errors }}</p>
        </template>
        <template v-else>
          <div class="flex items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-xs text-slate-400">
            <FileText class="h-4 w-4 shrink-0" />
            <span>Muat Naik Fail</span>
          </div>
          <p v-if="field.help_text" class="text-xs text-slate-500">{{ field.help_text }}</p>
        </template>
      </div>

      <!-- Currency -->
      <div v-else-if="field.type === 'currency'"
        class="flex items-center rounded-xl border border-slate-300"
        :class="[!isInteractive ? 'bg-slate-50' : 'bg-white focus-within:border-teal-500 focus-within:ring-2 focus-within:ring-teal-500/20']"
      >
        <span class="pl-4 text-sm text-slate-400">RM</span>
        <input :value="isInteractive ? value : ''"
          type="number" min="0" step="0.01" :placeholder="field.placeholder || '0.00'"
          :readonly="!isInteractive"
          class="flex-1 bg-transparent px-3 py-2.5 text-sm focus:outline-none"
          :class="!isInteractive ? 'text-slate-400' : ''"
          @input="isInteractive ? onNumberInput($event) : null" />
      </div>

      <!-- Financing Amount -->
      <div v-else-if="isFinancingAmount"
        class="flex items-center rounded-xl border border-slate-300"
        :class="[!isInteractive ? 'bg-slate-50' : 'bg-white focus-within:border-teal-500 focus-within:ring-2 focus-within:ring-teal-500/20']">
        <span class="pl-4 text-sm text-slate-400">RM</span>
        <input :value="isInteractive ? value : ''"
          type="number" min="0" step="0.01"
          :placeholder="`${fmtPlaceholder(finMinAmount)} – ${fmtPlaceholder(finMaxAmount)}`"
          :readonly="!isInteractive"
          class="flex-1 bg-transparent px-3 py-2.5 text-sm focus:outline-none"
          :class="!isInteractive ? 'text-slate-400' : ''"
          @input="isInteractive ? onNumberInput($event) : null" />
      </div>

      <!-- Financing Tenure -->
      <div v-else-if="isFinancingTenure"
        class="flex items-center rounded-xl border border-slate-300"
        :class="[!isInteractive ? 'bg-slate-50' : 'bg-white focus-within:border-teal-500 focus-within:ring-2 focus-within:ring-teal-500/20']">
        <input :value="isInteractive ? value : ''"
          type="number" min="1" step="1"
          :placeholder="`${finMinTenure} – ${finMaxTenure} bulan`"
          :readonly="!isInteractive"
          class="flex-1 bg-transparent px-3 py-2.5 text-sm focus:outline-none"
          :class="!isInteractive ? 'text-slate-400' : ''"
          @input="isInteractive ? onNumberInput($event) : null" />
        <span class="pr-4 text-sm text-slate-400">bulan</span>
      </div>

      <!-- Print/PDF value display -->
      <div v-else-if="isPrint" class="text-sm text-slate-700">
        {{ value ?? '-' }}
      </div>

      <!-- Default input -->
      <input v-else
        :value="isInteractive ? value : ''"
        :type="field.type === 'email' || field.type === 'member_email' ? 'email' : field.type === 'phone' || field.type === 'member_phone' ? 'tel' : field.type === 'date' || field.type === 'member_dob' ? 'date' : field.type === 'number' ? 'number' : 'text'"
        :placeholder="isPreview ? (field.placeholder || 'Isian...') : (field.placeholder || '')"
        :readonly="!isInteractive"
        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
        :class="[!isInteractive ? 'bg-slate-50 text-slate-400' : '', showErrors ? 'border-red-500' : '']"
        @input="isInteractive ? onInput($event) : null" />

      <!-- Help text + errors -->
      <p v-if="field.help_text" class="mt-1 text-xs text-slate-500">{{ field.help_text }}</p>
      <p v-if="showErrors" class="mt-1 text-sm text-red-600">{{ props.errors }}</p>
    </div>
  </div>
</template>