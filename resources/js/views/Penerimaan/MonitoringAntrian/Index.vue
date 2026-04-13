<script setup lang="ts">
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue'
import AntrianTruk from './AntrianTruk.vue'
import AntrianLori from './AntrianLori.vue'

const activeTab = ref<'antrian-truk' | 'antrian-lori'>('antrian-truk')
const tabScrollContainer = ref<HTMLElement | null>(null)
const canScrollTabLeft = ref(false)
const canScrollTabRight = ref(false)
const showTabScrollArrows = ref(false)

const tabMenus = [
  { key: 'antrian-truk' as const, label: 'Antrian Truk' },
  { key: 'antrian-lori' as const, label: 'Antrian Lori' },
]

function updateTabScrollState() {
  const container = tabScrollContainer.value
  if (!container) {
    canScrollTabLeft.value = false
    canScrollTabRight.value = false
    showTabScrollArrows.value = false
    return
  }

  const maxScrollLeft = container.scrollWidth - container.clientWidth
  showTabScrollArrows.value = maxScrollLeft > 8
  canScrollTabLeft.value = container.scrollLeft > 2
  canScrollTabRight.value = container.scrollLeft < maxScrollLeft - 2
}

function scrollTabMenu(direction: 'left' | 'right') {
  const container = tabScrollContainer.value
  if (!container) return
  const delta = container.clientWidth * 0.7
  container.scrollBy({ left: direction === 'left' ? -delta : delta, behavior: 'smooth' })
}

const handleResize = () => updateTabScrollState()

onMounted(async () => {
  await nextTick()
  updateTabScrollState()
  window.addEventListener('resize', handleResize)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<template>
  <div class="space-y-4">
    <div class="relative overflow-hidden rounded-2xl border border-slate-700/70 bg-slate-900 px-5 py-4 shadow-[0_10px_24px_rgba(2,6,23,0.45)]">
      <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500"></div>
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold tracking-tight text-white">Monitoring Antrian Tebu</h1>
          <p class="text-sm text-sky-200/90 mt-0.5">Pantau antrian kendaraan masuk timbang dan lori secara real-time.</p>
        </div>
      </div>
    </div>

    <div class="sticky -top-2 z-30 mb-2 bg-white/95 dark:bg-gray-900/90 backdrop-blur-sm rounded-xl border border-gray-200/70 dark:border-gray-800/70 shadow-md">
      <div class="px-3 sm:px-4 lg:px-6 py-0">
        <div class="relative">
          <button
            v-if="showTabScrollArrows"
            type="button"
            @click="scrollTabMenu('left')"
            :disabled="!canScrollTabLeft"
            :class="[
              'absolute left-0 top-1/2 z-10 -translate-y-1/2 rounded-full border px-2 py-1 shadow-sm transition',
              canScrollTabLeft
                ? 'border-gray-200 bg-white/95 text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-900/95 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white'
                : 'cursor-not-allowed border-gray-100 bg-gray-100/80 text-gray-300 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-600',
            ]"
            aria-label="Geser tab ke kiri"
          >
            ‹
          </button>

          <div
            ref="tabScrollContainer"
            @scroll="updateTabScrollState"
            :class="[
              'flex items-center gap-0.5 sm:gap-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden',
              showTabScrollArrows ? 'mx-8' : 'mx-0',
            ]"
          >
          <button
            v-for="tab in tabMenus"
            :key="tab.key"
            @click="activeTab = tab.key; updateTabScrollState()"
            :class="[
              'relative px-3 sm:px-4 lg:px-5 py-4 text-xs sm:text-sm font-medium transition-all duration-300 shrink-0 whitespace-nowrap',
              'after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:transition-all after:duration-300',
              activeTab === tab.key
                ? 'text-yellow-600 dark:text-yellow-400 after:bg-gradient-to-r after:from-yellow-400 after:to-yellow-600'
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 after:bg-transparent',
            ]"
          >
            {{ tab.label }}
          </button>
          </div>

          <button
            v-if="showTabScrollArrows"
            type="button"
            @click="scrollTabMenu('right')"
            :disabled="!canScrollTabRight"
            :class="[
              'absolute right-0 top-1/2 z-10 -translate-y-1/2 rounded-full border px-2 py-1 shadow-sm transition',
              canScrollTabRight
                ? 'border-gray-200 bg-white/95 text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-900/95 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white'
                : 'cursor-not-allowed border-gray-100 bg-gray-100/80 text-gray-300 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-600',
            ]"
            aria-label="Geser tab ke kanan"
          >
            ›
          </button>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl overflow-hidden">
      <AntrianTruk v-if="activeTab === 'antrian-truk'" />
      <AntrianLori v-else-if="activeTab === 'antrian-lori'" />
    </div>
  </div>
</template>
