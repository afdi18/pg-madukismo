import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

interface DefaultRouteOption {
    permission?: string
    path: string
}

interface User {
    id: number
    username: string
    name: string
    email: string | null
    jabatan: string | null
    divisi: string | null
    area_kebun: string | null
    avatar_url: string
    is_active: boolean
    last_login_at: string | null
    roles: Array<{ name: string; display_name: string; color: string }>
}

interface AccessSummary {
    permissions: string[]
    roles: string[]
    attributes: Record<string, string>
    is_admin: boolean
}

export const useAuthStore = defineStore('auth', () => {
    const user          = ref<User | null>(null)
    const access        = ref<AccessSummary | null>(null)
    const token         = ref<string | null>(localStorage.getItem('madukismo_token'))
    const isLoading     = ref(false)
    const loginError    = ref<string | null>(null)

    const isAuthenticated = computed(() => !!user.value && !!token.value)
    const isAdmin         = computed(() => access.value?.is_admin ?? false)
    const userRoles       = computed(() => access.value?.roles ?? [])
    const userPermissions = computed(() => access.value?.permissions ?? [])

    // Set axios default header
    if (token.value) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
    }

    /**
     * Login dengan username + password.
     */
    async function login(username: string, password: string): Promise<void> {
        isLoading.value  = true
        loginError.value = null

        try {
            const { data } = await axios.post('/api/auth/login', { username, password })

            token.value  = data.token
            user.value   = data.user
            access.value = data.access

            localStorage.setItem('madukismo_token', data.token)
            axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`
        } catch (err: any) {
            loginError.value = err.response?.data?.errors?.username?.[0]
                ?? err.response?.data?.message
                ?? 'Terjadi kesalahan. Coba lagi.'
            throw err
        } finally {
            isLoading.value = false
        }
    }

    /**
     * Muat ulang data user dari server (setelah refresh halaman).
     */
    async function fetchMe(): Promise<void> {
        if (!token.value) return

        try {
            const { data } = await axios.get('/api/auth/me')
            user.value   = data.user
            access.value = data.access
        } catch {
            logout()
        }
    }

    /**
     * Logout.
     */
    async function logout(): Promise<void> {
        try {
            if (token.value) {
                await axios.post('/api/auth/logout')
            }
        } finally {
            user.value   = null
            access.value = null
            token.value  = null
            localStorage.removeItem('madukismo_token')
            delete axios.defaults.headers.common['Authorization']
        }
    }

    /**
     * Cek apakah user memiliki permission tertentu (client-side guard).
     */
    function can(permission: string): boolean {
        if (access.value?.is_admin) return true
        return access.value?.permissions.includes(permission) ?? false
    }

    /**
     * Cek apakah user memiliki role tertentu.
     */
    function hasRole(roleName: string): boolean {
        return access.value?.roles.includes(roleName) ?? false
    }

    /**
     * Cek apakah user memiliki salah satu permission dari daftar.
     */
    function canAny(permissions: string[]): boolean {
        return permissions.some(p => can(p))
    }

    /**
     * Ambil halaman default pertama yang bisa diakses user.
     */
    function getDefaultRoute(): string {
        const routeOptions: DefaultRouteOption[] = [
            { permission: 'dashboard.view', path: '/' },
            { permission: 'operasional.view', path: '/dashboard/monitoring-pabrik' },
            { permission: 'lab_qa.view', path: '/dashboard/pengawasan-qa' },
            { permission: 'penerimaan.view', path: '/penerimaan/manajemen-spa' },
            { permission: 'peta_kebun.view', path: '/peta-kebun' },
            { permission: 'lab_qa.view', path: '/lab-qa' },
            { path: '/profile' },
        ]

        return routeOptions.find(route => !route.permission || can(route.permission))?.path ?? '/403'
    }

    return {
        user, access, token, isLoading, loginError,
        isAuthenticated, isAdmin, userRoles, userPermissions,
        login, fetchMe, logout, can, hasRole, canAny, getDefaultRoute,
    }
})
