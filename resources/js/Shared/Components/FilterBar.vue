<template>
    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="grid flex-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <slot />
            </div>
            <div v-if="$slots.actions" class="flex flex-col gap-3 [&>*]:w-full sm:flex-row sm:flex-wrap sm:items-center sm:[&>*]:w-auto xl:justify-end">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
