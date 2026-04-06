<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import { AlertTriangleIcon, CheckCircle2Icon } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

type OperatorKondisi = '>' | '<' | '>=' | '<=' | 'BETWEEN' | 'NONE'

interface Stasiun {
  id: number
  nama_stasiun: string
}

interface Parameter {
  id: number
  stasiun_id: number
  nama_parameter: string
  operator_kondisi: OperatorKondisi
  batas_bawah: number | null
  batas_atas: number | null
  is_aktif: boolean
  satuan?: string | null
  unit?: string | null
  suffix?: string | null
}

interface ParameterInput {
  parameter_id: number
  nilai_aktual: string
}

interface QaHistoryItem {
  id: number
  tanggal: string
  jam: string
  shift: number
  petugas: string
  stasiun_id?: number
  details?: Array<{ status_alert: boolean }>
}

const activeStasiun = ref<number | null>(null)
const loadingStasiun = ref(false)
const loadingParameter = ref(false)
const loadingHistory = ref(false)
const saving = ref(false)
const tabScrollContainer = ref<HTMLElement | null>(null)
const canScrollTabLeft = ref(false)
const canScrollTabRight = ref(false)
const showTabScrollArrows = ref(false)
const toast = useToast()

const stasiunList = ref<Stasiun[]>([])
const parameterList = ref<Parameter[]>([])
const historyListByStasiun = reactive(new Map<number, QaHistoryItem[]>())
const historyMetaByStasiun = reactive(new Map<number, { current_page: number; last_page: number; total: number; per_page: number }>())
const submitPreview = ref<string>('')

const form = reactive({
  tanggal: new Date().toISOString().slice(0, 10),
  jam: new Date().toTimeString().slice(0, 5),
  shift: '1',
  petugas: '',
  parameters: [] as ParameterInput[],
})

function openNativePicker(event: Event) {
  const input = event.currentTarget as (HTMLInputElement & { showPicker?: () => void }) | null
  if (!input || typeof input.showPicker !== 'function') return

  try {
    input.showPicker()
  } catch {
    input.focus()
  }
}

const hasSelectedStasiun = computed(() => !!activeStasiun.value)

const currentHistoryList = computed(() => {
  return activeStasiun.value ? (historyListByStasiun.get(activeStasiun.value) ?? []) : []
})

const currentHistoryMeta = computed(() => {
  return activeStasiun.value 
    ? (historyMetaByStasiun.get(activeStasiun.value) ?? { current_page: 1, last_page: 1, total: 0, per_page: 10 })
    : { current_page: 1, last_page: 1, total: 0, per_page: 10 }
})

const activeStasiunName = computed(() => {
  if (!activeStasiun.value) return '-'
  return stasiunList.value.find((item) => item.id === activeStasiun.value)?.nama_stasiun ?? '-'
})

const orderedParameterList = computed(() => {
  // Sort by ID (reflects seeder insertion order) so related params stay grouped:
  // Gilingan: Gil.1 Brix → Pol → HK → Nilai NPP, Gil.2 Brix → Pol → HK, ...
  // Ketel:    APK group → EKM 1 group → EKM 2 group → ...
  // Pemurnian/Penguapan: NE Brix → Pol → HK, NK Brix → Pol → HK, ...
  return [...parameterList.value].sort((a, b) => a.id - b.id)
})

const nilaiByParameterId = computed(() => {
  const map = new Map<number, string>()
  for (const item of form.parameters) {
    map.set(item.parameter_id, item.nilai_aktual)
  }
  return map
})

const parameterById = computed(() => {
  const map = new Map<number, Parameter>()
  for (const item of parameterList.value) {
    map.set(item.id, item)
  }
  return map
})

