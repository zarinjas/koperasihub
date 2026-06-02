<template>
    <div class="rounded-xl border border-slate-200 bg-slate-50 px-5 py-4">
        <div class="flex flex-wrap items-end gap-3 [&>*]:min-w-[140px] sm:[&>*]:min-w-[180px] [&>*]:flex-1">
            <slot />
            <div
                v-if="$slots.actions"
                class="ml-auto flex flex-wrap items-end gap-2"
                style="flex: none; min-width: 0"
            >
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>