<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'

interface DigilingRow {
  jam: string
  berat: number
  truk: number
  lori: number
  total: number
  pemasukan: number
}

type ApiDigilingRow = {
  jam: number
  rit_truk: number
  rit_lori: number
  total: number
  berat: number
  pemasukan: number
}

const hariGilingInput = ref(1)
const tanggalLabel = computed(() => new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }))
const pagiRows = ref<DigilingRow[]>([])
const siangRows = ref<DigilingRow[]>([])
const malamRows = ref<DigilingRow[]>([])
const antrianTrukCount = ref(0)
const antrianLoriCount = ref(0)
const antrianLoriBerat = ref(0)
const isLoading = ref(false)
const isBackgroundRefresh = ref(false)
const errorMessage = ref('')
const sampaiHariIniRitLori = ref(0)
const sampaiHariIniRitTruk = ref(0)
const sampaiHariIniBerat = ref(0)
const sampaiHariIniTotalRit = ref(0)
const sampaiHariIniRitPemasukan = ref(0)
const hasManualHariGilingSelection = ref(false)
const lastAutoResetDate = ref('')
let refreshTimer: ReturnType<typeof setInterval> | null = null

function normalizeHrGiling(input: number | string): string {
  const asNumber = Number(input)
  if (!Number.isFinite(asNumber) || asNumber < 0) return '001'
  return String(Math.trunc(asNumber)).padStart(3, '0')
}

function labelByHour(hourStart: number): string {
  const start = String(hourStart).padStart(2, '0')
  const end = String((hourStart + 1) % 24).padStart(2, '0')
  return `${start}-${end}`
}

function buildShiftRows(hours: number[], indexByHour: Map<number, ApiDigilingRow>): DigilingRow[] {
  return hours.map((hourStart) => {
    const found = indexByHour.get(hourStart)
    const truk = Number(found?.rit_truk ?? 0)
    const lori = Number(found?.rit_lori ?? 0)

    return {
      jam: labelByHour(hourStart),
      berat: Number(found?.berat ?? 0),
      truk,
      lori,
      total: truk + lori,
      pemasukan: Number(found?.pemasukan ?? 0),
    }
  })
}

async function loadDataDigiling(background = false) {
  if (!background) {
    isLoading.value = true
  } else {
    isBackgroundRefresh.value = true
  }

  try {
    const params = hasManualHariGilingSelection.value
      ? { hr_gil: normalizeHrGiling(hariGilingInput.value) }
      : undefined

    const { data } = await axios.get('/api/dashboard/data-digiling', { params })

    const hrGilingDefault = String(data?.meta?.hr_gil_default ?? '001')
    const hrGilingAktif = String(data?.meta?.hr_gil ?? hrGilingDefault)
    hariGilingInput.value = Number(hasManualHariGilingSelection.value ? hrGilingAktif : hrGilingDefault)

    const rows: ApiDigilingRow[] = Array.isArray(data?.data) ? data.data : []
    const indexByHour = new Map<number, ApiDigilingRow>()
    rows.forEach((row) => {
      indexByHour.set(Number(row.jam), {
        jam: Number(row.jam),
        rit_truk: Number(row.rit_truk ?? 0),
        rit_lori: Number(row.rit_lori ?? 0),
        total: Number(row.total ?? 0),
        berat: Number(row.berat ?? 0),
        pemasukan: Number(row.pemasukan ?? 0),
      })
    })

    pagiRows.value = buildShiftRows([6, 7, 8, 9, 10, 11, 12, 13], indexByHour)
    siangRows.value = buildShiftRows([14, 15, 16, 17, 18, 19, 20, 21], indexByHour)
    malamRows.value = buildShiftRows([22, 23, 0, 1, 2, 3, 4, 5], indexByHour)
    errorMessage.value = ''
  } catch (error: any) {
    console.error(error)
    if (!background) {
      errorMessage.value = error?.response?.data?.message || error?.message || 'Gagal memuat data digiling'
    }
  } finally {
    isLoading.value = false
    isBackgroundRefresh.value = false
  }
}

async function loadMonitoringAntrianSummary() {
  try {
    const { data } = await axios.get('/api/dashboard/antrian-summary')
    const summary = data?.data ?? {}

    antrianTrukCount.value = Number(summary.antrian_truk ?? 0)
    antrianLoriCount.value = Number(summary.antrian_lori ?? 0)
    antrianLoriBerat.value = Number(summary.berat_lori ?? 0)
  } catch (error) {
    console.error('Gagal memuat ringkasan antrian:', error)
  }
}

