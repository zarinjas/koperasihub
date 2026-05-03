<template>
    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid flex-1 gap-4 md:grid-cols-2">
                <slot />
            </div>
            <div v-if="$slots.actions" class="flex flex-wrap items-center gap-3">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
