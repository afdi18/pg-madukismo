<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import * as XLSX from 'xlsx'

type GenericRow = Record<string, unknown>

const hrGil = ref<number>(1)
const maxHrGil = ref<number>(1)
const searchQuery = ref('')
const rows = ref<GenericRow[]>([])
const initialLoading = ref(false)
const loadingByFilter = ref(false)
const isBackground = ref(false)
const errorMessage = ref('')
let refreshTimer: ReturnType<typeof setInterval> | null = null

const hrGilOptions = computed(() => Array.from({ length: maxHrGil.value }, (_, i) => i + 1))
const tableColumns = computed(() => {
  if (!rows.value.length) return [] as string[]
  return Object.keys(rows.value[0])
})
const filteredRows = computed(() => {
  const keyword = searchQuery.value.trim().toLowerCase()

  if (!keyword) return rows.value

  return rows.value.filter((row) =>
    tableColumns.value.some((column) => {
      const rawValue = row[column]
      const normalized = formatCell(rawValue).toLowerCase()
      return normalized.includes(keyword)
    })
  )
})

function padHari(value: number): string {
  return String(value).padStart(3, '0')
}

function formatCell(value: unknown): string {
  if (value === null || value === undefined) return '-'

  if (typeof value === 'number') {
    return value.toLocaleString('id-ID', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    })
  }

  if (typeof value === 'boolean') {
    return value ? 'Ya' : 'Tidak'
  }

  if (typeof value === 'string') {
    const num = Number(value)
    if (!isNaN(num)) {
      return num.toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      })
    }
  }

  return String(value)
}

