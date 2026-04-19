import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
    history: createWebHistory(),
    scrollBehavior: () => ({ top: 0 }),
    routes: [

        // ============================================================
        // AUTH (Public)
        // ============================================================
        {
            path: '/login',
            name: 'Login',
            component: () => import('@/views/Auth/Login.vue'),
            meta: { public: true, title: 'Login' },
        },

        // ============================================================
        // APLIKASI UTAMA (Membutuhkan autentikasi)
        // ============================================================
        {
            path: '/',
            component: () => import('@/components/layout/AdminLayout.vue'),
            meta: { requiresAuth: true },
            children: [

                // Dashboard
                {
                    path: '',
                    name: 'Dashboard',
                    component: () => import('@/views/Dashboard/Index.vue'),
                    meta: {
                        title: 'Dashboard Monitoring',
                        permission: 'dashboard.view',
                    },
                },
                {
                    path: 'dashboard/informasi-tebu',
                    name: 'DashboardPenerimaanTebu',
                    component: () => import('@/views/Dashboard/InformasiTebu.vue'),
                    meta: {
                        title: 'Dashboard Informasi Tebu',
                        permission: 'dashboard.view',
                    },
                },
                {
                    path: 'dashboard/penerimaan-tebu',
                    redirect: { name: 'DashboardPenerimaanTebu' },
                },
                {
                    path: 'dashboard/monitoring-pabrik',
                    name: 'DashboardMonitoringPabrik',
                    component: () => import('@/views/Dashboard/MonitoringPabrik.vue'),
                    meta: {
                        title: 'Dashboard Monitoring Pabrik',
                        permission: 'dashboard.view',
                    },
                },
                {
                    path: 'dashboard/pengawasan-qa',
                    name: 'DashboardPengawasanQa',
                    component: () => import('@/views/Dashboard/PengawasanQA.vue'),
                    meta: {
                        title: 'Dashboard Pengawasan QA',
                        permission: 'lab_qa.view',
                    },
                },

                // Peta Kebun
                {
                    path: 'peta-kebun',
                    name: 'PetaKebun',
                    component: () => import('@/views/PetaKebun/Index.vue'),
                    meta: {
                        title: 'Peta Kebun',
                        permission: 'peta_kebun.view',
                    },
                },

                // Data Tanaman
                {
                    path: 'tanaman',
                    name: 'Tanaman',
                    component: () => import('@/views/Tanaman/Index.vue'),
                    meta: {
                        title: 'Data Tanaman',
                        permission: 'tanaman.view',
                    },
                },

                // Lab QA — Pabrik Gula
                {
                    path: 'lab-qa',
                    name: 'LabQa',
                    component: () => import('@/views/LabQa/Index.vue'),
                    meta: {
                        title: 'Lab QA — Pabrik Gula',
                        permission: 'lab_qa.view',
                    },
                },

                // Lab QA — Pabrik Alkohol
                {
                    path: 'lab-qa/alkohol',
                    name: 'LabQaAlkohol',
                    component: () => import('@/views/LabQaAlkohol/Index.vue'),
                    meta: {
                        title: 'Lab QA — Pabrik Alkohol',
                        permission: 'lab_qa.view',
                    },
                },

                // Penerimaan Tebu (nested)
                {
                    path: 'penerimaan',
                    name: 'Penerimaan',
                    component: () => import('@/views/Penerimaan/Index.vue'),
                    meta: { title: 'Penerimaan Tebu', permission: 'penerimaan.view' },
                    children: [
                        {
                            path: '',
                            redirect: { name: 'PenerimaanManajemenSpa' },
                        },
                        {
                            path: 'manajemen-spa',
                            name: 'PenerimaanManajemenSpa',
                            component: () => import('@/views/Penerimaan/ManajemenSPA/Index.vue'),
                            meta: {
                                title: 'Manajemen SPA',
                                permission: 'penerimaan.view',
                            },
                        },
                        {
                            path: 'monitoring-antrian',
                            name: 'PenerimaanMonitoringAntrian',
                            component: () => import('@/views/Penerimaan/MonitoringAntrian/Index.vue'),
                            meta: {
                                title: 'Monitoring Antrian',
                                permission: 'penerimaan.view',
                            },
                        },
                        {
                            path: 'data-pemasukan',
                            name: 'PenerimaanDataPemasukan',
                            component: () => import('@/views/Penerimaan/DataPemasukan/Index.vue'),
                            meta: {
                                title: 'Data Pemasukan',
                                permission: 'penerimaan.view',
                            },
                        },
                    ],
                },

                // Operasional Pabrik
                {
                    path: 'operasional/data-digiling',
                    name: 'OperasionalDataDigiling',
                    component: () => import('@/views/Operasional/DataDigiling.vue'),
                    meta: {
                        title: 'Data Digiling',
                        permission: 'operasional.view',
                    },
                },

                // Manajemen User (Admin only)
                {
                    path: 'admin/users',
                    name: 'UserList',
                    component: () => import('@/views/Admin/Users/Index.vue'),
                    meta: {
                        title: 'Manajemen User',
                        permission: 'user.view',
                    },
                },
                {
                    path: 'admin/users/create',
                    name: 'UserCreate',
                    component: () => import('@/views/Admin/Users/CreateEdit.vue'),
                    meta: {
                        title: 'Tambah User',
                        permission: 'user.create',
                    },
                },
                {
                    path: 'admin/users/:id/edit',
                    name: 'UserEdit',
                    component: () => import('@/views/Admin/Users/CreateEdit.vue'),
                    meta: {
                        title: 'Edit User',
                        permission: 'user.update',
                    },
                },

                // Manajemen ACL / Role Permission
                {
                    path: 'admin/acl',
                    name: 'AclManagement',
                    component: () => import('@/views/Admin/Acl/Index.vue'),
                    meta: {
                        title: 'Manajemen ACL',
                        permission: 'role.view',
                    },
                },

                // Profile
                {
                    path: 'profile',
                    name: 'Profile',
                    component: () => import('@/views/Profile/Index.vue'),
                    meta: { title: 'Profil Saya' },
                },

            ],
        },

        // ============================================================
        // ERRORS
        // ============================================================
        {
            path: '/403',
            name: 'Forbidden',
            component: () => import('@/views/Errors/Forbidden.vue'),
            meta: { public: true, title: 'Akses Ditolak' },
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'NotFound',
            component: () => import('@/views/Errors/NotFound.vue'),
            meta: { public: true, title: 'Halaman Tidak Ditemukan' },
        },
    ],
})

// ================================================================
// NAVIGATION GUARD — ABAC
// ================================================================
router.beforeEach(async (to) => {
    const authStore = useAuthStore()

    // Set page title
    document.title = to.meta.title
        ? `${to.meta.title} — PG Madukismo`
        : 'Dashboard Monitoring PG Madukismo'

    // Route publik (login, error pages)
    if (to.meta.public) {
        // Redirect ke halaman default user jika sudah login dan buka halaman login
        if (to.name === 'Login' && authStore.isAuthenticated) {
            return authStore.getDefaultRoute()
        }
        return true
    }

    // Belum login → redirect ke login
    if (!authStore.isAuthenticated) {
        // Coba fetch user dari token yang tersimpan
        if (authStore.token) {
            await authStore.fetchMe()
            if (!authStore.isAuthenticated) {
                return { name: 'Login', query: { redirect: to.fullPath } }
            }
        } else {
            return { name: 'Login', query: { redirect: to.fullPath } }
        }
    }

    // Cek permission ABAC (jika route butuh permission tertentu)
    if (to.meta.permission && !authStore.can(to.meta.permission as string)) {
        return { name: 'Forbidden' }
    }

    return true
})

export default router
