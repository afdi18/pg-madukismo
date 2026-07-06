<script setup lang="ts">

import { ref, reactive, onMounted, watch } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { SearchIcon } from 'lucide-vue-next'

const authStore = useAuthStore()


// Table & filter state
const rows = ref<any[]>([])
const rowsTemp = ref<any[]>([])
const loading = ref(false)
const isFading = ref(false)
const posList = ref<{ idpos: number, nmpos: string }[]>([])
const selectedPos = ref('')
const selectedDate = ref(new Date().toISOString().slice(0, 10))
const timerRef = ref<NodeJS.Timeout | null>(null)

// Card summary
const summary = ref({
  madubaru1: 0,
  madubaru2: 0,
  madubaru3: 0,
  madubaru4: 0,
  madubaru5: 0,
  sragenTimur: 0,
  sragenBarat: 0,
  sragenSelatan: 0,
})

const SUMMARY_KEY_CANDIDATES = ['idpos', 'IDPOS', 'pos', 'POS', 'id_pos', 'pos_id', 'idPos']

function getRowPosId(row: Record<string, any>): number | null {
  for (const key of SUMMARY_KEY_CANDIDATES) {
    const value = row?.[key]
    if (value !== undefined && value !== null && value !== '') {
      const parsed = Number(value)
      if (Number.isFinite(parsed)) return parsed
    }
  }
  return null
}

function buildSummaryFromRows(arr: any[]) {
  const counts: Record<number, number> = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0, 6: 0, 7: 0, 9: 0 }

  for (const row of arr) {
    const posId = getRowPosId(row)
    if (posId !== null && counts[posId] !== undefined) {
      counts[posId] += 1
    }
  }

  return {
    madubaru1: counts[1],
    madubaru2: counts[2],
    madubaru3: counts[3],
    madubaru4: counts[4],
    madubaru5: counts[5],
    sragenTimur: counts[6],
    sragenBarat: counts[7],
    sragenSelatan: counts[9],
  }
}

async function fetchPosList() {
  // Ganti endpoint sesuai backend Anda
  const { data } = await axios.get('/api/epos/pospantau')
  posList.value = data?.data ?? []
}

async function loadData(isBackgroundRefresh = false) {
  if (!isBackgroundRefresh) loading.value = true
  try {
    const { data } = await axios.get('/api/epos/spa-data', {
      params: {
        tgl: selectedDate.value,
        pos: selectedPos.value || undefined,
      },
    })
    // Tambahkan field no (nomor urut) pada setiap baris
    const addNo = (arr: any[]) => arr.map((row, idx) => ({ no: idx + 1, ...row }))
    const mappedRows = addNo(data.rows ?? [])
    const nextSummary = buildSummaryFromRows(mappedRows)

    if (isBackgroundRefresh) {
      rowsTemp.value = mappedRows
      summary.value = nextSummary
      isFading.value = true
      setTimeout(() => {
        rows.value = rowsTemp.value
        isFading.value = false
      }, 350)
    } else {
      rows.value = mappedRows
      summary.value = nextSummary
    }
  } catch (e) {
    console.error(e)
  } finally {
    if (!isBackgroundRefresh) loading.value = false
  }
}

function startAutoRefresh() {
  if (timerRef.value) clearInterval(timerRef.value)
  timerRef.value = setInterval(() => loadData(true), 60000)
}

onMounted(async () => {
  await fetchPosList()
  await loadData()
  startAutoRefresh()
})

watch([selectedDate, selectedPos], () => loadData())
</script>

