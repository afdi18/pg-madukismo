<script setup lang="ts">
import { ref, computed } from 'vue'

type LoriRow = {
  id: number
  spa: string
  noTruk: string
  noLori: string
  berat: number
  brtStlhRaf: number
  induk: string
  petani: string
  kebun: string
  tglMasuk: string
  jamMasuk: string
  lamaTinggal: string
}

const antrianLoriRows = ref<LoriRow[]>([
  {
    id: 1,
    spa: '11000010',
    noTruk: 'AB 8421 XX',
    noLori: '0245',
    berat: 84.5,
    brtStlhRaf: 79.8,
    induk: '02802',
    petani: 'Suparman',
    kebun: 'Kebun Barat 2',
    tglMasuk: '13/04/2026',
    jamMasuk: '05:42',
    lamaTinggal: '02:14',
  },
  {
    id: 2,
    spa: '11000011',
    noTruk: 'AB 7743 YY',
    noLori: '0246',
    berat: 80.2,
    brtStlhRaf: 76.4,
    induk: '02818',
    petani: 'Sutopo',
    kebun: 'Kebun Tengah 1',
    tglMasuk: '13/04/2026',
    jamMasuk: '06:03',
    lamaTinggal: '01:53',
  },
])

const totalLori = computed(() => antrianLoriRows.value.length)
const totalBeratLori = computed(() => antrianLoriRows.value.reduce((total, row) => total + row.berat, 0))
const totalBeratLoriKuintal = computed(() => totalBeratLori.value * 10)

function formatKuintal(value: number) {
  return Math.round(value).toLocaleString('id-ID')
}
</script>

<template>
  <div class="p-4 space-y-5">
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div class="rounded-xl border border-violet-200/70 bg-violet-50/80 px-4 py-3 dark:border-violet-500/30 dark:bg-violet-500/10">
        <p class="text-xs font-semibold uppercase tracking-wide text-violet-700 dark:text-white">Lori Aktif</p>
        <p class="mt-1 text-2xl font-extrabold text-violet-800 dark:text-white dark:[text-shadow:0_0_14px_rgba(255,255,255,0.35)]">{{ totalLori }} <span class="text-base font-bold text-violet-700 dark:text-violet-100">Rit</span></p>
      </div>
      <div class="rounded-xl border border-emerald-200/70 bg-emerald-50/80 px-4 py-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
        <p class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Total Berat</p>
        <p class="mt-1 text-2xl font-bold text-emerald-800 dark:text-emerald-200">{{ formatKuintal(totalBeratLoriKuintal) }} <span class="text-base font-semibold">Kuintal</span></p>
      </div>
    </section>

    <section class="space-y-2">
      <h2 class="text-center text-lg font-bold text-gray-800 dark:text-white">ANTRIAN LORI</h2>
      <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm min-w-[1200px] text-gray-700 dark:text-slate-100">
          <thead class="bg-gray-100 dark:bg-gray-800">
            <tr class="text-gray-700 dark:text-white">
              <th class="px-3 py-2 text-center">No</th>
              <th class="px-3 py-2 text-left">SPA</th>
              <th class="px-3 py-2 text-left">No Truk</th>
              <th class="px-3 py-2 text-left">No Lori</th>
              <th class="px-3 py-2 text-right">Berat</th>
              <th class="px-3 py-2 text-right">Brt Stlh Raf</th>
              <th class="px-3 py-2 text-left">Induk</th>
              <th class="px-3 py-2 text-left">Petani</th>
              <th class="px-3 py-2 text-left">Kebun</th>
              <th class="px-3 py-2 text-center">Tgl Masuk</th>
              <th class="px-3 py-2 text-center">Jam Masuk</th>
              <th class="px-3 py-2 text-center">Jam Lama Tinggal</th>
            </tr>
          </thead>
          <tbody class="dark:text-slate-100">
            <tr v-for="(row, index) in antrianLoriRows" :key="`lori-${row.id}`" class="border-t border-gray-100 dark:border-gray-800">
              <td class="px-3 py-2 text-center">{{ index + 1 }}</td>
              <td class="px-3 py-2">{{ row.spa }}</td>
              <td class="px-3 py-2">{{ row.noTruk }}</td>
              <td class="px-3 py-2">{{ row.noLori }}</td>
              <td class="px-3 py-2 text-right">{{ row.berat }}</td>
              <td class="px-3 py-2 text-right">{{ row.brtStlhRaf }}</td>
              <td class="px-3 py-2">{{ row.induk }}</td>
              <td class="px-3 py-2">{{ row.petani }}</td>
              <td class="px-3 py-2">{{ row.kebun }}</td>
              <td class="px-3 py-2 text-center">{{ row.tglMasuk }}</td>
              <td class="px-3 py-2 text-center">{{ row.jamMasuk }}</td>
              <td class="px-3 py-2 text-center">{{ row.lamaTinggal }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>
