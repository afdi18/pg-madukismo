<script setup lang="ts">
import { ref, computed } from 'vue'

type QueueRow = {
  id: number
  spa: string
  noTruk: string
  induk: string
  petani: string
  kebun: string
  tglMasuk: string
  jamMasuk: string
  lamaTinggal: string
}

const sudahTimbangRows = ref<QueueRow[]>([
  {
    id: 1,
    spa: '21000001',
    noTruk: 'AB 8123 MD',
    induk: '028018',
    petani: 'Sutrisno',
    kebun: 'Kebun Utara',
    tglMasuk: '13/04/2026',
    jamMasuk: '06:12',
    lamaTinggal: '01:42',
  },
  {
    id: 2,
    spa: '21000002',
    noTruk: 'AB 9345 MK',
    induk: '028019',
    petani: 'Wahyudi',
    kebun: 'Kebun Timur',
    tglMasuk: '13/04/2026',
    jamMasuk: '06:35',
    lamaTinggal: '01:20',
  },
])

const belumTimbangRows = ref<QueueRow[]>([
  {
    id: 1,
    spa: '21000003',
    noTruk: 'AB 7689 ML',
    induk: '00035',
    petani: 'Slamet Riyadi',
    kebun: 'Kebun Barat',
    tglMasuk: '13/04/2026',
    jamMasuk: '07:10',
    lamaTinggal: '00:58',
  },
  {
    id: 2,
    spa: '21000004',
    noTruk: 'AB 5467 MA',
    induk: '02818',
    petani: 'Paijo',
    kebun: 'Kebun Selatan',
    tglMasuk: '13/04/2026',
    jamMasuk: '07:24',
    lamaTinggal: '00:43',
  },
  {
    id: 3,
    spa: '21000005',
    noTruk: 'AB 4512 MZ',
    induk: '00035',
    petani: 'Suratman',
    kebun: 'Kebun Tengah',
    tglMasuk: '13/04/2026',
    jamMasuk: '07:31',
    lamaTinggal: '00:37',
  },
])

const totalSudah = computed(() => sudahTimbangRows.value.length)
const totalBelum = computed(() => belumTimbangRows.value.length)
const totalSemua = computed(() => totalSudah.value + totalBelum.value)
</script>

<template>
  <div class="p-4 space-y-5">
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div class="rounded-xl border border-emerald-200/70 bg-emerald-50/80 px-4 py-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
        <p class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Antrian Sdh Timb 1</p>
        <p class="mt-1 text-2xl font-bold text-emerald-800 dark:text-emerald-200">{{ totalSudah }} <span class="text-base font-semibold">Rit</span></p>
      </div>
      <div class="rounded-xl border border-amber-200/70 bg-amber-50/80 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-500/10">
        <p class="text-xs font-medium uppercase tracking-wide text-amber-700 dark:text-amber-300">Antrian Blm Timb 1</p>
        <p class="mt-1 text-2xl font-bold text-amber-800 dark:text-amber-200">{{ totalBelum }} <span class="text-base font-semibold">Rit</span></p>
      </div>
      <div class="rounded-xl border border-sky-200/70 bg-sky-50/80 px-4 py-3 dark:border-sky-500/30 dark:bg-sky-500/10">
        <p class="text-xs font-medium uppercase tracking-wide text-sky-700 dark:text-sky-300">Jumlah</p>
        <p class="mt-1 text-2xl font-bold text-sky-800 dark:text-sky-200">{{ totalSemua }} <span class="text-base font-semibold">Rit</span></p>
      </div>
    </section>

    <section class="space-y-2">
      <h2 class="text-center text-lg font-bold text-gray-800 dark:text-white">ANTRIAN TRUK SUDAH TIMBANG 1</h2>
      <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm text-gray-700 dark:text-slate-100">
          <thead class="bg-gray-100 dark:bg-gray-800">
            <tr class="text-gray-700 dark:text-white">
              <th class="px-3 py-2 text-center">No</th>
              <th class="px-3 py-2 text-left">SPA</th>
              <th class="px-3 py-2 text-left">No Truk</th>
              <th class="px-3 py-2 text-left">Induk</th>
              <th class="px-3 py-2 text-left">Petani</th>
              <th class="px-3 py-2 text-left">Kebun</th>
              <th class="px-3 py-2 text-center">Tgl Masuk</th>
              <th class="px-3 py-2 text-center">Jam Masuk</th>
              <th class="px-3 py-2 text-center">Jam Lama Tinggal</th>
            </tr>
          </thead>
          <tbody class="dark:text-slate-100">
            <tr v-for="(row, index) in sudahTimbangRows" :key="`sudah-${row.id}`" class="border-t border-gray-100 dark:border-gray-800">
              <td class="px-3 py-2 text-center">{{ index + 1 }}</td>
              <td class="px-3 py-2">{{ row.spa }}</td>
              <td class="px-3 py-2">{{ row.noTruk }}</td>
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

    <section class="space-y-2">
      <h2 class="text-center text-lg font-bold text-gray-800 dark:text-white">ANTRIAN TRUK BELUM TIMBANG 1</h2>
      <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm text-gray-700 dark:text-slate-100">
          <thead class="bg-gray-100 dark:bg-gray-800">
            <tr class="text-gray-700 dark:text-white">
              <th class="px-3 py-2 text-center">No</th>
              <th class="px-3 py-2 text-left">SPA</th>
              <th class="px-3 py-2 text-left">No Truk</th>
              <th class="px-3 py-2 text-left">Induk</th>
              <th class="px-3 py-2 text-left">Petani</th>
              <th class="px-3 py-2 text-left">Kebun</th>
              <th class="px-3 py-2 text-center">Tgl Masuk</th>
              <th class="px-3 py-2 text-center">Jam Masuk</th>
              <th class="px-3 py-2 text-center">Jam Lama Tinggal</th>
            </tr>
          </thead>
          <tbody class="dark:text-slate-100">
            <tr v-for="(row, index) in belumTimbangRows" :key="`belum-${row.id}`" class="border-t border-gray-100 dark:border-gray-800">
              <td class="px-3 py-2 text-center">{{ index + 1 }}</td>
              <td class="px-3 py-2">{{ row.spa }}</td>
              <td class="px-3 py-2">{{ row.noTruk }}</td>
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
