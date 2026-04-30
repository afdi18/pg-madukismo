<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import PemasukanPerKebun from './PemasukanPerKebun.vue'
import PemasukanPerKategori from './PemasukanPerKategori.vue'
import PemasukanPerWilayah from './PemasukanPerWilayah.vue'
import SisaPagi from './SisaPagi.vue'
import DigilingPerSpa from './DigilingPerSpa.vue'

type TabKey = 'kebun' | 'kategori' | 'wilayah' | 'sisa-pagi' | 'digiling-spa'

interface DataPemasukanTab {
  key: TabKey
  label: string
  permission: string
}

const authStore = useAuthStore()

const tabs: DataPemasukanTab[] = [
  { key: 'sisa-pagi', label: 'Sisa Pagi', permission: 'penerimaan.pemasukan.sisa_pagi.view' },
  { key: 'kebun', label: 'Pemasukan per Kebun', permission: 'penerimaan.pemasukan.kebun.view' },
  { key: 'kategori', label: 'Pemasukan per Kategori', permission: 'penerimaan.pemasukan.kategori.view' },
  { key: 'wilayah', label: 'Pemasukan per Wilayah', permission: 'penerimaan.pemasukan.wilayah.view' },
  { key: 'digiling-spa', label: 'Digiling per SPA', permission: 'penerimaan.pemasukan.digiling_spa.view' },
]

const activeTab = ref<TabKey>('sisa-pagi')

const canViewAllDataPemasukan = computed(() => authStore.can('penerimaan.pemasukan.view'))

const visibleTabs = computed(() => {
  if (canViewAllDataPemasukan.value) {
    return tabs
  }

  return tabs.filter((tab) => authStore.can(tab.permission))
})

watch(
  visibleTabs,
  (currentTabs) => {
    if (currentTabs.length === 0) return

    if (!currentTabs.some((tab) => tab.key === activeTab.value)) {
      activeTab.value = currentTabs[0].key
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="space-y-4">
    <div
      class="relative overflow-hidden rounded-2xl border border-slate-700/70 bg-slate-900 px-5 py-4 shadow-[0_10px_24px_rgba(2,6,23,0.45)] print-hidden">
      <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500"></div>
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold tracking-tight text-white">Data Pemasukan</h1>
          <p class="text-sm text-sky-200/90 mt-0.5">Monitoring data pemasukan tebu dari penerimaan.</p>
        </div>
      </div>
    </div>

    <div
      class="sticky -top-2 z-30 mb-2 bg-white/95 dark:bg-gray-900/90 backdrop-blur-sm rounded-xl border border-gray-200/70 dark:border-gray-800/70 shadow-md print-hidden">
      <div class="px-3 sm:px-4 lg:px-6 py-0">
        <div
          class="flex items-center gap-0.5 sm:gap-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
          <button
            v-for="tab in visibleTabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            :class="[
              'relative px-3 sm:px-4 lg:px-5 py-4 text-xs sm:text-sm font-medium transition-all duration-300 shrink-0 whitespace-nowrap',
              activeTab === tab.key
                ? 'text-yellow-600 dark:text-yellow-400 after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-gradient-to-r after:from-yellow-400 after:to-yellow-600'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200',
            ]"
            type="button"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="visibleTabs.length === 0"
      class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300"
    >
      Anda tidak memiliki hak akses ke tab Data Pemasukan mana pun.
    </div>

    <template v-else>
      <PemasukanPerKebun v-if="activeTab === 'kebun'" />
      <PemasukanPerKategori v-else-if="activeTab === 'kategori'" />
      <PemasukanPerWilayah v-else-if="activeTab === 'wilayah'" />
      <SisaPagi v-else-if="activeTab === 'sisa-pagi'" />
      <DigilingPerSpa v-else-if="activeTab === 'digiling-spa'" />
    </template>
  </div>
</template>
