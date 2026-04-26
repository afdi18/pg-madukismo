<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'

type QueueRow = {
  id: number
  spa: string
  noTruk: string
  induk: string
  nmktgr: string
  petani: string
  kebun: string
  tglMasuk: string
  jamMasuk: string
  lamaTinggal: string
  lamaJam: number
}

const sudahTimbangRows = ref<QueueRow[]>([])

const belumTimbangRows = ref<QueueRow[]>([])
const initialLoading = ref(false)
const initialLoadingBelum = ref(false)
const errorMessage = ref('')
const errorMessageBelum = ref('')
const sortLamaSudah = ref<'asc' | 'desc'>('desc')
const sortLamaBelum = ref<'asc' | 'desc'>('desc')
let refreshTimer: ReturnType<typeof setInterval> | null = null

async function loadAntrianTrukSudahTimbang(isBackgroundRefresh = false) {
  if (!isBackgroundRefresh) {
    initialLoading.value = true
  }

  try {
    const { data } = await axios.get('/api/penerimaan/antrian-truk-sudah-timbang')
    const rows = Array.isArray(data?.data) ? data.data : []

    sudahTimbangRows.value = rows.map((row: any, index: number) => ({
      id: index + 1,
      spa: row.spa ?? '-',
      noTruk: String(row.nopol ?? '-').toUpperCase(),
      induk: row.induk ?? '-',
      nmktgr: row.nmktgr ?? '-',
      petani: row.petani ?? '-',
      kebun: row.kebun ?? '-',
      tglMasuk: row.tgl_msk ?? '-',
      jamMasuk: row.jam_msk != null ? String(row.jam_msk).slice(0, 8) : '-',
      lamaTinggal: row.lama != null ? `${row.lama} Jam` : '-',
      lamaJam: Number(row.lama ?? 0),
    }))
    errorMessage.value = ''
  } catch (error: any) {
    console.error(error)
    errorMessage.value = error?.response?.data?.error || error?.message || 'Gagal memuat data antrian truk sudah timbang'
    if (!isBackgroundRefresh) {
      sudahTimbangRows.value = []
    }
  } finally {
    if (!isBackgroundRefresh) {
      initialLoading.value = false
    }
  }
}

async function loadAntrianTrukBelumTimbang(isBackgroundRefresh = false) {
  if (!isBackgroundRefresh) {
    initialLoadingBelum.value = true
  }

  try {
    const { data } = await axios.get('/api/penerimaan/antrian-truk-belum-timbang')
    const rows = Array.isArray(data?.data) ? data.data : []

    belumTimbangRows.value = rows.map((row: any, index: number) => ({
      id: index + 1,
      spa: row.spa ?? '-',
      noTruk: String(row.nopol ?? '-').toUpperCase(),
      induk: row.induk ?? '-',
      nmktgr: row.nmktgr ?? '-',
      petani: row.petani ?? '-',
      kebun: row.kebun ?? '-',
      tglMasuk: row.tgl_msk ?? '-',
      jamMasuk: row.jam_msk != null ? String(row.jam_msk).slice(0, 8) : '-',
      lamaTinggal: row.lama != null ? `${row.lama} Jam` : '-',
      lamaJam: Number(row.lama ?? 0),
    }))
    errorMessageBelum.value = ''
  } catch (error: any) {
    console.error(error)
    errorMessageBelum.value = error?.response?.data?.error || error?.message || 'Gagal memuat data antrian truk belum timbang'
    if (!isBackgroundRefresh) {
      belumTimbangRows.value = []
    }
  } finally {
    if (!isBackgroundRefresh) {
      initialLoadingBelum.value = false
    }
  }
}

const totalSudah = computed(() => sudahTimbangRows.value.length)
const totalBelum = computed(() => belumTimbangRows.value.length)
const totalSemua = computed(() => totalSudah.value + totalBelum.value)

function beratPerTrukByKategori(nmktgr: string): number {
  return String(nmktgr ?? '').trim().toUpperCase() === 'TRK B' ? 80 : 60
}