async function loadTebuDigilingSampaiBariIni() {
  try {
    const { data } = await axios.get('/api/dashboard/tebu-digiling-sampai-hari-ini')
    const result = data?.data ?? {}

    sampaiHariIniRitLori.value = Number(result.rit_lori ?? 0)
    sampaiHariIniRitTruk.value = Number(result.rit_truk ?? 0)
    sampaiHariIniBerat.value = Number(result.berat ?? 0)
    sampaiHariIniTotalRit.value = Number(result.total_rit ?? 0)
    sampaiHariIniRitPemasukan.value = Number(result.rit_pemasukan ?? 0)
  } catch (error) {
    console.error('Gagal memuat data tebu digiling sampai hari ini:', error)
  }
}

const maxRows = computed(() => Math.max(pagiRows.value.length, siangRows.value.length, malamRows.value.length))

function rowAt(rows: DigilingRow[], idx: number): DigilingRow {
  return rows[idx] ?? { jam: '-', berat: 0, truk: 0, lori: 0, total: 0, pemasukan: 0 }
}

function sumBy(rows: DigilingRow[], key: keyof DigilingRow) {
  return rows.reduce((acc, row) => acc + Number(row[key] ?? 0), 0)
}

function formatNumber(value: number) {
  return value.toLocaleString('id-ID')
}

type ShiftKey = 'pagi' | 'siang' | 'malam'

const allRows = computed(() => [...pagiRows.value, ...siangRows.value, ...malamRows.value])
const totalRitTruk = computed(() => allRows.value.reduce((acc, row) => acc + row.truk, 0))
const totalRitLori = computed(() => allRows.value.reduce((acc, row) => acc + row.lori, 0))
const totalBeratLori = computed(() => allRows.value.reduce((acc, row) => acc + row.berat, 0))
const totalBerat = computed(() => allRows.value.reduce((acc, row) => acc + row.berat, 0))
const totalRitDigiling = computed(() => allRows.value.reduce((acc, row) => acc + row.total, 0))
const totalRitPemasukan = computed(() => allRows.value.reduce((acc, row) => acc + row.pemasukan, 0))

function applyHariGilingFilter() {
  hasManualHariGilingSelection.value = true
  loadDataDigiling(false)
}

function maybeResetHariGilingAtSixAM() {
  const now = new Date()
  const todayKey = now.toISOString().slice(0, 10)

  if (now.getHours() !== 6) return false
  if (lastAutoResetDate.value === todayKey) return false

  hasManualHariGilingSelection.value = false
  lastAutoResetDate.value = todayKey
  return true
}

function shiftClass(shift: ShiftKey, part: 'group' | 'head' | 'cell' | 'total') {
  const map = {
    pagi: {
      group: 'bg-sky-500 text-white',
      head: 'bg-sky-50 dark:bg-sky-950/50 text-sky-800 dark:text-sky-100 border-sky-200 dark:border-sky-700',
      cell: 'bg-sky-50/30 dark:bg-sky-950/15 !text-sky-800 dark:!text-sky-100',
      total: 'bg-sky-100 dark:bg-sky-900/45 !text-sky-900 dark:!text-white',
    },
    siang: {
      group: 'bg-amber-500 text-white',
      head: 'bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-100 border-amber-200 dark:border-amber-700',
      cell: 'bg-amber-50/25 dark:bg-amber-950/15 !text-amber-800 dark:!text-amber-100',
      total: 'bg-amber-100 dark:bg-amber-900/45 !text-amber-900 dark:!text-white',
    },
    malam: {
      group: 'bg-indigo-600 text-white',
      head: 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-800 dark:text-indigo-100 border-indigo-200 dark:border-indigo-700',
      cell: 'bg-indigo-50/25 dark:bg-indigo-950/15 !text-indigo-800 dark:!text-indigo-100',
      total: 'bg-indigo-100 dark:bg-indigo-900/45 !text-indigo-900 dark:!text-white',
    },
  } as const

  return map[shift][part]
}

