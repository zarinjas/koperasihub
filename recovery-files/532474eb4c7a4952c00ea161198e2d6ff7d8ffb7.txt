<script setup>
import { computed, ref } from 'vue';
import {
  Search,
  ChevronDown,
  ChevronRight,
  Check,
  X,
  Type,
  AlignLeft,
  Mail,
  Phone,
  CreditCard,
  Hash,
  DollarSign,
  Calendar,
  Circle,
  CheckSquare,
  ToggleLeft,
  Upload,
  Image,
  File,
  Pen,
  FileText,
  StickyNote,
  Info,
  ListChecks,
  PenTool,
  Table,
  MapPin,
  Heart,
  Users,
  User,
  Fingerprint,
  CalendarDays,
  Smartphone,
  Inbox,
  Briefcase,
  Building2,
  IdCard,
  Badge,
  Landmark,
} from 'lucide-vue-next';
import { FIELD_CATEGORIES, FIELD_TYPES, searchFieldTypes } from '@/Admin/Helpers/financingFieldTypes';

const props = defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: 'Jenis Maklumat' },
});

const emit = defineEmits(['update:modelValue']);

const searchQuery = ref('');
const openCategories = ref(['maklumat_asas']);

const iconMap = {
  Type, AlignLeft, Mail, Phone, CreditCard, Hash, DollarSign, Calendar,
  Circle, CheckSquare, ToggleLeft,
  Upload, Image, File, Pen,
  FileText, StickyNote, Info, ListChecks, PenTool,
  Table, MapPin, Heart, Users,
  User, Fingerprint, CalendarDays, Smartphone, Inbox, Briefcase, Building2, IdCard, Badge, Landmark,
};

const filteredTypes = computed(() => {
  if (!searchQuery.value.trim()) return FIELD_TYPES;
  return searchFieldTypes(searchQuery.value.trim());
});

const visibleGroups = computed(() => {
  return FIELD_CATEGORIES
    .map((cat) => ({
      ...cat,
      types: filteredTypes.value.filter((t) => t.category === cat.key),
    }))
    .filter((g) => g.types.length > 0);
});

const toggleCategory = (key) => {
  const idx = openCategories.value.indexOf(key);
  if (idx >= 0) {
    openCategories.value.splice(idx, 1);
  } else {
    openCategories.value.push(key);
  }
};

const isCategoryOpen = (key) => {
  if (searchQuery.value.trim()) return true;
  return openCategories.value.includes(key);
};

const select = (value) => {
  emit('update:modelValue', value);
};

const selectedLabel = computed(() => {
  if (!props.modelValue) return '';
  const cfg = FIELD_TYPES.find((t) => t.value === props.modelValue);
  return cfg ? cfg.label : props.modelValue;
});
</script>

<template>
  <div class="field-type-picker">
    <label class="mb-2 block text-sm font-medium text-slate-800">
      {{ label }}
    </label>

    <div v-if="modelValue" class="mb-2 flex items-center gap-2 rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-sm text-teal-800">
      <component :is="iconMap[FIELD_TYPES.find(t => t.value === modelValue)?.icon || 'FileText']" class="h-4 w-4 shrink-0" />
      <span class="flex-1 font-medium">{{ selectedLabel }}</span>
      <button type="button" class="text-teal-600 hover:text-teal-800" @click="searchQuery = ''; select('');">
        <X class="h-4 w-4" />
      </button>
    </div>

    <div class="relative">
      <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Cari jenis maklumat..."
        class="h-10 w-full rounded-lg border border-slate-300 bg-white pl-9 pr-3 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/20"
        @click.stop
      />
    </div>

    <div class="mt-2 max-h-80 overflow-y-auto rounded-lg border border-slate-200">
      <div v-for="group in visibleGroups" :key="group.key">
        <button
          type="button"
          class="flex w-full items-center gap-2 border-b border-slate-100 bg-slate-50 px-3 py-2.5 text-left text-sm font-medium text-slate-700 hover:bg-slate-100"
          @click="toggleCategory(group.key)"
        >
          <ChevronDown v-if="isCategoryOpen(group.key)" class="h-4 w-4 shrink-0 text-slate-400" />
          <ChevronRight v-else class="h-4 w-4 shrink-0 text-slate-400" />
          <span class="flex-1">{{ group.label }}</span>
          <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-500">{{ group.types.length }}</span>
        </button>

        <div v-show="isCategoryOpen(group.key)">
          <button
            v-for="type in group.types"
            :key="type.value"
            type="button"
            class="flex w-full items-start gap-3 border-b border-slate-50 px-3 py-2.5 text-left transition-colors hover:bg-teal-50"
            :class="modelValue === type.value ? 'bg-teal-50' : ''"
            @click="select(type.value)"
          >
            <component :is="iconMap[type.icon] || FileText" class="mt-0.5 h-4 w-4 shrink-0 text-slate-500" :class="modelValue === type.value ? 'text-teal-600' : ''" />
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-slate-900" :class="modelValue === type.value ? 'text-teal-800' : ''">
                  {{ type.label }}
                </span>
                <Check v-if="modelValue === type.value" class="h-3.5 w-3.5 shrink-0 text-teal-600" />
                <span v-if="type.isMemberAutofill" class="rounded-full bg-purple-50 px-1.5 py-0.5 text-[10px] font-medium text-purple-600">Auto</span>
              </div>
              <p class="mt-0.5 text-xs text-slate-500 line-clamp-2">{{ type.description }}</p>
            </div>
          </button>
        </div>
      </div>

      <div v-if="visibleGroups.length === 0" class="px-3 py-6 text-center text-sm text-slate-400">
        Tiada jenis maklumat ditemui untuk "{{ searchQuery }}"
      </div>
    </div>
  </div>
</template>
