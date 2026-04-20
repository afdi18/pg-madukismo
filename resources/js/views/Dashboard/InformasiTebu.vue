<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import VueApexCharts from 'vue3-apexcharts'
import DataDigiling from '@/views/Operasional/DataDigiling.vue'
import {
  PackageIcon, DropletIcon, TrendingUpIcon,
  CalendarIcon, RefreshCwIcon, LayersIcon,
} from 'lucide-vue-next'

// ================================================================
// STATE
// ================================================================
const isLoading     = ref(true)
const isLoadingSpa  = ref(true)
const musimList     = ref<any[]>([])
const musimDipilih  = ref<number>(new Date().getFullYear())
const summary       = ref<any>(null)
const rendemenTrend = ref<any>({ labels: [], rendemen_kebun: [], rendemen_pabrik: [], tonase_masuk: [] })
const perbandingan  = ref<any>({ labels: [], tonase_tebang: [], rendemen_rata: [] })
const periodeFilter = ref('30d')
const spaData       = ref<any[]>([])
const spaTotalPage  = ref(1)
const spaPage       = ref(1)
const spaTotal      = ref(0)

// ================================================================
// FETCH
// ================================================================
async function loadAll() {
  isLoading.value = true
  try {
    const [sumRes, trendRes, perbRes, musimRes] = await Promise.all([
      axios.get('/api/dashboard/summary',        { params: { musim_tanam: musimDipilih.value } }),
      axios.get('/api/dashboard/rendemen-trend', { params: { musim_tanam: musimDipilih.value, periode: periodeFilter.value } }),
      axios.get('/api/dashboard/perbandingan-musim'),
      axios.get('/api/dashboard/musim-list'),
    ])
    summary.value       = sumRes.data
    rendemenTrend.value = trendRes.data
    perbandingan.value  = perbRes.data
    musimList.value     = musimRes.data.data
  } finally {
    isLoading.value = false
  }
}

async function loadSpa(page = 1) {
  isLoadingSpa.value = true
  spaPage.value = page
  try {
    const res = await axios.get('/api/penerimaan', { params: { page, per_page: 10 } })
    spaData.value      = res.data.data
    spaTotal.value     = res.data.meta?.total ?? 0
    spaTotalPage.value = res.data.meta?.last_page ?? 1
  } finally {
    isLoadingSpa.value = false
  }
}

onMounted(() => {
  loadAll()
  loadSpa()
})

// ================================================================
// KPI CARDS
// ================================================================
const kpiCards = computed(() => {
  const r = summary.value?.ringkasan
  const h = summary.value?.hari_ini
  return [
    {
      label: 'Rendemen Kebun',
      value: h?.rendemen_kebun  ? `${h.rendemen_kebun}%`  : r?.rendemen_rata ? `${r.rendemen_rata}%` : '—',
      sub  : 'Rata-rata musim ini',
      icon : DropletIcon,
      color: 'amber',
    },
    {
      label: 'Rendemen Pabrik',
      value: h?.rendemen_pabrik ? `${h.rendemen_pabrik}%` : '—',
      sub  : 'Hari ini',
      icon : DropletIcon,
      color: 'blue',
    },
    {
      label: 'Tonase Masuk',
      value: h?.tonase_masuk ? `${Number(h.tonase_masuk).toLocaleString('id')} ton` : '—',
      sub  : 'Hari ini',
      icon : PackageIcon,
      color: 'green',
    },
    {
      label: 'Total Tonase Tebang',
      value: r?.total_tonase_tebang ? `${Number(r.total_tonase_tebang).toLocaleString('id')} ton` : '—',
      sub  : `Musim ${musimDipilih.value}`,
      icon : TrendingUpIcon,
      color: 'teal',
    },
    {
      label: 'Hari Giling',
      value: r?.jumlah_hari_giling ?? '—',
      sub  : r?.mulai_giling ? `Mulai ${r.mulai_giling}` : '—',
      icon : CalendarIcon,
      color: 'purple',
    },
    {
      label: 'Status Musim',
      value: r?.status_musim ?? '—',
      sub  : `Musim Tanam ${musimDipilih.value}`,
      icon : LayersIcon,
      color: r?.status_musim === 'berjalan' ? 'green' : 'gray',
    },
  ]
})

