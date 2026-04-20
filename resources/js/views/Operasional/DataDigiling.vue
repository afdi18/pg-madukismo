<script setup lang="ts">
import { ref } from 'vue'

interface DigilingRow {
  jam: string
  berat: number
  truk: number
  lori: number
  total: number
  pemasukan: number
}

const hariGilingInput = ref(205)
const tanggalLabel = 'Senin, 13 April 2026'
const tanggalInput = ref('2026-04-13')

const pagiRows: DigilingRow[] = [
  { jam: '06-07', berat: 1616, truk: 19, lori: 0, total: 19, pemasukan: 0 },
  { jam: '07-08', berat: 1658, truk: 21, lori: 0, total: 21, pemasukan: 6 },
  { jam: '08-09', berat: 1865, truk: 27, lori: 0, total: 27, pemasukan: 2 },
  { jam: '09-10', berat: 2004, truk: 25, lori: 0, total: 25, pemasukan: 3 },
  { jam: '10-11', berat: 1921, truk: 22, lori: 0, total: 22, pemasukan: 0 },
  { jam: '11-12', berat: 1639, truk: 19, lori: 0, total: 19, pemasukan: 1 },
  { jam: '12-13', berat: 2084, truk: 27, lori: 0, total: 27, pemasukan: 0 },
  { jam: '13-14', berat: 1473, truk: 19, lori: 0, total: 19, pemasukan: 1 },
]

const siangRows: DigilingRow[] = [
  { jam: '14-15', berat: 1680, truk: 15, lori: 0, total: 15, pemasukan: 0 },
  { jam: '15-16', berat: 1700, truk: 20, lori: 0, total: 20, pemasukan: 2 },
  { jam: '16-17', berat: 1567, truk: 18, lori: 0, total: 18, pemasukan: 0 },
  { jam: '17-18', berat: 1597, truk: 18, lori: 0, total: 18, pemasukan: 0 },
  { jam: '18-19', berat: 2118, truk: 17, lori: 0, total: 17, pemasukan: 0 },
  { jam: '19-20', berat: 676, truk: 4, lori: 0, total: 4, pemasukan: 0 },
  { jam: '20-21', berat: 0, truk: 0, lori: 0, total: 0, pemasukan: 0 },
  { jam: '21-22', berat: 0, truk: 0, lori: 0, total: 0, pemasukan: 0 },
]

const malamRows: DigilingRow[] = [
  { jam: '22-23', berat: 0, truk: 0, lori: 0, total: 0, pemasukan: 0 },
  { jam: '23-00', berat: 0, truk: 0, lori: 0, total: 0, pemasukan: 0 },
  { jam: '00-01', berat: 0, truk: 0, lori: 0, total: 0, pemasukan: 0 },
  { jam: '01-02', berat: 0, truk: 0, lori: 0, total: 0, pemasukan: 0 },
  { jam: '02-03', berat: 0, truk: 0, lori: 0, total: 0, pemasukan: 0 },
  { jam: '03-04', berat: 0, truk: 0, lori: 0, total: 0, pemasukan: 0 },
  { jam: '04-05', berat: 0, truk: 0, lori: 0, total: 0, pemasukan: 0 },
  { jam: '05-06', berat: 0, truk: 0, lori: 0, total: 0, pemasukan: 0 },
]

const maxRows = Math.max(pagiRows.length, siangRows.length, malamRows.length)

function rowAt(rows: DigilingRow[], idx: number): DigilingRow {
  return rows[idx] ?? { jam: '-', berat: 0, truk: 0, lori: 0, total: 0, pemasukan: 0 }
}

function sumBy(rows: DigilingRow[], key: keyof DigilingRow) {
  return rows.reduce((acc, row) => acc + Number(row[key] ?? 0), 0)
}

function formatNumber(value: number) {
  return value.toLocaleString('id-ID')
}

type ShiftKey = 'pagi' | 'siang' | 'malam'

function shiftClass(shift: ShiftKey, part: 'group' | 'head' | 'cell' | 'total') {
  const map = {
    pagi: {
      group: 'bg-sky-500 text-white',
      head: 'bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-200 border-sky-200 dark:border-sky-800',
      cell: 'bg-sky-50/30 dark:bg-sky-950/10 !text-sky-700 dark:!text-sky-200',
      total: 'bg-sky-100 dark:bg-sky-900/35 !text-sky-800 dark:!text-sky-100',
    },
    siang: {
      group: 'bg-amber-500 text-white',
      head: 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-200 border-amber-200 dark:border-amber-800',
      cell: 'bg-amber-50/25 dark:bg-amber-950/10 !text-amber-700 dark:!text-amber-200',
      total: 'bg-amber-100 dark:bg-amber-900/35 !text-amber-800 dark:!text-amber-100',
    },
    malam: {
      group: 'bg-indigo-600 text-white',
      head: 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-200 border-indigo-200 dark:border-indigo-800',
      cell: 'bg-indigo-50/25 dark:bg-indigo-950/10 !text-indigo-700 dark:!text-indigo-200',
      total: 'bg-indigo-100 dark:bg-indigo-900/35 !text-indigo-800 dark:!text-indigo-100',
    },
  } as const

  return map[shift][part]
}
</script>

