<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useAuthStore } from '@/stores/auth'
import { SaveIcon, RefreshCwIcon, ShieldIcon, CheckIcon, Trash2Icon } from 'lucide-vue-next'

interface PermissionItem {
  id: number
  name: string
  display_name: string
}

interface MenuAccessItem {
  key: string
  label: string
  permissionNames: string[]
}

interface MenuAccessGroup {
  key: string
  label: string
  items: MenuAccessItem[]
}

interface RoleItem {
  id: number
  name: string
  display_name: string
  description: string | null
  color: 'red' | 'purple' | 'blue' | 'green' | 'gray'
  is_system: boolean
  permissions: Array<{ id: number; name: string; display_name: string; group: string }>
}

const authStore = useAuthStore()
const isLoading   = ref(true)
const isSaving    = ref(false)
const isDeleting  = ref(false)
const roles     = ref<RoleItem[]>([])
const permissionGroups = ref<Record<string, PermissionItem[]>>({})
const selectedRoleId = ref<number | null>(null)
const errors = ref<Record<string, string[]>>({})

const form = ref({
  name: '',
  display_name: '',
  description: '',
  color: 'blue' as RoleItem['color'],
  make_admin: false,
  permission_ids: [] as number[],
})
const selectedMenuKeys = ref<string[]>([])
const nonMenuPermissionIds = ref<number[]>([])

const roleList = computed(() => roles.value)
const totalPermissionCount = computed(() => Object.values(permissionGroups.value).flat().length)
const isEditing = computed(() => selectedRoleId.value !== null)
const selectedRole = computed(() => roles.value.find(r => r.id === selectedRoleId.value) ?? null)
const isSystemRoleSelected = computed(() => selectedRole.value?.is_system ?? false)
const allPermissions = computed(() => Object.values(permissionGroups.value).flat())

const menuAccessGroups: MenuAccessGroup[] = [
  {
    key: 'dashboard',
    label: 'Dashboard',
    items: [
      { key: 'dashboard-informasi-tebu', label: 'Informasi Tebu', permissionNames: ['dashboard.view'] },
      { key: 'dashboard-monitoring-pabrik', label: 'Monitoring Pabrik', permissionNames: ['operasional.view'] },
      { key: 'dashboard-pengawasan-qa', label: 'Angka Pengawasan QA', permissionNames: ['dashboard.pengawasan_qa.view'] },
    ],
  },
  {
    key: 'penerimaan',
    label: 'Penerimaan Tebu',
    items: [
      {
        key: 'penerimaan-manajemen-spa',
        label: 'Manajemen SPA',
        permissionNames: ['penerimaan.spa.view', 'penerimaan.update'],
      },
      {
        key: 'penerimaan-monitoring-antrian',
        label: 'Monitoring Antrian',
        permissionNames: ['penerimaan.antrian.view'],
      },
      {
        key: 'penerimaan-data-pemasukan',
        label: 'Data Pemasukan',
        permissionNames: ['penerimaan.pemasukan.view'],
      },
    ],
  },
  {
    key: 'analisa-qa',
    label: 'Analisa QA',
    items: [
      {
        key: 'analisa-qa-akses-entri',
        label: 'Akses Entri Data Pabrik Gula / Alkohol',
        permissionNames: ['lab_qa.view', 'lab_qa.create'],
      },
      {
        key: 'analisa-qa-edit-entri',
        label: 'Edit Entri Data Pabrik Gula / Alkohol',
        permissionNames: ['lab_qa.update'],
      },
      {
        key: 'analisa-qa-hapus-entri',
        label: 'Hapus Entri Data Pabrik Gula / Alkohol',
        permissionNames: ['lab_qa.delete'],
      },
    ],
  },
  {
    key: 'peta-kebun',
    label: 'Peta Kebun',
    items: [
      { key: 'peta-kebun-index', label: 'Peta Kebun', permissionNames: ['peta_kebun.view'] },
    ],
  },
  {
    key: 'admin',
    label: 'Administrasi',
    items: [
      { key: 'manajemen-user', label: 'Manajemen User', permissionNames: ['user.view'] },
      { key: 'manajemen-acl', label: 'Manajemen ACL', permissionNames: ['role.view'] },
    ],
  },
]
const canSave = computed(() => {
  if (isSystemRoleSelected.value) return false
  return isEditing.value ? authStore.can('role.update') : authStore.can('role.create')
})