const totalEstimasiBerat = computed(() => {
  const semuaRows = [...sudahTimbangRows.value, ...belumTimbangRows.value]
  const total = semuaRows.reduce((sum, row) => sum + beratPerTrukByKategori(row.nmktgr), 0)
  return total.toLocaleString('id-ID')
})

const sortedSudahRows = computed(() => {
  const rows = [...sudahTimbangRows.value]
  rows.sort((a, b) => sortLamaSudah.value === 'asc' ? a.lamaJam - b.lamaJam : b.lamaJam - a.lamaJam)
  return rows
})

const sortedBelumRows = computed(() => {
  const rows = [...belumTimbangRows.value]
  rows.sort((a, b) => sortLamaBelum.value === 'asc' ? a.lamaJam - b.lamaJam : b.lamaJam - a.lamaJam)
  return rows
})

function toggleSortLamaSudah() {
  sortLamaSudah.value = sortLamaSudah.value === 'asc' ? 'desc' : 'asc'
}

function toggleSortLamaBelum() {
  sortLamaBelum.value = sortLamaBelum.value === 'asc' ? 'desc' : 'asc'
}

onMounted(() => {
  loadAntrianTrukSudahTimbang(false)
  loadAntrianTrukBelumTimbang(false)
  refreshTimer = setInterval(() => {
    loadAntrianTrukSudahTimbang(true)
    loadAntrianTrukBelumTimbang(true)
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
  <div class="p-4 space-y-5">
    <section class="grid grid-cols-1 sm:grid-cols-4 gap-3">
      <div class="rounded-xl border border-emerald-200/70 bg-emerald-50/80 px-4 py-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
        <p class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Antrian Sdh Timb 1</p>
        <p class="mt-1 text-2xl font-bold text-emerald-800 dark:text-emerald-200">{{ totalSudah }} <span class="text-base font-semibold">Rit</span></p>
      </div>
      <div class="rounded-xl border border-amber-200/70 bg-amber-50/80 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-500/10">
        <p class="text-xs font-medium uppercase tracking-wide text-amber-700 dark:text-amber-300">Antrian Blm Timb 1</p>
        <p class="mt-1 text-2xl font-bold text-amber-800 dark:text-amber-200">{{ totalBelum }} <span class="text-base font-semibold">Rit / - </span><span class="text-base font-semibold">Ku</span></p>
      </div>
      <div class="rounded-xl border border-sky-200/70 bg-sky-50/80 px-4 py-3 dark:border-sky-500/30 dark:bg-sky-500/10">
        <p class="text-xs font-medium uppercase tracking-wide text-sky-700 dark:text-sky-300">Jumlah Antrian Truk</p>
        <p class="mt-1 text-2xl font-bold text-sky-800 dark:text-sky-200">{{ totalSemua }} <span class="text-base font-semibold">Rit</span></p>
      </div>
      <div class="rounded-xl border border-sky-200/70 bg-sky-50/80 px-4 py-3 dark:border-sky-500/30 dark:bg-sky-500/10">
        <p class="text-xs font-medium uppercase tracking-wide text-sky-700 dark:text-sky-300">Estimasi Berat</p>
        <p class="mt-1 text-2xl font-bold text-sky-800 dark:text-sky-200">{{ totalEstimasiBerat }} <span class="text-base font-semibold">Ku</span></p>
      </div>
    </section>

    <section class="space-y-2">
      <h2 class="text-center text-lg font-bold text-gray-800 dark:text-white">ANTRIAN TRUK SUDAH TIMBANG 1</h2>
      <div class="max-h-[460px] overflow-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-[1100px] w-full text-sm text-gray-700 dark:text-slate-100">
          <thead class="bg-gray-100 dark:bg-gray-800">
            <tr class="text-gray-700 dark:text-white">
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-center dark:bg-gray-800">No</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">SPA</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">No Truk</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">Induk</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">Kategori</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">Petani</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">Kebun</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-center dark:bg-gray-800">Tgl Masuk</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-center dark:bg-gray-800">Jam Masuk</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-center dark:bg-gray-800">
                <button @click="toggleSortLamaSudah" class="inline-flex items-center gap-1 font-medium">
                  Jam Lama Tinggal
                  <span>{{ sortLamaSudah === 'asc' ? '↑' : '↓' }}</span>
                </button>
              </th>
            </tr>
          </thead>
          <tbody class="dark:text-slate-100">
            <tr v-if="initialLoading">
              <td colspan="10" class="px-3 py-6 text-center text-slate-500 dark:text-slate-300">Memuat data antrian truk...</td>
            </tr>
            <tr v-else-if="errorMessage && sudahTimbangRows.length === 0">
              <td colspan="10" class="px-3 py-6 text-center text-red-600 dark:text-red-400">{{ errorMessage }}</td>
            </tr>
            <tr v-else-if="sudahTimbangRows.length === 0">
              <td colspan="10" class="px-3 py-6 text-center text-slate-500 dark:text-slate-300">Belum ada data antrian truk sudah timbang.</td>
            </tr>
            <tr
              v-for="(row, index) in sortedSudahRows"
              :key="`sudah-${row.id}`"
              class="border-t border-gray-100 dark:border-gray-800"
              :class="row.lamaJam > 36 ? 'text-red-600 dark:text-red-400 font-medium' : ''"
            >
              <td class="px-3 py-2 text-center">{{ index + 1 }}</td>
              <td class="px-3 py-2">{{ row.spa }}</td>
              <td class="px-3 py-2">{{ row.noTruk }}</td>
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

    <section class="space-y-2">
      <h2 class="text-center text-lg font-bold text-gray-800 dark:text-white">ANTRIAN TRUK BELUM TIMBANG 1</h2>
      <div class="max-h-[460px] overflow-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-[1100px] w-full text-sm text-gray-700 dark:text-slate-100">
          <thead class="bg-gray-100 dark:bg-gray-800">
            <tr class="text-gray-700 dark:text-white">
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-center dark:bg-gray-800">No</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">SPA</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">No Truk</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">Induk</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">Kategori</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">Petani</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-left dark:bg-gray-800">Kebun</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-center dark:bg-gray-800">Tgl Masuk</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-center dark:bg-gray-800">Jam Masuk</th>
              <th class="sticky top-0 z-10 bg-gray-100 px-3 py-2 text-center dark:bg-gray-800">
                <button @click="toggleSortLamaBelum" class="inline-flex items-center gap-1 font-medium">
                  Jam Lama Tinggal
                  <span>{{ sortLamaBelum === 'asc' ? '↑' : '↓' }}</span>
                </button>
              </th>
            </tr>
          </thead>
          <tbody class="dark:text-slate-100">
            <tr v-if="initialLoadingBelum">
              <td colspan="10" class="px-3 py-6 text-center text-slate-500 dark:text-slate-300">Memuat data antrian truk...</td>
            </tr>
            <tr v-else-if="errorMessageBelum && belumTimbangRows.length === 0">
              <td colspan="10" class="px-3 py-6 text-center text-red-600 dark:text-red-400">{{ errorMessageBelum }}</td>
            </tr>
            <tr v-else-if="belumTimbangRows.length === 0">
              <td colspan="10" class="px-3 py-6 text-center text-slate-500 dark:text-slate-300">Belum ada data antrian truk belum timbang.</td>
            </tr>
            <tr
              v-for="(row, index) in sortedBelumRows"
              :key="`belum-${row.id}`"
              class="border-t border-gray-100 dark:border-gray-800"
              :class="row.lamaJam > 36 ? 'text-red-600 dark:text-red-400 font-medium' : ''"
            >
              <td class="px-3 py-2 text-center">{{ index + 1 }}</td>
              <td class="px-3 py-2">{{ row.spa }}</td>
              <td class="px-3 py-2">{{ row.noTruk }}</td>
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