<template>
  <div class="space-y-4">
    <div class="relative overflow-hidden rounded-2xl border border-slate-700/70 bg-slate-900 px-5 py-4 shadow-[0_10px_24px_rgba(2,6,23,0.45)]">
      <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500"></div>
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold tracking-tight text-white">Tebu Digiling per Jam</h1>
          <p class="text-sm text-sky-200/90 mt-0.5">Monitoring analisa tebu per jam · Hari Giling ke {{ hariGilingInput }} · {{ tanggalLabel }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <div class="inline-flex items-center gap-2 rounded-xl border border-slate-600 bg-slate-800 px-3 py-2 text-sm text-slate-200">
            <span class="text-slate-300">Hari Giling</span>
            <input
              v-model.number="hariGilingInput"
              type="number"
              min="1"
              class="rounded-md border border-slate-500 bg-slate-700 px-2 py-1 text-sm font-semibold text-white outline-none focus:border-indigo-400"
            />
          </div>

          <div class="grid grid-cols-2 gap-2 text-sm ml-0 lg:ml-2">
            <div class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-700">
              <span class="text-sky-300 font-semibold">Antrian Truk</span>
              <p class="text-2xl leading-6 font-extrabold text-sky-200">0</p>
            </div>
            <div class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-700">
              <span class="text-indigo-300 font-semibold">Antrian Lori</span>
              <p class="text-2xl leading-6 font-extrabold text-indigo-200">0</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[1400px] font-mono">
          <thead>
            <tr class="text-gray-700 dark:text-gray-100">
              <th class="px-3 py-2 text-center font-bold" :class="shiftClass('pagi', 'group')" colspan="6">Pagi</th>
              <th class="px-3 py-2 text-center font-bold" :class="shiftClass('siang', 'group')" colspan="6">Siang</th>
              <th class="px-3 py-2 text-center font-bold" :class="shiftClass('malam', 'group')" colspan="6">Malam</th>
            </tr>
            <tr class="text-slate-600 dark:text-slate-200">
              <th class="px-3 py-2 text-center border" :class="shiftClass('pagi', 'head')">Jam</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('pagi', 'head')">Berat (Ku)</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('pagi', 'head')">Truk</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('pagi', 'head')">Lori</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('pagi', 'head')">Total</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('pagi', 'head')">Pemasukan</th>

              <th class="px-3 py-2 text-center border" :class="shiftClass('siang', 'head')">Jam</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('siang', 'head')">Berat (Ku)</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('siang', 'head')">Truk</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('siang', 'head')">Lori</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('siang', 'head')">Total</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('siang', 'head')">Pemasukan</th>

              <th class="px-3 py-2 text-center border" :class="shiftClass('malam', 'head')">Jam</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('malam', 'head')">Berat (Ku)</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('malam', 'head')">Truk</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('malam', 'head')">Lori</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('malam', 'head')">Total</th>
              <th class="px-3 py-2 text-right border" :class="shiftClass('malam', 'head')">Pemasukan</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="idx in maxRows"
              :key="`row-${idx}`"
              class="border-t border-gray-100 dark:border-gray-800"
            >
              <td class="px-3 py-2 text-center font-medium text-gray-700 dark:text-gray-200" :class="shiftClass('pagi', 'cell')">{{ rowAt(pagiRows, idx - 1).jam }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('pagi', 'cell')">{{ formatNumber(rowAt(pagiRows, idx - 1).berat) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('pagi', 'cell')">{{ formatNumber(rowAt(pagiRows, idx - 1).truk) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('pagi', 'cell')">{{ formatNumber(rowAt(pagiRows, idx - 1).lori) }}</td>
              <td class="px-3 py-2 text-right font-semibold" :class="shiftClass('pagi', 'cell')">{{ formatNumber(rowAt(pagiRows, idx - 1).total) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('pagi', 'cell')">{{ formatNumber(rowAt(pagiRows, idx - 1).pemasukan) }}</td>

              <td class="px-3 py-2 text-center font-medium text-gray-700 dark:text-gray-200" :class="shiftClass('siang', 'cell')">{{ rowAt(siangRows, idx - 1).jam }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('siang', 'cell')">{{ formatNumber(rowAt(siangRows, idx - 1).berat) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('siang', 'cell')">{{ formatNumber(rowAt(siangRows, idx - 1).truk) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('siang', 'cell')">{{ formatNumber(rowAt(siangRows, idx - 1).lori) }}</td>
              <td class="px-3 py-2 text-right font-semibold" :class="shiftClass('siang', 'cell')">{{ formatNumber(rowAt(siangRows, idx - 1).total) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('siang', 'cell')">{{ formatNumber(rowAt(siangRows, idx - 1).pemasukan) }}</td>

              <td class="px-3 py-2 text-center font-medium text-gray-700 dark:text-gray-200" :class="shiftClass('malam', 'cell')">{{ rowAt(malamRows, idx - 1).jam }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('malam', 'cell')">{{ formatNumber(rowAt(malamRows, idx - 1).berat) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('malam', 'cell')">{{ formatNumber(rowAt(malamRows, idx - 1).truk) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('malam', 'cell')">{{ formatNumber(rowAt(malamRows, idx - 1).lori) }}</td>
              <td class="px-3 py-2 text-right font-semibold" :class="shiftClass('malam', 'cell')">{{ formatNumber(rowAt(malamRows, idx - 1).total) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('malam', 'cell')">{{ formatNumber(rowAt(malamRows, idx - 1).pemasukan) }}</td>
            </tr>

            <tr class="border-t border-gray-200 dark:border-gray-700 bg-slate-50 dark:bg-slate-800/60 font-semibold">
              <td class="px-3 py-2" :class="shiftClass('pagi', 'total')">Total</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('pagi', 'total')">{{ formatNumber(sumBy(pagiRows, 'berat')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('pagi', 'total')">{{ formatNumber(sumBy(pagiRows, 'truk')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('pagi', 'total')">{{ formatNumber(sumBy(pagiRows, 'lori')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('pagi', 'total')">{{ formatNumber(sumBy(pagiRows, 'total')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('pagi', 'total')">{{ formatNumber(sumBy(pagiRows, 'pemasukan')) }}</td>

              <td class="px-3 py-2" :class="shiftClass('siang', 'total')">Total</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('siang', 'total')">{{ formatNumber(sumBy(siangRows, 'berat')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('siang', 'total')">{{ formatNumber(sumBy(siangRows, 'truk')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('siang', 'total')">{{ formatNumber(sumBy(siangRows, 'lori')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('siang', 'total')">{{ formatNumber(sumBy(siangRows, 'total')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('siang', 'total')">{{ formatNumber(sumBy(siangRows, 'pemasukan')) }}</td>

              <td class="px-3 py-2" :class="shiftClass('malam', 'total')">Total</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('malam', 'total')">{{ formatNumber(sumBy(malamRows, 'berat')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('malam', 'total')">{{ formatNumber(sumBy(malamRows, 'truk')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('malam', 'total')">{{ formatNumber(sumBy(malamRows, 'lori')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('malam', 'total')">{{ formatNumber(sumBy(malamRows, 'total')) }}</td>
              <td class="px-3 py-2 text-right" :class="shiftClass('malam', 'total')">{{ formatNumber(sumBy(malamRows, 'pemasukan')) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-4">
        <div class="h-1 w-full rounded-full bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500 shadow-[0_0_10px_rgba(99,102,241,0.45)] mb-3"></div>
        <h3 class="text-base font-extrabold text-gray-900 dark:text-white mb-2 ps-2">Tebu Digiling Hari Ini</h3>
        <div class="grid grid-cols-3 gap-2 text-sm">
          <div class="rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-center">
            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Ku</p>
            <p class="text-lg font-extrabold text-sky-700 dark:text-sky-300">23.598</p>
          </div>
          <div class="rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-center">
            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Rit Digiling</p>
            <p class="text-lg font-extrabold text-emerald-700 dark:text-emerald-300">271</p>
          </div>
          <div class="rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-center">
            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Rit Pemasukan</p>
            <p class="text-lg font-extrabold text-amber-700 dark:text-amber-300">15</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-4">
        <div class="h-1 w-full rounded-full bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500 shadow-[0_0_10px_rgba(99,102,241,0.45)] mb-3"></div>
        <h3 class="text-base font-extrabold text-gray-900 dark:text-white mb-2 ps-2">Total Tebu Digiling Sampai Hari Ini</h3>
        <div class="grid grid-cols-3 gap-2 text-sm">
          <div class="rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-center">
            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Ku</p>
            <p class="text-lg font-extrabold text-sky-700 dark:text-sky-300">9.567.514</p>
          </div>
          <div class="rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-center">
            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Rit Digiling</p>
            <p class="text-lg font-extrabold text-emerald-700 dark:text-emerald-300">130.656</p>
          </div>
          <div class="rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-center">
            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Rit Pemasukan</p>
            <p class="text-lg font-extrabold text-amber-700 dark:text-amber-300">0</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
