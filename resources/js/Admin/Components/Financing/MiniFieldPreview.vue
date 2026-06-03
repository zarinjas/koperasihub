<script setup>
import { computed } from 'vue';
import {
  Type, AlignLeft, Mail, Phone, CreditCard, Hash, DollarSign, Calendar,
  Circle, CheckSquare, ToggleLeft, Upload, Image, File, Pen,
  FileText, StickyNote, Info, ListChecks, PenTool,
  Table, MapPin, Heart, Users, User, Fingerprint, CalendarDays,
  Smartphone, Inbox, Briefcase, Building2, IdCard, Badge, Landmark,
} from 'lucide-vue-next';
import { getFieldTypeConfig } from '@/Admin/Helpers/financingFieldTypes';

const props = defineProps({
  field: { type: Object, default: () => ({}) },
});

const config = computed(() => getFieldTypeConfig(props.field.type));

const iconMap = {
  Type, AlignLeft, Mail, Phone, CreditCard, Hash, DollarSign, Calendar,
  Circle, CheckSquare, ToggleLeft,
  Upload, Image, File, Pen,
  FileText, StickyNote, Info, ListChecks, PenTool,
  Table, MapPin, Heart, Users,
  User, Fingerprint, CalendarDays, Smartphone, Inbox, Briefcase, Building2, IdCard, Badge, Landmark,
};

const typeIcon = computed(() => iconMap[config.value?.icon] || FileText);
</script>

<template>
  <div class="mt-2 rounded-lg border border-slate-100 bg-white p-2.5">
    <!-- Label -->
    <div class="mb-1 flex items-center gap-1.5">
      <component :is="typeIcon" class="h-3.5 w-3.5 text-slate-400" />
      <span class="text-[11px] font-medium text-slate-600">{{ config?.label || field.type }}</span>
      <span v-if="field.is_required" class="text-[10px] text-red-500">*</span>
    </div>

    <!-- Content type: note / instruction -->
    <div v-if="config?.isNoteContent" class="rounded bg-blue-50 px-2 py-1.5 text-[11px] text-blue-700 whitespace-pre-wrap">
      {{ field.label || '(kosong)' }}
    </div>

    <!-- Rich text -->
    <div v-else-if="config?.needsRichText" class="rounded bg-slate-50 px-2 py-1.5 text-[11px] text-slate-500 italic">
      Nota / Kandungan
    </div>

    <!-- Image -->
    <div v-else-if="config?.isAdminUpload && field.type === 'image'" class="flex items-center gap-2 rounded bg-slate-50 px-2 py-2 text-[11px] text-slate-400">
      <Image class="h-4 w-4" /> Imej
    </div>

    <!-- PDF -->
    <div v-else-if="config?.isAdminUpload && field.type === 'pdf_document'" class="flex items-center gap-2 rounded bg-slate-50 px-2 py-2 text-[11px] text-slate-400">
      <File class="h-4 w-4" /> Dokumen PDF
    </div>

    <!-- Document checklist -->
    <div v-else-if="config?.needsChecklist" class="space-y-0.5">
      <div v-for="(item, idx) in (field.settings_json?.checklist_items || []).slice(0, 3)" :key="idx" class="flex items-center gap-1.5 text-[11px] text-slate-500">
        <span class="h-3 w-3 rounded border border-slate-300"></span>
        {{ item }}
      </div>
      <div v-if="(field.settings_json?.checklist_items?.length || 0) > 3" class="text-[10px] text-slate-400">
        +{{ field.settings_json.checklist_items.length - 3 }} lagi
      </div>
    </div>

    <!-- Signature block -->
    <div v-else-if="config?.needsSignature" class="flex gap-4">
      <div class="flex-1 border-t border-slate-300 pt-1 text-[10px] text-slate-400">
        {{ field.settings_json?.left_label || 'Tandatangan' }}
      </div>
      <div v-if="field.settings_json?.enable_right !== false" class="flex-1 border-t border-slate-300 pt-1 text-[10px] text-slate-400">
        {{ field.settings_json?.right_label || 'T/tangan' }}
      </div>
    </div>

    <!-- Repeater table -->
    <div v-else-if="config?.needsRepeater" class="rounded bg-slate-50 px-2 py-1.5 text-[11px] text-slate-500">
      <Table class="mr-1 inline h-3 w-3" /> Jadual Berulang
    </div>

    <!-- Address -->
    <div v-else-if="config?.isAddress" class="space-y-1">
      <div class="h-5 rounded border border-slate-200 bg-slate-50"></div>
      <div class="grid grid-cols-2 gap-1">
        <div class="h-5 rounded border border-slate-200 bg-slate-50"></div>
        <div class="h-5 rounded border border-slate-200 bg-slate-50"></div>
      </div>
    </div>

    <!-- Select -->
    <div v-else-if="config?.needsOptions" class="flex items-center justify-between rounded border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] text-slate-400">
      Pilih...
      <ChevronDown class="h-3 w-3" />
    </div>

    <!-- Yes/No -->
    <div v-else-if="field.type === 'yes_no'" class="flex gap-3">
      <span class="flex items-center gap-1 text-[11px] text-slate-400"><span class="h-3 w-3 rounded-full border border-slate-300"></span> Ya</span>
      <span class="flex items-center gap-1 text-[11px] text-slate-400"><span class="h-3 w-3 rounded-full border border-slate-300"></span> Tidak</span>
    </div>

    <!-- File upload -->
    <div v-else-if="field.type === 'file'" class="flex items-center gap-2 rounded border border-dashed border-slate-300 bg-slate-50 px-2 py-1.5 text-[11px] text-slate-400">
      <Upload class="h-3.5 w-3.5" /> Muat Naik Fail
    </div>

    <!-- Digital signature -->
    <div v-else-if="field.type === 'digital_signature'" class="flex items-center gap-2 rounded border border-dashed border-slate-300 bg-slate-50 px-2 py-1.5 text-[11px] text-slate-400">
      <Pen class="h-3.5 w-3.5" /> Tandatangan Digital
    </div>

    <!-- Long text -->
    <div v-else-if="field.type === 'long_text'" class="h-10 rounded border border-slate-200 bg-slate-50"></div>

    <!-- Date -->
    <div v-else-if="field.type === 'date' || field.type === 'member_dob'" class="flex items-center rounded border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] text-slate-400">
      <Calendar class="mr-1.5 h-3 w-3" /> Pilih tarikh...
    </div>

    <!-- Financing Amount -->
    <div v-else-if="field.type === 'financing_amount'" class="flex items-center gap-1.5 rounded border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] text-slate-400">
      <DollarSign class="h-3 w-3" /> RM 0.00
    </div>

    <!-- Financing Tenure -->
    <div v-else-if="field.type === 'financing_tenure'" class="flex items-center gap-1.5 rounded border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] text-slate-400">
      <Calendar class="h-3 w-3" /> 0 bulan
    </div>

    <!-- Default: text input -->
    <div v-else class="h-7 rounded border border-slate-200 bg-slate-50"></div>
  </div>
</template>