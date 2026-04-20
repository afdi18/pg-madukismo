<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { RouterView, RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'
import { useFullscreen } from '@/composables/useFullscreen'
import {
  LayoutDashboardIcon, MapIcon, FlaskConicalIcon,
  UsersIcon, ChevronDownIcon, BellIcon, MoonIcon, SunIcon,
  MaximizeIcon, MinimizeIcon, LogOutIcon, UserIcon,
  MenuIcon, XIcon, ShieldCheckIcon, TruckIcon,
} from 'lucide-vue-next'

interface NavChild {
  label: string
  path: string
  permission?: string
}

interface NavItem {
  label: string
  icon: any
  permission?: string
  path?: string
  children?: NavChild[]
}

interface AdminItem {
  label: string
  icon: any
  path: string
  permission?: string
}

const authStore  = useAuthStore()
const router     = useRouter()
const { theme, toggleTheme } = useTheme()
const { isFullscreen, toggle: toggleFullscreen } = useFullscreen()

const sidebarOpen   = ref(true)
const sidebarMobile = ref(false)
const sidebarHover  = ref(false)
const userMenuOpen  = ref(false)
const notifOpen     = ref(false)
const expandedMenu  = ref<string | null>(null)

const isExpanded = computed(() => sidebarOpen.value || sidebarHover.value || sidebarMobile.value)
const isAdministratorByRole = computed(() => authStore.user?.roles?.some(role => role.name === 'Administrator') ?? false)

// ================================================================
// NAVIGATION ITEMS (dengan ABAC permission check)
// ================================================================
const navItems = computed<NavItem[]>(() => [
    {
        label: 'Dashboard',
        icon: LayoutDashboardIcon,
        permission: 'dashboard.view',
        children: [
          { label: 'Informasi Tebu', path: '/dashboard/informasi-tebu', permission: 'dashboard.view' },
          { label: 'Monitoring Pabrik', path: '/dashboard/monitoring-pabrik', permission: 'operasional.view' },
          { label: 'Angka Pengawasan QA', path: '/dashboard/pengawasan-qa', permission: 'lab_qa.view' },
        ],
    },
    {
      label: 'Penerimaan Tebu',
      icon: TruckIcon,
      permission: 'penerimaan.view',
      children: [
        { label: 'Manajemen SPA', path: '/penerimaan/manajemen-spa', permission: 'penerimaan.view' },
        { label: 'Monitoring Antrian', path: '/penerimaan/monitoring-antrian', permission: 'penerimaan.view' },
        { label: 'Data Pemasukan', path: '/penerimaan/data-pemasukan', permission: 'penerimaan.view' },
      ],
    },
    {
        label: 'Analisa QA',
        icon: FlaskConicalIcon,
        permission: 'lab_qa.view',
        children: [
            { label: 'Pabrik Gula',    path: '/lab-qa', permission: 'lab_qa.view' },
            { label: 'Pabrik Alkohol', path: '/lab-qa/alkohol', permission: 'lab_qa.view' },
        ],
    },
   {
        label: 'Peta Kebun',
        icon: MapIcon,
        path: '/peta-kebun',
        permission: 'peta_kebun.view',
    },
])

const adminItems = computed<AdminItem[]>(() => [
    {
        label: 'Manajemen User',
        icon: UsersIcon,
        path: '/admin/users',
        permission: 'user.view',
    },
  {
    label: 'Manajemen ACL',
    icon: ShieldCheckIcon,
    path: '/admin/acl',
    permission: 'role.view',
  },
])

function canAccess(permission?: string): boolean {
  if (isAdministratorByRole.value) return true
    if (!permission) return true
    return authStore.can(permission)
}

function hasChildren(item: NavItem): boolean {
  return getVisibleChildren(item).length > 0
}

function getVisibleChildren(item: NavItem): NavChild[] {
  const children = item.children ?? []
  return children.filter((child) => canAccess(child.permission))
}

function toggleSubMenu(label: string) {
  if (!isExpanded.value && !sidebarMobile.value) {
    sidebarOpen.value = true
  }
    expandedMenu.value = expandedMenu.value === label ? null : label
}

function isSubMenuActive(children?: NavChild[]): boolean {
    if (!children) return false
    return children.some((child) => isChildActive(child.path, children))
}

/**
 * A child is active when currentPath matches its path or a sub-path,
 * AND no sibling has a longer (more specific) match — prevents /lab-qa
 * from being active when /lab-qa/alkohol is the current route.
 */
function isChildActive(path: string, siblings?: NavChild[]): boolean {
  const currentPath = router.currentRoute.value.path

  // Must at least match this path
  if (currentPath !== path && !currentPath.startsWith(`${path}/`)) return false

  // Yield to any sibling with a longer, more-specific match
  if (siblings) {
    for (const sib of siblings) {
      if (sib.path === path) continue
      if (
        sib.path.length > path.length &&
        (currentPath === sib.path || currentPath.startsWith(`${sib.path}/`))
      ) return false
    }
  }

  return true
}

// Auto-expand menu when active child route is detected
watch(() => router.currentRoute.value.path, () => {
    for (const item of navItems.value) {
      if (item.children && isSubMenuActive(item.children)) {
            expandedMenu.value = item.label
            return
        }
    }
}, { immediate: true })

async function handleLogout() {
    userMenuOpen.value = false
    await authStore.logout()
    router.push('/login')
}
</script>

<template>
  <div class="flex h-screen bg-gray-100 dark:bg-gray-950 overflow-hidden">

    <!-- ============================================================
         MOBILE BACKDROP
    ============================================================ -->
    <div
      v-if="sidebarMobile"
      class="fixed inset-0 z-40 bg-black/50 lg:hidden"
      @click="sidebarMobile = false"
    />

    <!-- ============================================================
         SIDEBAR
    ============================================================ -->
    <aside
      :class="[
        'fixed top-0 left-0 h-full z-50 flex flex-col',
        'bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800',
        'transition-all duration-300 ease-in-out',
        isExpanded ? 'w-[260px]' : 'w-[72px]',
        sidebarMobile ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
      ]"
      @mouseenter="!sidebarOpen && (sidebarHover = true)"
      @mouseleave="sidebarHover = false"
    >
      <!-- Logo area -->
      <div class="flex items-center h-16 px-4 border-b border-gray-200 dark:border-gray-800 shrink-0">
        <RouterLink to="/" class="flex items-center gap-3 overflow-hidden">
          <img
            src="/images/logo/madubaru.png"
            alt="Logo"
            class="w-9 h-9 object-contain shrink-0"
          />
          <transition name="fade">
            <div v-if="isExpanded" class="overflow-hidden">
              <p class="text-sm font-bold text-gray-800 dark:text-white leading-tight whitespace-nowrap">
                PG Madukismo
              </p>
              <p class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">
                Dashboard Monitoring
              </p>
            </div>
          </transition>
        </RouterLink>
      </div>

      <!-- Nav -->
      <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 scrollbar-thin">

        <!-- Menu utama -->
        <div>
          <p v-if="isExpanded" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-600">
            Menu Utama
          </p>
          <template v-for="item in navItems" :key="item.label">
            <!-- Item with children (sub-menu) -->
            <div v-if="hasChildren(item)">
              <button
                @click="toggleSubMenu(item.label)"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                       text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800
                       transition-colors group"
                :class="isSubMenuActive(getVisibleChildren(item)) ? '!bg-yellow-50 !text-yellow-600 dark:!bg-yellow-500/10 dark:!text-yellow-400' : ''"
              >
                <component
                  :is="item.icon"
                  class="w-5 h-5 shrink-0 transition-colors"
                  :class="isSubMenuActive(getVisibleChildren(item)) ? 'text-yellow-500' : 'text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300'"
                />
                <span v-if="isExpanded" class="flex-1 truncate text-left">{{ item.label }}</span>
                <ChevronDownIcon
                  v-if="isExpanded"
                  class="w-4 h-4 shrink-0 transition-transform duration-200"
                  :class="expandedMenu === item.label ? 'rotate-180' : ''"
                />
              </button>
              <!-- Sub-menu items -->
              <div
                v-if="isExpanded && expandedMenu === item.label"
                class="mt-0.5 ml-4 pl-4 border-l-2 border-yellow-200 dark:border-yellow-800 space-y-0.5"
              >
                <RouterLink
                  v-for="child in getVisibleChildren(item)"
                  :key="child.path"
                  :to="child.path"
                  class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm
                         text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800
                         transition-colors"
                  :class="isChildActive(child.path, getVisibleChildren(item)) ? '!bg-yellow-50 !text-yellow-600 dark:!bg-yellow-500/10 dark:!text-yellow-400 font-medium' : ''"
                  active-class=""
                >
                  <span class="w-1.5 h-1.5 rounded-full shrink-0"
                    :class="isChildActive(child.path, getVisibleChildren(item)) ? 'bg-yellow-500' : 'bg-gray-300 dark:bg-gray-600'"
                  />
                  {{ child.label }}
                </RouterLink>
              </div>
            </div>

            <!-- Regular item (no children) -->
            <RouterLink
              v-else-if="canAccess(item.permission) && item.path"
              :to="item.path"
              class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                     text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800
                     transition-colors group"
              :class="{'!bg-yellow-50 !text-yellow-600 dark:!bg-yellow-500/10 dark:!text-yellow-400': $route.path === item.path}"
              active-class=""
            >
              <component
                :is="item.icon"
                class="w-5 h-5 shrink-0 transition-colors"
                :class="$route.path === item.path ? 'text-yellow-500' : 'text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300'"
              />
              <span v-if="isExpanded" class="truncate">{{ item.label }}</span>
            </RouterLink>
          </template>
        </div>

        <!-- Menu admin -->
        <div v-if="isAdministratorByRole || authStore.isAdmin || authStore.canAny(['user.view', 'role.view'])" class="pt-4">
          <p v-if="isExpanded" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-600">
            Administrasi
          </p>
          <template v-for="item in adminItems" :key="item.path">
            <RouterLink
              v-if="canAccess(item.permission)"
              :to="item.path"
              class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                     text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800
                     transition-colors group"
              :class="{'!bg-yellow-50 !text-yellow-600 dark:!bg-yellow-500/10 dark:!text-yellow-400': $route.path.startsWith(item.path)}"
            >
              <component
                :is="item.icon"
                class="w-5 h-5 shrink-0"
                :class="$route.path.startsWith(item.path) ? 'text-yellow-500' : 'text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300'"
              />
              <span v-if="isExpanded" class="truncate">{{ item.label }}</span>
            </RouterLink>
          </template>
        </div>

      </nav>

      <!-- Sidebar collapse toggle (desktop) -->
      <div class="p-3 border-t border-gray-200 dark:border-gray-800 shrink-0 hidden lg:block">
        <button
          @click="sidebarOpen = !sidebarOpen"
          class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-xs
                 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        >
          <MenuIcon class="w-4 h-4 shrink-0" />
          <span v-if="isExpanded">Tutup sidebar</span>
        </button>
      </div>
    </aside>

    <!-- ============================================================
         MAIN AREA
    ============================================================ -->
    <div
      :class="[
        'flex-1 flex flex-col min-w-0 transition-all duration-300',
        sidebarOpen ? 'lg:ml-[260px]' : 'lg:ml-[72px]',
      ]"
    >
      <!-- HEADER -->
      <header class="sticky top-0 z-[120] h-16 flex items-center gap-3 px-4 lg:px-6
                     bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shrink-0">

        <!-- Mobile menu button -->
        <button
          @click="sidebarMobile = !sidebarMobile"
          class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
        >
          <MenuIcon class="w-5 h-5" />
        </button>

        <!-- Page title (spacer) -->
        <div class="flex-1" />

        <!-- Toolbar kanan -->
        <div class="flex items-center gap-1 sm:gap-2">

          <!-- Fullscreen -->
          <button
            @click="toggleFullscreen"
            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors"
            :title="isFullscreen ? 'Keluar Fullscreen' : 'Fullscreen'"
          >
            <MinimizeIcon v-if="isFullscreen" class="w-5 h-5" />
            <MaximizeIcon v-else class="w-5 h-5" />
          </button>

          <!-- Dark/Light toggle -->
          <button
            @click="toggleTheme"
            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors"
            :title="theme === 'dark' ? 'Mode Terang' : 'Mode Gelap'"
          >
            <SunIcon v-if="theme === 'dark'" class="w-5 h-5" />
            <MoonIcon v-else class="w-5 h-5" />
          </button>

          <!-- Notifikasi -->
          <div class="relative z-[130]">
            <button
              @click="notifOpen = !notifOpen"
              class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors"
            >
              <BellIcon class="w-5 h-5" />
              <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full" />
            </button>
          </div>

          <!-- Divider -->
          <div class="w-px h-6 bg-gray-200 dark:bg-gray-700 mx-1" />

          <!-- User menu -->
          <div class="relative">
            <button
              @click="userMenuOpen = !userMenuOpen"
              class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            >
              <div class="w-8 h-8 rounded-full bg-yellow-500 flex items-center justify-center text-white text-sm font-bold shrink-0">
                {{ authStore.user?.name.charAt(0).toUpperCase() }}
              </div>
              <div class="hidden sm:block text-left">
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-200 leading-tight">
                  {{ authStore.user?.name }}
                </p>
                <p class="text-[10px] text-gray-400 dark:text-gray-500 leading-tight">
                  {{ authStore.user?.roles[0]?.display_name ?? 'User' }}
                </p>
              </div>
              <ChevronDownIcon class="w-4 h-4 text-gray-400 hidden sm:block" />
            </button>

            <!-- Dropdown user -->
            <div
              v-if="userMenuOpen"
              class="absolute right-0 top-full mt-2 w-52 bg-white dark:bg-gray-800 border border-gray-200
                dark:border-gray-700 rounded-xl shadow-lg py-1 z-[140]"
              @click.away="userMenuOpen = false"
            >
              <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ authStore.user?.name }}</p>
                <p class="text-xs text-gray-400">@{{ authStore.user?.username }}</p>
              </div>
              <RouterLink
                to="/profile"
                @click="userMenuOpen = false"
                class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400
                       hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                <UserIcon class="w-4 h-4" /> Profil Saya
              </RouterLink>
              <button
                @click="handleLogout"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-500
                       hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
              >
                <LogOutIcon class="w-4 h-4" /> Keluar
              </button>
            </div>
          </div>

        </div>
      </header>

      <!-- PAGE CONTENT -->
      <main class="flex-1 overflow-y-auto p-4 lg:p-6">
        <RouterView />
      </main>

    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.scrollbar-thin::-webkit-scrollbar { width: 4px; }
.scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
.scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(156,163,175,0.4); border-radius: 2px; }
</style>
