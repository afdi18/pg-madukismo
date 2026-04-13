<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { SearchIcon, ChevronLeftIcon, ChevronRightIcon } from 'lucide-vue-next'

const authStore = useAuthStore()

// Table state
const rows = ref<any[]>([])
const meta = reactive({ total: 0, per_page: 15, current_page: 1, last_page: 1 })
const loading = ref(false)

// Filters
const q = ref('')
const induk = ref('')
const nopol = ref('')
const tglmasuk = ref('')

async function loadData(page = 1) {
  loading.value = true
  try {
    const { data } = await axios.get('/api/penerimaan', {
      params: {
        page,
        per_page: meta.per_page,
        q: q.value || undefined,
        induk: induk.value || undefined,
        nopol: nopol.value || undefined,
        tglmasuk: tglmasuk.value || undefined,
      },
    })

    rows.value = data.data
    meta.total = data.meta.total
    meta.per_page = data.meta.per_page
    meta.current_page = data.meta.current_page
    meta.last_page = data.meta.last_page
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => loadData(1))

// Debounce filters
let timer: ReturnType<typeof setTimeout>
watch([q, induk, nopol, tglmasuk], () => {
  clearTimeout(timer)
  timer = setTimeout(() => loadData(1), 350)
})
</script>

<template>
  <div>
    <!-- Filter bar -->
    <div class="flex flex-col sm:flex-row gap-3 mb-3">
      <div class="relative flex-1">
        <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input v-model="q" placeholder="Cari SPA / No. Polisi / Induk..."
          class="w-full pl-9 pr-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500" />
      </div>

      <input v-model="induk" placeholder="INDUK"
        class="px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300" />

      <input v-model="nopol" placeholder="NOPOL"
        class="px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300" />

      <input v-model="tglmasuk" type="date" placeholder="TGLMASUK"
        class="px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300" />

      <button @click="loadData(1)" :disabled="loading"
        class="px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl text-sm">Cari</button>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/40">
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">SPA</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">INDUK</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">NOPOL</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">TGLMASUK</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">PREMI</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <tr v-if="loading">
              <td colspan="6" class="px-4 py-6">
                <div class="h-10 bg-gray-100 dark:bg-gray-800 rounded-lg animate-pulse" />
              </td>
            </tr>
            <tr v-else-if="rows.length === 0">
              <td colspan="6" class="px-4 py-16 text-center text-sm text-gray-400">Tidak ada data SPA.</td>
            </tr>
            <tr v-else v-for="r in rows" :key="r.SPA" class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
              <td class="px-4 py-3"><span class="font-medium text-gray-800 dark:text-white">{{ r.SPA }}</span></td>
              <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ r.INDUK }}</td>
              <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ r.NOPOL }}</td>
              <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ r.TGLMULAI ? (new Date(r.TGLMULAI)).toLocaleString() : '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ r.PREMI ?? '-' }}</td>
              <td class="px-4 py-3 text-right">
                <button class="px-3 py-1 text-sm rounded-lg bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700">Lihat</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div class="flex items-center justify-between mt-3">
      <div class="text-sm text-gray-500">Menampilkan halaman {{ meta.current_page }} dari {{ meta.last_page }} — {{ meta.total }} baris</div>
      <div class="flex items-center gap-2">
        <button @click="meta.current_page > 1 && loadData(meta.current_page - 1)" :disabled="meta.current_page <= 1 || loading"
          class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
          <ChevronLeftIcon class="w-4 h-4" />
        </button>
        <button @click="meta.current_page < meta.last_page && loadData(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page || loading"
          class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
          <ChevronRightIcon class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
</template>
