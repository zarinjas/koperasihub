<script setup>
import { computed, ref, watch } from 'vue';
import { ChevronDown, ChevronRight } from 'lucide-vue-next';
import FieldTypePicker from '@/Admin/Components/Financing/FieldTypePicker.vue';
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import ToggleSwitch from '@/Shared/Components/Form/ToggleSwitch.vue';
import RichTextEditor from '@/Shared/Components/Form/RichTextEditor.vue';
import { Button } from '@/Shared/Components/ui/button';
import { getFieldTypeConfig } from '@/Admin/Helpers/financingFieldTypes';

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

const onFileChange = (e) => {
  const file = e.target.files?.[0];
  if (file) {
    emit('update:modelValue', { ...props.modelValue, _uploadFile: file });
  }
};

const handleSave = () => emit('save');
const handleCancel = () => emit('cancel');

const autoGrow = (arr, idx) => {
  if (idx === arr.length - 1 && arr[idx].trim()) {
    const newArr = [...arr, ''];
    update('checklist_items', newArr);
  }
};

watch(() => props.modelValue.type, () => {
  showAdvanced.value = false;
});
</script>

<template>
  <div class="form-field-editor rounded-xl border border-teal-200 bg-teal-50 p-4">
    <p class="mb-3 text-sm font-semibold text-teal-900">
      {{ mode === 'add' ? 'Maklumat Baharu' : 'Edit Maklumat' }}
    </p>

    <div class="grid gap-3 md:grid-cols-2">
      <!-- Label -->
      <div v-if="cfg?.showLabel !== false" class="md:col-span-2">
        <TextInput
          :id="mode + '-field-label'"
          :model-value="modelValue.label"
          label="Label Soalan"
          placeholder="cth: Nama Penuh Pemohon"
          @update:model-value="update('label', $event)"
        />
      </div>

      <!-- Field Type -->
      <div class="md:col-span-2">
        <FieldTypePicker
          :model-value="modelValue.type"
          label="Jenis Maklumat"
          @update:model-value="update('type', $event)"
        />
      </div>

      <!-- Required toggle -->
      <div v-if="cfg?.showRequired !== false && cfg?.group !== 'content'" class="md:col-span-2">
        <ToggleSwitch
          :id="mode + '-field-required'"
          :model-value="modelValue.is_required"
          label="Wajib Diisi"
          @update:model-value="update('is_required', $event)"
        />
      </div>

      <!-- Options (select/radio/checkbox) -->
      <div v-if="cfg?.needsOptions" class="md:col-span-2">
        <TextareaInput
          :id="mode + '-field-options'"
          :model-value="modelValue.options"
          label="Senarai Pilihan (satu baris setiap pilihan)"
          @update:model-value="update('options', $event)"
        />
      </div>

      <!-- Rich text content -->
      <div v-if="cfg?.needsRichText" class="md:col-span-2">
        <RichTextEditor
          :id="mode + '-field-content'"
          :model-value="modelValue.content"
          label="Kandungan"
          @update:model-value="update('content', $event)"
        />
      </div>

      <!-- Note content (note/instruction_text) -->
      <div v-if="cfg?.isNoteContent" class="md:col-span-2">
        <TextareaInput
          :id="mode + '-field-content'"
          :model-value="modelValue.content"
          label="Kandungan"
          :rows="4"
          @update:model-value="update('content', $event)"
        />
      </div>

      <!-- Checklist items -->
      <div v-if="cfg?.needsChecklist" class="md:col-span-2 space-y-4">
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-800">Item Senarai Semak</label>
          <div v-for="(_, idx) in modelValue.checklist_items" :key="mode + '-ci-' + idx" class="flex gap-2">
            <input
              :value="modelValue.checklist_items[idx]"
              placeholder="cth: Borang permohonan lengkap diisi"
              class="h-9 flex-1 rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-700 focus:outline-none"
              @input="
                const arr = [...modelValue.checklist_items];
                arr[idx] = $event.target.value;
                update('checklist_items', arr);
                autoGrow(arr, idx);
              "
            />
            <button
              v-if="modelValue.checklist_items.length > 1"
              type="button"
              class="rounded-lg px-2 text-slate-400 hover:text-red-500"
              @click="
                const arr = modelValue.checklist_items.filter((_, i) => i !== idx);
                update('checklist_items', arr.length ? arr : ['']);
              "
            >✕</button>
          </div>
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-800">Nota</label>
          <div v-for="(_, idx) in modelValue.checklist_notes" :key="mode + '-cn-' + idx" class="flex gap-2">
            <input
              :value="modelValue.checklist_notes[idx]"
              placeholder="cth: ** Hanya bagi produk yang berpenjamin"
              class="h-9 flex-1 rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-700 focus:outline-none"
              @input="
                const arr = [...modelValue.checklist_notes];
                arr[idx] = $event.target.value;
                update('checklist_notes', arr);
                if (idx === arr.length - 1 && arr[idx].trim()) {
                  update('checklist_notes', [...arr, '']);
                }
              "
            />
            <button
              v-if="modelValue.checklist_notes.length > 1"
              type="button"
              class="rounded-lg px-2 text-slate-400 hover:text-red-500"
              @click="
                const arr = modelValue.checklist_notes.filter((_, i) => i !== idx);
                update('checklist_notes', arr.length ? arr : ['']);
              "
            >✕</button>
          </div>
        </div>
      </div>

      <!-- Signature block settings -->
      <div v-if="cfg?.needsSignature" class="md:col-span-2 space-y-3">
        <div class="grid gap-3 md:grid-cols-2">
          <div class="space-y-2">
            <label class="flex items-center gap-2 text-sm font-medium text-slate-800 select-none cursor-pointer">
              <input
                type="checkbox"
                :checked="modelValue.sig_enable_left"
                class="h-4 w-4 rounded border-slate-300 text-teal-600"
                @change="update('sig_enable_left', $event.target.checked)"
              />
              Tandatangan Kiri
            </label>
            <TextInput
              v-if="modelValue.sig_enable_left"
              :id="mode + '-sig-left'"
              :model-value="modelValue.sig_left_label"
              label=""
              placeholder="cth: Tandatangan Pemohon"
              @update:model-value="update('sig_left_label', $event)"
            />
          </div>
          <div class="space-y-2">
            <label class="flex items-center gap-2 text-sm font-medium text-slate-800 select-none cursor-pointer">
              <input
                type="checkbox"
                :checked="modelValue.sig_enable_right"
                class="h-4 w-4 rounded border-slate-300 text-teal-600"
                @change="update('sig_enable_right', $event.target.checked)"
              />
              Tandatangan Kanan
            </label>
            <TextInput
              v-if="modelValue.sig_enable_right"
              :id="mode + '-sig-right'"
              :model-value="modelValue.sig_right_label"
              label=""
              placeholder="cth: T/tangan Penerima Borang"
              @update:model-value="update('sig_right_label', $event)"
            />
          </div>
        </div>
      </div>

      <!-- Admin upload (image/pdf) -->
      <div v-if="cfg?.isAdminUpload" class="md:col-span-2 space-y-2">
        <label class="text-sm font-medium text-slate-800">{{ modelValue.type === 'image' ? 'Muat Naik Imej' : 'Muat Naik PDF' }}</label>
        <input
          type="file"
          :accept="modelValue.type === 'image' ? 'image/*' : '.pdf'"
          class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-teal-700 hover:file:bg-teal-100"
          @change="onFileChange"
        />
        <p v-if="modelValue._existingFile" class="text-xs text-green-700">Fail sedia ada: {{ modelValue._existingFile }}</p>
        <p class="text-xs text-slate-400">Fail akan dipaparkan dalam borang untuk rujukan pemohon.</p>
      </div>

      <!-- ─── Tetapan Lanjutan ─── -->
      <div v-if="cfg?.showPlaceholder || cfg?.showHelpText || cfg?.needsRepeater || modelValue.type === 'file'" class="md:col-span-2 mt-2">
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
          <!-- Placeholder -->
          <TextInput
            v-if="cfg?.showPlaceholder"
            :id="mode + '-field-placeholder'"
            :model-value="modelValue.placeholder"
            label="Placeholder"
            placeholder="cth: Masukkan nama penuh"
            @update:model-value="update('placeholder', $event)"
          />

          <!-- Help text -->
          <TextInput
            v-if="cfg?.showHelpText"
            :id="mode + '-field-help'"
            :model-value="modelValue.help_text"
            label="Teks Bantuan"
            placeholder="cth: Nama mesti sama dengan kad pengenalan"
            @update:model-value="update('help_text', $event)"
          />

          <!-- File max size -->
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

          <!-- Repeater JSON editor -->
          <div v-if="cfg?.needsRepeater" class="space-y-1">
            <label class="text-sm font-medium text-slate-800">Tetapan Jadual (JSON)</label>
            <textarea
              :value="modelValue.repeater_settings"
              rows="6"
              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-xs focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
              @input="update('repeater_settings', $event.target.value)"
            ></textarea>
            <p class="text-xs text-slate-400">Edit JSON untuk mengubah suai lajur jadual berulang.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Error message -->
    <p v-if="fieldError" class="mt-2 text-sm text-red-600">{{ fieldError }}</p>

    <!-- Actions -->
    <div class="mt-3 flex justify-end gap-2">
      <Button type="button" variant="outline" @click="handleCancel">
        Batal
      </Button>
      <Button type="button" @click="handleSave">
        {{ mode === 'add' ? 'Tambah Maklumat' : 'Simpan' }}
      </Button>
    </div>
  </div>
</template>
