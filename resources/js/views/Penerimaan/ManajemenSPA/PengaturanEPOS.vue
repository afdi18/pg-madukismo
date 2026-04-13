<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import ModalCard from '@/components/ModalCard.vue'
import { useToast } from '@/composables/useToast'
import { CheckIcon, XIcon, Edit3Icon } from 'lucide-vue-next'

const devices = ref<any[]>([])
const posList = ref<any[]>([])
const loading = ref(false)

const toast = useToast()

// edit device modal
const showEditModal = ref(false)
const editingDevice = ref<any | null>(null)
const editMeter = ref<number | null>(null)
const editHari = ref<number | null>(null)
const editSaving = ref(false)

function openEdit(device: any) {
  editingDevice.value = device
  editMeter.value = device.METER_JARAK_MAKS != null ? Number(device.METER_JARAK_MAKS) : null
  editHari.value = device.HARI_EXPIRED_SPA != null ? Number(device.HARI_EXPIRED_SPA) : null
  showEditModal.value = true
}

function closeEdit() {
  showEditModal.value = false
  editingDevice.value = null
}

async function saveDeviceEdit() {
  if (!editingDevice.value) return
  editSaving.value = true
  try {
    const payload: any = {
      meter_jarak_maks: editMeter.value,
      hari_expired_spa: editHari.value,
    }
    const { data } = await axios.put(`/api/epos/devices/${editingDevice.value.ID_DEVICE}`, payload)
    if (data && data.ok) {
      // update local devices array
      const idx = devices.value.findIndex((x: any) => x.ID_DEVICE === editingDevice.value.ID_DEVICE)
      if (idx !== -1) devices.value[idx] = { ...devices.value[idx], ...data.device }
      toast.success('Perangkat diperbarui')
      closeEdit()
    } else if (data && data.error) {
      toast.error(data.error)
    }
  } catch (e) {
    console.error(e)
    const err: any = e
    toast.error(
      err?.response?.data?.error
      || err?.response?.data?.message
      || 'Gagal menyimpan perangkat'
    )
  } finally {
    editSaving.value = false
  }
}

const showPosModal = ref(false)
const posFilter = ref('')
const showPosFormModal = ref(false)
const posFormMode = ref<'create' | 'edit'>('create')
const posFormSaving = ref(false)
const posFormErrors = ref<any>({})
const reopenPosListAfterForm = ref(false)
const posForm = ref<any>({
  IDPOS: null,
  NMPOS: '',
  LAT: '',
  LONG: '',
})

const filteredPos = computed(() => {
  if (!posFilter.value) return posList.value
  const q = posFilter.value.toLowerCase()
  return posList.value.filter((p: any) => (p.NMPOS || '').toLowerCase().includes(q) || String(p.IDPOS).includes(q))
})

async function loadDevices() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/epos/devices')
    devices.value = data.data || []
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function loadPos() {
  try {
    const { data } = await axios.get('/api/epos/pos')
    posList.value = data.data || []
  } catch (e) {
    console.error(e)
  }
}

// Confirm modal state for activate/deactivate
const showConfirm = ref(false)
const pendingToggleDevice = ref<any | null>(null)
const pendingToggleNewVal = ref<number>(0)
const pendingConfirmMessage = ref('')

function toggleActive(device: any) {
  pendingToggleDevice.value = device
  pendingToggleNewVal.value = device.AKTIF ? 0 : 1
  pendingConfirmMessage.value = device.AKTIF ? 'Nonaktifkan perangkat ini?' : 'Aktifkan perangkat ini?'
  showConfirm.value = true
}

async function confirmToggleYes() {
  if (!pendingToggleDevice.value) return
  const device = pendingToggleDevice.value
  const newVal = pendingToggleNewVal.value
  showConfirm.value = false
  pendingToggleDevice.value = null
  try {
    const { data } = await axios.put(`/api/epos/devices/${device.ID_DEVICE}/active`, { aktif: newVal })
    if (data && data.ok) {
      device.AKTIF = data.device?.AKTIF ?? newVal
      device.HARI_EXPIRED_SPA = data.device?.HARI_EXPIRED_SPA ?? device.HARI_EXPIRED_SPA
      toast.success('Status perangkat diperbarui')
    } else {
      toast.info('Tidak ada perubahan pada perangkat')
    }
  } catch (e) {
    console.error(e)
    toast.error('Gagal mengubah status device')
  }
}

