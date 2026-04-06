// ================================================================
// useFullscreen.ts — Fullscreen API composable
// ================================================================
import { ref, onMounted, onUnmounted } from 'vue'

export function useFullscreen() {
    const isFullscreen = ref(false)

    const updateState = () => {
        isFullscreen.value = !!document.fullscreenElement
    }

    const toggle = async () => {
        try {
            if (!document.fullscreenElement) {
                await document.documentElement.requestFullscreen()
            } else {
                await document.exitFullscreen()
            }
        } catch (err) {
            console.warn('Fullscreen tidak didukung:', err)
        }
    }

    const enter = async () => {
        if (!document.fullscreenElement) {
            await document.documentElement.requestFullscreen().catch(() => {})
        }
    }

    const exit = async () => {
        if (document.fullscreenElement) {
            await document.exitFullscreen().catch(() => {})
        }
    }

    onMounted(() => document.addEventListener('fullscreenchange', updateState))
    onUnmounted(() => document.removeEventListener('fullscreenchange', updateState))

    return { isFullscreen, toggle, enter, exit }
}
