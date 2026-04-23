<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'

type KebunRow = {
  no:             number | string
  register:       string
  kelompok:       string
  kebun:          string
  luasHa:         number
  taksasi:        number
  hariIniRit:     number
  hariIniBerat:   number
  sdHariIniRit:   number
  sdHariIniBerat: number
  produktivitas:  number
  persentase:     number
}

// ── helpers ────────────────────────────────────────────────
function toMmDdYyyy(iso: string): string {
  if (!iso) return ''
  const [y, m, d] = iso.split('-')
  return `${m}/${d}/${y}`
}

function todayIso(): string {
  const now = new Date()
  const y = now.getFullYear()
  const m = String(now.getMonth() + 1).padStart(2, '0')
  const d = String(now.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

// ── state ──────────────────────────────────────────────────
const tanggalIso  = ref(todayIso())
const rows        = ref<KebunRow[]>([])
const loading     = ref(false)
const isBackground = ref(false)
const errorMsg    = ref('')
let   timer: ReturnType<typeof setInterval> | null = null

// ── SP row mapping — cover common SQLSRV column name variants ──
// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapRow(r: any, idx: number): KebunRow {
  return {
    no:             r.no            ?? r.NO            ?? r.urut       ?? (idx + 1),
    register:       r.register      ?? r.REGISTER      ?? r.folio      ?? r.FOLIO      ?? '',
    kelompok:       r.kelompok      ?? r.KELOMPOK      ?? r.klmpk      ?? r.KLMPK      ?? '',
    kebun:          r.kebun         ?? r.KEBUN         ?? r.nminduk    ?? r.nmkebun    ?? r.NMINDUK ?? '',
    luasHa:         Number(r.luas   ?? r.LUAS          ?? r.luasha     ?? r.LUASHA     ?? 0),
    taksasi:        Number(r.taksasi ?? r.TAKSASI      ?? 0),
    hariIniRit:     Number(r.rithi  ?? r.RITHI         ?? r.rit_hi     ?? 0),
    hariIniBerat:   Number(r.berathi ?? r.BERATHI      ?? r.berat_hi   ?? 0),
    sdHariIniRit:   Number(r.ritsd  ?? r.RITSD         ?? r.rit_sd     ?? 0),
    sdHariIniBerat: Number(r.beratsd ?? r.BERATSD      ?? r.berat_sd   ?? 0),
    produktivitas:  Number(r.produktivitas ?? r.PRODUKTIVITAS ?? r.prodk ?? r.PRODK ?? 0),
    persentase:     Number(r.persen  ?? r.PERSEN        ?? r.persentase ?? r.PERSENTASE ?? 0),
  }
}

// ── fetch ──────────────────────────────────────────────────
async function fetchData(background = false) {
  if (!tanggalIso.value) return
  isBackground.value = background
  if (!background) loading.value = true
  errorMsg.value = ''

  try {
    const tanggal = toMmDdYyyy(tanggalIso.value)
    const res = await axios.get('/api/penerimaan/pemasukan-kebun', { params: { tanggal } })
    const raw: unknown[] = res.data?.data ?? []
    rows.value = raw.map((r, i) => mapRow(r, i))
  } catch (e: unknown) {
    if (!background) errorMsg.value = 'Gagal memuat data.'
    console.error(e)
  } finally {
    loading.value   = false
    isBackground.value = false
  }
}

// ── totals ─────────────────────────────────────────────────
const totalLuasHa       = computed(() => rows.value.reduce((s, r) => s + r.luasHa, 0))
const totalTaksasi      = computed(() => rows.value.reduce((s, r) => s + r.taksasi, 0))
const totalHariIniRit   = computed(() => rows.value.reduce((s, r) => s + r.hariIniRit, 0))
const totalHariIniBerat = computed(() => rows.value.reduce((s, r) => s + r.hariIniBerat, 0))
const totalSdRit        = computed(() => rows.value.reduce((s, r) => s + r.sdHariIniRit, 0))
const totalSdBerat      = computed(() => rows.value.reduce((s, r) => s + r.sdHariIniBerat, 0))

function fmt(n: number): string {
  return n.toLocaleString('id-ID')
}

// ── lifecycle ──────────────────────────────────────────────
onMounted(() => {
  fetchData()
  timer = setInterval(() => fetchData(true), 60_000)
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})
</script>

<template>
  <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl overflow-hidden">
    <div class="p-4 space-y-4">
      <!-- Filter -->
      <div class="flex flex-wrap items-end gap-3">
        <label class="text-sm text-gray-700 dark:text-gray-200">
          <span class="mb-1 block">Tanggal</span>
          <input
            v-model="tanggalIso"
            type="date"
            class="h-10 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 text-sm text-gray-800 dark:text-gray-100"
          />
        </label>

        <button
          class="h-10 rounded-lg bg-slate-800 px-6 text-sm font-medium text-white hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 self-end disabled:opacity-50"
          :disabled="loading"
          @click="fetchData(false)"
        >
          Lihat
        </button>

        <span v-if="isBackground" class="self-end text-xs text-gray-400 animate-pulse">Memperbarui…</span>
      </div>

      <!-- error -->
      <p v-if="errorMsg" class="text-sm text-red-500">{{ errorMsg }}</p>

      <!-- loading -->
      <div v-if="loading" class="flex items-center justify-center py-10 text-gray-400 text-sm">
        Memuat data…
      </div>

      <!-- table -->
      <div v-else class="max-h-[500px] overflow-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full min-w-[1400px] text-sm">
          <thead>
            <tr class="bg-gray-200/90 dark:bg-gray-800 text-gray-800 dark:text-gray-100">
              <th rowspan="2" class="sticky top-0 z-10 bg-gray-200/90 dark:bg-gray-800 px-2 py-2 text-center border border-gray-300 dark:border-gray-700">No</th>
              <th rowspan="2" class="sticky top-0 z-10 bg-gray-200/90 dark:bg-gray-800 px-2 py-2 text-center border border-gray-300 dark:border-gray-700">Register</th>
              <!-- <th rowspan="2" class="sticky top-0 z-10 bg-gray-200/90 dark:bg-gray-800 px-2 py-2 text-center border border-gray-300 dark:border-gray-700">Kelompok</th> -->
              <th rowspan="2" class="sticky top-0 z-10 bg-gray-200/90 dark:bg-gray-800 px-2 py-2 text-center border border-gray-300 dark:border-gray-700">Kebun</th>
              <!-- <th rowspan="2" class="sticky top-0 z-10 bg-gray-200/90 dark:bg-gray-800 px-2 py-2 text-center border border-gray-300 dark:border-gray-700">Luas (Ha)</th> -->
              <!-- <th rowspan="2" class="sticky top-0 z-10 bg-gray-200/90 dark:bg-gray-800 px-2 py-2 text-center border border-gray-300 dark:border-gray-700">Taksasi</th> -->
              <th colspan="2" class="sticky top-0 z-10 bg-gray-200/90 dark:bg-gray-800 px-2 py-2 text-center border border-gray-300 dark:border-gray-700">Hari Ini</th>
              <th colspan="2" class="sticky top-0 z-10 bg-gray-200/90 dark:bg-gray-800 px-2 py-2 text-center border border-gray-300 dark:border-gray-700">S / d Hari Ini</th>
              <!-- <th rowspan="2" class="sticky top-0 z-10 bg-gray-200/90 dark:bg-gray-800 px-2 py-2 text-center border border-gray-300 dark:border-gray-700">Produktifitas (Ku/Ha)</th> -->
              <!-- <th rowspan="2" class="sticky top-0 z-10 bg-gray-200/90 dark:bg-gray-800 px-2 py-2 text-center border border-gray-300 dark:border-gray-700">(%) Terhadap Taks</th> -->
            </tr>
            <tr class="bg-gray-200/90 dark:bg-gray-800 text-gray-800 dark:text-gray-100">
              <th class="sticky top-[37px] z-10 bg-gray-200/90 dark:bg-gray-800 px-2 py-1.5 text-center border border-gray-300 dark:border-gray-700">Rit</th>
              <th class="sticky top-[37px] z-10 bg-gray-200/90 dark:bg-gray-800 px-2 py-1.5 text-center border border-gray-300 dark:border-gray-700">Berat (Ku)</th>
              <th class="sticky top-[37px] z-10 bg-gray-200/90 dark:bg-gray-800 px-2 py-1.5 text-center border border-gray-300 dark:border-gray-700">Rit</th>
              <th class="sticky top-[37px] z-10 bg-gray-200/90 dark:bg-gray-800 px-2 py-1.5 text-center border border-gray-300 dark:border-gray-700">Berat (Ku)</th>
            </tr>
          </thead>
          <tbody>
            <!-- empty -->
            <tr v-if="rows.length === 0">
              <td colspan="12" class="px-4 py-6 text-center text-gray-400 border border-gray-200 dark:border-gray-700">
                Tidak ada data
              </td>
            </tr>

            <!-- data rows -->
            <tr
              v-for="row in rows"
              :key="String(row.no) + row.register"
              class="bg-cyan-50/70 dark:bg-cyan-950/20 text-gray-800 dark:text-gray-100"
              :class="row.persentase > 100 ? 'text-red-600 dark:text-red-400 font-medium' : ''"
            >
              <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-center">{{ row.no }}</td>
              <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40">{{ row.register }}</td>
              <!-- <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40">{{ row.kelompok }}</td> -->
              <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40">{{ row.kebun }}</td>
              <!-- <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right">{{ fmt(row.luasHa) }}</td> -->
              <!-- <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right">{{ fmt(row.taksasi) }}</td> -->
              <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right">{{ fmt(row.hariIniRit) }}</td>
              <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right">{{ fmt(row.hariIniBerat) }}</td>
              <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right">{{ fmt(row.sdHariIniRit) }}</td>
              <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right">{{ fmt(row.sdHariIniBerat) }}</td>
              <!-- <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right">{{ fmt(row.produktivitas) }}</td>
              <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right">{{ fmt(row.persentase) }}</td> -->
            </tr>

            <!-- total footer -->
            <tr v-if="rows.length > 0" class="bg-gray-200/90 dark:bg-gray-800 text-gray-900 dark:text-white font-semibold">
              <td colspan="3" class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-center">Jumlah</td>
              <!-- <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ fmt(totalLuasHa) }}</td>
              <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ fmt(totalTaksasi) }}</td> -->
              <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ fmt(totalHariIniRit) }}</td>
              <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ fmt(totalHariIniBerat) }}</td>
              <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ fmt(totalSdRit) }}</td>
              <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ fmt(totalSdBerat) }}</td>
              <!-- <td class="px-2 py-2 border border-gray-300 dark:border-gray-700"></td>
              <td class="px-2 py-2 border border-gray-300 dark:border-gray-700"></td> -->
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
