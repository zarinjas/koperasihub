<script setup>
import { ref } from 'vue';
import { FileText, Check } from 'lucide-vue-next';
import { FIELD_TEMPLATES } from '@/Admin/Helpers/financingFieldTypes';
import { Button } from '@/Shared/Components/ui/button';
import ConfirmDialog from '@/Shared/Components/ConfirmDialog.vue';

const emit = defineEmits(['select']);

const showConfirm = ref(null);

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
  <div class="space-y-3">
    <div class="flex items-center gap-2">
      <FileText class="h-4 w-4 text-slate-500" />
      <p class="text-sm font-medium text-slate-700">Templat Borang Pantas</p>
    </div>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="template in FIELD_TEMPLATES"
        :key="template.id"
        class="group relative rounded-xl border border-slate-200 bg-white p-4 transition-all hover:border-teal-200 hover:shadow-sm"
      >
        <div class="space-y-2">
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
        <div class="mt-3">
          <Button
            type="button"
            variant="outline"
            size="sm"
            class="w-full"
            @click="openConfirm(template)"
          >
            <Check class="mr-1.5 h-3.5 w-3.5" />
            Guna Templat
          </Button>
        </div>
      </div>
    </div>

    <p class="text-[11px] text-slate-400">
      Templat akan menambah medan ke dalam seksyen ini. Medan akan disimpan terus.
    </p>

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
