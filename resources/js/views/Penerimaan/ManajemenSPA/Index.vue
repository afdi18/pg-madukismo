<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import DataSPA from './DataSPA.vue'
import PengaturanEPOS from './PengaturanEPOS.vue'
import PageHeader from '@/components/PageHeader.vue'

const authStore = useAuthStore()
const activeTab = ref<'data' | 'epos'>('data')

function setTab(t: 'data' | 'epos') {
  activeTab.value = t
}

</script>

<template>
  <div class="space-y-5">

    <!-- HEADER -->
    <PageHeader title="Manajemen SPA" subtitle="Kelola SPA dan integrasi EPOS.">
      <template #actions>
        <div class="flex items-center gap-2">
          <!-- Add buttons removed as requested -->
        </div>
      </template>
    </PageHeader>

    <!-- TABS -->
    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-3">
      <div class="flex items-center gap-2">
        <button
          @click="setTab('data')"
          :class="['px-3 py-2 rounded-lg text-sm font-medium', activeTab === 'data' ? 'bg-yellow-50 text-yellow-600 dark:bg-yellow-500/10 dark:text-yellow-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800']"
        >Data SPA</button>

        <button
          @click="setTab('epos')"
          :class="['px-3 py-2 rounded-lg text-sm font-medium', activeTab === 'epos' ? 'bg-yellow-50 text-yellow-600 dark:bg-yellow-500/10 dark:text-yellow-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800']"
        >Pengaturan EPOS</button>
      </div>

      <div class="mt-4">
        <div v-if="activeTab === 'data'">
          <DataSPA />
        </div>
        <div v-else>
          <PengaturanEPOS />
        </div>
      </div>
    </div>

  </div>
</template>