const colorMap: Record<string, string> = {
  amber : 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400',
  blue  : 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400',
  green : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400',
  teal  : 'bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400',
  purple: 'bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400',
  gray  : 'bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400',
}

// ================================================================
// CHARTS
// ================================================================
const rendemenChartOpts = computed(() => ({
  chart: {
    type: 'line', height: 300, toolbar: { show: false },
    zoom: { enabled: false }, background: 'transparent', fontFamily: 'inherit',
  },
  colors: ['#f59e0b', '#3b82f6'],
  stroke: { curve: 'smooth', width: [2.5, 2.5] },
  fill: { type: 'solid' },
  dataLabels: { enabled: false },
  xaxis: {
    categories: rendemenTrend.value.labels,
    labels: { style: { fontSize: '11px' }, rotate: -30 },
    axisBorder: { show: false }, axisTicks: { show: false },
  },
  yaxis: {
    labels: { formatter: (v: number) => `${v}%`, style: { fontSize: '11px' } },
    min: 0,
  },
  tooltip: { y: { formatter: (v: number) => `${v}%` } },
  grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
  legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px' },
  markers: { size: 3 },
}))

const rendemenSeries = computed(() => [
  { name: 'Rendemen Kebun (%)',  data: rendemenTrend.value.rendemen_kebun },
  { name: 'Rendemen Pabrik (%)', data: rendemenTrend.value.rendemen_pabrik },
])

const tonaseChartOpts = computed(() => ({
  chart: {
    type: 'bar', height: 300, toolbar: { show: false },
    background: 'transparent', fontFamily: 'inherit',
  },
  colors: ['#22c55e'],
  plotOptions: { bar: { borderRadius: 5, columnWidth: '55%' } },
  dataLabels: { enabled: false },
  xaxis: {
    categories: rendemenTrend.value.labels,
    labels: { style: { fontSize: '11px' }, rotate: -30 },
    axisBorder: { show: false }, axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      formatter: (v: number) => v >= 1000 ? `${(v / 1000).toFixed(1)}K` : `${v}`,
      style: { fontSize: '11px' },
    },
  },
  tooltip: { y: { formatter: (v: number) => `${Number(v).toLocaleString('id')} ton` } },
  grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
}))

const tonaseSeries = computed(() => [
  { name: 'Tonase Masuk (ton)', data: rendemenTrend.value.tonase_masuk },
])

const perbandinganOpts = computed(() => ({
  chart: {
    type: 'bar', height: 260, toolbar: { show: false },
    background: 'transparent', fontFamily: 'inherit',
  },
  colors: ['#f59e0b'],
  plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%' } },
  dataLabels: { enabled: false },
  xaxis: {
    categories: perbandingan.value.labels ?? [],
    labels: {
      formatter: (v: number) => v >= 1000 ? `${(v / 1000).toFixed(0)}K` : `${v}`,
      style: { fontSize: '11px' },
    },
  },
  yaxis: { labels: { style: { fontSize: '11px' } } },
  tooltip: { y: { formatter: (v: number) => `${Number(v).toLocaleString('id')} ton` } },
  grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
}))

const perbandinganSeries = computed(() => [
  { name: 'Tonase Tebang (ton)', data: perbandingan.value.tonase_tebang ?? [] },
])
</script>

