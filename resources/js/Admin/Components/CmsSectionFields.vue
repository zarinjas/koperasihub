<script setup>
import TextInput from '@/Shared/Components/Form/TextInput.vue';
import TextareaInput from '@/Shared/Components/Form/TextareaInput.vue';
import SelectInput from '@/Shared/Components/Form/SelectInput.vue';
import ToggleSwitch from '@/Shared/Components/Form/ToggleSwitch.vue';
import { Button } from '@/Shared/Components/ui/button';

const props = defineProps({
    fields: {
        type: Array,
        required: true,
    },
    model: {
        type: Object,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    prefix: {
        type: String,
        required: true,
    },
});

const normaliseOptions = (options = []) => options.map((option) => (
    typeof option === 'object'
        ? option
        : { value: option, label: String(option) }
));

const errorFor = (key) => props.errors[`${props.prefix}.${key}`] || '';

const setFieldValue = (field, value) => {
    props.model[field.key] = field.type === 'number'
        ? (value === '' ? null : Number(value))
        : value;
};

const addRepeaterItem = (field) => {
    const item = Object.fromEntries(field.fields.map((nestedField) => [nestedField.key, nestedField.type === 'toggle' ? false : '']));

    if (!Array.isArray(props.model[field.key])) {
        props.model[field.key] = [];
    }

    props.model[field.key].push(item);
};

const removeRepeaterItem = (field, index) => {
    props.model[field.key].splice(index, 1);
};
</script>

<template>
    <div class="grid gap-4">
        <template v-for="field in fields" :key="`${prefix}-${field.key}`">
            <TextInput
                v-if="['text', 'url', 'email', 'number'].includes(field.type)"
                :id="`${prefix}-${field.key}`"
                :label="field.label"
                :model-value="model[field.key]"
                :type="field.type"
                :error="errorFor(field.key)"
                @update:model-value="setFieldValue(field, $event)"
            />

            <TextareaInput
                v-else-if="field.type === 'textarea'"
                :id="`${prefix}-${field.key}`"
                :label="field.label"
                :model-value="model[field.key]"
                :help="field.help"
                :error="errorFor(field.key)"
                @update:model-value="setFieldValue(field, $event)"
            />

            <SelectInput
                v-else-if="field.type === 'select'"
                :id="`${prefix}-${field.key}`"
                :label="field.label"
                :model-value="model[field.key]"
                :options="normaliseOptions(field.options)"
                :error="errorFor(field.key)"
                @update:model-value="setFieldValue(field, $event)"
            />

            <ToggleSwitch
                v-else-if="field.type === 'toggle'"
                :id="`${prefix}-${field.key}`"
                :label="field.label"
                :description="field.help"
                :model-value="Boolean(model[field.key])"
                @update:model-value="setFieldValue(field, $event)"
            />

            <div v-else-if="field.type === 'repeater'" class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ field.label }}</h3>
                        <p v-if="field.max_items" class="text-xs leading-5 text-slate-500">
                            Maksimum {{ field.max_items }} item.
                        </p>
                    </div>
                    <Button type="button" variant="outline" @click="addRepeaterItem(field)">Tambah Item</Button>
                </div>

                <div v-if="errors[`${prefix}.${field.key}`]" class="text-sm text-red-700">
                    {{ errors[`${prefix}.${field.key}`] }}
                </div>

                <div v-if="!Array.isArray(model[field.key]) || model[field.key].length === 0" class="rounded-2xl border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-500">
                    Belum ada item ditambah.
                </div>

                <div v-for="(item, index) in model[field.key] || []" :key="`${field.key}-${index}`" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900">Item {{ index + 1 }}</p>
                        <Button type="button" variant="ghost" class="text-red-600 hover:bg-red-50 hover:text-red-700" @click="removeRepeaterItem(field, index)">
                            Padam
                        </Button>
                    </div>

                    <div class="grid gap-4">
                        <template v-for="nestedField in field.fields" :key="`${field.key}-${index}-${nestedField.key}`">
                            <TextInput
                                v-if="['text', 'url', 'email', 'number'].includes(nestedField.type)"
                                :id="`${prefix}-${field.key}-${index}-${nestedField.key}`"
                                :label="nestedField.label"
                                :model-value="item[nestedField.key]"
                                :type="nestedField.type"
                                :error="errors[`${prefix}.${field.key}.${index}.${nestedField.key}`] || ''"
                                @update:model-value="item[nestedField.key] = nestedField.type === 'number' ? ($event === '' ? null : Number($event)) : $event"
                            />

                            <TextareaInput
                                v-else
                                :id="`${prefix}-${field.key}-${index}-${nestedField.key}`"
                                :label="nestedField.label"
                                :model-value="item[nestedField.key]"
                                :error="errors[`${prefix}.${field.key}.${index}.${nestedField.key}`] || ''"
                                @update:model-value="item[nestedField.key] = $event"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