function confirmToggleNo() {
  showConfirm.value = false
  pendingToggleDevice.value = null
}

onMounted(() => {
  loadDevices()
  loadPos()
})

function openPosModal() {
  posFilter.value = ''
  showPosModal.value = true
}

function closePosModal() {
  showPosModal.value = false
}

function openCreatePos() {
  reopenPosListAfterForm.value = showPosModal.value
  showPosModal.value = false
  posFormMode.value = 'create'
  posFormErrors.value = {}
  posForm.value = {
    IDPOS: null,
    NMPOS: '',
    LAT: '',
    LONG: '',
  }
  showPosFormModal.value = true
}

function openEditPos(p: any) {
  reopenPosListAfterForm.value = showPosModal.value
  showPosModal.value = false
  posFormMode.value = 'edit'
  posFormErrors.value = {}
  posForm.value = {
    IDPOS: p.IDPOS,
    NMPOS: p.NMPOS ?? '',
    LAT: p.LAT ?? '',
    LONG: p.LONG ?? '',
  }
  showPosFormModal.value = true
}

function closePosFormModal() {
  showPosFormModal.value = false
  if (reopenPosListAfterForm.value) {
    showPosModal.value = true
  }
}

async function savePosForm() {
  posFormErrors.value = {}

  if (!String(posForm.value.NMPOS ?? '').trim()) {
    posFormErrors.value.NMPOS = 'Nama POS wajib diisi'
  }
  if (posForm.value.LAT === '' || posForm.value.LAT === null || Number.isNaN(Number(posForm.value.LAT))) {
    posFormErrors.value.LAT = 'Latitude harus berupa angka'
  }
  if (posForm.value.LONG === '' || posForm.value.LONG === null || Number.isNaN(Number(posForm.value.LONG))) {
    posFormErrors.value.LONG = 'Longitude harus berupa angka'
  }

  if (Object.keys(posFormErrors.value).length > 0) {
    toast.error('Periksa kembali data POS')
    return
  }

  posFormSaving.value = true
  try {
    const payload = {
      NMPOS: String(posForm.value.NMPOS).trim(),
      LAT: Number(posForm.value.LAT),
      LONG: Number(posForm.value.LONG),
    }

    if (posFormMode.value === 'create') {
      const { data } = await axios.post('/api/epos/pos', payload)
      if (!data?.ok) {
        toast.error(data?.error || 'Gagal menambah POS')
        return
      }
      toast.success('POS berhasil ditambahkan')
    } else {
      const { data } = await axios.put(`/api/epos/pos/${posForm.value.IDPOS}`, payload)
      if (!data?.ok) {
        toast.error(data?.error || 'Gagal memperbarui POS')
        return
      }
      toast.success('POS berhasil diperbarui')
    }

    await loadPos()
    reopenPosListAfterForm.value = true
    closePosFormModal()
  } catch (e: any) {
    console.error(e)
    toast.error(
      e?.response?.data?.error
      || e?.response?.data?.message
      || 'Gagal menyimpan POS'
    )
  } finally {
    posFormSaving.value = false
  }
}

function centerOnPos(p: any) {
  const lat = Number(p.LAT)
  const lng = Number(p.LONG)
  if (Number.isNaN(lat) || Number.isNaN(lng)) {
    toast.error('Koordinat POS tidak valid')
    return
  }
  window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank')
}

async function copyCoords(p: any) {
  try {
    const text = `${p.LAT}, ${p.LONG}`
    await navigator.clipboard.writeText(text)
    toast.success('Koordinat disalin: ' + text)
  } catch (e) {
    console.error(e)
    toast.error('Gagal menyalin koordinat')
  }
}
</script>

