<script setup>
import { ref } from 'vue';
import { ChevronDown, ChevronRight, FileText, Check } from 'lucide-vue-next';
import { FIELD_TEMPLATES } from '@/Admin/Helpers/financingFieldTypes';
import { Button } from '@/Shared/Components/ui/button';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';

const emit = defineEmits(['select']);

const showConfirm = ref(null);
const isOpen = ref(false);

const openConfirm = (template) => {
  showConfirm.value = template;
};

const confirmTemplate = () => {
  if (showConfirm.value) {
    emit('select', showConfirm.value.fields, showConfirm.value.name);
  }
  showConfirm.value = null;
};

const cancelTemplate = () => {
  showConfirm.value = null;
};
</script>

<template>
  <div class="rounded-lg border border-slate-200 bg-white">
    <button
      type="button"
      class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors"
      @click="isOpen = !isOpen"
    >
      <ChevronDown v-if="isOpen" class="h-4 w-4 shrink-0 text-slate-400" />
      <ChevronRight v-else class="h-4 w-4 shrink-0 text-slate-400" />
      <FileText class="h-4 w-4 shrink-0 text-slate-500" />
      <span class="flex-1">Templat Borang Pantas</span>
      <span class="text-xs text-slate-400">{{ FIELD_TEMPLATES.length }} templat</span>
    </button>

    <div v-if="isOpen" class="border-t border-slate-100 px-3 pb-3 pt-2">
      <p class="mb-2 text-xs text-slate-500">
        Gunakan template untuk tambah field automatik.
      </p>

      <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        <div
          v-for="template in FIELD_TEMPLATES"
          :key="template.id"
          class="group relative rounded-lg border border-slate-200 bg-slate-50 p-3 transition-all hover:border-teal-200 hover:bg-white hover:shadow-sm"
        >
          <div class="space-y-1">
            <h4 class="text-sm font-semibold text-slate-900 group-hover:text-teal-800">
              {{ template.name }}
            </h4>
            <p class="text-xs text-slate-500 leading-relaxed">{{ template.description }}</p>
            <p class="text-[11px] text-slate-400">
              {{ template.fieldCount }} medan
              <span v-if="template.fields.filter(f => f.is_required).length > 0">
                · {{ template.fields.filter(f => f.is_required).length }} wajib
              </span>
            </p>
          </div>
          <div class="mt-2">
            <Button type="button" variant="outline" size="sm" class="w-full text-xs" @click="openConfirm(template)">
              <Check class="mr-1 h-3 w-3" />
              Guna Templat
            </Button>
          </div>
        </div>
      </div>
    </div>

    <ConfirmDialog
      :open="Boolean(showConfirm)"
      :title="`Guna Templat: ${showConfirm?.name || ''}`"
      :description="`Templat ini akan menambah ${showConfirm?.fieldCount || 0} medan ke dalam seksyen ini. Teruskan?`"
      confirm-label="Guna Templat"
      @cancel="cancelTemplate"
      @confirm="confirmTemplate"
    />
  </div>
</template>
