import { ref, provide, inject, onMounted, watch } from 'vue'
import type { Ref } from 'vue'

type Theme = 'light' | 'dark'
const THEME_KEY = 'madukismo_theme'
const ThemeSymbol = Symbol('theme')

interface ThemeContext {
    theme: Ref<Theme>
    toggleTheme: () => void
    setTheme: (t: Theme) => void
    isDark: Ref<boolean>
}

export function useThemeProvider(): ThemeContext {
    const theme       = ref<Theme>('light')
    const isDark      = ref(false)
    const initialized = ref(false)

    onMounted(() => {
        const saved = localStorage.getItem(THEME_KEY) as Theme | null
        theme.value  = saved ?? 'light'
        initialized.value = true
    })

    watch(theme, (newTheme) => {
        if (!initialized.value) return
        isDark.value = newTheme === 'dark'
        localStorage.setItem(THEME_KEY, newTheme)
        if (newTheme === 'dark') {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    }, { immediate: true })

    const toggleTheme = () => {
        theme.value = theme.value === 'light' ? 'dark' : 'light'
    }

    const setTheme = (t: Theme) => {
        theme.value = t
    }

    const ctx: ThemeContext = { theme, toggleTheme, setTheme, isDark }
    provide(ThemeSymbol, ctx)
    return ctx
}

export function useTheme(): ThemeContext {
    const ctx = inject<ThemeContext>(ThemeSymbol)
    if (!ctx) throw new Error('useTheme harus berada dalam ThemeProvider')
    return ctx
}