async function loadData() {
  isLoading.value = true
  try {
    const [rolesRes, permissionsRes] = await Promise.all([
      axios.get('/api/acl/roles'),
      axios.get('/api/acl/permissions'),
    ])
    roles.value = rolesRes.data.data
    permissionGroups.value = permissionsRes.data.data
  } finally {
    isLoading.value = false
  }
}

function resetForm() {
  selectedRoleId.value = null
  errors.value = {}
  selectedMenuKeys.value = []
  nonMenuPermissionIds.value = []
  form.value = {
    name: '',
    display_name: '',
    description: '',
    color: 'blue',
    make_admin: false,
    permission_ids: [],
  }
}

function loadRoleToForm(role: RoleItem) {
  const rolePermissionIds = role.permissions.map(p => p.id)
  const allMenuPermissionIds = new Set(
    menuAccessGroups
      .flatMap(group => group.items)
      .flatMap(item => permissionIdsByNames(item.permissionNames))
  )

  nonMenuPermissionIds.value = rolePermissionIds.filter(id => !allMenuPermissionIds.has(id))
  selectedMenuKeys.value = inferSelectedMenuKeys(rolePermissionIds)

  selectedRoleId.value = role.id
  errors.value = {}
  form.value = {
    name: role.name,
    display_name: role.display_name,
    description: role.description ?? '',
    color: role.color,
    make_admin: role.permissions.length === totalPermissionCount.value,
    permission_ids: [],
  }

  syncPermissionIdsFromMenuSelection()
}

function togglePermission(permissionId: number) {
  const idx = form.value.permission_ids.indexOf(permissionId)
  if (idx === -1) form.value.permission_ids.push(permissionId)
  else form.value.permission_ids.splice(idx, 1)
}

function groupChecked(groupPermissions: PermissionItem[]): boolean {
  if (groupPermissions.length === 0) return false
  return groupPermissions.every(p => form.value.permission_ids.includes(p.id))
}

function toggleGroup(groupPermissions: PermissionItem[]) {
  const allChecked = groupChecked(groupPermissions)
  if (allChecked) {
    form.value.permission_ids = form.value.permission_ids.filter(id => !groupPermissions.some(p => p.id === id))
    return
  }

  const merged = new Set([...form.value.permission_ids, ...groupPermissions.map(p => p.id)])
  form.value.permission_ids = Array.from(merged)
}

function applyAdminPermissions() {
  if (form.value.make_admin) {
    selectedMenuKeys.value = menuAccessGroups.flatMap(group => group.items.map(item => item.key))
    nonMenuPermissionIds.value = []
    form.value.permission_ids = Object.values(permissionGroups.value).flat().map(p => p.id)
  }
}

function permissionIdsByNames(permissionNames: string[]): number[] {
  return allPermissions.value
    .filter(permission => permissionNames.includes(permission.name))
    .map(permission => permission.id)
}

function syncPermissionIdsFromMenuSelection() {
  const selectedIds = Array.from(
    new Set(
      menuAccessGroups
        .flatMap(group => group.items)
        .filter(item => selectedMenuKeys.value.includes(item.key))
        .flatMap(item => permissionIdsByNames(item.permissionNames))
    )
  )

  form.value.permission_ids = Array.from(new Set([...nonMenuPermissionIds.value, ...selectedIds]))
}

function inferSelectedMenuKeys(permissionIds: number[]): string[] {
  const selected = new Set<string>()
  const usedSignature = new Set<string>()

  for (const group of menuAccessGroups) {
    for (const item of group.items) {
      const ids = permissionIdsByNames(item.permissionNames)
      if (ids.length === 0) continue
      if (!ids.every(id => permissionIds.includes(id))) continue

      const signature = [...ids].sort((a, b) => a - b).join('-')
      if (usedSignature.has(signature)) continue

      usedSignature.add(signature)
      selected.add(item.key)
    }
  }

  return Array.from(selected)
}

function isMenuItemChecked(item: MenuAccessItem): boolean {
  return selectedMenuKeys.value.includes(item.key)
}

function toggleMenuItem(item: MenuAccessItem) {
  if (isMenuItemChecked(item)) {
    selectedMenuKeys.value = selectedMenuKeys.value.filter(key => key !== item.key)
  } else {
    selectedMenuKeys.value = [...selectedMenuKeys.value, item.key]
  }

  syncPermissionIdsFromMenuSelection()
}

