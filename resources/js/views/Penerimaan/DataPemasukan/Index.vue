<script setup lang="ts">
import { ref } from 'vue'
import PemasukanPerKebun from './PemasukanPerKebun.vue'
import PemasukanPerKategori from './PemasukanPerKategori.vue'
import PemasukanPerWilayah from './PemasukanPerWilayah.vue'
import SisaPagi from './SisaPagi.vue'
import DigilingPerSpa from './DigilingPerSpa.vue'

const activeTab = ref<'kebun' | 'kategori' | 'wilayah' | 'sisa-pagi' | 'digiling-spa'>('kebun')
</script>

<template>
  <div class="space-y-4">
    <div class="relative overflow-hidden rounded-2xl border border-slate-700/70 bg-slate-900 px-5 py-4 shadow-[0_10px_24px_rgba(2,6,23,0.45)]">
      <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500"></div>
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold tracking-tight text-white">Data Pemasukan</h1>
          <p class="text-sm text-sky-200/90 mt-0.5">Monitoring data pemasukan tebu dari penerimaan.</p>
        </div>
      </div>
    </div>

    <div class="sticky -top-2 z-30 mb-2 bg-white/95 dark:bg-gray-900/90 backdrop-blur-sm rounded-xl border border-gray-200/70 dark:border-gray-800/70 shadow-md">
      <div class="px-3 sm:px-4 lg:px-6 py-0">
        <div class="flex items-center gap-0.5 sm:gap-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
          <button
            @click="activeTab = 'kebun'"
            :class="[
              'relative px-3 sm:px-4 lg:px-5 py-4 text-xs sm:text-sm font-medium transition-all duration-300 shrink-0 whitespace-nowrap',
              activeTab === 'kebun'
                ? 'text-yellow-600 dark:text-yellow-400 after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-gradient-to-r after:from-yellow-400 after:to-yellow-600'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200',
            ]"
            type="button"
          >
            Pemasukan per Kebun
          </button>
          <button
            @click="activeTab = 'kategori'"
            :class="[
              'relative px-3 sm:px-4 lg:px-5 py-4 text-xs sm:text-sm font-medium transition-all duration-300 shrink-0 whitespace-nowrap',
              activeTab === 'kategori'
                ? 'text-yellow-600 dark:text-yellow-400 after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-gradient-to-r after:from-yellow-400 after:to-yellow-600'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200',
            ]"
            type="button"
          >
            Pemasukan per Kategori
          </button>
          <button
            @click="activeTab = 'wilayah'"
            :class="[
              'relative px-3 sm:px-4 lg:px-5 py-4 text-xs sm:text-sm font-medium transition-all duration-300 shrink-0 whitespace-nowrap',
              activeTab === 'wilayah'
                ? 'text-yellow-600 dark:text-yellow-400 after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-gradient-to-r after:from-yellow-400 after:to-yellow-600'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200',
            ]"
            type="button"
          >
            Pemasukan per Wilayah
          </button>
          <button
            @click="activeTab = 'sisa-pagi'"
            :class="[
              'relative px-3 sm:px-4 lg:px-5 py-4 text-xs sm:text-sm font-medium transition-all duration-300 shrink-0 whitespace-nowrap',
              activeTab === 'sisa-pagi'
                ? 'text-yellow-600 dark:text-yellow-400 after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-gradient-to-r after:from-yellow-400 after:to-yellow-600'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200',
            ]"
            type="button"
          >
            Sisa Pagi
          </button>
          <button
            @click="activeTab = 'digiling-spa'"
            :class="[
              'relative px-3 sm:px-4 lg:px-5 py-4 text-xs sm:text-sm font-medium transition-all duration-300 shrink-0 whitespace-nowrap',
              activeTab === 'digiling-spa'
                ? 'text-yellow-600 dark:text-yellow-400 after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-gradient-to-r after:from-yellow-400 after:to-yellow-600'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200',
            ]"
            type="button"
          >
            Digiling per SPA
          </button>
        </div>
      </div>
    </div>

    <PemasukanPerKebun v-if="activeTab === 'kebun'" />
    <PemasukanPerKategori v-else-if="activeTab === 'kategori'" />
    <PemasukanPerWilayah v-else-if="activeTab === 'wilayah'" />
    <SisaPagi v-else-if="activeTab === 'sisa-pagi'" />
    <DigilingPerSpa v-else-if="activeTab === 'digiling-spa'" />
  </div>
</template>
