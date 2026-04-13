<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import VueApexCharts from 'vue3-apexcharts'
import {
  TrendingUpIcon, DropletIcon, PackageIcon,
  FactoryIcon, CalendarIcon, RefreshCwIcon,
} from 'lucide-vue-next'

const authStore   = useAuthStore()
const route       = useRoute()
const isLoading   = ref(true)
const musimList   = ref<any[]>([])
const musimDipilih= ref<number>(new Date().getFullYear())
const summary     = ref<any>(null)
const kpiData     = ref<any>({})
const rendemenTrend = ref<any>({ labels: [], rendemen_kebun: [], rendemen_pabrik: [], tonase_masuk: [], efisiensi: [] })
const perbandingan  = ref<any>({ labels: [], tonase_tebang: [], hasil_gula: [], rendemen_rata: [] })
const periodeFilter = ref('30d')

const pageTitle = computed(() => {
  const title = route.meta?.title
  return typeof title === 'string' && title.trim() ? title : 'Dashboard Monitoring'
})

const pageSubtitle = computed(() => {
  if (route.name === 'DashboardPenerimaanTebu') {
    return `Ringkasan tebu masuk, rendemen, dan KPI produksi — Musim Tanam ${musimDipilih.value}`
  }
  if (route.name === 'DashboardMonitoringPabrik') {
    return `Data monitoring pabrik PG Madukismo — Musim Tanam ${musimDipilih.value}`
  }
  return `Data produksi PG Madukismo — Musim Tanam ${musimDipilih.value}`
})

// ================================================================
// FETCH DATA
// ================================================================
async function loadAll() {
  isLoading.value = true
  try {
    const [sumRes, trendRes, kpiRes, perbRes, musimRes] = await Promise.all([
      axios.get('/api/dashboard/summary', { params: { musim_tanam: musimDipilih.value } }),
      axios.get('/api/dashboard/rendemen-trend', { params: { musim_tanam: musimDipilih.value, periode: periodeFilter.value } }),
      axios.get('/api/dashboard/kpi', { params: { musim_tanam: musimDipilih.value } }),
      axios.get('/api/dashboard/perbandingan-musim'),
      axios.get('/api/dashboard/musim-list'),
    ])
    summary.value       = sumRes.data
    rendemenTrend.value = trendRes.data
    kpiData.value       = kpiRes.data.kpi ?? {}
    perbandingan.value  = perbRes.data
    musimList.value     = musimRes.data.data
  } finally {
    isLoading.value = false
  }
}

onMounted(loadAll)

// ================================================================
// KPI CARDS
// ================================================================
const kpiCards = computed(() => {
  const r = summary.value?.ringkasan
  const h = summary.value?.hari_ini
  return [
    {
      label   : 'Rendemen Pabrik',
      value   : h?.rendemen_pabrik ? `${h.rendemen_pabrik}%` : '—',
      sub     : 'Hari ini',
      icon    : DropletIcon,
      color   : 'blue',
      trend   : null,
    },
    {
      label   : 'Tonase Masuk',
      value   : h?.tonase_masuk ? `${Number(h.tonase_masuk).toLocaleString('id')} ton` : '—',
      sub     : 'Hari ini',
      icon    : PackageIcon,
      color   : 'green',
      trend   : null,
    },
    {
      label   : 'Total Hasil Gula',
      value   : r?.total_hasil_gula ? `${Number(r.total_hasil_gula).toLocaleString('id')} ton` : '—',
      sub     : `Musim ${musimDipilih.value}`,
      icon    : TrendingUpIcon,
      color   : 'amber',
      trend   : null,
    },
    {
      label   : 'Efisiensi Pabrik',
      value   : h?.efisiensi_pabrik ? `${h.efisiensi_pabrik}%` : '—',
      sub     : 'Hari ini',
      icon    : FactoryIcon,
      color   : 'purple',
      trend   : null,
    },
    {
      label   : 'Hari Giling',
      value   : r?.jumlah_hari_giling ?? '—',
      sub     : `Dari ${r?.mulai_giling ?? '—'}`,
      icon    : CalendarIcon,
      color   : 'teal',
      trend   : null,
    },
    {
      label   : 'Rendemen Rata-rata',
      value   : r?.rendemen_rata ? `${r.rendemen_rata}%` : '—',
      sub     : `Musim ${musimDipilih.value}`,
      icon    : DropletIcon,
      color   : 'coral',
      trend   : null,
    },
  ]
})

