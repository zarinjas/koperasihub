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

    router.post(`/${prefix}/notifications/${id}/read`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            if (url && url !== '#') {
                window.location.href = url;
            }
        },
    });
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

        <DropdownMenuContent align="end" class="w-80">
            <DropdownMenuLabel class="flex items-center justify-between">
                <span>Notifikasi</span>
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

            <DropdownMenuSeparator />

            <div v-if="recent.length === 0" class="px-3 py-6 text-center text-sm text-slate-500">
                Tiada notifikasi baharu.
            </div>

            <div v-for="n in recent" :key="n.id" class="group">
                <DropdownMenuItem
                    class="cursor-pointer py-3"
                    @click="markAsRead(n.id, n.url)"
                >
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <Megaphone class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ n.title }}</p>
                            <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ n.summary }}</p>
                            <p class="mt-1 text-[10px] text-slate-400">{{ n.created_at }}</p>
                        </div>
                    </div>
                </DropdownMenuItem>
                <DropdownMenuSeparator v-if="n !== recent[recent.length - 1]" />
            </div>

            <DropdownMenuSeparator />

            <Link
                :href="allUrl"
                class="flex items-center justify-center gap-1 px-3 py-2 text-xs font-medium text-teal-700 hover:bg-slate-50"
                @click="open = false"
            >
                Lihat Semua
                <ChevronRight class="h-3 w-3" />
            </Link>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
