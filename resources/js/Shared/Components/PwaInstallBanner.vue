<script setup>
import { usePage } from '@inertiajs/vue3'
import { useInstallPrompt } from '@/Shared/Composables/useInstallPrompt'
import { computed } from 'vue'

const { isIOS, isStandalone, installed, loading, install } = useInstallPrompt()
const cooperative = computed(() => usePage().props.appSettings?.cooperative ?? {})

const visible = computed(() => !isStandalone.value && !installed.value && !isIOS.value)
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="visible"
            class="fixed bottom-0 left-0 right-0 z-50 border-t border-teal-200/60 bg-white/95 shadow-2xl shadow-teal-900/10 backdrop-blur-xl"
            style="padding-bottom: env(safe-area-inset-bottom, 0px)"
        >
            <div class="mx-auto flex max-w-7xl items-center justify-center md:justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                        <img
                            v-if="cooperative.logo_url"
                            :src="cooperative.logo_url"
                            :alt="cooperative.name"
                            class="h-7 w-7 object-contain"
                        />
                        <span
                            v-else
                            class="text-xs font-bold text-teal-700"
                        >{{ (cooperative.name || 'K')[0] }}</span>
                    </span>
                    <p class="text-sm font-medium text-slate-800 truncate">
                        Cuba guna dalam aplikasi telefon untuk pengalaman lebih pantas.
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-teal-600 to-emerald-600 px-4 py-2 text-xs font-medium text-white shadow-sm transition hover:brightness-105 disabled:opacity-50"
                        :disabled="loading"
                        @click="install"
                    >
                        {{ loading ? 'Memasang...' : 'Pasang Aplikasi' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
