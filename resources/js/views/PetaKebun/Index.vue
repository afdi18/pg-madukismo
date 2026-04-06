<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import {
  PlusIcon, SearchIcon,
  XIcon, EditIcon, Trash2Icon,
} from 'lucide-vue-next'

const authStore = useAuthStore()
const mapContainer = ref<HTMLElement | null>(null)
const isLoading  = ref(true)
const kebunList  = ref<any[]>([])
const selected   = ref<any>(null)
const searchQ    = ref('')
const statusFilter = ref('')
const statistik  = ref({ total_kebun: 0, total_luas: 0, aktif: 0, fallow: 0 })
const showForm   = ref(false)

let leafletMap: any = null
let L: any = null
let geoserverWmsLayer: any = null
let geoserverHighlightLayer: any = null

const geoserverBaseUrl = String(import.meta.env.VITE_GEOSERVER_BASE_URL ?? '').replace(/\/+$/, '')
const geoserverWorkspace = String(import.meta.env.VITE_GEOSERVER_WORKSPACE ?? '')
const geoserverLayer = String(import.meta.env.VITE_GEOSERVER_LAYER ?? '')
const geoserverEnabled = !!geoserverBaseUrl && !!geoserverWorkspace && !!geoserverLayer

// ================================================================
// LOAD DATA
// ================================================================
async function loadData() {
  isLoading.value = true
  try {
    const { data } = await axios.get('/api/peta-kebun', {
      params: { status: statusFilter.value || undefined, search: searchQ.value || undefined },
    })
    kebunList.value = data.data
    statistik.value = data.statistik
    if (leafletMap && data.geojson) {
      renderGeoJson(data.geojson)
    }
  } finally {
    isLoading.value = false
  }
}

// ================================================================
// INISIALISASI LEAFLET
// ================================================================
async function initMap() {
  // Dynamic import Leaflet (tidak bundled di server)
  L = (await import('leaflet')).default
  await import('leaflet/dist/leaflet.css')

  if (!mapContainer.value) return

  leafletMap = L.map(mapContainer.value, {
    center: [-7.8, 110.4], // Pusat Yogyakarta
    zoom: 10,
    zoomControl: true,
  })

  // Base tile layer — OpenStreetMap
  const roadLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  }).addTo(leafletMap)

  // Alternatif: Satellite layer (Esri)
  const satellite = L.tileLayer(
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    { attribution: 'Tiles &copy; Esri', maxZoom: 19 }
  )

  const baseMaps = {
    'Peta Jalan': roadLayer,
    'Satelit': satellite,
  }

  const overlayMaps: Record<string, any> = {}
  if (geoserverEnabled) {
    geoserverWmsLayer = L.tileLayer.wms(`${geoserverBaseUrl}/geoserver/${geoserverWorkspace}/wms`, {
      layers: `${geoserverWorkspace}:${geoserverLayer}`,
      format: 'image/png',
      transparent: true,
      version: '1.1.0',
      maxZoom: 22,
    }).addTo(leafletMap)

    overlayMaps['GeoServer - Lahan Kebun'] = geoserverWmsLayer
    leafletMap.on('click', handleGeoServerMapClick)
  }

  L.control.layers(baseMaps, overlayMaps).addTo(leafletMap)

  await loadData()
}

// Simpan layer GeoJSON agar bisa diganti saat refresh
let geoLayer: any = null

function normalizeGeoServerFeature(properties: Record<string, any>) {
  return {
    kode_kebun: properties.kode_kebun ?? properties.kebun ?? '-',
    nama_kebun: properties.nama_kebun ?? properties.nama ?? 'Lahan GeoServer',
    luas_ha: properties.luas_ha ?? properties.area ?? 0,
    wilayah: properties.wilayah ?? '-',
    status: properties.status ?? 'aktif',
    pengelola: properties.pengelola ?? null,
    varietas: properties.varietas ?? null,
    tanggal_tanam: properties.tanggal_tanam ?? null,
    perkiraan_panen: properties.perkiraan_panen ?? null,
    latitude: null,
    longitude: null,
  }
}