const colorMap: Record<string, string> = {
  blue  : 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400',
  green : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400',
  amber : 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400',
  purple: 'bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400',
  teal  : 'bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400',
  coral : 'bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400',
}

// ================================================================
// CHART OPTIONS
// ================================================================
const rendemenChartOpts = computed(() => ({
  chart: {
    type: 'area', height: 320, toolbar: { show: false },
    zoom: { enabled: false },
    background: 'transparent',
    fontFamily: 'inherit',
  },
  colors: ['#f59e0b', '#3b82f6'],
  fill: {
    type: 'gradient',
    gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] },
  },
  stroke: { curve: 'smooth', width: 2.5 },
  dataLabels: { enabled: false },
  xaxis: {
    categories: rendemenTrend.value.labels,
    labels: { style: { fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: { formatter: (v: number) => `${v}%`, style: { fontSize: '11px' } },
    min: 0,
  },
  tooltip: { y: { formatter: (v: number) => `${v}%` } },
  grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
  legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px' },
  theme: { mode: 'light' },
}))

const rendemenSeries = computed(() => [
  { name: 'Rendemen Kebun (%)', data: rendemenTrend.value.rendemen_kebun },
  { name: 'Rendemen Pabrik (%)', data: rendemenTrend.value.rendemen_pabrik },
])