function isMenuGroupChecked(group: MenuAccessGroup): boolean {
  if (group.items.length === 0) return false
  return group.items.every(item => isMenuItemChecked(item))
}

function toggleMenuGroup(group: MenuAccessGroup) {
  if (isMenuGroupChecked(group)) {
    selectedMenuKeys.value = selectedMenuKeys.value.filter(
      key => !group.items.some(item => item.key === key)
    )
  } else {
    const merged = new Set([...selectedMenuKeys.value, ...group.items.map(item => item.key)])
    selectedMenuKeys.value = Array.from(merged)
  }

  syncPermissionIdsFromMenuSelection()
}

const showDeleteAclConfirm = ref(false)
const aclToDelete = ref<RoleItem | null>(null)

function askDeleteAcl(role?: RoleItem) {
  if (role) selectedRoleId.value = role.id
  if (!selectedRole.value) return
  aclToDelete.value = selectedRole.value
  showDeleteAclConfirm.value = true
}

async function deleteAcl() {
  // kept for backward compatibility
  if (!selectedRole.value) return
  aclToDelete.value = selectedRole.value
  await deleteAclConfirmed()
}

async function deleteAclConfirmed() {
  if (!aclToDelete.value) return
  isDeleting.value = true
  try {
    await axios.delete(`/api/acl/roles/${aclToDelete.value.id}`)
    await loadData()
    resetForm()
  } catch (err: any) {
    const msg = err.response?.data?.message ?? 'Gagal menghapus ACL.'
    // use simple alert for errors (kept as-is)
    alert(msg)
  } finally {
    isDeleting.value = false
    aclToDelete.value = null
    showDeleteAclConfirm.value = false
  }
}

async function saveAcl() {
  errors.value = {}
  isSaving.value = true

  const payload = {
    ...form.value,
    description: form.value.description || null,
  }

  try {
    if (selectedRoleId.value) {
      await axios.put(`/api/acl/roles/${selectedRoleId.value}`, payload)
    } else {
      await axios.post('/api/acl/roles', payload)
    }

    await loadData()
    resetForm()
  } catch (err: any) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors ?? {}
    }
  } finally {
    isSaving.value = false
  }
}

onMounted(loadData)
</script>

