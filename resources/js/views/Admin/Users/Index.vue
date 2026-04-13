<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import {
  PlusIcon, SearchIcon, EditIcon, Trash2Icon,
  ShieldCheckIcon, UserCheckIcon, UserXIcon, RefreshCwIcon,
} from 'lucide-vue-next'

const router    = useRouter()
const authStore = useAuthStore()

const isLoading = ref(true)
const users     = ref<any[]>([])
const meta      = ref({ total: 0, per_page: 15, current_page: 1, last_page: 1 })
const searchQ   = ref('')
const statusFilter = ref('')
const roleFilter   = ref('')
const roles     = ref<any[]>([])
const deletingId = ref<number | null>(null)

// ================================================================
// LOAD
// ================================================================
async function loadUsers(page = 1) {
  isLoading.value = true
  try {
    const { data } = await axios.get('/api/users', {
      params: {
        search  : searchQ.value || undefined,
        status  : statusFilter.value || undefined,
        role    : roleFilter.value || undefined,
        per_page: meta.value.per_page,
        page,
      },
    })
    users.value = data.data
    meta.value  = data.meta
  } finally {
    isLoading.value = false
  }
}

async function loadRoles() {
  const { data } = await axios.get('/api/users/roles')
  roles.value = data.data
}

onMounted(() => { loadUsers(); loadRoles() })

// Debounce search
let searchT: ReturnType<typeof setTimeout>
watch(searchQ, () => { clearTimeout(searchT); searchT = setTimeout(() => loadUsers(1), 400) })
watch([statusFilter, roleFilter], () => loadUsers(1))

// ================================================================
// DELETE
// ================================================================
const showDeleteConfirm = ref(false)
const userToDelete = ref<any | null>(null)

function askDeleteUser(user: any) {
  userToDelete.value = user
  showDeleteConfirm.value = true
}

async function deleteUser(user: any) {
  // kept for backward compatibility
  userToDelete.value = user
  await deleteUserConfirmed()
}

async function deleteUserConfirmed() {
  if (!userToDelete.value) return
  deletingId.value = userToDelete.value.id
  try {
    await axios.delete(`/api/users/${userToDelete.value.id}`)
    await loadUsers(meta.value.current_page)
  } finally {
    deletingId.value = null
    userToDelete.value = null
    showDeleteConfirm.value = false
  }
}

// ================================================================
// HELPERS
// ================================================================
function roleBadgeClass(color: string): string {
  return {
    red   : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    purple: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    blue  : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    green : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    gray  : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
  }[color] ?? 'bg-gray-100 text-gray-600'
}

function avatarInitials(name: string): string {
  return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
}

const avatarColors = [
  'bg-blue-500', 'bg-purple-500', 'bg-green-500',
  'bg-amber-500', 'bg-rose-500', 'bg-teal-500',
]
function avatarColor(id: number): string {
  return avatarColors[id % avatarColors.length]
}
</script>

