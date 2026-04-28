<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'

type WilayahRow = {
  nmktgr: string
  kab: string
  rithi: number
  berathi: number
  ritsd: number
  beratsd: number
}

const selectedDate = ref(new Date().toISOString().slice(0, 10))
const rows = ref<WilayahRow[]>([])
const initialLoading = ref(false)
const loadingByFilter = ref(false)
const errorMessage = ref('')
let refreshTimer: ReturnType<typeof setInterval> | null = null

const totalRitHi = computed(() => rows.value.reduce((sum, row) => sum + row.rithi, 0))
const totalBeratHi = computed(() => rows.value.reduce((sum, row) => sum + row.berathi, 0))
const totalRitSd = computed(() => rows.value.reduce((sum, row) => sum + row.ritsd, 0))
const totalBeratSd = computed(() => rows.value.reduce((sum, row) => sum + row.beratsd, 0))

function toMmDdYyyy(value: string): string {
  if (!value) return ''
  const [year, month, day] = value.split('-')
  return `${month}/${day}/${year}`
}

function toNumber(value: unknown): number {
  const numeric = Number(value ?? 0)
  return Number.isFinite(numeric) ? numeric : 0
}

async function loadWilayah(isBackgroundRefresh = false) {
  if (!isBackgroundRefresh) {
    if (rows.value.length === 0) {
      initialLoading.value = true
    } else {
      loadingByFilter.value = true
    }
  }

  try {
    const { data } = await axios.get('/api/penerimaan/pemasukan-wilayah', {
      params: { tanggal: toMmDdYyyy(selectedDate.value) },
    })

    rows.value = Array.isArray(data?.data)
      ? data.data.map((row: any) => ({
          nmktgr: String(row.nmktgr ?? row.NMKTGR ?? '-'),
          kab: String(row.kab ?? row.KAB ?? '-'),
          rithi: toNumber(row.rithi ?? row.RITHI),
          berathi: toNumber(row.berathi ?? row.BERATHI),
          ritsd: toNumber(row.ritsd ?? row.RITSD),
          beratsd: toNumber(row.beratsd ?? row.BERATSD),
        }))
      : []

    errorMessage.value = ''
  } catch (error: any) {
    console.error(error)
    errorMessage.value = error?.response?.data?.error || error?.message || 'Gagal memuat data pemasukan per wilayah'
    if (!isBackgroundRefresh) {
      rows.value = []
    }
  } finally {
    if (!isBackgroundRefresh) {
      initialLoading.value = false
      loadingByFilter.value = false
    }
  }
}

function formatNumber(value: number): string {
  return value.toLocaleString('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  })
}

function onApplyFilter() {
  loadWilayah(false)
}

onMounted(() => {
  loadWilayah(false)
  refreshTimer = setInterval(() => loadWilayah(true), 60000)
})

onBeforeUnmount(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
})
</script>

<template>
  <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl overflow-hidden">
    <div class="p-4 space-y-4">
      <div class="flex flex-wrap items-end gap-3">
        <label class="text-sm text-gray-700 dark:text-gray-200">
          <span class="mb-1 block">Tanggal Laporan</span>
          <input
            v-model="selectedDate"
            type="date"
            class="h-10 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 text-sm text-gray-800 dark:text-gray-100"
          />
        </label>

        <button
          @click="onApplyFilter"
          :disabled="loadingByFilter"
          class="h-10 rounded-lg bg-slate-800 px-6 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-60 dark:bg-slate-700 dark:hover:bg-slate-600 self-end"
        >
          {{ loadingByFilter ? 'Memuat...' : 'Lihat' }}
        </button>
      </div>

      <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full min-w-[820px] text-sm">
          <thead>
            <tr class="bg-gray-200/90 dark:bg-gray-800 text-gray-800 dark:text-gray-100">
              <th class="px-2 py-2 text-center border border-gray-300 dark:border-gray-700">No</th>
              <th class="px-2 py-2 text-left border border-gray-300 dark:border-gray-700">Kategori</th>
              <th class="px-2 py-2 text-left border border-gray-300 dark:border-gray-700">Wilayah</th>
              <th class="px-2 py-2 text-right border border-gray-300 dark:border-gray-700">RIT HI</th>
              <th class="px-2 py-2 text-right border border-gray-300 dark:border-gray-700">Berat HI</th>
              <th class="px-2 py-2 text-right border border-gray-300 dark:border-gray-700">RIT SDHI</th>
              <th class="px-2 py-2 text-right border border-gray-300 dark:border-gray-700">Berat SDHI</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="initialLoading">
              <td colspan="7" class="px-3 py-6 text-center text-slate-500 dark:text-slate-300">Memuat data pemasukan per wilayah...</td>
            </tr>
            <tr v-else-if="errorMessage && rows.length === 0">
              <td colspan="7" class="px-3 py-6 text-center text-red-600 dark:text-red-400">{{ errorMessage }}</td>
            </tr>
            <tr v-else-if="rows.length === 0">
              <td colspan="7" class="px-3 py-6 text-center text-slate-500 dark:text-slate-300">Belum ada data pemasukan per wilayah.</td>
            </tr>

            <tr v-for="(row, index) in rows" :key="`${row.nmktgr}-${row.kab}-${index}`" class="bg-cyan-50/70 dark:bg-cyan-950/20 text-gray-800 dark:text-gray-100">
              <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-center">{{ index + 1 }}</td>
              <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40">{{ row.nmktgr }}</td>
              <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40">{{ row.kab }}</td>
              <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right">{{ formatNumber(row.rithi) }}</td>
              <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right">{{ formatNumber(row.berathi) }}</td>
              <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right">{{ formatNumber(row.ritsd) }}</td>
              <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right">{{ formatNumber(row.beratsd) }}</td>
            </tr>

            <tr v-if="rows.length > 0" class="bg-gray-200/90 dark:bg-gray-800 text-gray-900 dark:text-white font-semibold">
              <td colspan="3" class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-center">Jumlah</td>
              <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ formatNumber(totalRitHi) }}</td>
              <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ formatNumber(totalBeratHi) }}</td>
              <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ formatNumber(totalRitSd) }}</td>
              <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ formatNumber(totalBeratSd) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