<template>
  <div class="space-y-5">

    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-white">Manajemen ACL</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          Buat dan atur hak akses role per modul
        </p>
      </div>
      <button
        @click="loadData"
        :disabled="isLoading"
        class="p-2 rounded-lg border border-gray-200 dark:border-gray-700
               bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400
               hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors"
      >
        <RefreshCwIcon class="w-4 h-4" :class="{ 'animate-spin': isLoading }" />
      </button>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
      <div class="xl:col-span-4 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-4">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Daftar ACL</h2>
          <button
            v-if="authStore.can('role.create')"
            @click="resetForm"
            class="text-xs px-2.5 py-1 rounded-md bg-yellow-500 text-white hover:bg-yellow-600"
          >
            + Baru
          </button>
        </div>

        <div v-if="isLoading" class="space-y-2">
          <div v-for="i in 8" :key="i" class="h-10 rounded-lg bg-gray-100 dark:bg-gray-800 animate-pulse" />
        </div>

        <div v-else class="space-y-1.5 max-h-[620px] overflow-y-auto pr-1">
          <button
            v-for="role in roleList"
            :key="role.id"
            @click="loadRoleToForm(role)"
            class="w-full text-left px-3 py-2.5 rounded-lg border transition-colors"
            :class="selectedRoleId === role.id
              ? 'border-yellow-300 bg-yellow-50 dark:border-yellow-700 dark:bg-yellow-900/20'
              : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/70'"
          >
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ role.display_name }}</p>
            <p class="text-xs text-gray-400">{{ role.name }} · {{ role.permissions.length }} permission</p>
            <div class="flex items-center justify-between mt-1">
              <p v-if="role.is_system" class="text-[10px] inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                Role Sistem
              </p>
              <button
                v-if="!role.is_system && authStore.can('role.delete')"
                type="button"
                @click.stop.prevent="askDeleteAcl(role)"
                class="ml-auto p-1 rounded text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                title="Hapus ACL"
              >
                <Trash2Icon class="w-3.5 h-3.5" />
              </button>
            </div>
          </button>
        </div>
      </div>

      <form @submit.prevent="saveAcl" class="xl:col-span-8 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-5 space-y-4">
        <div class="flex items-center gap-2">
          <ShieldIcon class="w-4 h-4 text-yellow-500" />
          <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
            {{ isEditing ? 'Edit ACL' : 'Create New ACL' }}
          </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">ACL Name (Key)</label>
            <input
              v-model="form.name"
              :disabled="isEditing || isSystemRoleSelected"
              placeholder="contoh: support_team"
              class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 disabled:opacity-60"
            />
            <p v-if="errors.name" class="text-xs text-red-500 mt-1">{{ errors.name[0] }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Display Name</label>
            <input
              v-model="form.display_name"
              :disabled="isSystemRoleSelected"
              placeholder="contoh: Support Team"
              class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500"
            />
            <p v-if="errors.display_name" class="text-xs text-red-500 mt-1">{{ errors.display_name[0] }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Warna Badge</label>
            <select
              v-model="form.color"
              :disabled="isSystemRoleSelected"
              class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500"
            >
              <option value="blue">Biru</option>
              <option value="green">Hijau</option>
              <option value="purple">Ungu</option>
              <option value="red">Merah</option>
              <option value="gray">Abu-abu</option>
            </select>
          </div>

          <div class="flex items-end">
            <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
              <input
                v-model="form.make_admin"
                type="checkbox"
                :disabled="isSystemRoleSelected"
                @change="applyAdminPermissions"
                class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-yellow-500 focus:ring-yellow-500"
              />
              Make Admin (akses semua)
            </label>
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Deskripsi</label>
          <textarea
            v-model="form.description"
            :disabled="isSystemRoleSelected"
            rows="2"
            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 resize-none"
            placeholder="Keterangan fungsi ACL ini"
          />
        </div>

        <div class="space-y-3">
          <div
            v-for="group in menuAccessGroups"
            :key="group.key"
            class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/40 p-3"
          >
            <label class="inline-flex items-center gap-2 mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
              <input
                type="checkbox"
                :checked="isMenuGroupChecked(group)"
                  :disabled="isSystemRoleSelected"
                @change="toggleMenuGroup(group)"
                class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-yellow-500 focus:ring-yellow-500"
              />
              {{ group.label }}
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1">
              <label
                v-for="item in group.items"
                :key="item.key"
                class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300"
              >
                <input
                  type="checkbox"
                  :checked="isMenuItemChecked(item)"
                  :disabled="isSystemRoleSelected"
                  @change="toggleMenuItem(item)"
                  class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-yellow-500 focus:ring-yellow-500"
                />
                {{ item.label }}
              </label>
            </div>
          </div>
        </div>

        <p v-if="errors.permission_ids" class="text-xs text-red-500">{{ errors.permission_ids[0] }}</p>

        <div class="flex items-center justify-between pt-1">
          <div>
            <p class="text-xs text-gray-400">{{ form.permission_ids.length }} permission dipilih</p>
            <p v-if="isSystemRoleSelected" class="text-xs text-amber-500 mt-0.5">
              ACL sistem bersifat read-only dan tidak bisa diubah.
            </p>
          </div>
          <div class="flex items-center gap-2">
            <button
              v-if="isEditing && !isSystemRoleSelected && authStore.can('role.delete')"
              type="button"
              @click.prevent="askDeleteAcl()"
              :disabled="isDeleting"
              class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 disabled:opacity-60 disabled:cursor-not-allowed rounded-xl"
            >
              <Trash2Icon class="w-4 h-4" />
              {{ isDeleting ? 'Menghapus...' : 'Hapus ACL' }}
            </button>
            <button
              type="button"
              @click="resetForm"
              class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl"
            >
              Reset
            </button>
            <button
              type="submit"
              :disabled="isSaving || !canSave"
              class="flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-yellow-500 hover:bg-yellow-600 disabled:opacity-60 disabled:cursor-not-allowed rounded-xl"
            >
              <SaveIcon class="w-4 h-4" />
              {{ isSaving ? 'Menyimpan...' : (isEditing ? 'Update ACL' : 'Simpan ACL') }}
            </button>
          </div>
        </div>
      </form>
    </div>

    <ConfirmDialog
      v-model="showDeleteAclConfirm"
      :message="aclToDelete ? 'Hapus ACL ' + aclToDelete.display_name + '? Tindakan ini tidak dapat dibatalkan.' : ''"
      @confirm="deleteAclConfirmed"
    />
  </div>
</template>