function parameterGridClass(parameter: Parameter): string {
  const name = parameter.nama_parameter.toLowerCase()

  // Paksa pembuka grup tetap mulai dari kolom 1.
  // Contoh:
  // - Kap. Gilingan di row pertama, lalu Gil. 1 Brix turun ke row berikutnya kolom 1.
  // - Gil. 2 Brix, NE Brix, NK Brix, dst juga selalu mulai dari kolom 1.
  // - Ketel: awal grup EKM/KCC tetap dimulai dari kolom 1.
  const isBrixStarter = name.includes('brix')
  const isKetelGroupStarter = /^ph ekm\s*\d+$/i.test(parameter.nama_parameter) || /^ph kcc$/i.test(parameter.nama_parameter)

  return isBrixStarter || isKetelGroupStarter ? 'col-start-1' : ''
}

function getTargetText(parameter: Parameter): string {
  let bottom = parameter.batas_bawah
  let top = parameter.batas_atas

  // bottom = bottom !== null ? Number(bottom.toFixed(2)) : null
  // top = top !== null ? Number(top.toFixed(2)) : null;

  return parameter.operator_kondisi === 'BETWEEN'
    ? `Target: ${bottom ?? '-'} - ${top ?? '-'}`
    : parameter.operator_kondisi === 'NONE'
      ? 'Target: Tidak ada batas'
      : parameter.operator_kondisi === '>' || parameter.operator_kondisi === '>='
        ? `Target: ${parameter.operator_kondisi} ${bottom ?? '-'}`
        : `Target: ${parameter.operator_kondisi} ${top ?? '-'}`
}

function getSatuan(parameter: Parameter): string {
  return parameter.satuan ?? parameter.unit ?? parameter.suffix ?? ''
}

function isBrixParameter(parameterName: string): boolean {
  return /\bbrix$/i.test(parameterName)
}

function isPolParameter(parameterName: string): boolean {
  return /\bpol$/i.test(parameterName)
}

function isHkParameter(parameterName: string): boolean {
  return /\bhk$/i.test(parameterName)
}

function getParameterGroupKey(parameterName: string): string {
  return parameterName
    .replace(/\s+(brix|pol|hk)$/i, '')
    .trim()
    .toLowerCase()
}

function findParameterByType(groupKey: string, type: 'brix' | 'pol' | 'hk'): Parameter | undefined {
  return parameterList.value.find((parameter) => {
    if (getParameterGroupKey(parameter.nama_parameter) !== groupKey) return false

    if (type === 'brix') return isBrixParameter(parameter.nama_parameter)
    if (type === 'pol') return isPolParameter(parameter.nama_parameter)

    return isHkParameter(parameter.nama_parameter)
  })
}

function formatAutoCalculatedValue(value: number): string {
  const rounded = Number(value.toFixed(2))
  return Number.isInteger(rounded) ? String(rounded) : rounded.toFixed(2)
}

function isAutoCalculatedHk(parameter: Parameter): boolean {
  if (!isHkParameter(parameter.nama_parameter)) return false

  const groupKey = getParameterGroupKey(parameter.nama_parameter)
  return !!findParameterByType(groupKey, 'brix') && !!findParameterByType(groupKey, 'pol')
}

function syncAutoCalculatedHk(changedParameterId: number) {
  const changedParameter = parameterList.value.find((parameter) => parameter.id === changedParameterId)
  if (!changedParameter) return

  const isBrixOrPol = isBrixParameter(changedParameter.nama_parameter) || isPolParameter(changedParameter.nama_parameter)
  if (!isBrixOrPol) return

  const groupKey = getParameterGroupKey(changedParameter.nama_parameter)
  const brixParameter = findParameterByType(groupKey, 'brix')
  const polParameter = findParameterByType(groupKey, 'pol')
  const hkParameter = findParameterByType(groupKey, 'hk')

  if (!brixParameter || !polParameter || !hkParameter) return

  const brixValue = Number(getNilai(brixParameter.id))
  const polValue = Number(getNilai(polParameter.id))
  const hkTarget = form.parameters.find((item) => item.parameter_id === hkParameter.id)

  if (!hkTarget) return

  const hasValidBrix = getNilai(brixParameter.id) !== '' && !Number.isNaN(brixValue) && brixValue !== 0
  const hasValidPol = getNilai(polParameter.id) !== '' && !Number.isNaN(polValue)

  if (!hasValidBrix || !hasValidPol) {
    hkTarget.nilai_aktual = ''
    return
  }

  hkTarget.nilai_aktual = formatAutoCalculatedValue((polValue / brixValue) * 100)
}

