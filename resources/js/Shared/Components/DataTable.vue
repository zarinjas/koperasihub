<script setup>
defineProps({
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        required: true,
    },
});

const isActionColumn = (column) => column.key === 'actions';
</script>

<template>
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500"
                        >
                            {{ column.label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <tr v-for="row in rows" :key="row.id" class="align-top">
                        <td v-for="column in columns" :key="column.key" class="px-4 py-4 text-sm text-slate-700">
                            <slot :name="`cell-${column.key}`" :row="row">
                                {{ row[column.key] }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="grid gap-4 p-4 lg:hidden">
            <article
                v-for="row in rows"
                :key="row.id"
                class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm"
            >
                <div class="space-y-4">
                    <div
                        v-for="column in columns.filter((item) => !isActionColumn(item))"
                        :key="column.key"
                        class="space-y-1"
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                            {{ column.label }}
                        </p>
                        <div class="text-sm text-slate-700">
                            <slot :name="`cell-${column.key}`" :row="row">
                                {{ row[column.key] }}
                            </slot>
                        </div>
                    </div>

                    <div v-if="columns.some(isActionColumn)" class="border-t border-slate-200 pt-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                            Tindakan
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <slot :name="`cell-actions`" :row="row">
                                {{ row.actions }}
                            </slot>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </div>
</template>