<template>
  <div>
    <!-- Card summary -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-3 mb-4">
      <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4 flex flex-col items-center shadow">
        <div class="text-xs text-gray-500 mb-1">Madubaru (ID 1)</div>
        <div class="text-2xl font-bold text-yellow-600">{{ summary.madubaru1 }}</div>
      </div>
      <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4 flex flex-col items-center shadow">
        <div class="text-xs text-gray-500 mb-1">Madubaru (ID 2)</div>
        <div class="text-2xl font-bold text-yellow-600">{{ summary.madubaru2 }}</div>
      </div>
      <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4 flex flex-col items-center shadow">
        <div class="text-xs text-gray-500 mb-1">Madubaru (ID 3)</div>
        <div class="text-2xl font-bold text-yellow-600">{{ summary.madubaru3 }}</div>
      </div>
      <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4 flex flex-col items-center shadow">
        <div class="text-xs text-gray-500 mb-1">Madubaru (ID 4)</div>
        <div class="text-2xl font-bold text-yellow-600">{{ summary.madubaru4 }}</div>
      </div>
      <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4 flex flex-col items-center shadow">
        <div class="text-xs text-gray-500 mb-1">Madubaru (ID 5)</div>
        <div class="text-2xl font-bold text-yellow-600">{{ summary.madubaru5 }}</div>
      </div>
      <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4 flex flex-col items-center shadow">
        <div class="text-xs text-gray-500 mb-1">Sragen Timur (ID 6)</div>
        <div class="text-2xl font-bold text-blue-600">{{ summary.sragenTimur }}</div>
      </div>
      <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4 flex flex-col items-center shadow">
        <div class="text-xs text-gray-500 mb-1">Sragen Barat (ID 7)</div>
        <div class="text-2xl font-bold text-green-600">{{ summary.sragenBarat }}</div>
      </div>
      <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4 flex flex-col items-center shadow">
        <div class="text-xs text-gray-500 mb-1">Sragen Selatan (ID 9)</div>
        <div class="text-2xl font-bold text-red-600">{{ summary.sragenSelatan }}</div>
      </div>
    </div>

    <!-- Filter bar -->
    <div class="flex flex-col sm:flex-row gap-3 mb-3">
      <input v-model="selectedDate" type="date" class="px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300" />
      <select v-model="selectedPos" class="px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
        <option value="">Semua POS</option>
        <option v-for="pos in posList" :key="pos.idpos" :value="pos.idpos">{{ pos.idpos }} : {{ pos.nmpos }}</option>
      </select>
      <button @click="() => loadData(false)" :disabled="loading" class="px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl text-sm">Refresh</button>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl overflow-hidden">
      <div class="overflow-x-auto" style="max-height: 420px;">
        <transition name="fade" mode="out-in">
          <table v-if="!isFading" class="w-full text-sm">
            <thead class="sticky top-0 z-10 bg-white dark:bg-gray-900">
              <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/40">
                <th v-for="col in (rows[0] ? Object.keys(rows[0]) : [])" :key="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">
                  {{ col }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-800 text-gray-400">
              <tr v-if="loading">
                <td :colspan="rows[0] ? Object.keys(rows[0]).length : 1" class="px-4 py-6">
                  <div class="h-10 bg-gray-100 dark:bg-gray-800 rounded-lg animate-pulse" />
                </td>
              </tr>
              <tr v-else-if="rows.length === 0">
                <td :colspan="rows[0] ? Object.keys(rows[0]).length : 1" class="px-4 py-16 text-center text-sm text-gray-400">Tidak ada data SPA.</td>
              </tr>
              <tr v-else v-for="(r, idx) in rows" :key="r.SPA || idx" class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                <td v-for="col in Object.keys(r)" :key="col" class="px-4 py-3">{{ r[col] }}</td>
              </tr>
            </tbody>
          </table>
          <table v-else class="w-full text-sm opacity-60 pointer-events-none select-none">
            <thead class="sticky top-0 z-10 bg-white dark:bg-gray-900">
              <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/40">
                <th v-for="col in (rowsTemp[0] ? Object.keys(rowsTemp[0]) : [])" :key="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">
                  {{ col }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-800 text-gray-400">
              <tr v-if="loading">
                <td :colspan="rowsTemp[0] ? Object.keys(rowsTemp[0]).length : 1" class="px-4 py-6">
                  <div class="h-10 bg-gray-100 dark:bg-gray-800 rounded-lg animate-pulse" />
                </td>
              </tr>
              <tr v-else-if="rowsTemp.length === 0">
                <td :colspan="rowsTemp[0] ? Object.keys(rowsTemp[0]).length : 1" class="px-4 py-16 text-center text-sm text-gray-400">Tidak ada data SPA.</td>
              </tr>
              <tr v-else v-for="(r, idx) in rowsTemp" :key="r.SPA || idx" class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                <td v-for="col in Object.keys(r)" :key="col" class="px-4 py-3">{{ r[col] }}</td>
              </tr>
            </tbody>
          </table>
        </transition>
      </div>
    <style scoped>
    .fade-enter-active, .fade-leave-active {
      transition: opacity 0.35s;
    }
    .fade-enter-from, .fade-leave-to {
      opacity: 0;
    }
    </style>
    </div>
  </div>
</template>
