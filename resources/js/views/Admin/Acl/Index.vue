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

interface MenuAccessAction {
  key: string
  label: string
  permissionNames: string[]
}

interface MenuAccessMenu {
  key: string
  label: string
  actions: MenuAccessAction[]
}

interface MenuAccessGroup {
  key: string
  label: string
  menus: MenuAccessMenu[]
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
const selectedActionKeys = ref<string[]>([])
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
    menus: [
      {
        key: 'dashboard-informasi-tebu',
        label: 'Informasi Tebu',
        actions: [
          { key: 'view', label: 'Lihat', permissionNames: ['dashboard.view'] },
          { key: 'export', label: 'Export', permissionNames: ['dashboard.export'] },
          { key: 'print', label: 'Cetak', permissionNames: ['dashboard.print'] },
        ],
      },
      {
        key: 'dashboard-monitoring-pabrik',
        label: 'Monitoring Pabrik',
        actions: [
          { key: 'view', label: 'Lihat', permissionNames: ['operasional.view'] },
          { key: 'create', label: 'Tambah', permissionNames: ['operasional.create'] },
          { key: 'update', label: 'Edit', permissionNames: ['operasional.update'] },
          { key: 'delete', label: 'Hapus', permissionNames: ['operasional.delete'] },
          { key: 'export', label: 'Export', permissionNames: ['operasional.export'] },
          { key: 'print', label: 'Cetak', permissionNames: ['operasional.print'] },
        ],
      },
      {
        key: 'dashboard-pengawasan-qa',
        label: 'Angka Pengawasan QA',
        actions: [
          { key: 'view', label: 'Lihat', permissionNames: ['dashboard.pengawasan_qa.view'] },
        ],
      },
    ],
  },
  {
    key: 'penerimaan',
    label: 'Penerimaan Tebu',
    menus: [
      {
        key: 'penerimaan-manajemen-spa',
        label: 'Monitoring SPA',
        actions: [
          { key: 'view', label: 'Lihat', permissionNames: ['penerimaan.spa.view'] },
          { key: 'create', label: 'Tambah', permissionNames: ['penerimaan.create'] },
          { key: 'update', label: 'Edit', permissionNames: ['penerimaan.update'] },
          { key: 'delete', label: 'Hapus', permissionNames: ['penerimaan.delete'] },
          { key: 'export', label: 'Export', permissionNames: ['penerimaan.export'] },
          { key: 'print', label: 'Cetak', permissionNames: ['penerimaan.spa.print'] },
        ],
      },
      {
        key: 'penerimaan-monitoring-antrian',
        label: 'Monitoring Antrian',
        actions: [
          { key: 'view', label: 'Lihat', permissionNames: ['penerimaan.antrian.view'] },
          { key: 'print', label: 'Cetak', permissionNames: ['penerimaan.antrian.print'] },
        ],
      },
      {
        key: 'penerimaan-data-pemasukan-all',
        label: 'Data Pemasukan (Semua Tab)',
        actions: [
          { key: 'view', label: 'Lihat', permissionNames: ['penerimaan.pemasukan.view'] },
          { key: 'export', label: 'Export', permissionNames: ['penerimaan.pemasukan.export'] },
          { key: 'print', label: 'Cetak', permissionNames: ['penerimaan.pemasukan.print'] },
        ],
      },
      {
        key: 'penerimaan-data-pemasukan-kebun',
        label: 'Tab Pemasukan per Kebun',
        actions: [
          { key: 'view', label: 'Lihat', permissionNames: ['penerimaan.pemasukan.kebun.view'] },
          { key: 'export', label: 'Export', permissionNames: ['penerimaan.pemasukan.kebun.export'] },
          { key: 'print', label: 'Cetak', permissionNames: ['penerimaan.pemasukan.kebun.print'] },
        ],
      },
      {
        key: 'penerimaan-data-pemasukan-kategori',
        label: 'Tab Pemasukan per Kategori',
        actions: [
          { key: 'view', label: 'Lihat', permissionNames: ['penerimaan.pemasukan.kategori.view'] },
          { key: 'export', label: 'Export', permissionNames: ['penerimaan.pemasukan.kategori.export'] },
          { key: 'print', label: 'Cetak', permissionNames: ['penerimaan.pemasukan.kategori.print'] },
        ],
      },
      {
        key: 'penerimaan-data-pemasukan-wilayah',
        label: 'Tab Pemasukan per Wilayah',
        actions: [
          { key: 'view', label: 'Lihat', permissionNames: ['penerimaan.pemasukan.wilayah.view'] },
          { key: 'export', label: 'Export', permissionNames: ['penerimaan.pemasukan.wilayah.export'] },
          { key: 'print', label: 'Cetak', permissionNames: ['penerimaan.pemasukan.wilayah.print'] },
        ],
      },
      {
        key: 'penerimaan-data-pemasukan-sisa-pagi',
        label: 'Tab Sisa Pagi',
        actions: [
          { key: 'view', label: 'Lihat', permissionNames: ['penerimaan.pemasukan.sisa_pagi.view'] },
          { key: 'export', label: 'Export', permissionNames: ['penerimaan.pemasukan.sisa_pagi.export'] },
          { key: 'print', label: 'Cetak', permissionNames: ['penerimaan.pemasukan.sisa_pagi.print'] },
        ],
      },
      {
        key: 'penerimaan-data-pemasukan-digiling-spa',
        label: 'Tab Digiling per SPA',
        actions: [
          { key: 'view', label: 'Lihat', permissionNames: ['penerimaan.pemasukan.digiling_spa.view'] },
          { key: 'export', label: 'Export', permissionNames: ['penerimaan.pemasukan.digiling_spa.export'] },
          { key: 'print', label: 'Cetak', permissionNames: ['penerimaan.pemasukan.digiling_spa.print'] },
        ],
      },
    ],
  },
  {
    key: 'analisa-qa',
    label: 'Analisa QA',
    menus: [
      {
        key: 'analisa-qa-data',
        label: 'Data QA Pabrik Gula / Alkohol',
        actions: [
          { key: 'view', label: 'Lihat', permissionNames: ['lab_qa.view'] },
          { key: 'create', label: 'Tambah', permissionNames: ['lab_qa.create'] },
          { key: 'update', label: 'Edit', permissionNames: ['lab_qa.update'] },
          { key: 'delete', label: 'Hapus', permissionNames: ['lab_qa.delete'] },
          { key: 'approve', label: 'Approve', permissionNames: ['lab_qa.approve'] },
          { key: 'export', label: 'Export', permissionNames: ['lab_qa.export'] },
          { key: 'print', label: 'Cetak', permissionNames: ['lab_qa.print'] },
        ],
      },
      {
        key: 'analisa-qa-pos-npp',
        label: 'Pos NPP',
        actions: [
          { key: 'view', label: 'Akses', permissionNames: ['lab_qa.pos_npp'] },
        ],
      },
    ],
  },
  {
    key: 'peta-kebun',
    label: 'Peta Kebun',
    menus: [
      {
        key: 'peta-kebun-index',
        label: 'Peta Kebun',
        actions: [
          { key: 'view', label: 'Lihat', permissionNames: ['peta_kebun.view'] },
          { key: 'create', label: 'Tambah', permissionNames: ['peta_kebun.create'] },
          { key: 'update', label: 'Edit', permissionNames: ['peta_kebun.update'] },
          { key: 'delete', label: 'Hapus', permissionNames: ['peta_kebun.delete'] },
          { key: 'print', label: 'Cetak', permissionNames: ['peta_kebun.print'] },
        ],
      },
    ],
  },
  {
    key: 'admin',
    label: 'Administrasi',
    menus: [
      {
        key: 'manajemen-user',
        label: 'Manajemen User',
        actions: [
          { key: 'view', label: 'Lihat', permissionNames: ['user.view'] },
          { key: 'create', label: 'Tambah', permissionNames: ['user.create'] },
          { key: 'update', label: 'Edit', permissionNames: ['user.update'] },
          { key: 'delete', label: 'Hapus', permissionNames: ['user.delete'] },
          { key: 'assign-role', label: 'Assign Role', permissionNames: ['user.assign_role'] },
          { key: 'assign-attribute', label: 'Assign Atribut', permissionNames: ['user.assign_attribute'] },
        ],
      },
      {
        key: 'manajemen-acl',
        label: 'Manajemen ACL',
        actions: [
          { key: 'view', label: 'Lihat', permissionNames: ['role.view'] },
          { key: 'create', label: 'Tambah', permissionNames: ['role.create'] },
          { key: 'update', label: 'Edit', permissionNames: ['role.update'] },
          { key: 'delete', label: 'Hapus', permissionNames: ['role.delete'] },
          { key: 'assign-permission', label: 'Assign Permission', permissionNames: ['role.assign_permission'] },
        ],
      },
    ],
  },
]

