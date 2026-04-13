<script setup lang="ts">
import { ref } from 'vue'

type PemasukanKebunRow = {
  no: number
  register: string
  kelompok: string
  kebun: string
  luasHa: string
  taksasi: string
  hariIniRit: string
  hariIniBerat: string
  sdHariIniRit: string
  sdHariIniBerat: string
  produktivitas: string
  persentase: string
}

const hariKe = ref(206)
const kategori = ref('561 - TS I')

const kebunRows: PemasukanKebunRow[] = [
  { no: 1, register: '2.05.561.A.EI21', kelompok: '0', kebun: 'Sidorejo', luasHa: '1.334', taksasi: '937', hariIniRit: '0', hariIniBerat: '0', sdHariIniRit: '23', sdHariIniBerat: '1.214', produktivitas: '910', persentase: '97' },
  { no: 2, register: '2.05.561.A.EI23', kelompok: '0', kebun: 'Brahu', luasHa: '1.950', taksasi: '904', hariIniRit: '0', hariIniBerat: '0', sdHariIniRit: '34', sdHariIniBerat: '2.001', produktivitas: '1.026', persentase: '113' },
  { no: 3, register: '2.05.561.A.EI28', kelompok: '0', kebun: 'Sidorejo', luasHa: '2.005', taksasi: '882', hariIniRit: '0', hariIniBerat: '0', sdHariIniRit: '27', sdHariIniBerat: '1.658', produktivitas: '827', persentase: '94' },
  { no: 4, register: '2.05.561.A.EI31', kelompok: '0', kebun: 'Dagangan', luasHa: '0.290', taksasi: '799', hariIniRit: '0', hariIniBerat: '0', sdHariIniRit: '4', sdHariIniBerat: '273', produktivitas: '941', persentase: '118' },
  { no: 5, register: '2.06.561.A.EI90', kelompok: '0', kebun: 'Sidorejo', luasHa: '7.534', taksasi: '983', hariIniRit: '0', hariIniBerat: '0', sdHariIniRit: '104', sdHariIniBerat: '6.047', produktivitas: '803', persentase: '82' },
  { no: 6, register: '2.08.561.A.EJ83', kelompok: '0', kebun: 'Kajang', luasHa: '3.640', taksasi: '929', hariIniRit: '0', hariIniBerat: '0', sdHariIniRit: '55', sdHariIniBerat: '3.080', produktivitas: '846', persentase: '91' },
  { no: 137, register: '5.15.561.A.EL73', kelompok: '0', kebun: 'Lanud Iswahyudi', luasHa: '1.503', taksasi: '950', hariIniRit: '0', hariIniBerat: '0', sdHariIniRit: '29', sdHariIniBerat: '1.966', produktivitas: '1.308', persentase: '138' },
  { no: 138, register: '5.15.561.A.EQ94', kelompok: '0', kebun: 'LANUD ISWAHYUDI', luasHa: '5.000', taksasi: '700', hariIniRit: '0', hariIniBerat: '0', sdHariIniRit: '55', sdHariIniBerat: '3.503', produktivitas: '701', persentase: '100' },
  { no: 139, register: '5.16.561.A.FM18', kelompok: '0', kebun: 'Lanud Iswahyudi', luasHa: '1.606', taksasi: '905', hariIniRit: '0', hariIniBerat: '0', sdHariIniRit: '28', sdHariIniBerat: '1.456', produktivitas: '907', persentase: '100' },
  { no: 140, register: '5.16.561.A.EM19', kelompok: '0', kebun: 'Tambran', luasHa: '6.047', taksasi: '1.050', hariIniRit: '0', hariIniBerat: '0', sdHariIniRit: '93', sdHariIniBerat: '6.175', produktivitas: '1.021', persentase: '97' },
]