<template>
  <div class="space-y-3">

    <div class="mt-2 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
      <div class="mb-3 flex items-center justify-between gap-3">
        <h3 class="font-semibold">Perangkat Terdaftar</h3>
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:text-slate-200">
            {{ posList.length }} POS
          </span>
          <button
            @click.prevent="openPosModal"
            class="px-3 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded text-sm"
          >
            Lihat Daftar POS
          </button>
        </div>
      </div>
      <div v-if="loading" class="text-sm text-slate-500">Memuat perangkat...</div>
      <div v-else class="overflow-x-auto rounded-lg">
        <table class="w-full text-sm table-auto">
          <thead class="bg-slate-50 dark:bg-slate-800">
            <tr class="text-left text-xs text-slate-500 uppercase">
              <th class="px-4 py-3">Kode</th>
              <th class="px-4 py-3">Email</th>
              <th class="px-4 py-3">POS</th>
              <th class="px-4 py-3">Maks Jarak (m)</th>
              <th class="px-4 py-3">Aktif</th>
              <th class="px-4 py-3">Hari Expired SPA</th>
              <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in devices" :key="d.ID_DEVICE" class="hover:bg-slate-50 dark:hover:bg-slate-800">
              <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-200">{{ d.KODE_DEVICE }}</td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ d.GMAIL }}</td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ d.NMPOS ?? d.ID_POS }}</td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ d.METER_JARAK_MAKS ?? '-' }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span v-if="d.AKTIF" class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full text-xs font-semibold"><CheckIcon class="w-3 h-3" />Ya</span>
                  <span v-else class="inline-flex items-center gap-2 bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full text-xs font-medium"><XIcon class="w-3 h-3" />Tidak</span>
                  <button @click="toggleActive(d)" class="ml-2 px-2 py-1 text-xs rounded bg-yellow-500 text-white">{{ d.AKTIF ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                </div>
              </td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ d.HARI_EXPIRED_SPA ?? '-' }}</td>
              <td class="px-4 py-3 text-right">
                <button @click.prevent="openEdit(d)" class="inline-flex items-center gap-2 px-3 py-1.5 rounded text-sm font-medium bg-sky-600 hover:bg-sky-700 text-white">
                  <Edit3Icon class="w-4 h-4" /> <span>Edit</span>
                </button>
              </td>
            </tr>
            <tr v-if="devices.length === 0">
              <td colspan="7" class="px-4 py-6 text-center text-slate-500">Belum ada perangkat terdaftar.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Confirm Modal -->
    <div v-if="showConfirm" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative max-w-md w-full mx-4 bg-white dark:bg-gray-900 rounded-lg shadow-lg overflow-hidden">
        <div class="p-4">
          <h3 class="text-lg font-semibold">Konfirmasi</h3>
          <p class="text-sm text-gray-600 mt-2">{{ pendingConfirmMessage }}</p>
          <div class="mt-4 text-right flex gap-2">
            <button @click="confirmToggleNo" class="px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded">Batal</button>
            <button @click="confirmToggleYes" class="px-3 py-2 bg-yellow-500 text-white rounded">Ya, Lanjutkan</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Top-level Edit Device Modal using ModalCard -->
  <ModalCard v-model="showEditModal" sizeClass="max-w-2xl">
    <template #header>
      <div class="p-6 border-b dark:border-gray-800">
        <h3 class="text-xl font-semibold">Edit Perangkat</h3>
        <p class="text-sm text-gray-500 mt-1">Ubah pengaturan jarak maksimum dan masa berlaku SPA untuk perangkat ini.</p>
      </div>
    </template>

    <div class="p-6">
      <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm text-gray-600">Maks Jarak (meter)</label>
          <input type="number" v-model.number="editMeter" class="w-full mt-2 px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-800" />
        </div>
        <div>
          <label class="text-sm text-gray-600">Hari Expired SPA</label>
          <input type="number" v-model.number="editHari" class="w-full mt-2 px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-800" />
        </div>
      </div>
    </div>

    <template #footer>
      <div class="p-6 border-t dark:border-gray-800 flex justify-end gap-3">
        <button @click="closeEdit" class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm bg-white dark:bg-gray-800">Tutup</button>
        <button @click="saveDeviceEdit" :disabled="editSaving" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm">{{ editSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}</button>
      </div>
    </template>
  </ModalCard>

    <!-- POS List Modal -->
    <div v-if="showPosModal" class="fixed inset-0 z-50 flex items-center justify-center">
  <div class="absolute inset-0 bg-black/50" @click="closePosModal"></div>
  <div class="relative max-w-3xl w-full mx-4 bg-white dark:bg-gray-900 rounded-lg shadow-lg overflow-hidden">
    <div class="p-4 border-b dark:border-gray-800 flex items-center justify-between">
      <h3 class="text-lg font-semibold">Daftar POS Pantau</h3>
      <div class="flex items-center gap-2">
        <input v-model="posFilter" placeholder="Cari POS..." class="px-2 py-1 text-sm border rounded" />
        <button @click="openCreatePos" class="px-3 py-1 bg-emerald-600 text-white rounded text-sm">Tambah POS</button>
        <button @click="closePosModal" class="px-3 py-1 bg-gray-100 dark:bg-gray-800 rounded">Tutup</button>
      </div>
    </div>
    <div class="p-4">
      <div v-if="filteredPos.length === 0" class="text-sm text-gray-500">Tidak ada POS cocok.</div>
      <div v-else class="space-y-2">
        <div v-for="p in filteredPos" :key="p.IDPOS" class="flex items-center justify-between p-2 border rounded">
          <div>
            <div class="font-medium text-slate-700">{{ p.NMPOS }}</div>
            <div class="text-xs text-gray-500">ID: {{ p.IDPOS }} — Lat: {{ p.LAT }}, Long: {{ p.LONG }}</div>
          </div>
          <div class="flex items-center gap-2">
            <button @click.prevent="openEditPos(p)" class="px-3 py-1 bg-amber-500 text-white rounded text-sm">Edit</button>
            <button @click.prevent="centerOnPos(p)" class="px-3 py-1 bg-sky-600 text-white rounded text-sm">Pusat</button>
            <button @click.prevent="copyCoords(p)" class="px-3 py-1 bg-gray-100 dark:bg-gray-800 rounded text-sm">Salin</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>

  <!-- POS Form Modal (Tambah/Edit) -->
  <ModalCard v-model="showPosFormModal" sizeClass="max-w-lg">
    <template #header>
      <div class="p-6 border-b dark:border-gray-800">
        <h3 class="text-xl font-semibold">{{ posFormMode === 'create' ? 'Tambah POS Pantau' : 'Edit POS Pantau' }}</h3>
        <p class="text-sm text-gray-500 mt-1">Isi nama POS dan koordinat lokasi.</p>
      </div>
    </template>

    <div class="p-6 space-y-4">
      <div>
        <label class="text-sm text-gray-600">Nama POS</label>
        <input v-model="posForm.NMPOS" type="text" class="w-full mt-2 px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-800" />
        <div v-if="posFormErrors.NMPOS" class="text-xs text-red-500 mt-1">{{ posFormErrors.NMPOS }}</div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm text-gray-600">Latitude</label>
          <input v-model="posForm.LAT" type="number" step="any" class="w-full mt-2 px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-800" />
          <div v-if="posFormErrors.LAT" class="text-xs text-red-500 mt-1">{{ posFormErrors.LAT }}</div>
        </div>
        <div>
          <label class="text-sm text-gray-600">Longitude</label>
          <input v-model="posForm.LONG" type="number" step="any" class="w-full mt-2 px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-800" />
          <div v-if="posFormErrors.LONG" class="text-xs text-red-500 mt-1">{{ posFormErrors.LONG }}</div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="p-6 border-t dark:border-gray-800 flex justify-end gap-3">
        <button @click="closePosFormModal" class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm bg-white dark:bg-gray-800">Batal</button>
        <button @click="savePosForm" :disabled="posFormSaving" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm disabled:opacity-50">
          {{ posFormSaving ? 'Menyimpan...' : (posFormMode === 'create' ? 'Tambah POS' : 'Simpan Perubahan') }}
        </button>
      </div>
    </template>
  </ModalCard>
</template>