function getNilai(parameterId: number): string {
  return nilaiByParameterId.value.get(parameterId) ?? ''
}

function setNilai(parameterId: number, value: string) {
  const target = form.parameters.find((item) => item.parameter_id === parameterId)
  if (target) {
    target.nilai_aktual = value
    syncAutoCalculatedHk(parameterId)
  }
}

function evaluateStatus(parameter: Parameter, rawValue: string): 'empty' | 'safe' | 'alert' {
  if (rawValue === '' || rawValue === null || rawValue === undefined) return 'empty'

  const value = Number(rawValue)
  if (Number.isNaN(value)) return 'alert'

  const bottom = parameter.batas_bawah
  const top = parameter.batas_atas

  const isAlert = (() => {
    switch (parameter.operator_kondisi) {
      case '>':
        return bottom === null ? false : value <= bottom
      case '>=':
        return bottom === null ? false : value < bottom
      case '<':
        return top === null ? false : value >= top
      case '<=':
        return top === null ? false : value > top
      case 'BETWEEN':
        if (bottom === null || top === null) return false
        return value < bottom || value > top
      case 'NONE':
      default:
        return false
    }
  })()

  return isAlert ? 'alert' : 'safe'
}

function getStatus(parameterId: number): 'empty' | 'safe' | 'alert' {
  const parameter = parameterById.value.get(parameterId)
  if (!parameter) return 'empty'
  return evaluateStatus(parameter, getNilai(parameterId))
}

function inputClassByStatus(status: 'empty' | 'safe' | 'alert'): string {
  if (status === 'alert') {
    return 'border-red-500 focus:border-red-600 bg-red-50/50 dark:bg-red-500/10 text-red-900 dark:text-red-100'
  }
  if (status === 'safe') {
    return 'border-green-500 focus:border-green-600 bg-green-50/50 dark:bg-green-500/10 text-green-900 dark:text-green-100'
  }
  return 'border-gray-300 dark:border-gray-600 focus:border-yellow-500 focus:ring-yellow-500/20'
}

async function fetchStasiun() {
  loadingStasiun.value = true
  try {
    const { data } = await axios.get('/api/lab-qa/master/stasiun')
    stasiunList.value = (data.data ?? []).filter((item: Stasiun) => {
      if (item.nama_stasiun.startsWith('Alkohol -')) return false
      if (item.nama_stasiun === 'Produksi Alkohol') return false
      return true
    })

    if (!activeStasiun.value && stasiunList.value.length > 0) {
      activeStasiun.value = stasiunList.value[0].id
    }

    await nextTick()
    updateTabScrollState()
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? 'Gagal memuat data stasiun.')
  } finally {
    loadingStasiun.value = false
  }
}

async function fetchParametersByStasiun(stasiunId: number) {
  loadingParameter.value = true
  try {
    const { data } = await axios.get('/api/lab-qa/master/parameters', {
      params: { stasiun_id: stasiunId },
    })

    parameterList.value = (data.data ?? []).filter((item: Parameter) => item.is_aktif)

    form.parameters = parameterList.value.map((parameter) => ({
      parameter_id: parameter.id,
      nilai_aktual: '',
    }))
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? 'Gagal memuat parameter stasiun.')
    parameterList.value = []
    form.parameters = []
  } finally {
    loadingParameter.value = false
  }
}

const isHeaderValid = computed(() => {
  return !!(activeStasiun.value && form.tanggal && form.jam && isJam24Valid.value && form.shift && form.petugas.trim())
})

const isJam24Valid = computed(() => /^([01]\d|2[0-3]):[0-5]\d$/.test(form.jam))

const hasAnyParameterValue = computed(() => {
  for (const value of nilaiByParameterId.value.values()) {
    if (value !== '') return true
  }
  return false
})

const isSubmitDisabled = computed(() => saving.value || !activeStasiun.value)