interface AccessActionEntry {
  key: string
  action: MenuAccessAction
}

const allAccessActionEntries = computed<AccessActionEntry[]>(() =>
  menuAccessGroups.flatMap((group) =>
    group.menus.flatMap((menu) =>
      menu.actions.map((action) => ({
        key: `${group.key}::${menu.key}::${action.key}`,
        action,
      }))
    )
  )
)
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
  selectedActionKeys.value = []
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
    allAccessActionEntries.value
      .flatMap((entry) => permissionIdsByNames(entry.action.permissionNames))
  )

  nonMenuPermissionIds.value = rolePermissionIds.filter(id => !allMenuPermissionIds.has(id))
  selectedActionKeys.value = inferSelectedActionKeys(rolePermissionIds)

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

function applyAdminPermissions() {
  if (form.value.make_admin) {
    selectedActionKeys.value = allAccessActionEntries.value.map((entry) => entry.key)
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
      allAccessActionEntries.value
        .filter((entry) => selectedActionKeys.value.includes(entry.key))
        .flatMap((entry) => permissionIdsByNames(entry.action.permissionNames))
    )
  )

  form.value.permission_ids = Array.from(new Set([...nonMenuPermissionIds.value, ...selectedIds]))
}

function inferSelectedActionKeys(permissionIds: number[]): string[] {
  const selected = new Set<string>()

  for (const entry of allAccessActionEntries.value) {
    const ids = permissionIdsByNames(entry.action.permissionNames)
    if (ids.length === 0) continue
    if (!ids.every((id) => permissionIds.includes(id))) continue
    selected.add(entry.key)
  }

  return Array.from(selected)
}

