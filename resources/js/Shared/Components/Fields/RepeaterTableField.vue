<script setup>
const model = defineModel({ type: Array, default: () => [] });

const props = defineProps({
    field: { type: Object, required: true },
    error: { type: String, default: null },
});

const settings = () => {
    try {
        return typeof props.field.settings_json === 'string'
            ? JSON.parse(props.field.settings_json)
            : (props.field.settings_json ?? {});
    } catch {
        return {};
    }
};

const columns = () => settings().columns ?? [];
const maxRows = () => Number(settings().max_rows ?? 20);

const ensureRows = () => {
    const min = Number(settings().min_rows ?? 1);
    if (!Array.isArray(model.value)) model.value = [];
    while (model.value.length < min) addRow();
};

const addRow = () => {
    if ((model.value?.length ?? 0) >= maxRows()) return;
    const row = {};
    for (const column of columns()) row[column.key] = '';
    model.value = [...(model.value ?? []), row];
};

const removeRow = (index) => {
    model.value = (model.value ?? []).filter((_, idx) => idx !== index);
};

ensureRows();
</script>

<template>
    <div class="col-span-full space-y-2">
        <label class="block text-sm font-medium text-slate-800">
            {{ field.label }}<span v-if="field.is_required" class="ml-0.5 text-red-500">*</span>
        </label>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full min-w-[640px] text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th v-for="column in columns()" :key="column.key" class="px-3 py-2">
                            {{ column.label }}<span v-if="column.required" class="text-red-500">*</span>
                        </th>
                        <th class="w-20 px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, rowIndex) in model" :key="rowIndex" class="border-t border-slate-100">
                        <td v-for="column in columns()" :key="column.key" class="px-3 py-2">
                            <input
                                v-model="row[column.key]"
                                :type="column.type === 'currency' || column.type === 'number' ? 'number' : column.type === 'date' ? 'date' : 'text'"
                                :step="column.type === 'currency' ? '0.01' : undefined"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                            />
                        </td>
                        <td class="px-3 py-2 text-right">
                            <button type="button" class="text-xs font-medium text-red-600 hover:underline" @click="removeRow(rowIndex)">
                                Buang
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between">
            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
            <button type="button" class="ml-auto text-sm font-medium text-teal-700 hover:underline" :disabled="model.length >= maxRows()" @click="addRow">
                Tambah Baris
            </button>
        </div>
    </div>
</template>
