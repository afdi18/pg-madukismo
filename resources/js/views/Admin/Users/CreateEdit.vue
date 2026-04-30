<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import { SaveIcon, ArrowLeftIcon, EyeIcon, EyeOffIcon, ShieldIcon, UserIcon } from 'lucide-vue-next'

const route     = useRoute()
const router    = useRouter()
const authStore = useAuthStore()

const isEdit    = computed(() => !!route.params.id)
const isLoading = ref(false)
const isSaving  = ref(false)
const roles     = ref<any[]>([])
const errors    = ref<Record<string, string[]>>({})
const showPass  = ref(false)

// ================================================================
// FORM DATA
// ================================================================
const form = ref({
  username    : '',
  name        : '',
  email       : '',
  password    : '',
  jabatan     : '',
  divisi      : '',
  area_kebun  : '',
  phone       : '',
  is_active   : true,
  notes       : '',
  roles       : [] as number[],
  attributes  : {
    shift       : '',
    level_akses : '',
  },
})

// ================================================================
// LOAD
// ================================================================
onMounted(async () => {
  isLoading.value = true
  try {
    const rolesRes = await axios.get('/api/users/roles')
    roles.value = rolesRes.data.data

    if (isEdit.value) {
      const { data } = await axios.get(`/api/users/${route.params.id}`)
      const u = data.user
      form.value = {
        username   : u.username,
        name       : u.name,
        email      : u.email ?? '',
        password   : '',
        jabatan    : u.jabatan ?? '',
        divisi     : u.divisi ?? '',
        area_kebun : u.area_kebun ?? '',
        phone      : u.phone ?? '',
        is_active  : u.is_active,
        notes      : '',
        roles      : u.roles.map((r: any) => r.id),
        attributes : {
          shift      : data.attributes?.shift ?? '',
          level_akses: data.attributes?.level_akses ?? '',
        },
      }
    }
  } finally {
    isLoading.value = false
  }
})

// ================================================================
// SUBMIT
// ================================================================
async function handleSubmit() {
  errors.value = {}
  isSaving.value = true

  const payload: any = { ...form.value }
  // Hapus field yang kosong
  if (!payload.password) delete payload.password
  if (!payload.email) payload.email = null

  try {
    if (isEdit.value) {
      await axios.put(`/api/users/${route.params.id}`, payload)
    } else {
      await axios.post('/api/users', payload)
    }
    router.push('/admin/users')
  } catch (err: any) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors ?? {}
    }
  } finally {
    isSaving.value = false
  }
}

function toggleRole(roleId: number) {
  const idx = form.value.roles.indexOf(roleId)
  if (idx === -1) {
    form.value.roles.push(roleId)
  } else {
    form.value.roles.splice(idx, 1)
  }
}

function hasRole(roleId: number): boolean {
  return form.value.roles.includes(roleId)
}

const roleBadgeClass: Record<string, string> = {
  red   : 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/20',
  purple: 'border-purple-300 bg-purple-50 dark:border-purple-700 dark:bg-purple-900/20',
  blue  : 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20',
  green : 'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-900/20',
  gray  : 'border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-800',
}
</script>