const tonaseChartOpts = computed(() => ({
  chart: {
    type: 'bar', height: 300, toolbar: { show: false },
    background: 'transparent', fontFamily: 'inherit',
  },
  colors: ['#22c55e'],
  plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
  dataLabels: { enabled: false },
  xaxis: {
    categories: rendemenTrend.value.labels,
    labels: { style: { fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      formatter: (v: number) => v >= 1000 ? `${(v/1000).toFixed(1)}K` : `${v}`,
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
    type: 'bar', height: 280, toolbar: { show: false },
    background: 'transparent', fontFamily: 'inherit',
  },
  colors: ['#f59e0b', '#3b82f6'],
  plotOptions: { bar: { borderRadius: 4, columnWidth: '60%', grouped: true } },
  dataLabels: { enabled: false },
  xaxis: {
    categories: perbandingan.value.labels,
    labels: { style: { fontSize: '11px' } },
  },
  yaxis: {
    labels: {
      formatter: (v: number) => v >= 1000 ? `${(v/1000).toFixed(0)}K` : `${v}`,
      style: { fontSize: '11px' },
    },
  },
  tooltip: { y: { formatter: (v: number) => `${Number(v).toLocaleString('id')} ton` } },
  grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
  legend: { position: 'top', fontSize: '12px' },
}))

const perbandinganSeries = computed(() => [
  { name: 'Tonase Tebang (ton)', data: perbandingan.value.tonase_tebang },
  { name: 'Hasil Gula (ton)', data: perbandingan.value.hasil_gula },
])

// ================================================================
// KPI STATUS COLOR
// ================================================================
function kpiStatusClass(status: string): string {
  return {
    on_track : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    at_risk  : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    off_track: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  }[status] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
}

function kpiStatusLabel(status: string): string {
  return { on_track: 'On Track', at_risk: 'At Risk', off_track: 'Off Track' }[status] ?? status
}

function progressColor(pct: number): string {
  if (pct >= 90) return 'bg-green-500'
  if (pct >= 70) return 'bg-amber-500'
  return 'bg-red-500'
}
</script>

<template>
  <div class="space-y-6">

    <!-- ================================================================
         HEADER BAR
    ================================================================ -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-white">{{ pageTitle }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          {{ pageSubtitle }}
        </p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <!-- Filter musim -->
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
        <!-- Refresh -->
        <button
          @click="loadAll"
          :disabled="isLoading"
          class="p-2 rounded-lg border border-gray-200 dark:border-gray-700
                 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400
                 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors"
        >
          <RefreshCwIcon class="w-4 h-4" :class="{ 'animate-spin': isLoading }" />
        </button>
        <!-- Updated at -->
        <span class="text-xs text-gray-400 dark:text-gray-600 hidden sm:block">
          {{ summary?.updated_at ?? '—' }}
        </span>
      </div>
    </div>

    <!-- ================================================================
         KPI CARDS
    ================================================================ -->
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

    <!-- ================================================================
         CHART ROW 1 — Rendemen Trend + Tonase Harian
    ================================================================ -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">

      <!-- Rendemen Trend (3/5) -->
      <div class="xl:col-span-3 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Trend Rendemen</h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Perbandingan rendemen kebun vs pabrik</p>
          </div>
          <div class="flex gap-1">
            <button
              v-for="p in ['7d','30d','3m']"
              :key="p"
              @click="periodeFilter = p; loadAll()"
              :class="[
                'px-2.5 py-1 text-xs rounded-lg font-medium transition-colors',
                periodeFilter === p
                  ? 'bg-yellow-500 text-white'
                  : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
              ]"
            >{{ p }}</button>
          </div>
        </div>
        <div v-if="isLoading" class="h-[320px] bg-gray-100 dark:bg-gray-800 rounded-xl animate-pulse" />
        <VueApexCharts v-else type="area" height="320" :options="rendemenChartOpts" :series="rendemenSeries" />
      </div>

      <!-- Tonase Harian (2/5) -->
      <div class="xl:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
        <div class="mb-4">
          <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Tonase Masuk Harian</h3>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Ton tebu masuk gilingan</p>
        </div>
        <div v-if="isLoading" class="h-[300px] bg-gray-100 dark:bg-gray-800 rounded-xl animate-pulse" />
        <VueApexCharts v-else type="bar" height="300" :options="tonaseChartOpts" :series="tonaseSeries" />
      </div>

    </div>

    <!-- ================================================================
         CHART ROW 2 — Perbandingan Musim + KPI Table
    ================================================================ -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">

      <!-- KPI per kategori (3/5) -->
      <div class="xl:col-span-3 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">KPI Operasional</h3>
        <div v-if="isLoading" class="space-y-3">
          <div v-for="i in 5" :key="i" class="h-12 bg-gray-100 dark:bg-gray-800 rounded-lg animate-pulse" />
        </div>
        <div v-else-if="Object.keys(kpiData).length === 0" class="text-center py-10 text-sm text-gray-400">
          Tidak ada data KPI untuk periode ini.
        </div>
        <div v-else class="space-y-5">
          <div v-for="(items, kategori) in kpiData" :key="kategori">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-2">
              {{ kategori }}
            </p>
            <div class="space-y-3">
              <div v-for="kpi in (items as any[])" :key="kpi.kode" class="space-y-1.5">
                <div class="flex items-center justify-between text-xs">
                  <span class="text-gray-600 dark:text-gray-300 font-medium">{{ kpi.nama }}</span>
                  <div class="flex items-center gap-2">
                    <span class="text-gray-500 dark:text-gray-400">
                      {{ kpi.realisasi }} / {{ kpi.target }} {{ kpi.satuan }}
                    </span>
                    <span :class="['px-1.5 py-0.5 rounded text-[10px] font-semibold', kpiStatusClass(kpi.status)]">
                      {{ kpiStatusLabel(kpi.status) }}
                    </span>
                  </div>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5">
                  <div
                    :class="['h-1.5 rounded-full transition-all duration-700', progressColor(kpi.persentase)]"
                    :style="{ width: `${Math.min(kpi.persentase, 100)}%` }"
                  />
                </div>
                <p class="text-[10px] text-gray-400 text-right">{{ kpi.persentase }}%</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Perbandingan antar musim (2/5) -->
      <div class="xl:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
        <div class="mb-4">
          <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Perbandingan Musim Tanam</h3>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">5 musim tanam terakhir</p>
        </div>
        <div v-if="isLoading" class="h-[280px] bg-gray-100 dark:bg-gray-800 rounded-xl animate-pulse" />
        <VueApexCharts v-else type="bar" height="280" :options="perbandinganOpts" :series="perbandinganSeries" />

        <!-- Ringkasan musim berjalan -->
        <div
          v-if="summary?.ringkasan && !isLoading"
          class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 grid grid-cols-2 gap-3"
        >
          <div class="text-center">
            <p class="text-lg font-bold text-gray-800 dark:text-white">
              {{ summary.ringkasan.rendemen_rata }}%
            </p>
            <p class="text-xs text-gray-400">Rendemen Rata-rata</p>
          </div>
          <div class="text-center">
            <p class="text-lg font-bold text-gray-800 dark:text-white">
              {{ summary.ringkasan.efisiensi_rata }}%
            </p>
            <p class="text-xs text-gray-400">Efisiensi Rata-rata</p>
          </div>
          <div class="text-center col-span-2">
            <span :class="[
              'px-3 py-1 rounded-full text-xs font-semibold',
              summary.ringkasan.status_musim === 'berjalan'
                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
            ]">
              Status: {{ summary.ringkasan.status_musim }}
            </span>
          </div>
        </div>
      </div>

    </div>

  </div>
</template>
