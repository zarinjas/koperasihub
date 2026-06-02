<script setup>
import { Link } from '@inertiajs/vue3';
import { MoreHorizontal } from 'lucide-vue-next';
import { Button } from '@/Shared/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Shared/Components/ui/dropdown-menu';

defineProps({
    actions: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div class="flex min-h-10 items-center justify-center">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    type="button"
                    variant="outline"
                    class="h-9 w-9 rounded-lg p-0"
                    aria-label="Buka tindakan"
                >
                    <MoreHorizontal class="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end" class="w-48 max-w-[90vw] border-none">
                <template v-for="(action, index) in actions" :key="index">
                    <DropdownMenuSeparator
                        v-if="action.divider && action.condition !== false"
                    />
                    <DropdownMenuItem
                        v-if="!action.divider && action.condition !== false"
                        :as="action.onClick ? 'button' : (action.target ? 'a' : Link)"
                        :href="(!action.onClick) ? action.href : undefined"
                        :target="action.target ?? undefined"
                        rel="noopener noreferrer"
                        :variant="action.variant ?? 'default'"
                        :disabled="action.disabled"
                        class="cursor-pointer"
                        @select.prevent="action.onClick"
                    >
                        <component :is="action.icon" v-if="action.icon" class="mr-2 h-4 w-4" />
                        {{ action.label }}
                    </DropdownMenuItem>
                </template>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>