<template>
  <div class="space-y-5">

    <!-- HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-white">Manajemen User</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          {{ meta.total }} user terdaftar
        </p>
      </div>
      <button
        v-if="authStore.can('user.create')"
        @click="router.push('/admin/users/create')"
        class="flex items-center gap-2 px-4 py-2 bg-yellow-500 hover:bg-yellow-600
               text-white text-sm font-medium rounded-xl shadow-sm transition-colors"
      >
        <PlusIcon class="w-4 h-4" /> Tambah User
      </button>
    </div>

    <!-- FILTER BAR -->
    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-4">
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
          <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            v-model="searchQ"
            placeholder="Cari nama atau username..."
            class="w-full pl-9 pr-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700
                   bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300
                   focus:outline-none focus:ring-2 focus:ring-yellow-500"
          />
        </div>
        <select
          v-model="statusFilter"
          class="px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700
                 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300
                 focus:outline-none focus:ring-2 focus:ring-yellow-500"
        >
          <option value="">Semua Status</option>
          <option value="aktif">Aktif</option>
          <option value="nonaktif">Nonaktif</option>
        </select>
        <select
          v-model="roleFilter"
          class="px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700
                 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300
                 focus:outline-none focus:ring-2 focus:ring-yellow-500"
        >
          <option value="">Semua Role</option>
          <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.display_name }}</option>
        </select>
        <button
          @click="loadUsers(1)"
          :disabled="isLoading"
          class="p-2 rounded-lg border border-gray-200 dark:border-gray-700
                 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400
                 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors"
        >
          <RefreshCwIcon class="w-4 h-4" :class="{ 'animate-spin': isLoading }" />
        </button>
      </div>
    </div>

    <!-- TABLE -->
    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/40">
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">User</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide hidden md:table-cell">Jabatan / Divisi</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Role</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide hidden lg:table-cell">Login Terakhir</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">

            <!-- Loading skeleton -->
            <template v-if="isLoading">
              <tr v-for="i in 8" :key="i">
                <td class="px-4 py-3" colspan="6">
                  <div class="h-10 bg-gray-100 dark:bg-gray-800 rounded-lg animate-pulse" />
                </td>
              </tr>
            </template>

            <!-- Empty -->
            <tr v-else-if="users.length === 0">
              <td colspan="6" class="px-4 py-16 text-center text-sm text-gray-400">
                Tidak ada user ditemukan.
              </td>
            </tr>

            <!-- Rows -->
            <tr
              v-else
              v-for="user in users"
              :key="user.id"
              class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors"
            >
              <!-- User info -->
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div :class="['w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0', avatarColor(user.id)]">
                    {{ avatarInitials(user.name) }}
                  </div>
                  <div class="min-w-0">
                    <p class="font-semibold text-gray-800 dark:text-white truncate">{{ user.name }}</p>
                    <p class="text-xs text-gray-400">@{{ user.username }}</p>
                  </div>
                </div>
              </td>

              <!-- Jabatan / divisi -->
              <td class="px-4 py-3 hidden md:table-cell">
                <p class="text-gray-600 dark:text-gray-300 text-xs">{{ user.jabatan ?? '—' }}</p>
                <p class="text-gray-400 text-xs">{{ user.divisi ?? '—' }}</p>
              </td>

              <!-- Roles -->
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="role in user.roles"
                    :key="role.name"
                    :class="['text-[10px] font-semibold px-2 py-0.5 rounded-full', roleBadgeClass(role.color)]"
                  >
                    {{ role.display_name }}
                  </span>
                </div>
              </td>

              <!-- Status -->
              <td class="px-4 py-3 text-center">
                <span :class="[
                  'inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-1 rounded-full',
                  user.is_active
                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                ]">
                  <UserCheckIcon v-if="user.is_active" class="w-3 h-3" />
                  <UserXIcon v-else class="w-3 h-3" />
                  {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>

              <!-- Last login -->
              <td class="px-4 py-3 hidden lg:table-cell">
                <p class="text-xs text-gray-400">{{ user.last_login_at ?? 'Belum pernah' }}</p>
              </td>

              <!-- Actions -->
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1">
                  <button
                    v-if="authStore.can('user.update')"
                    @click="router.push(`/admin/users/${user.id}/edit`)"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-blue-500 hover:bg-blue-50
                           dark:hover:bg-blue-900/20 transition-colors"
                    title="Edit user"
                  >
                    <EditIcon class="w-4 h-4" />
                  </button>
                  <button
                    v-if="authStore.can('user.delete') && user.id !== authStore.user?.id"
                    @click.prevent="askDeleteUser(user)"
                    :disabled="deletingId === user.id"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50
                           dark:hover:bg-red-900/20 transition-colors disabled:opacity-50"
                    title="Hapus user"
                  >
                    <Trash2Icon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>

          </tbody>
        </table>
      </div>

      <!-- PAGINATION -->
      <div
        v-if="!isLoading && meta.last_page > 1"
        class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-800"
      >
        <p class="text-xs text-gray-400">
          Halaman {{ meta.current_page }} dari {{ meta.last_page }} ({{ meta.total }} user)
        </p>
        <div class="flex gap-1">
          <button
            v-for="page in meta.last_page"
            :key="page"
            @click="loadUsers(page)"
            :class="[
              'w-8 h-8 text-xs rounded-lg font-medium transition-colors',
              page === meta.current_page
                ? 'bg-yellow-500 text-white'
                : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800',
            ]"
          >{{ page }}</button>
        </div>
      </div>
    </div>

  </div>
</template>

<ConfirmDialog v-model="showDeleteConfirm" :message="userToDelete ? 'Hapus user ' + userToDelete.name + ' (@' + userToDelete.username + ')?' : ''" @confirm="deleteUserConfirmed" />
