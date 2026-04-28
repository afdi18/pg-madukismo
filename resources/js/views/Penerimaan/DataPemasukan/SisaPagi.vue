<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'

type SisaPagiRow = {
  HRPem:   number
  HRGil:   number
  PemritHI:  number
  PembrtHI:  number
  GilritHI:  number
  GilbrtHI:  number
  Pemritsd:  number
  Pembrtsd:  number
  Gilritsd:  number
  Gilbrtsd:  number
  SisaRit:   number
  SisaBrt:   number
}

// ── state ──────────────────────────────────────────────────
const hrGil     = ref<number>(1)
const hrPem     = ref<number>(4)
const minPem    = ref<number>(4)
const maxHrGil  = ref<number>(1)
const maxHrPem  = ref<number>(4)
const data      = ref<SisaPagiRow | null>(null)
const loading   = ref(false)
const isBackground = ref(false)
const errorMsg  = ref('')
const syncGuard = ref(false)
let   timer: ReturnType<typeof setInterval> | null = null

// ── helpers ────────────────────────────────────────────────
function fmt(n: number | null | undefined): string {
  if (n === null || n === undefined) return '0'
  return Number(n).toLocaleString('id-ID')
}

function padHari(n: number): string {
  return String(n).padStart(3, '0')
}

function clamp(value: number, min: number, max: number): number {
  return Math.min(max, Math.max(min, value))
}

const hariOffset = computed(() => Math.max(0, minPem.value - 1))

const maxHrGilSelectable = computed(() => {
  const candidate = Math.min(maxHrGil.value, maxHrPem.value - hariOffset.value)
  return Math.max(1, candidate)
})

const hariGilingOptions = computed(() => {
  return Array.from({ length: maxHrGilSelectable.value }, (_, i) => i + 1)
})

const hariPemasukanOptions = computed(() => {
  return Array.from({ length: maxHrPem.value }, (_, i) => i + 1)
})

// ── map SP row ─────────────────────────────────────────────
// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapRow(r: any): SisaPagiRow {
  const n = (k: string) => Number(r[k] ?? r[k.toLowerCase()] ?? r[k.toUpperCase()] ?? 0)
  return {
    HRPem:    n('HRPem'),
    HRGil:    n('HRGil'),
    PemritHI:  n('Pemrithi'),
    PembrtHI:  n('Pembrthi'),
    GilritHI:  n('Gilrithi'),
    GilbrtHI:  n('Gilbrthi'),
    Pemritsd:  n('Pemritsd'),
    Pembrtsd:  n('Pembrtsd'),
    Gilritsd:  n('Gilritsd'),
    Gilbrtsd:  n('Gilbrtsd'),
    SisaRit:   n('SisaRit'),
    SisaBrt:   n('SisaBrt'),
  }
}

// ── fetch default hari dari TBL_DEFAULT ────────────────────
async function fetchDefault() {
  try {
    const res = await axios.get('/api/penerimaan/default-hari')
    const defaultHrGil = Number(res.data?.hr_gil ?? 1)
    const defaultHrPem = Number(res.data?.hr_pem ?? 4)
    const defaultMinPem = Number(res.data?.min_pem ?? Math.max(1, defaultHrPem - defaultHrGil + 1))

    minPem.value = Number.isFinite(defaultMinPem) && defaultMinPem > 0 ? defaultMinPem : 4
    maxHrGil.value = Number.isFinite(defaultHrGil) && defaultHrGil > 0 ? defaultHrGil : 1
    maxHrPem.value = Number.isFinite(defaultHrPem) && defaultHrPem > 0 ? defaultHrPem : minPem.value

    hrGil.value = clamp(maxHrGil.value, 1, maxHrGilSelectable.value)
    hrPem.value = hrGil.value + hariOffset.value
  } catch {
    // pakai default 1
  }
}

watch(hrGil, (newVal) => {
  if (syncGuard.value) return
  syncGuard.value = true
  const safeHrGil = clamp(newVal, 1, maxHrGilSelectable.value)
  hrGil.value = safeHrGil
  hrPem.value = safeHrGil + hariOffset.value
  syncGuard.value = false
})

watch(hrPem, (newVal) => {
  if (syncGuard.value) return
  syncGuard.value = true
  hrPem.value = clamp(newVal, 1, maxHrPem.value)
  const mappedHrGil = hrPem.value - hariOffset.value
  hrGil.value = clamp(mappedHrGil, 1, maxHrGilSelectable.value)
  syncGuard.value = false
})