function alertCount(item: QaHistoryItem): number {
  return item.details?.filter((detail) => detail.status_alert).length ?? 0
}

function statusBadgeClass(alertTotal: number): string {
  return alertTotal > 0
    ? 'bg-red-100/80 dark:bg-red-900/40 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800'
    : 'bg-green-100/80 dark:bg-green-900/40 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800'
}

function formatTanggal(tanggal: string): string {
  try {
    return new Date(tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
  } catch {
    return tanggal
  }
}

function resolveRequestErrorMessage(error: any, fallback: string): string {
  const status = error?.response?.status
  const dataMessage = error?.response?.data?.message
  const errors = (error?.response?.data?.errors ?? {}) as Record<string, string[]>
  const firstValidationError = Object.values(errors)[0]?.[0]
  if (dataMessage) return status ? `[${status}] ${dataMessage}` : dataMessage
  if (firstValidationError) return status ? `[${status}] ${firstValidationError}` : String(firstValidationError)
  return fallback
}

async function fetchHistory(stasiunId: number, page = 1, append = false) {
  loadingHistory.value = true
  try {
    const { data } = await axios.get('/api/lab-qa', {
      params: { per_page: 10, page, stasiun_id: stasiunId },
    })

    const rows: QaHistoryItem[] = data?.data ?? []
    const existing = historyListByStasiun.get(stasiunId) ?? []
    historyListByStasiun.set(stasiunId, append ? [...existing, ...rows] : rows)
    
    historyMetaByStasiun.set(stasiunId, {
      total: data?.meta?.total ?? 0,
      current_page: data?.meta?.current_page ?? page,
      last_page: data?.meta?.last_page ?? 1,
      per_page: 10,
    })
  } catch (error: any) {
    toast.error(resolveRequestErrorMessage(error, 'Gagal memuat riwayat data QA.'))
  } finally {
    loadingHistory.value = false
  }
}

watch(
  () => activeStasiun.value,
  async (stasiunId) => {
    parameterList.value = []
    form.parameters = []

    if (!stasiunId) return
    await Promise.all([
      fetchParametersByStasiun(stasiunId),
      fetchHistory(stasiunId, 1, false),
    ])
  }
)

async function submitForm() {
  if (saving.value) return

  if (!activeStasiun.value) {
    toast.error('Stasiun QA belum dipilih.')
    return
  }

  if (!form.petugas.trim() || !form.tanggal || !form.jam || !form.shift) {
    toast.error('Lengkapi data header (tanggal, jam, shift, petugas) sebelum menyimpan.')
    return
  }

  if (!isJam24Valid.value) {
    toast.error('Format jam harus 24 jam (HH:mm), contoh 07:30.')
    return
  }

  if (!hasAnyParameterValue.value) {
    toast.error('Isi minimal satu nilai parameter sebelum menyimpan.')
    return
  }

  saving.value = true
  try {
    const filledParameters = form.parameters
      .filter((item) => item.nilai_aktual !== '')
      .map((item) => ({
        parameter_id: item.parameter_id,
        nilai_aktual: Number(item.nilai_aktual),
      }))

    const payload = {
      tanggal: form.tanggal,
      jam: `${form.jam}:00`,
      shift: Number(form.shift),
      petugas: form.petugas.trim(),
      stasiun_id: activeStasiun.value,
      parameters: filledParameters,
    }

    const { data } = await axios.post('/api/lab-qa', payload)

    toast.success(data?.message ?? 'Data QA berhasil disimpan.')

    submitPreview.value = JSON.stringify(payload, null, 2)

    form.parameters = form.parameters.map((item) => ({
      ...item,
      nilai_aktual: '',
    }))

    await fetchHistory(activeStasiun.value, 1, false)
  } catch (error: any) {
    toast.error(resolveRequestErrorMessage(error, 'Gagal menyimpan data QA.'))
  } finally {
    saving.value = false
  }
}

function setActiveStasiun(stasiunId: number) {
  activeStasiun.value = stasiunId
}

function updateTabScrollState() {
  const container = tabScrollContainer.value
  if (!container) {
    showTabScrollArrows.value = false
    canScrollTabLeft.value = false
    canScrollTabRight.value = false
    return
  }

  const maxScrollLeft = container.scrollWidth - container.clientWidth
  showTabScrollArrows.value = maxScrollLeft > 2
  const current = Math.max(0, container.scrollLeft)
  canScrollTabLeft.value = showTabScrollArrows.value && current > 2
  canScrollTabRight.value = showTabScrollArrows.value && current < maxScrollLeft - 2
}

function scrollTabMenu(direction: 'left' | 'right') {
  if (!tabScrollContainer.value) return

  if (direction === 'left' && !canScrollTabLeft.value) return
  if (direction === 'right' && !canScrollTabRight.value) return

  const delta = direction === 'left' ? -240 : 240
  tabScrollContainer.value.scrollBy({ left: delta, behavior: 'smooth' })
  window.setTimeout(updateTabScrollState, 220)
}

onMounted(async () => {
  await fetchStasiun()
  updateTabScrollState()
  window.addEventListener('resize', updateTabScrollState)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateTabScrollState)
})
</script>