onMounted(async () => {
  maybeResetHariGilingAtSixAM()

  await Promise.all([
    loadDataDigiling(false),
    loadMonitoringAntrianSummary(),
    loadTebuDigilingSampaiBariIni(),
  ])

  refreshTimer = setInterval(async () => {
    const didAutoReset = maybeResetHariGilingAtSixAM()

    if (didAutoReset) {
      await Promise.all([
        loadDataDigiling(false),
        loadMonitoringAntrianSummary(),
        loadTebuDigilingSampaiBariIni(),
      ])
      return
    }

    loadDataDigiling(true)
    loadMonitoringAntrianSummary()
    loadTebuDigilingSampaiBariIni()
  }, 30000)
})

onBeforeUnmount(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
})
</script>

<template>
  <div class="space-y-3 lg:space-y-4">
    <div class="relative overflow-hidden rounded-2xl border border-slate-700/70 bg-slate-900 px-4 py-3 lg:px-5 lg:py-4 shadow-[0_10px_24px_rgba(2,6,23,0.45)]">
      <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500"></div>
      <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_auto] gap-3 lg:gap-4 items-start xl:items-center">
        <div class="min-w-0">
          <h1 class="text-lg lg:text-xl font-bold tracking-tight text-white leading-tight">Tebu Digiling per Jam</h1>
          <p class="text-xs lg:text-sm text-sky-200/90 mt-1 leading-snug">Monitoring analisa tebu per jam · Hari Giling ke {{ hariGilingInput }} · {{ tanggalLabel }}</p>
        </div>

        <div class="flex flex-wrap items-stretch justify-start xl:justify-end gap-2 lg:gap-3">
          <div class="inline-flex items-center gap-2 rounded-xl border border-slate-600 bg-slate-800 px-2.5 py-1.5 lg:px-3 lg:py-2 text-xs lg:text-sm text-slate-200">
            <span class="text-slate-300 whitespace-nowrap">Hari Giling</span>
            <input
              v-model.number="hariGilingInput"
              type="number"
              min="1"
              @change="applyHariGilingFilter"
              @keyup.enter="applyHariGilingFilter"
              class="w-28 lg:w-32 rounded-md border border-slate-500 bg-slate-700 px-2 py-1 text-xs lg:text-sm font-semibold text-white outline-none focus:border-indigo-400"
            />
          </div>

          <div class="grid grid-cols-2 gap-2 text-xs lg:text-sm">
            <div class="min-w-[96px] lg:min-w-[100px] px-2.5 py-2 rounded-lg bg-slate-800 border border-slate-700">
              <span class="text-sky-300 font-semibold">Antrian Truk</span>
              <p class="text-2xl leading-6 font-extrabold text-sky-200">{{ formatNumber(antrianTrukCount) }}</p>
            </div>
            <div class="min-w-[96px] lg:min-w-[100px] px-2.5 py-2 rounded-lg bg-slate-800 border border-slate-700">
              <span class="text-indigo-300 font-semibold">Antrian Lori</span>
              <p class="text-2xl leading-6 font-extrabold text-indigo-200">{{ formatNumber(antrianLoriCount) }}</p>
              <p class="text-sm leading-4 font-bold text-indigo-200">{{ formatNumber(antrianLoriBerat) }} Ku</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-red-300/50 bg-red-50/70 px-3 py-2 text-sm text-red-700 dark:border-red-500/40 dark:bg-red-900/20 dark:text-red-300">
      {{ errorMessage }}
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="digiling-table w-full min-w-[1080px] text-sm lg:text-base font-sans">
          <colgroup>
            <col class="dig-col-jam" />
            <col class="dig-col-berat" />
            <col class="dig-col-truk" />
            <col class="dig-col-lori" />
            <col class="dig-col-total" />
            <col class="dig-col-pemasukan" />

            <col class="dig-col-jam" />
            <col class="dig-col-berat" />
            <col class="dig-col-truk" />
            <col class="dig-col-lori" />
            <col class="dig-col-total" />
            <col class="dig-col-pemasukan" />

            <col class="dig-col-jam" />
            <col class="dig-col-berat" />
            <col class="dig-col-truk" />
            <col class="dig-col-lori" />
            <col class="dig-col-total" />
            <col class="dig-col-pemasukan" />
          </colgroup>
          <thead>
            <tr class="text-gray-700 dark:text-gray-100">
              <th class="px-3 py-2 text-center font-bold" :class="shiftClass('pagi', 'group')" colspan="6">Pagi</th>
              <th class="px-3 py-2 text-center font-bold" :class="shiftClass('siang', 'group')" colspan="6">Siang</th>
              <th class="px-3 py-2 text-center font-bold" :class="shiftClass('malam', 'group')" colspan="6">Malam</th>
            </tr>
            <tr class="text-slate-600 dark:text-slate-200">
              <th class="px-3 py-2 text-center border" :class="shiftClass('pagi', 'head')">Jam</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('pagi', 'head')">Berat (Ku)</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('pagi', 'head')">Truk</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('pagi', 'head')">Lori</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('pagi', 'head')">Total</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('pagi', 'head')">Pemasukan</th>

              <th class="px-3 py-2 text-center border" :class="shiftClass('siang', 'head')">Jam</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('siang', 'head')">Berat (Ku)</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('siang', 'head')">Truk</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('siang', 'head')">Lori</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('siang', 'head')">Total</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('siang', 'head')">Pemasukan</th>

              <th class="px-3 py-2 text-center border" :class="shiftClass('malam', 'head')">Jam</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('malam', 'head')">Berat (Ku)</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('malam', 'head')">Truk</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('malam', 'head')">Lori</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('malam', 'head')">Total</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('malam', 'head')">Pemasukan</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="idx in maxRows"
              :key="`row-${idx}`"
              class="border-t border-gray-100 dark:border-gray-800"
            >
              <td class="px-3 py-2 text-center font-semibold text-gray-800 dark:text-gray-100" :class="shiftClass('pagi', 'cell')">{{ rowAt(pagiRows, idx - 1).jam }}</td>
              <td class="px-3 py-2 text-right font-semibold" :class="shiftClass('pagi', 'cell')">{{ formatNumber(rowAt(pagiRows, idx - 1).berat) }}</td>
              <td class="px-3 py-2 text-right font-semibold" :class="shiftClass('pagi', 'cell')">{{ formatNumber(rowAt(pagiRows, idx - 1).truk) }}</td>
              <td class="px-3 py-2 text-right font-semibold" :class="shiftClass('pagi', 'cell')">{{ formatNumber(rowAt(pagiRows, idx - 1).lori) }}</td>
              <td class="px-3 py-2 text-right font-bold" :class="shiftClass('pagi', 'cell')">{{ formatNumber(rowAt(pagiRows, idx - 1).total) }}</td>
              <td class="px-3 py-2 text-right font-semibold" :class="shiftClass('pagi', 'cell')">{{ formatNumber(rowAt(pagiRows, idx - 1).pemasukan) }}</td>

              <td class="px-3 py-2 text-center font-semibold text-gray-800 dark:text-gray-100" :class="shiftClass('siang', 'cell')">{{ rowAt(siangRows, idx - 1).jam }}</td>
              <td class="px-3 py-2 text-right font-semibold" :class="shiftClass('siang', 'cell')">{{ formatNumber(rowAt(siangRows, idx - 1).berat) }}</td>
              <td class="px-3 py-2 text-right font-semibold" :class="shiftClass('siang', 'cell')">{{ formatNumber(rowAt(siangRows, idx - 1).truk) }}</td>
              <td class="px-3 py-2 text-right font-semibold" :class="shiftClass('siang', 'cell')">{{ formatNumber(rowAt(siangRows, idx - 1).lori) }}</td>
              <td class="px-3 py-2 text-right font-bold" :class="shiftClass('siang', 'cell')">{{ formatNumber(rowAt(siangRows, idx - 1).total) }}</td>
              <td class="px-3 py-2 text-right font-semibold" :class="shiftClass('siang', 'cell')">{{ formatNumber(rowAt(siangRows, idx - 1).pemasukan) }}</td>

              <td class="px-3 py-2 text-center font-semibold text-gray-800 dark:text-gray-100" :class="shiftClass('malam', 'cell')">{{ rowAt(malamRows, idx - 1).jam }}</td>
              <td class="px-3 py-2 text-right font-semibold" :class="shiftClass('malam', 'cell')">{{ formatNumber(rowAt(malamRows, idx - 1).berat) }}</td>
              <td class="px-3 py-2 text-right font-semibold" :class="shiftClass('malam', 'cell')">{{ formatNumber(rowAt(malamRows, idx - 1).truk) }}</td>
              <td class="px-3 py-2 text-right font-semibold" :class="shiftClass('malam', 'cell')">{{ formatNumber(rowAt(malamRows, idx - 1).lori) }}</td>
              <td class="px-3 py-2 text-right font-bold" :class="shiftClass('malam', 'cell')">{{ formatNumber(rowAt(malamRows, idx - 1).total) }}</td>
              <td class="px-3 py-2 text-right font-semibold" :class="shiftClass('malam', 'cell')">{{ formatNumber(rowAt(malamRows, idx - 1).pemasukan) }}</td>
            </tr>

            <tr class="border-t border-gray-200 dark:border-gray-700 bg-slate-50 dark:bg-slate-800/60 font-semibold">
              <td class="px-3 py-2" :class="shiftClass('pagi', 'total')">Total</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('pagi', 'total')">{{ formatNumber(sumBy(pagiRows, 'berat')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('pagi', 'total')">{{ formatNumber(sumBy(pagiRows, 'truk')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('pagi', 'total')">{{ formatNumber(sumBy(pagiRows, 'lori')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('pagi', 'total')">{{ formatNumber(sumBy(pagiRows, 'total')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('pagi', 'total')">{{ formatNumber(sumBy(pagiRows, 'pemasukan')) }}</td>

              <td class="px-3 py-2" :class="shiftClass('siang', 'total')">Total</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('siang', 'total')">{{ formatNumber(sumBy(siangRows, 'berat')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('siang', 'total')">{{ formatNumber(sumBy(siangRows, 'truk')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('siang', 'total')">{{ formatNumber(sumBy(siangRows, 'lori')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('siang', 'total')">{{ formatNumber(sumBy(siangRows, 'total')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('siang', 'total')">{{ formatNumber(sumBy(siangRows, 'pemasukan')) }}</td>

              <td class="px-3 py-2" :class="shiftClass('malam', 'total')">Total</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('malam', 'total')">{{ formatNumber(sumBy(malamRows, 'berat')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('malam', 'total')">{{ formatNumber(sumBy(malamRows, 'truk')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('malam', 'total')">{{ formatNumber(sumBy(malamRows, 'lori')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('malam', 'total')">{{ formatNumber(sumBy(malamRows, 'total')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('malam', 'total')">{{ formatNumber(sumBy(malamRows, 'pemasukan')) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-4">
        <div class="h-1 w-full rounded-full bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500 shadow-[0_0_10px_rgba(99,102,241,0.45)] mb-3"></div>
        <h3 class="text-base font-extrabold text-gray-900 dark:text-white mb-2 ps-2">Tebu Digiling Hari Ini</h3>
        <div class="grid grid-cols-3 gap-2 text-sm">
          <div class="rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-center">
            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Ku</p>
            <p class="text-lg font-extrabold text-sky-700 dark:text-sky-300">{{ formatNumber(totalBerat) }}</p>
          </div>
          <div class="rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-center">
            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Rit Digiling</p>
            <p class="text-lg font-extrabold text-emerald-700 dark:text-emerald-300">{{ formatNumber(totalRitDigiling) }}</p>
          </div>
          <div class="rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-center">
            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Rit Pemasukan</p>
            <p class="text-lg font-extrabold text-amber-700 dark:text-amber-300">{{ formatNumber(totalRitPemasukan) }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-4">
        <div class="h-1 w-full rounded-full bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500 shadow-[0_0_10px_rgba(99,102,241,0.45)] mb-3"></div>
        <h3 class="text-base font-extrabold text-gray-900 dark:text-white mb-2 ps-2">Total Tebu Digiling Sampai Hari Ini</h3>
        <div class="grid grid-cols-3 gap-2 text-sm">
          <div class="rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-center">
            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Ku</p>
            <p class="text-lg font-extrabold text-sky-700 dark:text-sky-300">{{ formatNumber(sampaiHariIniBerat) }}</p>
          </div>
          <div class="rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-center">
            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Rit Digiling</p>
            <p class="text-lg font-extrabold text-emerald-700 dark:text-emerald-300">{{ formatNumber(sampaiHariIniTotalRit) }}</p>
          </div>
          <div class="rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-center">
            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Rit Pemasukan</p>
            <p class="text-lg font-extrabold text-amber-700 dark:text-amber-300">{{ formatNumber(sampaiHariIniRitPemasukan) }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dig-col-jam {
  width: 62px;
}

.dig-col-berat {
  width: 76px;
}

.dig-col-truk,
.dig-col-lori {
  width: 50px;
}

.dig-col-total {
  width: 54px;
}

.dig-col-pemasukan {
  width: 68px;
}

.digiling-table th,
.digiling-table td {
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
}

@media (min-width: 1024px) and (max-width: 1366px) {
  .digiling-table th,
  .digiling-table td {
    padding: 0.34rem 0.42rem !important;
    font-size: 13px;
  }
}
</style>