const totalRow = {
  luasHa: '473.548',
  taksasi: '861',
  hariIniRit: '0',
  hariIniBerat: '0',
  sdHariIniRit: '6.352',
  sdHariIniBerat: '398.372',
  produktivitas: '841',
  persentase: '98',
}
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
            class="relative px-3 sm:px-4 lg:px-5 py-4 text-xs sm:text-sm font-medium transition-all duration-300 shrink-0 whitespace-nowrap text-yellow-600 dark:text-yellow-400 after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-gradient-to-r after:from-yellow-400 after:to-yellow-600"
            type="button"
          >
            Pemasukan per Kebun
          </button>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl overflow-hidden">
      <div class="p-4 space-y-4">
        <div class="flex flex-wrap items-end gap-3">
          <label class="text-sm text-gray-700 dark:text-gray-200">
            <span class="mb-1 block">Harike</span>
            <input
              v-model.number="hariKe"
              type="number"
              min="1"
              class="w-28 h-10 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 text-sm text-gray-800 dark:text-gray-100"
            />
          </label>

          <label class="text-sm text-gray-700 dark:text-gray-200">
            <span class="mb-1 block">Kategori</span>
            <select
              v-model="kategori"
              class="w-40 h-10 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 text-sm text-gray-800 dark:text-gray-100"
            >
              <option>561 - TS I</option>
            </select>
          </label>

          <button class="h-10 rounded-lg bg-slate-800 px-6 text-sm font-medium text-white hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 self-end">
            Lihat
          </button>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
          <table class="w-full min-w-[1400px] text-sm">
            <thead>
              <tr class="bg-gray-200/90 dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                <th rowspan="2" class="px-2 py-2 text-center border border-gray-300 dark:border-gray-700">No</th>
                <th rowspan="2" class="px-2 py-2 text-center border border-gray-300 dark:border-gray-700">Register</th>
                <th rowspan="2" class="px-2 py-2 text-center border border-gray-300 dark:border-gray-700">Kelompok</th>
                <th rowspan="2" class="px-2 py-2 text-center border border-gray-300 dark:border-gray-700">Kebun</th>
                <th rowspan="2" class="px-2 py-2 text-center border border-gray-300 dark:border-gray-700">Luas (Ha)</th>
                <th rowspan="2" class="px-2 py-2 text-center border border-gray-300 dark:border-gray-700">Taksasi</th>
                <th colspan="2" class="px-2 py-2 text-center border border-gray-300 dark:border-gray-700">Hari Ini</th>
                <th colspan="2" class="px-2 py-2 text-center border border-gray-300 dark:border-gray-700">S / d Hari Ini</th>
                <th rowspan="2" class="px-2 py-2 text-center border border-gray-300 dark:border-gray-700">Produktifitas (Ku/Ha)</th>
                <th rowspan="2" class="px-2 py-2 text-center border border-gray-300 dark:border-gray-700">(%) Terhadap Taks</th>
              </tr>
              <tr class="bg-gray-200/90 dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                <th class="px-2 py-1.5 text-center border border-gray-300 dark:border-gray-700">Rit</th>
                <th class="px-2 py-1.5 text-center border border-gray-300 dark:border-gray-700">Berat (Ku)</th>
                <th class="px-2 py-1.5 text-center border border-gray-300 dark:border-gray-700">Rit</th>
                <th class="px-2 py-1.5 text-center border border-gray-300 dark:border-gray-700">Berat (Ku)</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in kebunRows"
                :key="row.no"
                class="bg-cyan-50/70 dark:bg-cyan-950/20 text-gray-800 dark:text-gray-100"
              >
                <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-left" :class="Number(row.persentase) > 100 ? 'text-red-600 dark:text-red-400' : ''">{{ row.no }}</td>
                <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40" :class="Number(row.persentase) > 100 ? 'text-red-600 dark:text-red-400' : ''">{{ row.register }}</td>
                <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40">{{ row.kelompok }}</td>
                <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40" :class="Number(row.persentase) > 100 ? 'text-red-600 dark:text-red-400' : ''">{{ row.kebun }}</td>
                <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right" :class="Number(row.persentase) > 100 ? 'text-red-600 dark:text-red-400' : ''">{{ row.luasHa }}</td>
                <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right" :class="Number(row.persentase) > 100 ? 'text-red-600 dark:text-red-400' : ''">{{ row.taksasi }}</td>
                <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right" :class="Number(row.persentase) > 100 ? 'text-red-600 dark:text-red-400' : ''">{{ row.hariIniRit }}</td>
                <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right" :class="Number(row.persentase) > 100 ? 'text-red-600 dark:text-red-400' : ''">{{ row.hariIniBerat }}</td>
                <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right" :class="Number(row.persentase) > 100 ? 'text-red-600 dark:text-red-400' : ''">{{ row.sdHariIniRit }}</td>
                <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right" :class="Number(row.persentase) > 100 ? 'text-red-600 dark:text-red-400' : ''">{{ row.sdHariIniBerat }}</td>
                <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right" :class="Number(row.persentase) > 100 ? 'text-red-600 dark:text-red-400' : ''">{{ row.produktivitas }}</td>
                <td class="px-2 py-1.5 border border-cyan-200 dark:border-cyan-900/40 text-right" :class="Number(row.persentase) > 100 ? 'text-red-600 dark:text-red-400' : ''">{{ row.persentase }}</td>
              </tr>

              <tr class="bg-gray-200/90 dark:bg-gray-800 text-gray-900 dark:text-white font-semibold">
                <td colspan="4" class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-center">Jumlah</td>
                <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ totalRow.luasHa }}</td>
                <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ totalRow.taksasi }}</td>
                <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ totalRow.hariIniRit }}</td>
                <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ totalRow.hariIniBerat }}</td>
                <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ totalRow.sdHariIniRit }}</td>
                <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ totalRow.sdHariIniBerat }}</td>
                <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ totalRow.produktivitas }}</td>
                <td class="px-2 py-2 border border-gray-300 dark:border-gray-700 text-right">{{ totalRow.persentase }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