function getActionCompositeKey(group: MenuAccessGroup, menu: MenuAccessMenu, action: MenuAccessAction): string {
  return `${group.key}::${menu.key}::${action.key}`
}

function isActionChecked(group: MenuAccessGroup, menu: MenuAccessMenu, action: MenuAccessAction): boolean {
  return selectedActionKeys.value.includes(getActionCompositeKey(group, menu, action))
}

function toggleAction(group: MenuAccessGroup, menu: MenuAccessMenu, action: MenuAccessAction) {
  const actionKey = getActionCompositeKey(group, menu, action)

  if (selectedActionKeys.value.includes(actionKey)) {
    selectedActionKeys.value = selectedActionKeys.value.filter((key) => key !== actionKey)
  } else {
    selectedActionKeys.value = [...selectedActionKeys.value, actionKey]
  }

  syncPermissionIdsFromMenuSelection()
}

function isMenuChecked(group: MenuAccessGroup, menu: MenuAccessMenu): boolean {
  if (menu.actions.length === 0) return false
  return menu.actions.every((action) => isActionChecked(group, menu, action))
}

function isMenuIndeterminate(group: MenuAccessGroup, menu: MenuAccessMenu): boolean {
  if (menu.actions.length === 0) return false
  const checkedCount = menu.actions.filter((action) => isActionChecked(group, menu, action)).length
  return checkedCount > 0 && checkedCount < menu.actions.length
}

function toggleMenu(group: MenuAccessGroup, menu: MenuAccessMenu) {
  if (isMenuChecked(group, menu)) {
    const menuActionKeys = menu.actions.map((action) => getActionCompositeKey(group, menu, action))
    selectedActionKeys.value = selectedActionKeys.value.filter((key) => !menuActionKeys.includes(key))
  } else {
    const merged = new Set([
      ...selectedActionKeys.value,
      ...menu.actions.map((action) => getActionCompositeKey(group, menu, action)),
    ])
    selectedActionKeys.value = Array.from(merged)
  }

  syncPermissionIdsFromMenuSelection()
}

function isMenuGroupChecked(group: MenuAccessGroup): boolean {
  if (group.menus.length === 0) return false
  return group.menus.every((menu) => isMenuChecked(group, menu))
}

function isMenuGroupIndeterminate(group: MenuAccessGroup): boolean {
  if (group.menus.length === 0) return false
  const checkedCount = group.menus.filter((menu) => isMenuChecked(group, menu)).length
  return checkedCount > 0 && checkedCount < group.menus.length
}

function toggleMenuGroup(group: MenuAccessGroup) {
  if (isMenuGroupChecked(group)) {
    const groupActionKeys = group.menus.flatMap((menu) =>
      menu.actions.map((action) => getActionCompositeKey(group, menu, action))
    )
    selectedActionKeys.value = selectedActionKeys.value.filter((key) => !groupActionKeys.includes(key))
  } else {
    const merged = new Set([
      ...selectedActionKeys.value,
      ...group.menus.flatMap((menu) => menu.actions.map((action) => getActionCompositeKey(group, menu, action))),
    ])
    selectedActionKeys.value = Array.from(merged)
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
            <label class="inline-flex items-center gap-2 mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">
              <input
                type="checkbox"
                :checked="isMenuGroupChecked(group)"
                :indeterminate.prop="isMenuGroupIndeterminate(group)"
                :disabled="isSystemRoleSelected"
                @change="toggleMenuGroup(group)"
                class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-yellow-500 focus:ring-yellow-500"
              />
              {{ group.label }}
            </label>

            <div class="space-y-3">
              <div
                v-for="menu in group.menus"
                :key="menu.key"
                class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-900/50 px-3 py-2.5"
              >
                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                  <input
                    type="checkbox"
                    :checked="isMenuChecked(group, menu)"
                    :indeterminate.prop="isMenuIndeterminate(group, menu)"
                    :disabled="isSystemRoleSelected"
                    @change="toggleMenu(group, menu)"
                    class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-yellow-500 focus:ring-yellow-500"
                  />
                  {{ menu.label }}
                </label>

                <div class="mt-2 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-4 gap-y-1.5 pl-6">
                  <label
                    v-for="action in menu.actions"
                    :key="`${menu.key}-${action.key}`"
                    class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300"
                  >
                    <input
                      type="checkbox"
                      :checked="isActionChecked(group, menu, action)"
                      :disabled="isSystemRoleSelected"
                      @change="toggleAction(group, menu, action)"
                      class="w-3.5 h-3.5 rounded border-gray-300 dark:border-gray-600 text-yellow-500 focus:ring-yellow-500"
                    />
                    {{ action.label }}
                  </label>
                </div>
              </div>
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