<template>
  <div class="space-y-5">
    <div>
      <h1 class="text-xl font-bold text-gray-800 dark:text-white">Entri Data QA Pabrik Gula</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
        Parameter produksi pabrik gula: gilingan, ketel, pemurnian, penguapan, masakan, puteran, produksi, dan neraca warna
      </p>
    </div>

    <!-- Tab Navigation - Modern Horizontal Scroll -->
    <div class="sticky -top-2 z-30 mb-6 bg-white/95 dark:bg-gray-900/90 backdrop-blur-sm rounded-xl border border-gray-200/70 dark:border-gray-800/70 shadow-md">
      <div class="px-3 sm:px-4 lg:px-6 py-0">
        <div v-if="loadingStasiun" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
          Memuat daftar stasiun...
        </div>

        <div v-else class="relative">
          <button
            v-if="showTabScrollArrows"
            type="button"
            @click="scrollTabMenu('left')"
            :disabled="!canScrollTabLeft"
            :class="[
              'absolute left-0 top-1/2 z-10 -translate-y-1/2 rounded-full border px-2 py-1 shadow-sm transition',
              canScrollTabLeft
                ? 'border-gray-200 bg-white/95 text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-900/95 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white'
                : 'cursor-not-allowed border-gray-100 bg-gray-100/80 text-gray-300 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-600',
            ]"
            aria-label="Geser tab ke kiri"
          >
            ‹
          </button>

          <div
            ref="tabScrollContainer"
            @scroll="updateTabScrollState"
            :class="[
              'flex items-center gap-0.5 sm:gap-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden',
              showTabScrollArrows ? 'mx-8' : 'mx-0',
            ]"
          >
            <button
              v-for="stasiun in stasiunList"
              :key="stasiun.id"
              @click="setActiveStasiun(stasiun.id)"
              :class="[
                'relative px-3 sm:px-4 lg:px-5 py-4 text-xs sm:text-sm font-medium transition-all duration-300 shrink-0 whitespace-nowrap',
                'after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:transition-all after:duration-300',
                activeStasiun === stasiun.id
                  ? 'text-yellow-600 dark:text-yellow-400 after:bg-gradient-to-r after:from-yellow-400 after:to-yellow-600'
                  : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 after:bg-transparent',
              ]"
            >
              {{ stasiun.nama_stasiun }}
            </button>
          </div>

          <button
            v-if="showTabScrollArrows"
            type="button"
            @click="scrollTabMenu('right')"
            :disabled="!canScrollTabRight"
            :class="[
              'absolute right-0 top-1/2 z-10 -translate-y-1/2 rounded-full border px-2 py-1 shadow-sm transition',
              canScrollTabRight
                ? 'border-gray-200 bg-white/95 text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-900/95 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white'
                : 'cursor-not-allowed border-gray-100 bg-gray-100/80 text-gray-300 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-600',
            ]"
            aria-label="Geser tab ke kanan"
          >
            ›
          </button>
        </div>
      </div>
    </div>

    <!-- Content for Active Stasiun -->
    <div v-if="activeStasiun" class="space-y-6">
      <!-- Form Entri -->
      <div class="bg-white dark:bg-gray-900/50 backdrop-blur-sm border border-gray-200/50 dark:border-gray-800/50 rounded-xl shadow-sm">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800/50">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white">📋 Form Entri Data Analisa</h3>
          <span class="text-sm text-gray-500 dark:text-gray-400">
            Stasiun: <span class="font-semibold text-yellow-600 dark:text-yellow-400">{{ activeStasiunName }}</span>
          </span>
        </div>

        <div class="px-6 py-5">
          <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
            <div>
              <label class="mb-2 block text-sm font-semibold text-gray-600 dark:text-gray-300">Tanggal</label>
              <input
                v-model="form.tanggal"
                @click="openNativePicker"
                @focus="openNativePicker"
                type="date"
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-yellow-500"
              />
            </div>

            <div>
              <label class="mb-2 block text-sm font-semibold text-gray-600 dark:text-gray-300">Jam</label>
              <input
                v-model="form.jam"
                @click="openNativePicker"
                @focus="openNativePicker"
                type="time"
                step="60"
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-yellow-500"
              />
            </div>

            <div>
              <label class="mb-2 block text-sm font-semibold text-gray-600 dark:text-gray-300">Shift</label>
              <select
                v-model="form.shift"
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-yellow-500"
              >
                <option value="1">Shift 1</option>
                <option value="2">Shift 2</option>
                <option value="3">Shift 3</option>
              </select>
            </div>

            <div>
              <label class="mb-2 block text-sm font-semibold text-gray-600 dark:text-gray-300">Petugas</label>
              <input
                v-model="form.petugas"
                type="text"
                placeholder="Nama petugas QA"
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-yellow-500"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Parameter Input Form -->
      <div
        v-if="hasSelectedStasiun"
        class="bg-white dark:bg-gray-900/50 backdrop-blur-sm border border-gray-200/50 dark:border-gray-800/50 rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-300"
      >
        <div class="border-b border-gray-100 dark:border-gray-800/50 bg-gradient-to-r from-white to-gray-50 dark:from-gray-900 dark:to-gray-800/30 px-6 py-5 rounded-t-2xl flex items-center justify-between">
          <h3 class="font-semibold text-lg text-gray-800 dark:text-white">📊 Parameter Stasiun</h3>
          <span class="text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full">
            {{ parameterList.length }} parameter
          </span>
        </div>
        <div class="mt-2 mx-4 flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-xs text-blue-700 dark:text-blue-300 font-medium">
          <span class="inline-block w-1.5 h-1.5 rounded-full bg-blue-500"></span>
          Gunakan tombol TAB untuk pindah ke kolom input berikutnya
        </div>

      <div class="p-6">
        <div v-if="loadingParameter" class="py-12 text-center">
          <div class="inline-flex flex-col items-center gap-3">
            <div class="w-8 h-8 rounded-full border-2 border-yellow-300 border-t-yellow-600 dark:border-yellow-700 dark:border-t-yellow-400 animate-spin"></div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Memuat parameter stasiun...</p>
          </div>
        </div>

        <div v-else-if="parameterList.length === 0" class="py-12 text-center">
          <div class="inline-flex flex-col items-center gap-2">
            <div class="text-4xl">📭</div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada parameter aktif untuk stasiun ini</p>
          </div>
        </div>

        <div v-else class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
          <div
            v-for="(parameter, index) in orderedParameterList"
            :key="parameter.id"
            :class="parameterGridClass(parameter)"
          >
            <div class="rounded-xl border border-gray-200/70 bg-gray-50/60 dark:border-gray-700/70 dark:bg-gray-900/40 p-3.5">
              <div class="flex items-center justify-between mb-2 gap-2">
                <label class="text-xs font-semibold text-blue-600 dark:text-blue-400 leading-tight truncate" :title="parameter.nama_parameter">
                  {{ parameter.nama_parameter }}
                </label>
                <span
                  v-if="getStatus(parameter.id) === 'alert'"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/30"
                >
                  <AlertTriangleIcon class="w-3.5 h-3.5" /> Alert
                </span>
                <span
                  v-else-if="getStatus(parameter.id) === 'safe'"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900/30"
                >
                  <CheckCircle2Icon class="w-3.5 h-3.5" /> OK
                </span>
              </div>

              <div
                :class="[
                  'flex items-center rounded-lg border overflow-hidden transition-all duration-200',
                  getStatus(parameter.id) === 'alert'
                    ? 'border-red-400 ring-1 ring-red-300 dark:ring-red-700'
                    : getStatus(parameter.id) === 'safe'
                      ? 'border-green-400 ring-1 ring-green-300 dark:ring-green-700'
                      : 'border-gray-300 dark:border-gray-600',
                ]"
              >
                <input
                  :value="getNilai(parameter.id)"
                  @input="setNilai(parameter.id, String(($event.target as HTMLInputElement).value))"
                  :tabindex="100 + index"
                  :readonly="isAutoCalculatedHk(parameter)"
                  type="number"
                  step="any"
                  :placeholder="isAutoCalculatedHk(parameter) ? 'Auto' : '0'"
                  :class="[
                    'flex-1 min-w-0 px-3 py-2 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 outline-none placeholder-gray-400 dark:placeholder-gray-600',
                    isAutoCalculatedHk(parameter) ? 'cursor-not-allowed bg-gray-100 dark:bg-gray-700/70' : '',
                    getStatus(parameter.id) === 'alert'
                      ? 'bg-red-50 dark:bg-red-900/10'
                      : getStatus(parameter.id) === 'safe'
                        ? 'bg-green-50 dark:bg-green-900/10'
                        : 'bg-white dark:bg-gray-800',
                  ]"
                />
                <span
                  v-if="getSatuan(parameter)"
                  class="flex-shrink-0 min-w-10 px-2.5 py-2 text-xs font-semibold text-gray-500 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 border-l border-gray-300 dark:border-gray-600 text-center select-none"
                >
                  {{ getSatuan(parameter) }}
                </span>
              </div>

              <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ getTargetText(parameter) }}</p>
            </div>
          </div>
        </div>

          <div class="mt-5 border-t border-gray-100 dark:border-gray-800/50 pt-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
              <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ hasAnyParameterValue ? 'Data siap diproses untuk disimpan.' : 'Isi minimal satu parameter sebelum submit.' }}
              </div>
              <button
                type="button"
                :disabled="isSubmitDisabled"
                @click.prevent="submitForm"
                :class="[
                  'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-all duration-200',
                  'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white shadow-sm hover:from-yellow-600 hover:to-yellow-700',
                  'disabled:cursor-not-allowed disabled:from-gray-400 disabled:to-gray-500 disabled:opacity-50'
                ]"
              >
                <svg v-if="!saving" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                {{ saving ? 'Menyimpan...' : 'Simpan Entri QA' }}
              </button>
            </div>
          </div>

          <div v-if="submitPreview" class="mt-4 rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 p-4">
            <p class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">📝 Payload JSON</p>
            <div class="overflow-x-auto rounded-lg bg-gray-900 p-2 [&::-webkit-scrollbar]:h-1 [&::-webkit-scrollbar-track]:bg-gray-800 [&::-webkit-scrollbar-thumb]:bg-gray-700 [&::-webkit-scrollbar-thumb]:rounded">
              <pre class="text-xs text-gray-300 font-mono">{{ submitPreview }}</pre>
            </div>
          </div>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-900/50 backdrop-blur-sm border border-gray-200/50 dark:border-gray-800/50 rounded-2xl shadow-sm">
      <div class="border-b border-gray-100 dark:border-gray-800/50 bg-gradient-to-r from-white to-gray-50 dark:from-gray-900 dark:to-gray-800/30 px-6 py-5 rounded-t-2xl flex items-center justify-between">
        <h3 class="font-semibold text-lg text-gray-800 dark:text-white">📜 Riwayat Entri - Stasiun Ini</h3>
        <button
          @click="currentHistoryMeta && activeStasiun && fetchHistory(activeStasiun, 1, false)"
          class="text-xs font-semibold px-3.5 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-yellow-50 dark:hover:bg-yellow-900/10 hover:text-yellow-700 dark:hover:text-yellow-400 transition-colors duration-200"
        >
          🔄 Refresh
        </button>
      </div>

      <div class="p-6">
        <div v-if="loadingHistory" class="py-12 text-center">
          <div class="inline-flex flex-col items-center gap-3">
            <div class="w-8 h-8 rounded-full border-2 border-yellow-300 border-t-yellow-600 dark:border-yellow-700 dark:border-t-yellow-400 animate-spin"></div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Memuat riwayat entri QA...</p>
          </div>
        </div>

        <div v-else-if="currentHistoryList.length === 0" class="py-12 text-center">
          <div class="inline-flex flex-col items-center gap-2">
            <div class="text-4xl">📭</div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada data entri QA untuk stasiun ini</p>
          </div>
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
          <div class="overflow-x-auto [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-track]:dark:bg-gray-800 [&::-webkit-scrollbar-thumb]:bg-gray-300 [&::-webkit-scrollbar-thumb]:dark:bg-gray-700 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:hover:bg-gray-400 [&::-webkit-scrollbar-thumb]:dark:hover:bg-gray-600">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800/50 dark:to-gray-700/50 border-b border-gray-200 dark:border-gray-800">
                  <th class="py-3 px-4">Tanggal</th>
                  <th class="py-3 px-4">Jam</th>
                  <th class="py-3 px-4">Shift</th>
                  <th class="py-3 px-4">Petugas</th>
                  <th class="py-3 px-4">Total Parameter</th>
                  <th class="py-3 px-4">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr
                  v-for="item in currentHistoryList"
                  :key="item.id"
                  class="text-gray-700 dark:text-gray-200 transition-colors duration-200 hover:bg-blue-50/30 dark:hover:bg-blue-900/10"
                >
                  <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">{{ formatTanggal(item.tanggal) }}</td>
                  <td class="py-3 px-4 font-mono text-gray-600 dark:text-gray-400">{{ item.jam }}</td>
                  <td class="py-3 px-4 font-medium">Shift {{ item.shift }}</td>
                  <td class="py-3 px-4 text-gray-700 dark:text-gray-300">{{ item.petugas }}</td>
                  <td class="py-3 px-4 text-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-[11px] font-semibold text-indigo-700 dark:text-indigo-300">
                      {{ item.details?.length ?? 0 }}
                    </span>
                  </td>
                  <td class="py-3 px-4">
                    <span :class="['text-xs font-semibold px-3 py-1 rounded-full inline-block', statusBadgeClass(alertCount(item))]">
                      {{ alertCount(item) > 0 ? `${alertCount(item)} Alert` : '✓ Aman' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-if="currentHistoryList.length > 0 && currentHistoryMeta.current_page < currentHistoryMeta.last_page" class="mt-5 flex justify-center">
          <button
            @click="activeStasiun && fetchHistory(activeStasiun, currentHistoryMeta.current_page + 1, true)"
            class="text-xs font-semibold px-5 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-yellow-50 dark:hover:bg-yellow-900/10 hover:text-yellow-700 dark:hover:text-yellow-400 transition-colors duration-200"
          >
            ↓ Load More
          </button>
        </div>
      </div>
    </div>
    </div>

    <!-- Empty State -->
    <div v-else class="min-h-[70vh] flex items-center justify-center">
      <div class="text-center">
        <div class="text-6xl mb-4">🏭</div>
        <p class="text-lg font-semibold text-gray-800 dark:text-white">Belum ada data</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
          Silakan pilih stasiun untuk memulai entri data QA
        </p>
      </div>
    </div>
  </div>
</template>