<template>
  <div class="max-w-3xl mx-auto space-y-5">

    <!-- HEADER -->
    <div class="flex items-center gap-3">
      <button
        @click="router.back()"
        class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
      >
        <ArrowLeftIcon class="w-5 h-5" />
      </button>
      <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-white">
          {{ isEdit ? 'Edit User' : 'Tambah User Baru' }}
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          {{ isEdit ? 'Perbarui data dan akses user' : 'Daftarkan user baru dan tentukan haknya' }}
        </p>
      </div>
    </div>

    <div v-if="isLoading" class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-8">
      <div class="space-y-4">
        <div v-for="i in 6" :key="i" class="h-10 bg-gray-100 dark:bg-gray-800 rounded-lg animate-pulse" />
      </div>
    </div>

    <form v-else @submit.prevent="handleSubmit" class="space-y-5">

      <!-- ============================================================
           INFORMASI DASAR
      ============================================================ -->
      <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-6 space-y-4">
        <div class="flex items-center gap-2 mb-2">
          <UserIcon class="w-4 h-4 text-yellow-500" />
          <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Informasi Dasar</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

          <!-- Username (tidak bisa diubah saat edit) -->
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
              Username <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.username"
              type="text"
              :disabled="isEdit"
              placeholder="contoh: budi.santoso"
              :class="[
                'w-full px-3 py-2 text-sm rounded-lg border',
                errors.username
                  ? 'border-red-400 focus:ring-red-500'
                  : 'border-gray-200 dark:border-gray-700 focus:ring-yellow-500',
                'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300',
                'focus:outline-none focus:ring-2 disabled:bg-gray-50 dark:disabled:bg-gray-800/60 disabled:cursor-not-allowed',
              ]"
            />
            <p v-if="errors.username" class="text-xs text-red-500 mt-1">{{ errors.username[0] }}</p>
          </div>

          <!-- Nama lengkap -->
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
              Nama Lengkap <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              placeholder="Nama lengkap"
              :class="[
                'w-full px-3 py-2 text-sm rounded-lg border',
                errors.name ? 'border-red-400' : 'border-gray-200 dark:border-gray-700',
                'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300',
                'focus:outline-none focus:ring-2 focus:ring-yellow-500',
              ]"
            />
            <p v-if="errors.name" class="text-xs text-red-500 mt-1">{{ errors.name[0] }}</p>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Email</label>
            <input
              v-model="form.email"
              type="email"
              placeholder="email@madukismo.co.id (opsional)"
              :class="[
                'w-full px-3 py-2 text-sm rounded-lg border',
                errors.email ? 'border-red-400' : 'border-gray-200 dark:border-gray-700',
                'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300',
                'focus:outline-none focus:ring-2 focus:ring-yellow-500',
              ]"
            />
            <p v-if="errors.email" class="text-xs text-red-500 mt-1">{{ errors.email[0] }}</p>
          </div>

          <!-- Password -->
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
              Password {{ isEdit ? '(kosongkan jika tidak diubah)' : '' }}
              <span v-if="!isEdit" class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPass ? 'text' : 'password'"
                placeholder="Min. 8 karakter, huruf besar + angka"
                :class="[
                  'w-full px-3 py-2 pr-10 text-sm rounded-lg border',
                  errors.password ? 'border-red-400' : 'border-gray-200 dark:border-gray-700',
                  'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300',
                  'focus:outline-none focus:ring-2 focus:ring-yellow-500',
                ]"
              />
              <button
                type="button"
                @click="showPass = !showPass"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <EyeOffIcon v-if="showPass" class="w-4 h-4" />
                <EyeIcon v-else class="w-4 h-4" />
              </button>
            </div>
            <p v-if="errors.password" class="text-xs text-red-500 mt-1">{{ errors.password[0] }}</p>
          </div>

          <!-- Jabatan -->
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Jabatan</label>
            <input v-model="form.jabatan" type="text" placeholder="Jabatan/posisi"
              class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700
                     bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                     focus:outline-none focus:ring-2 focus:ring-yellow-500" />
          </div>

          <!-- Divisi -->
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Divisi</label>
            <select v-model="form.divisi"
              class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700
                     bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                     focus:outline-none focus:ring-2 focus:ring-yellow-500">
              <option value="">— Pilih divisi —</option>
              <option>Tanaman</option>
              <option>Pabrikasi</option>
              <option>Instalasi</option>
              <option>Quality Assurance</option>
              <option>IT</option>
              <option>Umum / HRD</option>
              <option>Akuntansi & Keuangan</option>
              <option>Direksi</option>
            </select>
          </div>

          <!-- Area Kebun -->
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
              Area Kebun
              <span class="text-gray-400 font-normal">(untuk pembatasan akses ABAC)</span>
            </label>
            <input v-model="form.area_kebun" type="text" placeholder="Contoh: A, B, A1, B2"
              class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700
                     bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                     focus:outline-none focus:ring-2 focus:ring-yellow-500" />
          </div>

          <!-- Phone -->
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">No. HP</label>
            <input v-model="form.phone" type="tel" placeholder="08xx-xxxx-xxxx"
              class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700
                     bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                     focus:outline-none focus:ring-2 focus:ring-yellow-500" />
          </div>

        </div>

        <!-- Status aktif -->
        <div class="flex items-center gap-3 pt-1">
          <button
            type="button"
            @click="form.is_active = !form.is_active"
            :class="[
              'relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
              form.is_active ? 'bg-yellow-500' : 'bg-gray-300 dark:bg-gray-600',
            ]"
          >
            <span :class="[
              'inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform',
              form.is_active ? 'translate-x-4.5' : 'translate-x-0.5',
            ]" />
          </button>
          <span class="text-sm text-gray-600 dark:text-gray-300">
            Akun {{ form.is_active ? 'Aktif' : 'Nonaktif' }}
          </span>
        </div>
      </div>

      <!-- ============================================================
           ROLE & ABAC
      ============================================================ -->
      <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-6 space-y-4">
        <div class="flex items-center gap-2 mb-2">
          <ShieldIcon class="w-4 h-4 text-yellow-500" />
          <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Role & Hak Akses (ABAC)</h2>
        </div>

        <!-- Role selector -->
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">
            Role <span class="text-red-500">*</span>
            <span class="text-gray-400 font-normal ml-1">— pilih satu atau lebih</span>
          </label>
          <p v-if="errors.roles" class="text-xs text-red-500 mb-2">{{ errors.roles[0] }}</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button
              v-for="role in roles"
              :key="role.id"
              type="button"
              @click="toggleRole(role.id)"
              :class="[
                'flex items-start gap-3 p-3 rounded-xl border-2 text-left transition-all',
                hasRole(role.id)
                  ? (roleBadgeClass[role.color] ?? roleBadgeClass.gray) + ' border-opacity-100'
                  : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600',
              ]"
            >
              <div :class="[
                'w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 mt-0.5',
                hasRole(role.id) ? 'bg-yellow-500 border-yellow-500' : 'border-gray-300 dark:border-gray-600',
              ]">
                <svg v-if="hasRole(role.id)" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ role.display_name }}</p>
                <p class="text-xs text-gray-400 mt-0.5 leading-relaxed">{{ role.description }}</p>
              </div>
            </button>
          </div>
        </div>

        <!-- Atribut ABAC -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100 dark:border-gray-800">
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
              Shift Kerja
              <span class="text-gray-400 font-normal">(opsional, untuk policy waktu)</span>
            </label>
            <select v-model="form.attributes.shift"
              class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700
                     bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                     focus:outline-none focus:ring-2 focus:ring-yellow-500">
              <option value="">— Tidak ada batasan —</option>
              <option value="pagi">Pagi (06:00–14:00)</option>
              <option value="siang">Siang (14:00–22:00)</option>
              <option value="malam">Malam (22:00–06:00)</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
              Level Akses Data
            </label>
            <select v-model="form.attributes.level_akses"
              class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700
                     bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                     focus:outline-none focus:ring-2 focus:ring-yellow-500">
              <option value="">— Standar —</option>
              <option value="publik">Publik</option>
              <option value="internal">Internal</option>
              <option value="rahasia">Rahasia</option>
            </select>
          </div>
        </div>

        <!-- Catatan admin -->
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Catatan Admin</label>
          <textarea
            v-model="form.notes"
            rows="2"
            placeholder="Catatan internal tentang user ini..."
            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700
                   bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                   focus:outline-none focus:ring-2 focus:ring-yellow-500 resize-none"
          />
        </div>
      </div>

      <!-- SUBMIT -->
      <div class="flex items-center justify-end gap-3">
        <button
          type="button"
          @click="router.back()"
          class="px-5 py-2 text-sm font-medium text-gray-600 dark:text-gray-300
                 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700
                 rounded-xl transition-colors"
        >
          Batal
        </button>
        <button
          type="submit"
          :disabled="isSaving || form.roles.length === 0"
          class="flex items-center gap-2 px-6 py-2 text-sm font-semibold text-white
                 bg-yellow-500 hover:bg-yellow-600 disabled:opacity-60 disabled:cursor-not-allowed
                 rounded-xl shadow-sm transition-colors"
        >
          <SaveIcon class="w-4 h-4" />
          {{ isSaving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah User') }}
        </button>
      </div>

    </form>
  </div>
</template>
