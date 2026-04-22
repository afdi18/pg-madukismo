<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import DataSPA from './DataSPA.vue'
import PengaturanEPOS from './PengaturanEPOS.vue'

const authStore = useAuthStore()
const activeTab = ref<'data' | 'epos'>('data')

function setTab(t: 'data' | 'epos') {
  activeTab.value = t
}

</script>

<template>
  <div class="space-y-4">
    <div class="relative overflow-hidden rounded-2xl border border-slate-700/70 bg-slate-900 px-5 py-4 shadow-[0_10px_24px_rgba(2,6,23,0.45)]">
      <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500"></div>
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold tracking-tight text-white">Monitoring SPA</h1>
          <p class="text-sm text-sky-200/90 mt-0.5">Kelola data SPA dan pengaturan integrasi EPOS.</p>
        </div>
      </div>
    </div>

    <div class="sticky -top-2 z-30 mb-2 bg-white/95 dark:bg-gray-900/90 backdrop-blur-sm rounded-xl border border-gray-200/70 dark:border-gray-800/70 shadow-md">
      <div class="px-3 sm:px-4 lg:px-6 py-0">
        <div class="flex items-center gap-0.5 sm:gap-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
          <button
            @click="setTab('data')"
            :class="[
              'relative px-3 sm:px-4 lg:px-5 py-4 text-xs sm:text-sm font-medium transition-all duration-300 shrink-0 whitespace-nowrap',
              'after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:transition-all after:duration-300',
              activeTab === 'data'
                ? 'text-yellow-600 dark:text-yellow-400 after:bg-gradient-to-r after:from-yellow-400 after:to-yellow-600'
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 after:bg-transparent',
            ]"
          >
            Data SPA
          </button>

          <button
            @click="setTab('epos')"
            :class="[
              'relative px-3 sm:px-4 lg:px-5 py-4 text-xs sm:text-sm font-medium transition-all duration-300 shrink-0 whitespace-nowrap',
              'after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:transition-all after:duration-300',
              activeTab === 'epos'
                ? 'text-yellow-600 dark:text-yellow-400 after:bg-gradient-to-r after:from-yellow-400 after:to-yellow-600'
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 after:bg-transparent',
            ]"
          >
            Pengaturan EPOS
          </button>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl overflow-hidden">
      <div v-if="activeTab === 'data'" class="p-4">
          <DataSPA />
      </div>
      <div v-else class="p-4">
          <PengaturanEPOS />
      </div>
    </div>
  </div>
</template>