function formatHeader(column: string): string {
  const key = column.toLowerCase()
  const labels: Record<string, string> = {
    nmktgr: 'Kategori',
    kab: 'Kabupaten',
    rithi: 'RIT HI',
    berathi: 'Berat HI',
    ritsd: 'RIT SDHI',
    beratsd: 'Berat SDHI',
    tgllap: 'Tgl Lap',
    tgl_lap: 'Tgl Lap',
  }

  if (labels[key]) return labels[key]

  return column
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

function isDateColumn(column: string): boolean {
  const key = column.toLowerCase()
  return key.includes('tgl') || key.includes('date')
}

function isNumericValue(value: unknown): boolean {
  if (typeof value === 'number') return true
  if (typeof value === 'string' && value.trim() !== '') {
    return !Number.isNaN(Number(value))
  }
  return false
}

function getCellClass(column: string, value: unknown): string {
  if (isDateColumn(column)) {
    return 'px-3 py-2 border border-cyan-200 dark:border-cyan-900/40 text-center whitespace-nowrap'
  }

  if (isNumericValue(value)) {
    return 'px-3 py-2 border border-cyan-200 dark:border-cyan-900/40 text-right tabular-nums whitespace-nowrap'
  }

  return 'px-3 py-2 border border-cyan-200 dark:border-cyan-900/40 text-left whitespace-nowrap'
}

function getHeaderClass(column: string): string {
  if (isDateColumn(column)) {
    return 'px-3 py-2 text-center border border-gray-300 dark:border-gray-700 whitespace-nowrap'
  }

  const hasNumericSample = rows.value.some((row) => isNumericValue(row[column]))

  if (hasNumericSample) {
    return 'px-3 py-2 text-right border border-gray-300 dark:border-gray-700 whitespace-nowrap'
  }

  return 'px-3 py-2 text-left border border-gray-300 dark:border-gray-700 whitespace-nowrap'
}

async function fetchDefault() {
  const { data } = await axios.get('/api/penerimaan/default-hari')
  const max = Number(data?.hr_gil ?? 1)
  maxHrGil.value = Number.isFinite(max) && max > 0 ? max : 1
  hrGil.value = maxHrGil.value
}

async function loadData(background = false) {
  isBackground.value = background
  if (!background) {
    if (rows.value.length === 0) {
      initialLoading.value = true
    } else {
      loadingByFilter.value = true
    }
  }

  try {
    const { data } = await axios.get('/api/penerimaan/digiling-per-spa', {
      params: { hr_gil: hrGil.value },
    })

    rows.value = Array.isArray(data?.data) ? data.data : []

    const serverMaxHrGil = Number(data?.meta?.max_hr_gil ?? maxHrGil.value)
    if (Number.isFinite(serverMaxHrGil) && serverMaxHrGil > 0) {
      maxHrGil.value = serverMaxHrGil
      if (hrGil.value > maxHrGil.value) {
        hrGil.value = maxHrGil.value
      }
    }

    errorMessage.value = ''
  } catch (error: any) {
    console.error(error)
    errorMessage.value = error?.response?.data?.error || error?.message || 'Gagal memuat data digiling per SPA'
    if (!background) {
      rows.value = []
    }
  } finally {
    if (!background) {
      initialLoading.value = false
      loadingByFilter.value = false
    }
    isBackground.value = false
  }
}

function onApplyFilter() {
  loadData(false)
}

function exportToExcel() {
  if (!filteredRows.value.length) return

  const header = tableColumns.value
  const dataRows = filteredRows.value.map((row) =>
    header.map((col) => {
      const val = row[col]
      if (val === null || val === undefined) return ''
      if (typeof val === 'number') return Math.round(val)
      return val
    })
  )

  const ws = XLSX.utils.aoa_to_sheet([header, ...dataRows])

  // auto column width
  const colWidths = header.map((col, ci) => {
    const maxLen = Math.max(
      col.length,
      ...dataRows.map((r) => String(r[ci] ?? '').length)
    )
    return { wch: maxLen + 2 }
  })
  ws['!cols'] = colWidths

  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Digiling per SPA')

  const filename = `Digiling_Per_SPA_HR${String(hrGil.value).padStart(3, '0')}.xlsx`
  XLSX.writeFile(wb, filename)
}

onMounted(async () => {
  try {
    await fetchDefault()
  } catch {
    maxHrGil.value = 1
    hrGil.value = 1
  }

  await loadData(false)
  refreshTimer = setInterval(() => loadData(true), 60000)
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
          <span class="mb-1 block">Hari Giling</span>
          <select
            v-model.number="hrGil"
            class="h-10 w-28 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-yellow-400"
          >
            <option v-for="value in hrGilOptions" :key="`hr-gil-${value}`" :value="value">
              {{ padHari(value) }}
            </option>
          </select>
        </label>

        <button
          @click="onApplyFilter"
          :disabled="loadingByFilter"
          class="h-10 rounded-lg bg-slate-800 px-6 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-60 dark:bg-slate-700 dark:hover:bg-slate-600 self-end"
        >
          {{ loadingByFilter ? 'Memuat...' : 'Lihat' }}
        </button>

        <label class="text-sm text-gray-700 dark:text-gray-200">
          <span class="mb-1 block">Cari</span>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari di semua kolom..."
            class="h-10 w-64 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 text-sm text-gray-800 dark:text-gray-100 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-400"
          />
        </label>

        <button
          @click="exportToExcel"
          :disabled="!filteredRows.length || loadingByFilter"
          class="h-10 rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 px-5 text-sm font-medium text-white transition-colors self-end flex items-center gap-1.5"
          type="button"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 3v13m0 0l-4-4m4 4l4-4" />
          </svg>
          Export Excel
        </button>

        <span v-if="isBackground" class="flex items-center gap-1.5 text-xs text-gray-400">
          <span class="inline-block h-2 w-2 animate-ping rounded-full bg-yellow-400"></span>
          Memperbarui...
        </span>

        <span class="self-end text-xs text-gray-500 dark:text-gray-400">
          Tampil {{ filteredRows.length.toLocaleString('id-ID') }} dari {{ rows.length.toLocaleString('id-ID') }} baris
        </span>
      </div>

      <div class="max-h-[70vh] overflow-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full min-w-[900px] text-sm">
          <thead>
            <tr class="bg-gray-200/90 dark:bg-gray-800 text-gray-800 dark:text-gray-100">
              <th
                v-for="column in tableColumns"
                :key="`head-${column}`"
                :class="['sticky top-0 z-10 bg-gray-200/95 dark:bg-gray-800', getHeaderClass(column)]"
              >
                {{ formatHeader(column) }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="initialLoading">
              <td :colspan="Math.max(tableColumns.length, 1)" class="px-3 py-6 text-center text-slate-500 dark:text-slate-300">
                Memuat data digiling per SPA...
              </td>
            </tr>
            <tr v-else-if="errorMessage && rows.length === 0">
              <td :colspan="Math.max(tableColumns.length, 1)" class="px-3 py-6 text-center text-red-600 dark:text-red-400">
                {{ errorMessage }}
              </td>
            </tr>
            <tr v-else-if="rows.length === 0">
              <td :colspan="Math.max(tableColumns.length, 1)" class="px-3 py-6 text-center text-slate-500 dark:text-slate-300">
                Belum ada data digiling per SPA.
              </td>
            </tr>
            <tr v-else-if="filteredRows.length === 0">
              <td :colspan="Math.max(tableColumns.length, 1)" class="px-3 py-6 text-center text-slate-500 dark:text-slate-300">
                Tidak ada data yang cocok dengan pencarian.
              </td>
            </tr>
            <tr
              v-for="(row, index) in filteredRows"
              :key="`row-${index}`"
              :class="[
                'text-gray-800 dark:text-gray-100',
                index % 2 === 0
                  ? 'bg-cyan-50/70 dark:bg-cyan-950/20'
                  : 'bg-white dark:bg-gray-900',
              ]"
            >
              <td
                v-for="column in tableColumns"
                :key="`cell-${index}-${column}`"
                :class="getCellClass(column, row[column])"
              >
                {{ formatCell(row[column]) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