function buildGeoServerGetFeatureInfoUrl(e: any): string {
  const point = leafletMap.latLngToContainerPoint(e.latlng, leafletMap.getZoom())
  const size = leafletMap.getSize()
  const bounds = leafletMap.getBounds().toBBoxString()

  const params = new URLSearchParams({
    service: 'WMS',
    version: '1.1.0',
    request: 'GetFeatureInfo',
    layers: `${geoserverWorkspace}:${geoserverLayer}`,
    query_layers: `${geoserverWorkspace}:${geoserverLayer}`,
    info_format: 'application/json',
    feature_count: '1',
    x: String(Math.floor(point.x)),
    y: String(Math.floor(point.y)),
    bbox: bounds,
    width: String(size.x),
    height: String(size.y),
    srs: 'EPSG:4326',
  })

  return `${geoserverBaseUrl}/geoserver/${geoserverWorkspace}/wms?${params.toString()}`
}

function clearGeoServerHighlight() {
  if (!leafletMap || !geoserverHighlightLayer) return
  leafletMap.removeLayer(geoserverHighlightLayer)
  geoserverHighlightLayer = null
}

async function highlightGeoServerFeature(feature: any) {
  if (!leafletMap || !L || !feature) return

  const params = new URLSearchParams({
    service: 'WFS',
    version: '1.0.0',
    request: 'GetFeature',
    typeName: `${geoserverWorkspace}:${geoserverLayer}`,
    outputFormat: 'application/json',
    srsName: 'EPSG:4326',
  })

  if (feature.id) {
    params.set('featureID', String(feature.id))
  } else {
    const kebun = feature?.properties?.kebun
    const area = feature?.properties?.area
    if (!kebun || area === undefined || area === null) return
    params.set('cql_filter', `kebun='${String(kebun).replace(/'/g, "''")}' AND area=${area}`)
  }

  const response = await fetch(`${geoserverBaseUrl}/geoserver/${geoserverWorkspace}/ows?${params.toString()}`)
  if (!response.ok) return

  const data = await response.json()
  if (!data?.features?.length) return

  clearGeoServerHighlight()
  geoserverHighlightLayer = L.geoJSON(data, {
    style: {
      color: '#ff1100',
      weight: 4,
      opacity: 0.9,
      fillColor: '#ff9191',
      fillOpacity: 0.35,
    },
  }).addTo(leafletMap)
  geoserverHighlightLayer.bringToFront()
}

async function handleGeoServerMapClick(e: any) {
  if (!geoserverEnabled || !leafletMap) return

  try {
    const response = await fetch(buildGeoServerGetFeatureInfoUrl(e))
    if (!response.ok) return

    const data = await response.json()
    if (!data?.features?.length) {
      clearGeoServerHighlight()
      return
    }

    const feature = data.features[0]
    const properties = feature?.properties ?? {}

    const matched = kebunList.value.find(k => {
      const targetKode = String(properties.kode_kebun ?? properties.kebun ?? '').toLowerCase()
      const targetNama = String(properties.nama_kebun ?? '').toLowerCase()
      return (
        (k.kode_kebun && String(k.kode_kebun).toLowerCase() === targetKode) ||
        (targetNama && k.nama_kebun && String(k.nama_kebun).toLowerCase() === targetNama)
      )
    })

    selected.value = matched ?? normalizeGeoServerFeature(properties)
    await highlightGeoServerFeature(feature)
  } catch {
    clearGeoServerHighlight()
  }
}

function renderGeoJson(geojson: any) {
  if (!leafletMap || !L) return
  if (geoLayer) { leafletMap.removeLayer(geoLayer) }

  geoLayer = L.geoJSON(geojson, {
    style: (feature: any) => ({
      color      : feature.properties.color ?? '#22c55e',
      weight     : 2,
      opacity    : 0.9,
      fillOpacity: 0.35,
    }),
    pointToLayer: (feature: any, latlng: any) =>
      L.circleMarker(latlng, {
        radius     : 8,
        fillColor  : feature.properties.color ?? '#22c55e',
        color      : '#fff',
        weight     : 2,
        opacity    : 1,
        fillOpacity: 0.9,
      }),
    onEachFeature: (feature: any, layer: any) => {
      const p = feature.properties
      layer.bindTooltip(`<b>${p.kode_kebun}</b> — ${p.nama_kebun}<br>${p.luas_ha} ha`, {
        direction: 'top', className: 'leaflet-tooltip-custom',
      })
      layer.on('click', () => {
        selected.value = kebunList.value.find(k => k.kode_kebun === p.kode_kebun)
      })
    },
  }).addTo(leafletMap)

  // Fit bounds ke semua kebun jika ada
  try {
    const bounds = geoLayer.getBounds()
    if (bounds.isValid()) leafletMap.fitBounds(bounds, { padding: [40, 40] })
  } catch {}
}

