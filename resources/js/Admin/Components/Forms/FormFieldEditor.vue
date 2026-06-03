<script setup>
import { computed, ref, watch } from 'vue';
import { ChevronDown, ChevronRight } from 'lucide-vue-next';
import FieldTypePicker from '@/Admin/Components/Forms/FieldTypePicker.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import ToggleSwitch from '@/Shared/Components/Form/ToggleSwitch.vue';
import { Button } from '@/Shared/Components/ui/button';
import { getFieldTypeConfig } from '@/Admin/Helpers/formFieldTypes';

const props = defineProps({
  mode: { type: String, default: 'add' },
  modelValue: { type: Object, default: () => ({}) },
  fieldError: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'save', 'cancel']);

const cfg = computed(() => getFieldTypeConfig(props.modelValue.type));

const showAdvanced = ref(false);

const update = (key, value) => {
  emit('update:modelValue', { ...props.modelValue, [key]: value });
};

const handleSave = () => emit('save');
const handleCancel = () => emit('cancel');

watch(() => props.modelValue.type, () => {
  showAdvanced.value = false;
});
</script>

<template>
  <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
      <span class="h-1 w-1 rounded-full bg-teal-600"></span>
      <p class="text-sm font-semibold text-slate-900">
        {{ mode === 'add' ? 'Tambah Soalan' : 'Edit Soalan' }}
      </p>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
      <div class="md:col-span-2">
        <TextInput
          :id="mode + '-field-label'"
          :model-value="modelValue.label"
          label="Teks Soalan"
          placeholder="cth: Nama Penuh Pemohon"
          @update:model-value="update('label', $event)"
        />
      </div>

      <div class="md:col-span-2">
        <FieldTypePicker
          :model-value="modelValue.type"
          label="Jenis Maklumat"
          @update:model-value="update('type', $event)"
        />
      </div>

      <div v-if="cfg?.showRequired !== false && !cfg?.isMemberAutofill" class="md:col-span-2">
        <ToggleSwitch
          :id="mode + '-field-required'"
          :model-value="modelValue.is_required"
          label="Wajib Diisi"
          @update:model-value="update('is_required', $event)"
        />
      </div>

      <div v-if="cfg?.needsOptions" class="md:col-span-2">
        <TextareaInput
          :id="mode + '-field-options'"
          :model-value="modelValue.options_text"
          label="Senarai Pilihan (satu baris setiap pilihan)"
          @update:model-value="update('options_text', $event)"
        />
      </div>

      <div v-if="cfg?.isMemberAutofill" class="md:col-span-2">
        <div class="rounded-lg border border-purple-200 bg-purple-50 p-3 text-sm text-purple-800">
          <p class="font-medium">Auto-isi daripada profil ahli</p>
          <p class="mt-1 text-xs text-purple-600">Medan ini akan diisi secara automatik berdasarkan data ahli yang login. Tiada input manual diperlukan.</p>
        </div>
      </div>

      <div v-if="cfg?.isAddress && !cfg?.isMemberAutofill" class="md:col-span-2">
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
          <p class="font-medium">Medan Alamat</p>
          <p class="mt-1 text-xs text-blue-600">Medan ini mengandungi sub-medan: Alamat Baris 1, Alamat Baris 2 (pilihan), Poskod, Bandar, dan Negeri (dropdown).</p>
        </div>
      </div>

      <!-- Agreement text -->
      <div v-if="modelValue.type === 'agreement_checkbox'" class="md:col-span-2">
        <TextareaInput
          :id="mode + '-field-agreement'"
          :model-value="modelValue.help_text"
          label="Teks Persetujuan"
          placeholder="cth: Saya mengaku bahawa maklumat yang diberikan adalah benar."
          @update:model-value="update('help_text', $event)"
        />
      </div>

      <!-- Instruction text -->
      <div v-if="modelValue.type === 'instruction_text' || modelValue.type === 'note'" class="md:col-span-2">
        <TextareaInput
          :id="mode + '-field-content'"
          :model-value="modelValue.help_text"
          :label="modelValue.type === 'instruction_text' ? 'Teks Arahan' : 'Kandungan Nota'"
          :rows="4"
          @update:model-value="update('help_text', $event)"
        />
      </div>

      <!-- ─── Tetapan Lanjutan ─── -->
      <div v-if="(cfg?.showPlaceholder || cfg?.showHelpText || modelValue.type === 'file' || modelValue.type === 'office_use_box') && !cfg?.isAddress" class="md:col-span-2 mt-2">
        <button
          type="button"
          class="flex w-full items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
          @click="showAdvanced = !showAdvanced"
        >
          <ChevronDown v-if="showAdvanced" class="h-4 w-4" />
          <ChevronRight v-else class="h-4 w-4" />
          Tetapan Lanjutan
        </button>

        <div v-if="showAdvanced" class="mt-3 space-y-3">
          <TextInput
            v-if="cfg?.showPlaceholder"
            :id="mode + '-field-placeholder'"
            :model-value="modelValue.placeholder"
            label="Placeholder"
            placeholder="cth: Masukkan nama penuh"
            @update:model-value="update('placeholder', $event)"
          />

          <TextInput
            v-if="cfg?.showHelpText && modelValue.type !== 'agreement_checkbox'"
            :id="mode + '-field-help'"
            :model-value="modelValue.help_text"
            label="Teks Bantuan"
            placeholder="cth: Nama mesti sama dengan kad pengenalan"
            @update:model-value="update('help_text', $event)"
          />

          <div v-if="modelValue.type === 'file'" class="space-y-1">
            <label class="text-sm font-medium text-slate-800">Had Saiz Fail (KB)</label>
            <input
              :value="modelValue.file_max_size_kb"
              type="number"
              min="1"
              class="h-10 w-40 rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
              @input="update('file_max_size_kb', parseInt($event.target.value) || 5120)"
            />
          </div>

          <div v-if="modelValue.type === 'office_use_box'" class="md:col-span-2">
            <ToggleSwitch
              :id="mode + '-field-printonly'"
              :model-value="modelValue.print_only"
              label="Paparkan untuk cetakan sahaja"
              @update:model-value="update('print_only', $event)"
            />
          </div>
        </div>
      </div>
    </div>

    <p v-if="fieldError" class="mt-2 text-sm text-red-600">{{ fieldError }}</p>

    <div class="mt-3 flex justify-end gap-2">
      <Button type="button" variant="outline" @click="handleCancel">
        Batal
      </Button>
      <Button type="button" @click="handleSave">
        {{ mode === 'add' ? 'Tambah Soalan' : 'Simpan' }}
      </Button>
    </div>
  </div>
</template>