// ── fetch sisa pagi ─────────────────────────────────────────
async function fetchData(background = false) {
  isBackground.value = background
  if (!background) loading.value = true
  errorMsg.value = ''

  try {
    const res = await axios.get('/api/penerimaan/sisa-pagi', {
      params: { hr_gil: padHari(hrGil.value), hr_pem: padHari(hrPem.value) },
    })
    const raw: unknown[] = res.data?.data ?? []
    data.value = raw.length > 0 ? mapRow(raw[0]) : null
  } catch {
    if (!background) errorMsg.value = 'Gagal memuat data sisa pagi.'
  } finally {
    loading.value = false
    isBackground.value = false
  }
}

// ── lifecycle ──────────────────────────────────────────────
onMounted(async () => {
  await fetchDefault()
  await fetchData()
  timer = setInterval(() => fetchData(true), 60_000)
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})
</script>

<template>
  <div class="space-y-4">

    <!-- Filter Card -->
    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-4">
      <div class="flex flex-wrap items-end gap-4">
        <!-- Hari Giling -->
        <label class="flex flex-col gap-1 text-sm text-gray-700 dark:text-gray-200">
          <span class="font-medium">Hari Giling</span>
          <select
            v-model.number="hrGil"
            class="h-10 w-28 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-yellow-400"
          >
            <option v-for="value in hariGilingOptions" :key="`hgil-${value}`" :value="value">
              {{ padHari(value) }}
            </option>
          </select>
        </label>

        <!-- Hari Pemasukan -->
        <label class="flex flex-col gap-1 text-sm text-gray-700 dark:text-gray-200">
          <span class="font-medium">Hari Pemasukan</span>
          <select
            v-model.number="hrPem"
            class="h-10 w-28 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-yellow-400"
          >
            <option v-for="value in hariPemasukanOptions" :key="`hpem-${value}`" :value="value">
              {{ padHari(value) }}
            </option>
          </select>
        </label>

        <!-- Tombol Tampilkan -->
        <button
          @click="fetchData()"
          :disabled="loading"
          class="h-10 px-5 rounded-lg bg-yellow-500 hover:bg-yellow-600 disabled:opacity-50 text-white text-sm font-semibold transition-colors"
          type="button"
        >
          {{ loading ? 'Memuat…' : 'Tampilkan' }}
        </button>

        <!-- Indicator background refresh -->
        <span v-if="isBackground" class="flex items-center gap-1.5 text-xs text-gray-400">
          <span class="inline-block h-2 w-2 animate-ping rounded-full bg-yellow-400"></span>
          Memperbarui…
        </span>
      </div>
    </div>

    <!-- Error -->
    <div
      v-if="errorMsg"
      class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-600 dark:text-red-400"
    >
      {{ errorMsg }}
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="h-12 rounded-xl bg-gray-100 dark:bg-gray-800 animate-pulse" />
    </div>

    <!-- Summary Card -->
    <div
      v-else
      class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm"
    >
      <table class="w-full border-collapse text-center text-sm">

        <!-- ── Header Hari ── -->
        <thead>
          <tr>
            <th
              colspan="2"
              class="border border-gray-300 dark:border-gray-700 bg-green-600 dark:bg-green-700 px-4 py-2 text-white font-semibold text-sm"
            >
              Hari Giling
            </th>
            <th
              colspan="2"
              class="border border-gray-300 dark:border-gray-700 bg-yellow-500 dark:bg-yellow-600 px-4 py-2 text-white font-semibold text-sm"
            >
              Hari Pemasukan
            </th>
          </tr>
          <tr>
            <td
              colspan="2"
              class="border border-gray-300 dark:border-gray-700 bg-green-50 dark:bg-green-900/30 px-4 py-2 font-bold text-green-700 dark:text-green-300 text-lg tracking-widest"
            >
              {{ data ? padHari(data.HRGil) : padHari(hrGil) }}
            </td>
            <td
              colspan="2"
              class="border border-gray-300 dark:border-gray-700 bg-yellow-50 dark:bg-yellow-900/20 px-4 py-2 font-bold text-yellow-700 dark:text-yellow-300 text-lg tracking-widest"
            >
              {{ data ? padHari(data.HRPem) : padHari(hrPem) }}
            </td>
          </tr>
        </thead>

        <tbody>

          <!-- ── Sub-header Tebu Digiling | Tebu Masuk ── -->
          <tr>
            <th
              colspan="2"
              class="border border-gray-300 dark:border-gray-700 bg-green-500 dark:bg-green-700 px-4 py-2 text-white font-semibold"
            >
              Tebu Digiling
            </th>
            <th
              colspan="2"
              class="border border-gray-300 dark:border-gray-700 bg-teal-500 dark:bg-teal-700 px-4 py-2 text-white font-semibold"
            >
              Tebu Masuk
            </th>
          </tr>

          <!-- ── Label Rit HI / Berat HI ── -->
          <tr class="bg-gray-50 dark:bg-gray-800/60">
            <td class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-gray-600 dark:text-gray-300 font-medium">
              Rit HI
            </td>
            <td class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-gray-600 dark:text-gray-300 font-medium">
              Berat HI
            </td>
            <td class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-gray-600 dark:text-gray-300 font-medium">
              Rit HI
            </td>
            <td class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-gray-600 dark:text-gray-300 font-medium">
              Berat HI
            </td>
          </tr>

          <!-- ── Value Rit HI / Berat HI ── -->
          <tr>
            <td class="border border-gray-300 dark:border-gray-700 px-4 py-3 font-bold text-gray-800 dark:text-gray-100 text-base">
              {{ data ? fmt(data.GilritHI) : '0' }}
            </td>
            <td class="border border-gray-300 dark:border-gray-700 px-4 py-3 font-bold text-gray-800 dark:text-gray-100 text-base">
              {{ data ? fmt(data.GilbrtHI) : '0' }}
            </td>
            <td class="border border-gray-300 dark:border-gray-700 px-4 py-3 font-bold text-gray-800 dark:text-gray-100 text-base">
              {{ data ? fmt(data.PemritHI) : '0' }}
            </td>
            <td class="border border-gray-300 dark:border-gray-700 px-4 py-3 font-bold text-gray-800 dark:text-gray-100 text-base">
              {{ data ? fmt(data.PembrtHI) : '0' }}
            </td>
          </tr>

          <!-- ── Label Rit sd HI / Berat sd HI ── -->
          <tr class="bg-gray-50 dark:bg-gray-800/60">
            <td class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-gray-600 dark:text-gray-300 font-medium">
              Rit sd HI
            </td>
            <td class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-gray-600 dark:text-gray-300 font-medium">
              Berat sd HI
            </td>
            <td class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-gray-600 dark:text-gray-300 font-medium">
              Rit sd HI
            </td>
            <td class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-gray-600 dark:text-gray-300 font-medium">
              Berat sd HI
            </td>
          </tr>

          <!-- ── Value Rit sd HI / Berat sd HI ── -->
          <tr>
            <td class="border border-gray-300 dark:border-gray-700 px-4 py-3 font-bold text-gray-800 dark:text-gray-100 text-base">
              {{ data ? fmt(data.Gilritsd) : '0' }}
            </td>
            <td class="border border-gray-300 dark:border-gray-700 px-4 py-3 font-bold text-gray-800 dark:text-gray-100 text-base">
              {{ data ? fmt(data.Gilbrtsd) : '0' }}
            </td>
            <td class="border border-gray-300 dark:border-gray-700 px-4 py-3 font-bold text-gray-800 dark:text-gray-100 text-base">
              {{ data ? fmt(data.Pemritsd) : '0' }}
            </td>
            <td class="border border-gray-300 dark:border-gray-700 px-4 py-3 font-bold text-gray-800 dark:text-gray-100 text-base">
              {{ data ? fmt(data.Pembrtsd) : '0' }}
            </td>
          </tr>

          <!-- ── Label Sisa Rit | Sisa Berat ── -->
          <tr class="bg-red-50 dark:bg-red-900/20">
            <td
              colspan="2"
              class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-red-700 dark:text-red-300 font-semibold"
            >
              Sisa Rit
            </td>
            <td
              colspan="2"
              class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-red-700 dark:text-red-300 font-semibold"
            >
              Sisa Berat
            </td>
          </tr>

          <!-- ── Value Sisa Rit | Sisa Berat ── -->
          <tr>
            <td
              colspan="2"
              class="border border-gray-300 dark:border-gray-700 px-4 py-4 font-extrabold text-red-600 dark:text-red-400 text-xl"
            >
              {{ data ? fmt(data.SisaRit) : '0' }}
            </td>
            <td
              colspan="2"
              class="border border-gray-300 dark:border-gray-700 px-4 py-4 font-extrabold text-red-600 dark:text-red-400 text-xl"
            >
              {{ data ? fmt(data.SisaBrt) : '0' }}
            </td>
          </tr>

        </tbody>
      </table>

      <!-- No data fallback -->
      <div
        v-if="!loading && !data"
        class="py-10 text-center text-sm text-gray-500 dark:text-gray-400"
      >
        Tidak ada data untuk Hari Giling <strong>{{ padHari(hrGil) }}</strong> / Hari Pemasukan <strong>{{ padHari(hrPem) }}</strong>.
      </div>
    </div>

  </div>
</template>