function flyToKebun(kebun: any) {
  if (!leafletMap || !kebun.latitude || !kebun.longitude) return
  leafletMap.flyTo([kebun.latitude, kebun.longitude], 14, { duration: 1 })
  selected.value = kebun
}

// ================================================================
// HAPUS KEBUN
// ================================================================
async function deleteKebun(kebun: any) {
  if (!confirm(`Hapus kebun "${kebun.nama_kebun}"?`)) return
  await axios.delete(`/api/peta-kebun/${kebun.id}`)
  selected.value = null
  await loadData()
}

// ================================================================
// STATUS BADGE
// ================================================================
function statusBadgeClass(status: string): string {
  return {
    aktif   : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    fallow  : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    konversi: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  }[status] ?? 'bg-gray-100 text-gray-600'
}

// Search debounce
let searchTimeout: ReturnType<typeof setTimeout>
watch(searchQ, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(loadData, 400)
})

onMounted(async () => {
  await nextTick()
  await initMap()
})

onUnmounted(() => {
  if (leafletMap) {
    leafletMap.off('click', handleGeoServerMapClick)
  }
  clearGeoServerHighlight()
  if (leafletMap) { leafletMap.remove(); leafletMap = null }
})
</script>

<template>
  <div class="space-y-4">

    <!-- HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-white">Peta Kebun</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          Monitoring lokasi dan status kebun tebu
        </p>
      </div>
      <button
        v-if="authStore.can('peta_kebun.create')"
        @click="showForm = true"
        class="flex items-center gap-2 px-4 py-2 bg-yellow-500 hover:bg-yellow-600
               text-white text-sm font-medium rounded-xl shadow-sm transition-colors"
      >
        <PlusIcon class="w-4 h-4" /> Tambah Kebun
      </button>
    </div>

    <!-- STATISTIK CARDS -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-4 text-center">
        <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ statistik.total_kebun }}</p>
        <p class="text-xs text-gray-400 mt-1">Total Kebun</p>
      </div>
      <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-4 text-center">
        <p class="text-2xl font-bold text-gray-800 dark:text-white">
          {{ Number(statistik.total_luas).toLocaleString('id') }}
        </p>
        <p class="text-xs text-gray-400 mt-1">Total Luas (ha)</p>
      </div>
      <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-4 text-center">
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ statistik.aktif }}</p>
        <p class="text-xs text-gray-400 mt-1">Aktif</p>
      </div>
      <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-4 text-center">
        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ statistik.fallow }}</p>
        <p class="text-xs text-gray-400 mt-1">Fallow</p>
      </div>
    </div>

    <!-- MAP + SIDEBAR -->
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-4">

      <!-- Sidebar kiri: daftar kebun -->
      <div class="xl:col-span-1 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl flex flex-col overflow-hidden"
           style="height: 600px">
        <!-- Search & filter -->
        <div class="p-3 border-b border-gray-100 dark:border-gray-800 space-y-2">
          <div class="relative">
            <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
              v-model="searchQ"
              placeholder="Cari kebun..."
              class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700
                     bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300
                     focus:outline-none focus:ring-2 focus:ring-yellow-500"
            />
          </div>
          <select
            v-model="statusFilter"
            @change="loadData"
            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700
                   bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300
                   focus:outline-none focus:ring-2 focus:ring-yellow-500"
          >
            <option value="">Semua Status</option>
            <option value="aktif">Aktif</option>
            <option value="fallow">Fallow</option>
            <option value="konversi">Konversi</option>
          </select>
        </div>

        <!-- List kebun -->
        <div class="flex-1 overflow-y-auto divide-y divide-gray-50 dark:divide-gray-800">
          <div v-if="isLoading" class="p-4 space-y-3">
            <div v-for="i in 6" :key="i" class="h-14 bg-gray-100 dark:bg-gray-800 rounded-lg animate-pulse" />
          </div>
          <div v-else-if="kebunList.length === 0" class="p-6 text-center text-sm text-gray-400">
            Tidak ada data kebun.
          </div>
          <button
            v-else
            v-for="kebun in kebunList"
            :key="kebun.kode_kebun"
            @click="flyToKebun(kebun)"
            :class="[
              'w-full text-left px-4 py-3 hover:bg-yellow-50 dark:hover:bg-yellow-500/5 transition-colors',
              selected?.kode_kebun === kebun.kode_kebun
                ? 'bg-yellow-50 dark:bg-yellow-500/10 border-l-2 border-yellow-500'
                : '',
            ]"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 truncate">
                  {{ kebun.nama_kebun }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">{{ kebun.kode_kebun }} · {{ kebun.luas_ha }} ha</p>
                <p class="text-xs text-gray-400">{{ kebun.wilayah }}</p>
              </div>
              <span :class="['text-[10px] font-semibold px-2 py-0.5 rounded-full shrink-0', statusBadgeClass(kebun.status)]">
                {{ kebun.status }}
              </span>
            </div>
          </button>
        </div>
      </div>

      <!-- Map area -->
      <div class="xl:col-span-3 relative rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-800"
           style="height: 600px">
        <!-- Leaflet map container -->
        <div ref="mapContainer" class="w-full h-full" />

        <!-- Loading overlay -->
        <div
          v-if="isLoading"
          class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex items-center justify-center z-[999]"
        >
          <div class="text-center">
            <div class="w-10 h-10 border-4 border-yellow-500 border-t-transparent rounded-full animate-spin mx-auto mb-3" />
            <p class="text-sm text-gray-500 dark:text-gray-400">Memuat peta...</p>
          </div>
        </div>

        <!-- Popup detail kebun yang dipilih -->
        <transition name="slide-up">
          <div
            v-if="selected"
            class="absolute bottom-4 left-4 right-4 z-[999] bg-white dark:bg-gray-900
                   border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-4"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <h3 class="font-bold text-gray-800 dark:text-white text-sm">{{ selected.nama_kebun }}</h3>
                  <span :class="['text-[10px] font-semibold px-2 py-0.5 rounded-full', statusBadgeClass(selected.status)]">
                    {{ selected.status }}
                  </span>
                </div>
                <div class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs text-gray-500 dark:text-gray-400">
                  <div><span class="font-medium text-gray-700 dark:text-gray-300">Kode:</span> {{ selected.kode_kebun }}</div>
                  <div><span class="font-medium text-gray-700 dark:text-gray-300">Luas:</span> {{ selected.luas_ha }} ha</div>
                  <div><span class="font-medium text-gray-700 dark:text-gray-300">Wilayah:</span> {{ selected.wilayah }}</div>
                  <div><span class="font-medium text-gray-700 dark:text-gray-300">Varietas:</span> {{ selected.varietas ?? '—' }}</div>
                  <div><span class="font-medium text-gray-700 dark:text-gray-300">Pengelola:</span> {{ selected.pengelola ?? '—' }}</div>
                  <div><span class="font-medium text-gray-700 dark:text-gray-300">Tanam:</span> {{ selected.tanggal_tanam ?? '—' }}</div>
                  <div><span class="font-medium text-gray-700 dark:text-gray-300">Est. Panen:</span> {{ selected.perkiraan_panen ?? '—' }}</div>
                </div>
              </div>
              <div class="flex items-center gap-1 shrink-0">
                <button
                  v-if="authStore.can('peta_kebun.update')"
                  class="p-1.5 rounded-lg text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                >
                  <EditIcon class="w-4 h-4" />
                </button>
                <button
                  v-if="authStore.can('peta_kebun.delete')"
                  @click="deleteKebun(selected)"
                  class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                >
                  <Trash2Icon class="w-4 h-4" />
                </button>
                <button
                  @click="selected = null"
                  class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                >
                  <XIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </transition>

        <!-- Legend -->
        <div class="absolute top-3 right-3 z-[998] bg-white dark:bg-gray-900 border border-gray-200
                    dark:border-gray-700 rounded-xl shadow-md px-3 py-2 text-xs">
          <p class="font-semibold text-gray-600 dark:text-gray-300 mb-1.5">Keterangan</p>
          <div class="space-y-1">
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-green-500 shrink-0"/><span class="text-gray-500 dark:text-gray-400">Aktif</span></div>
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"/><span class="text-gray-500 dark:text-gray-400">Fallow</span></div>
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-500 shrink-0"/><span class="text-gray-500 dark:text-gray-400">Konversi</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.25s ease; }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateY(12px); }
</style>
