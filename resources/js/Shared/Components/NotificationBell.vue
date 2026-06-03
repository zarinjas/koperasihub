<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, CheckCheck, ChevronRight, Megaphone } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/Shared/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Shared/Components/ui/dropdown-menu';

const page = usePage();
const open = ref(false);

const notifications = computed(() => page.props.notifications);
const unreadCount = computed(() => notifications.value?.unread_count ?? 0);
const recent = computed(() => notifications.value?.recent ?? []);

const markAsRead = (id, url) => {
    const isAdmin = window.location.pathname.startsWith('/admin');
    const prefix = isAdmin ? 'admin' : 'member';
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch(`/${prefix}/notifications/${id}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
        },
    });

    if (url && url !== '#') {
        router.get(url);
    }
};

const markAllAsRead = () => {
    const isAdmin = window.location.pathname.startsWith('/admin');
    const prefix = isAdmin ? 'admin' : 'member';

    router.post(`/${prefix}/notifications/read-all`, {}, {
        preserveScroll: true,
    });
};

const allUrl = computed(() => {
    return window.location.pathname.startsWith('/admin')
        ? '/admin/notifications'
        : '/member/notifications';
});
</script>

<template>
    <DropdownMenu v-if="notifications" :open="open" @update:open="open = $event">
        <DropdownMenuTrigger as-child>
            <Button type="button" variant="ghost" size="icon" class="relative">
                <Bell class="h-5 w-5" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-none text-white"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-80 max-w-[90vw] border-0 p-0 shadow-xl">
            <div class="rounded-xl bg-white shadow-lg ring-1 ring-slate-200/60">
                <DropdownMenuLabel class="flex items-center justify-between px-4 pt-3.5 pb-2">
                    <span class="text-sm font-semibold text-slate-950">Notifikasi</span>
                    <Button
                        v-if="unreadCount > 0"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-auto text-xs font-normal text-teal-700"
                        @click="markAllAsRead"
                    >
                        <CheckCheck class="mr-1 h-3 w-3" />
                        Tandakan semua dibaca
                    </Button>
                </DropdownMenuLabel>

                <div v-if="recent.length === 0" class="px-4 py-6 text-center text-sm text-slate-500">
                    Tiada notifikasi baharu.
                </div>

                <div v-for="(n, idx) in recent" :key="n.id">
                    <DropdownMenuItem
                        class="cursor-pointer px-4 py-3 focus:bg-slate-50"
                        @click="markAsRead(n.id, n.url)"
                    >
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                                <Megaphone class="h-3.5 w-3.5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-950">{{ n.title }}</p>
                                <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ n.summary }}</p>
                                <p class="mt-1 text-[10px] text-slate-400">{{ n.created_at }}</p>
                            </div>
                        </div>
                    </DropdownMenuItem>
                    <div v-if="idx < recent.length - 1" class="mx-4 h-px bg-slate-100" />
                </div>

                <Link
                    :href="allUrl"
                    class="flex items-center justify-center gap-1 rounded-b-xl px-4 py-2.5 text-xs font-medium text-teal-700 hover:bg-slate-50"
                    @click="open = false"
                >
                    Lihat Semua
                    <ChevronRight class="h-3 w-3" />
                </Link>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>