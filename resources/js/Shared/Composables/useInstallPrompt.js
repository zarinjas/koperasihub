import { ref, computed, onMounted, onUnmounted } from 'vue'

const STORAGE_KEY = 'pwa_install_dismissed'

const deferredPrompt = ref(null)
const isStandalone = ref(false)
const isIOS = ref(false)
const dismissed = ref(false)
const installed = ref(false)
const eventFired = ref(false)
const loading = ref(false)

function safeLocalRemove(key) {
    try { localStorage.removeItem(key) } catch { /* noop */ }
}

function onBeforeInstallPrompt(e) {
    e.preventDefault()
    deferredPrompt.value = e
    eventFired.value = true
}

function onAppInstalled() {
    installed.value = true
    deferredPrompt.value = null
}

if (typeof window !== 'undefined') {
    window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt)
    window.addEventListener('appinstalled', onAppInstalled)
}

export function useInstallPrompt() {
    let mediaQuery = null

    function checkStandalone() {
        isStandalone.value =
            window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone === true
    }

    function checkIOS() {
        isIOS.value = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream
    }

    function checkDismissed() {
        safeLocalRemove(STORAGE_KEY)
        dismissed.value = false
    }

    function onDisplayModeChange() {
        checkStandalone()
    }

    onMounted(() => {
        checkStandalone()
        checkIOS()
        checkDismissed()

        mediaQuery = window.matchMedia('(display-mode: standalone)')
        mediaQuery.addEventListener('change', onDisplayModeChange)
    })

    onUnmounted(() => {
        if (mediaQuery) {
            mediaQuery.removeEventListener('change', onDisplayModeChange)
        }
    })

    const show = computed(() => {
        if (isStandalone.value) return false
        if (dismissed.value) return false
        if (installed.value) return false
        if (isIOS.value) return true
        return eventFired.value
    })

    async function install() {
        const prompt = deferredPrompt.value
        if (!prompt) return

        loading.value = true
        prompt.prompt()
        const result = await prompt.userChoice
        loading.value = false
        deferredPrompt.value = null

        if (result.outcome === 'accepted') {
            installed.value = true
        }
    }

    function dismiss() {
        dismissed.value = true
    }

    function reset() {
        dismissed.value = false
        safeLocalRemove(STORAGE_KEY)
    }

    return {
        show,
        isIOS,
        isStandalone,
        installed,
        loading,
        install,
        dismiss,
        reset,
    }
}
