<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'

type LoriRow = {
  id: number
  spa: string
  noTruk: string
  noLori: string
  berat: number
  brtStlhRaf: number
  induk: string
  nmktgr: string
  petani: string
  kebun: string
  tglMasuk: string
  jamMasuk: string
  lamaTinggal: string
  lamaJam: number
}

const antrianLoriRows = ref<LoriRow[]>([])
const initialLoading = ref(false)
const errorMessage = ref('')
const sortLama = ref<'asc' | 'desc'>('desc')
let refreshTimer: ReturnType<typeof setInterval> | null = null

function formatNoLori(value: unknown): string {
  if (value == null || value === '') return '-'
  const asNumber = Number(value)
  if (Number.isNaN(asNumber)) return '-'
  return String(Math.trunc(asNumber)).padStart(4, '0')
}

async function loadAntrianLori(isBackgroundRefresh = false) {
  if (!isBackgroundRefresh) {
    initialLoading.value = true
  }

  try {
    const { data } = await axios.get('/api/penerimaan/antrian-lori')
    const rows = Array.isArray(data?.data) ? data.data : []

    antrianLoriRows.value = rows.map((row: any, index: number) => ({
      id: index + 1,
      spa: row.spa ?? '-',
      noTruk: String(row.nopol ?? '-').toUpperCase(),
      noLori: formatNoLori(row.no_lori),
      berat: Number(row.kw_netto ?? 0),
      brtStlhRaf: Number(row.kw_netto ?? 0),
      induk: row.induk ?? '-',
      nmktgr: row.nmktgr ?? '-',
      petani: row.petani ?? '-',
      kebun: row.kebun ?? '-',
      tglMasuk: row.tgl_msk ?? '-',
      jamMasuk: row.jam_msk ?? '-',
      lamaTinggal: row.lama != null ? `${row.lama} Jam` : '-',
      lamaJam: Number(row.lama ?? 0),
    }))
    errorMessage.value = ''
  } catch (error: any) {
    console.error(error)
    errorMessage.value = error?.response?.data?.error || error?.message || 'Gagal memuat data antrian lori'
    if (!isBackgroundRefresh) {
      antrianLoriRows.value = []
    }
  } finally {
    if (!isBackgroundRefresh) {
      initialLoading.value = false
    }
  }
}

const totalLori = computed(() => antrianLoriRows.value.length)
const totalBeratLori = computed(() => antrianLoriRows.value.reduce((total, row) => total + row.berat, 0))
const totalBeratLoriKuintal = computed(() => totalBeratLori.value)
const totalLoriMerah = computed(() => {
  // Lori merah adalah lori dengan lama tinggal > 36 jam
  return antrianLoriRows.value.filter(row => row.lamaJam > 36).length
})

const sortedLoriRows = computed(() => {
  const rows = [...antrianLoriRows.value]
  rows.sort((a, b) => sortLama.value === 'asc' ? a.lamaJam - b.lamaJam : b.lamaJam - a.lamaJam)
  return rows
})

function toggleSortLama() {
  sortLama.value = sortLama.value === 'asc' ? 'desc' : 'asc'
}

function formatKuintal(value: number) {
  return Math.round(value).toLocaleString('id-ID')
}

onMounted(() => {
  loadAntrianLori(false)
  refreshTimer = setInterval(() => loadAntrianLori(true), 30000)
})

onBeforeUnmount(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
})
</script>

<template>
  <div class="p-4 space-y-5">
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div class="rounded-xl border border-violet-200/70 bg-violet-50/80 px-4 py-3 dark:border-violet-500/30 dark:bg-violet-500/10">
        <p class="text-xs font-semibold uppercase tracking-wide text-violet-700 dark:text-white">Lori Aktif</p>
        <p class="mt-1 text-2xl font-extrabold text-violet-800 dark:text-white dark:[text-shadow:0_0_14px_rgba(255,255,255,0.35)]">{{ totalLori }} <span class="text-base font-bold text-violet-700 dark:text-violet-100">Rit</span></p>
        <p class="mt-2 text-sm font-semibold text-violet-600 dark:text-violet-300">(Lori Merah: {{ totalLoriMerah }} Rit)</p>
      </div>
      <div class="rounded-xl border border-emerald-200/70 bg-emerald-50/80 px-4 py-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
        <p class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Total Berat</p>
        <p class="mt-1 text-2xl font-bold text-emerald-800 dark:text-emerald-200">{{ formatKuintal(totalBeratLoriKuintal) }} <span class="text-base font-semibold">Kuintal</span></p>
      </div>
    </section>

    <section class="space-y-2">
      <h2 class="text-center text-lg font-bold text-gray-800 dark:text-white">ANTRIAN LORI</h2>
      <div class="max-h-[460px] overflow-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-[1200px] w-full text-sm text-gray-700 dark:text-slate-100">
          <thead class="bg-gray-100 dark:bg-gray-800">
            <tr class="text-gray-700 dark:text-white">
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-center dark:bg-gray-800">No</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">SPA</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">No Truk</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">No Lori</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-right dark:bg-gray-800">Berat</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-right dark:bg-gray-800">Brt Stlh Raf</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">Induk</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">Kategori</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">Petani</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">Kebun</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-center dark:bg-gray-800">Tgl Masuk</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-center dark:bg-gray-800">Jam Masuk</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-center dark:bg-gray-800">
                <button @click="toggleSortLama" class="inline-flex items-center gap-1 font-medium">
                  Jam Lama Tinggal
                  <span>{{ sortLama === 'asc' ? '↑' : '↓' }}</span>
                </button>
              </th>
            </tr>
          </thead>
          <tbody class="dark:text-slate-100">
            <tr v-if="initialLoading">
              <td colspan="12" class="px-3 py-6 text-center text-slate-500 dark:text-slate-300">Memuat data antrian lori...</td>
            </tr>
            <tr v-else-if="errorMessage && antrianLoriRows.length === 0">
              <td colspan="12" class="px-3 py-6 text-center text-red-600 dark:text-red-400">{{ errorMessage }}</td>
            </tr>
            <tr v-else-if="antrianLoriRows.length === 0">
              <td colspan="12" class="px-3 py-6 text-center text-slate-500 dark:text-slate-300">Belum ada data antrian lori.</td>
            </tr>
            <tr
              v-for="(row, index) in sortedLoriRows"
              :key="`lori-${row.id}`"
              class="border-t border-gray-100 dark:border-gray-800"
              :class="row.lamaJam > 36 ? 'text-red-600 dark:text-red-400 font-medium' : ''"
            >
              <td class="px-3 py-2 text-center">{{ index + 1 }}</td>
              <td class="px-3 py-2">{{ row.spa }}</td>
              <td class="px-3 py-2">{{ row.noTruk }}</td>
              <td class="px-3 py-2">{{ row.noLori }}</td>
              <td class="px-3 py-2 text-right">{{ row.berat }}</td>
              <td class="px-3 py-2 text-right">{{ row.brtStlhRaf }}</td>
              <td class="px-3 py-2">{{ row.induk }}</td>
              <td class="px-3 py-2">{{ row.nmktgr }}</td>
              <td class="px-3 py-2">{{ row.petani }}</td>
              <td class="px-3 py-2">{{ row.kebun }}</td>
              <td class="px-3 py-2 text-center">{{ row.tglMasuk }}</td>
              <td class="px-3 py-2 text-center">{{ row.jamMasuk }}</td>
              <td class="px-3 py-2 text-center">{{ row.lamaTinggal }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>
