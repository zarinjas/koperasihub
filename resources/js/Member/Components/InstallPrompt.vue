<script setup>
import { useInstallPrompt } from '@/Shared/Composables/useInstallPrompt'
import { Smartphone, X, Download } from 'lucide-vue-next'

const { show, isIOS, loading, install, dismiss } = useInstallPrompt()
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-full opacity-0"
        >
            <div
                v-if="show"
                class="fixed bottom-0 left-0 right-0 z-50"
                style="padding-bottom: env(safe-area-inset-bottom, 0px)"
            >
                <div class="mx-4 mb-4 overflow-hidden rounded-2xl border border-teal-200/60 bg-white shadow-2xl shadow-teal-900/10 backdrop-blur-xl">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-teal-400 to-emerald-500" />

                    <div class="relative p-4">
                        <button
                            type="button"
                            class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            @click="dismiss"
                        >
                            <X class="h-4 w-4" />
                        </button>

                        <div class="flex items-start gap-3 pr-6">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 to-emerald-600 text-white shadow-sm">
                                <Smartphone class="h-6 w-6" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-900">
                                    Pasang Portal Ahli
                                </p>

                                <template v-if="isIOS">
                                    <p class="mt-1 text-xs leading-relaxed text-slate-500">
                                        Tekan butang
                                        <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-600">
                                            <Download class="h-3 w-3" /> Share
                                        </span>
                                        dan pilih
                                        <span class="font-medium text-slate-700">Add to Home Screen</span>
                                        untuk akses pantas.
                                    </p>
                                    <button
                                        type="button"
                                        class="mt-2.5 rounded-lg bg-teal-600 px-4 py-2 text-xs font-medium text-white transition hover:bg-teal-700"
                                        @click="dismiss"
                                    >
                                        Nanti, lain kali
                                    </button>
                                </template>

                                <template v-else>
                                    <p class="mt-1 text-xs leading-relaxed text-slate-500">
                                        Pasang aplikasi ini pada skrin utama untuk akses lebih pantas dan pengalaman seperti aplikasi asli.
                                    </p>
                                    <div class="mt-2.5 flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-teal-600 to-emerald-600 px-4 py-2 text-xs font-medium text-white shadow-sm transition hover:brightness-105 disabled:opacity-50"
                                            :disabled="loading"
                                            @click="install"
                                        >
                                            <Download class="h-3.5 w-3.5" />
                                            {{ loading ? 'Memasang...' : 'Pasang' }}
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-lg px-3 py-2 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                                            @click="dismiss"
                                        >
                                            Nanti
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