<template>
  <div class="space-y-6">
    <div v-if="false">
    <!-- ============================================================
         HEADER
    ============================================================ -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-white">
          Dashboard Informasi Tebu
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          Ringkasan penerimaan tebu, rendemen, dan tonase — Musim Tanam {{ musimDipilih }}
        </p>
      </div>

      <div class="flex items-center gap-2 flex-wrap">
        <select
          v-model.number="musimDipilih"
          @change="loadAll"
          class="px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700
                 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                 focus:outline-none focus:ring-2 focus:ring-yellow-500"
        >
          <option v-for="m in musimList" :key="m.musim_tanam" :value="m.musim_tanam">
            Musim {{ m.musim_tanam }}
          </option>
        </select>

        <button
          @click="loadAll(); loadSpa(1)"
          :disabled="isLoading"
          class="p-2 rounded-lg border border-gray-200 dark:border-gray-700
                 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400
                 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors"
        >
          <RefreshCwIcon class="w-4 h-4" :class="{ 'animate-spin': isLoading }" />
        </button>

        <span class="text-xs text-gray-400 dark:text-gray-600 hidden sm:block">
          {{ summary?.updated_at ?? '—' }}
        </span>
      </div>
    </div>

    <!-- ============================================================
         KPI CARDS
    ============================================================ -->
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4">
      <div
        v-for="card in kpiCards"
        :key="card.label"
        class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4
               hover:shadow-md transition-shadow duration-200"
      >
        <div class="flex items-start justify-between mb-3">
          <div :class="['p-2 rounded-xl', colorMap[card.color]]">
            <component :is="card.icon" class="w-4 h-4" />
          </div>
        </div>
        <div v-if="isLoading" class="h-7 w-20 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mb-1" />
        <p v-else class="text-xl font-bold text-gray-800 dark:text-white leading-tight">
          {{ card.value }}
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ card.label }}</p>
        <p class="text-[10px] text-gray-400 dark:text-gray-600 mt-0.5">{{ card.sub }}</p>
      </div>
    </div>

    <!-- ============================================================
         CHART ROW — Rendemen Trend & Tonase Harian
    ============================================================ -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">

      <!-- Rendemen Kebun vs Pabrik (3/5) -->
      <div class="xl:col-span-3 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
              Rendemen Kebun vs Pabrik
            </h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
              Perbandingan harian rendemen kebun dan pabrik
            </p>
          </div>
          <div class="flex gap-1">
            <button
              v-for="p in ['7d', '30d', '3m']"
              :key="p"
              @click="periodeFilter = p; loadAll()"
              :class="[
                'px-2.5 py-1 text-xs rounded-lg font-medium transition-colors',
                periodeFilter === p
                  ? 'bg-yellow-500 text-white'
                  : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700',
              ]"
            >{{ p }}</button>
          </div>
        </div>
        <div v-if="isLoading" class="h-[300px] bg-gray-100 dark:bg-gray-800 rounded-xl animate-pulse" />
        <VueApexCharts
          v-else
          type="line"
          height="300"
          :options="rendemenChartOpts"
          :series="rendemenSeries"
        />
      </div>

      <!-- Tonase Masuk Harian (2/5) -->
      <div class="xl:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
        <div class="mb-4">
          <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
            Tonase Masuk Harian
          </h3>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
            Ton tebu masuk gilingan per hari
          </p>
        </div>
        <div v-if="isLoading" class="h-[300px] bg-gray-100 dark:bg-gray-800 rounded-xl animate-pulse" />
        <VueApexCharts
          v-else
          type="bar"
          height="300"
          :options="tonaseChartOpts"
          :series="tonaseSeries"
        />
      </div>

    </div>

    <!-- ============================================================
         ROW 2 — Perbandingan Musim & Tabel SPA
    ============================================================ -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">

      <!-- Perbandingan Tonase Tebang antar Musim (2/5) -->
      <div class="xl:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
        <div class="mb-4">
          <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
            Perbandingan Tonase Tebang
          </h3>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
            5 musim tanam terakhir
          </p>
        </div>
        <div v-if="isLoading" class="h-[260px] bg-gray-100 dark:bg-gray-800 rounded-xl animate-pulse" />
        <VueApexCharts
          v-else
          type="bar"
          height="260"
          :options="perbandinganOpts"
          :series="perbandinganSeries"
        />

        <!-- Rendemen rata-rata per musim -->
        <div
          v-if="!isLoading && perbandingan.rendemen_rata?.length"
          class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800"
        >
          <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-2">
            Rendemen Rata-rata per Musim
          </p>
          <div class="space-y-1.5">
            <div
              v-for="(val, i) in perbandingan.rendemen_rata"
              :key="i"
              class="flex items-center justify-between text-xs"
            >
              <span class="text-gray-500 dark:text-gray-400">{{ perbandingan.labels[i] }}</span>
              <span class="font-semibold text-amber-600 dark:text-amber-400">{{ val }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabel SPA Penerimaan Tebu (3/5) -->
      <div class="xl:col-span-3 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
              Data SPA Penerimaan Tebu
            </h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
              {{ spaTotal.toLocaleString('id') }} data tersedia
            </p>
          </div>
        </div>

        <!-- Skeleton -->
        <div v-if="isLoadingSpa" class="space-y-2">
          <div v-for="i in 8" :key="i" class="h-8 bg-gray-100 dark:bg-gray-800 rounded animate-pulse" />
        </div>

        <!-- Tabel data -->
        <div v-else class="overflow-x-auto">
          <table class="w-full text-xs text-left">
            <thead>
              <tr class="border-b border-gray-100 dark:border-gray-800">
                <th class="pb-2 pr-4 font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">No. SPA</th>
                <th class="pb-2 pr-4 font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">Induk</th>
                <th class="pb-2 pr-4 font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">Nopol</th>
                <th class="pb-2 pr-4 font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">Tgl Mulai</th>
                <th class="pb-2 font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">Premi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="spaData.length === 0">
                <td colspan="5" class="py-8 text-center text-gray-400">Tidak ada data SPA.</td>
              </tr>
              <tr
                v-for="(row, i) in spaData"
                :key="i"
                class="border-b border-gray-50 dark:border-gray-800/60
                       hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
              >
                <td class="py-2 pr-4 font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap">
                  {{ row.SPA ?? row.spa ?? '—' }}
                </td>
                <td class="py-2 pr-4 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                  {{ row.INDUK ?? row.induk ?? '—' }}
                </td>
                <td class="py-2 pr-4 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                  {{ row.NOPOL ?? row.nopol ?? '—' }}
                </td>
                <td class="py-2 pr-4 text-gray-500 dark:text-gray-500 whitespace-nowrap">
                  {{
                    (row.TGLMULAI ?? row.tglmulai)
                      ? new Date(row.TGLMULAI ?? row.tglmulai).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
                      : '—'
                  }}
                </td>
                <td class="py-2 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                  {{ row.PREMI ?? row.premi ?? '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="spaTotalPage > 1"
          class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100 dark:border-gray-800"
        >
          <button
            @click="loadSpa(spaPage - 1)"
            :disabled="spaPage <= 1 || isLoadingSpa"
            class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700
                   bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400
                   hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 transition-colors"
          >
            ← Prev
          </button>
          <span class="text-xs text-gray-400 dark:text-gray-600">
            Hal {{ spaPage }} / {{ spaTotalPage }}
          </span>
          <button
            @click="loadSpa(spaPage + 1)"
            :disabled="spaPage >= spaTotalPage || isLoadingSpa"
            class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700
                   bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400
                   hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 transition-colors"
          >
            Next →
          </button>
        </div>
      </div>

    </div>
    </div>

    <!-- ============================================================
         DATA TEBU DIGILING (dipindah dari menu Operasional)
    ============================================================ -->
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 sm:p-5">
      <div class="mb-4">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
          Data Tebu Digiling
        </h3>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
          Monitoring tebu digiling per jam
        </p>
      </div>

      <DataDigiling />
    </div>

  </div>
</template>
