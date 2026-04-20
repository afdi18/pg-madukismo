<script setup lang="ts">
import { computed, ref } from 'vue'
import { ActivityIcon, FlameIcon, GaugeIcon, RefreshCwIcon, ZapIcon } from 'lucide-vue-next'

type ScreenType = 'boiler' | 'mill' | 'power-house'

const activeScreen = ref<ScreenType>('boiler')
const updatedAt = ref(new Date().toLocaleString('id-ID'))

const boilerOverview = [
  { label: 'Total Steam Flow', value: '327,67', unit: 'TPH', accent: 'blue' },
  { label: 'Tekanan Uap Bekas', value: '0,49', unit: 'Kg/cm²', accent: 'amber' },
  { label: 'Tekanan Uap Baru', value: '13,20', unit: 'Kg/cm²', accent: 'cyan' },
  { label: 'Steam Drum Pressure', value: '1,1', unit: 'Kg/cm²', accent: 'green' },
  { label: 'Temp. Steam', value: '101', unit: '°C', accent: 'red' },
  { label: 'Drum Level', value: '49', unit: '%', accent: 'purple' },
  { label: 'Water Flow', value: '3,5', unit: 'T/H', accent: 'teal' },
]

const boilerAirTemp = [
  { label: 'A/H Inlet Air', value: '33', unit: '°C' },
  { label: 'A/H Outlet Air', value: '43', unit: '°C' },
  { label: 'A/H Outlet Gas', value: '35', unit: '°C' },
  { label: 'B. Outlet Gas', value: '69', unit: '°C' },
]

const boilerUnits = [
  { boiler: 'Boiler EKM 1', steamPressure: 'NA', furnaceTemp: '219', waterFlow: '0,00', steamFlow: '0,00' },
  { boiler: 'Boiler EKM 2', steamPressure: 'NA', furnaceTemp: '228', waterFlow: '0,00', steamFlow: '0,00' },
  { boiler: 'Boiler EKM 3', steamPressure: 'NA', furnaceTemp: '203', waterFlow: '0,00', steamFlow: '0,00' },
  { boiler: 'Boiler EKM 4', steamPressure: 'NA', furnaceTemp: '178', waterFlow: '0,00', steamFlow: '0,00' },
  { boiler: 'Boiler EKM 5', steamPressure: 'NA', furnaceTemp: '0', waterFlow: '0,00', steamFlow: '0,00' },
]

const millOverview = [
  { label: 'Steam Pressure', value: 'NA', unit: 'Kg/cm²' },
  { label: 'Tekanan Uap Bekas Evapo', value: '0,49', unit: 'Kg/cm²' },
  { label: 'Temp Uap Bekas Evapo', value: 'NA', unit: '°C' },
]

const millStations = [
  { station: 'ST. Gilingan 1', ampereMotor: 'NA', rpmMotor: '749', steamCharge: '-', rpmTurbin: '-' },
  { station: 'ST. Gilingan 2', ampereMotor: '-', rpmMotor: '-', steamCharge: 'NA', rpmTurbin: '2956' },
  { station: 'ST. Gilingan 3', ampereMotor: '-', rpmMotor: '-', steamCharge: 'NA', rpmTurbin: '3021' },
  { station: 'ST. Gilingan 4', ampereMotor: '-', rpmMotor: '-', steamCharge: 'NA', rpmTurbin: '3154' },
  { station: 'ST. Gilingan 5', ampereMotor: '-', rpmMotor: '-', steamCharge: 'NA', rpmTurbin: '0' },
]

const powerPln = [
  { section: 'PLN Utara', arus: '988', tegangan: '385', frekuensi: '50,0', cosPhi: '0,92' },
  { section: 'PLN Selatan', arus: 'NA', tegangan: 'NA', frekuensi: 'NA', cosPhi: 'NA' },
]

const powerSteam = [
  { label: 'UBa Pressure', value: '1,1', unit: 'Bar' },
  { label: 'Turbin Gen. 1', value: '0,0', unit: 'T/H' },
  { label: 'Turbin Gen. 2', value: '0,0', unit: 'T/H' },
  { label: 'Turbin Gen. 3', value: '0,0', unit: 'T/H' },
]

const generators = [
  { name: 'Generator 1', daya: '586', ampere: '88', tegangan: '6163', frekuensi: '50,02', cosPhi: '0,79' },
  { name: 'Generator 2', daya: '717', ampere: '82', tegangan: '6251', frekuensi: '50,05', cosPhi: '0,77' },
  { name: 'Generator 3', daya: '749', ampere: '1121', tegangan: '387', frekuensi: '50,31', cosPhi: '1,00' },
]

const screenTitle = computed(() => {
  if (activeScreen.value === 'boiler') return 'Monitoring Pabrik — Boiler Screen'
  if (activeScreen.value === 'mill') return 'Monitoring Pabrik — Mill Screen'
  return 'Monitoring Pabrik — Power House'
})

function refreshData() {
  updatedAt.value = new Date().toLocaleString('id-ID')
}

function screenButtonClass(screen: ScreenType): string {
  return activeScreen.value === screen
    ? 'bg-yellow-500 text-white border-yellow-500'
    : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
}

function accentClass(accent: string): string {
  return {
    blue: 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400',
    amber: 'bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400',
    cyan: 'bg-cyan-50 text-cyan-600 dark:bg-cyan-900/20 dark:text-cyan-400',
    green: 'bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400',
    red: 'bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400',
    purple: 'bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400',
    teal: 'bg-teal-50 text-teal-600 dark:bg-teal-900/20 dark:text-teal-400',
  }[accent] ?? 'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-white">{{ screenTitle }}</h1>
        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
          Parameter operasional real-time boiler, mill, dan power house.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <button
          @click="refreshData"
          class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-600 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
        >
          <RefreshCwIcon class="h-3.5 w-3.5" />
          Refresh
        </button>
        <span class="text-xs text-gray-400 dark:text-gray-500">Update: {{ updatedAt }}</span>
      </div>
    </div>

    <div class="flex flex-wrap gap-2">
      <button @click="activeScreen = 'boiler'" :class="['rounded-lg border px-3 py-2 text-sm font-semibold transition-colors', screenButtonClass('boiler')]">
        Boiler Screen
      </button>
      <button @click="activeScreen = 'mill'" :class="['rounded-lg border px-3 py-2 text-sm font-semibold transition-colors', screenButtonClass('mill')]">
        Mill Screen
      </button>
      <button @click="activeScreen = 'power-house'" :class="['rounded-lg border px-3 py-2 text-sm font-semibold transition-colors', screenButtonClass('power-house')]">
        Power House
      </button>
    </div>

    <div v-if="activeScreen === 'boiler'" class="space-y-4">
      <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-7">
        <div
          v-for="item in boilerOverview"
          :key="item.label"
          class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
        >
          <div class="mb-2 inline-flex rounded-lg p-2" :class="accentClass(item.accent)">
            <FlameIcon class="h-4 w-4" />
          </div>
          <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ item.label }}</p>
          <p class="mt-1 text-xl font-bold text-gray-800 dark:text-white">{{ item.value }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">{{ item.unit }}</p>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div
          v-for="temp in boilerAirTemp"
          :key="temp.label"
          class="rounded-xl border border-gray-100 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900"
        >
          <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ temp.label }}</p>
          <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white">{{ temp.value }} {{ temp.unit }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-3 xl:grid-cols-6">
        <div
          v-for="unit in boilerUnits"
          :key="unit.boiler"
          class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
        >
          <p class="mb-3 text-sm font-bold text-gray-700 dark:text-gray-200">{{ unit.boiler }}</p>
          <div class="space-y-2 text-xs">
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">Steam Pressure</span>
              <span class="font-semibold text-gray-800 dark:text-gray-100">{{ unit.steamPressure }} Kg/cm²</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">Temp Furnace</span>
              <span class="font-semibold text-rose-600 dark:text-rose-400">{{ unit.furnaceTemp }} °C</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">Water Flow</span>
              <span class="font-semibold text-cyan-600 dark:text-cyan-400">{{ unit.waterFlow }} M³/H</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">Steam Flow</span>
              <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ unit.steamFlow }} TON</span>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
          <p class="mb-3 text-sm font-bold text-gray-700 dark:text-gray-200">Totalizer</p>
          <div class="space-y-2 text-xs">
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">Steam Flow</span>
              <span class="font-semibold text-cyan-600 dark:text-cyan-400">0,00</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">Water Flow</span>
              <span class="font-semibold text-cyan-600 dark:text-cyan-400">0,00</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="activeScreen === 'mill'" class="space-y-4">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <div
          v-for="item in millOverview"
          :key="item.label"
          class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
        >
          <div class="mb-2 inline-flex rounded-lg bg-green-50 p-2 text-green-600 dark:bg-green-900/20 dark:text-green-400">
            <GaugeIcon class="h-4 w-4" />
          </div>
          <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ item.label }}</p>
          <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ item.value }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">{{ item.unit }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
        <div
          v-for="station in millStations"
          :key="station.station"
          class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
        >
          <p class="mb-3 text-sm font-bold text-gray-700 dark:text-gray-200">{{ station.station }}</p>
          <div class="space-y-2 text-xs">
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">Ampere Motor</span>
              <span class="font-semibold text-gray-800 dark:text-gray-100">{{ station.ampereMotor }} Amp</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">RPM Motor</span>
              <span class="font-semibold text-amber-600 dark:text-amber-400">{{ station.rpmMotor }} RPM</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">Steam Charge</span>
              <span class="font-semibold text-gray-800 dark:text-gray-100">{{ station.steamCharge }} Kg/cm²</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">RPM Turbin</span>
              <span class="font-semibold text-cyan-600 dark:text-cyan-400">{{ station.rpmTurbin }} RPM</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="space-y-4">
      <div class="grid grid-cols-1 gap-3 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 xl:col-span-2">
          <p class="mb-3 text-sm font-bold text-gray-700 dark:text-gray-200">Parameter PLN</p>
          <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div
              v-for="pln in powerPln"
              :key="pln.section"
              class="rounded-xl border border-gray-100 p-3 dark:border-gray-800"
            >
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ pln.section }}</p>
              <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="rounded-md bg-gray-50 p-2 dark:bg-gray-800">
                  <p class="text-gray-400">Arus</p>
                  <p class="font-semibold text-gray-800 dark:text-gray-100">{{ pln.arus }} A</p>
                </div>
                <div class="rounded-md bg-gray-50 p-2 dark:bg-gray-800">
                  <p class="text-gray-400">Tegangan</p>
                  <p class="font-semibold text-gray-800 dark:text-gray-100">{{ pln.tegangan }} V</p>
                </div>
                <div class="rounded-md bg-gray-50 p-2 dark:bg-gray-800">
                  <p class="text-gray-400">Frekuensi</p>
                  <p class="font-semibold text-gray-800 dark:text-gray-100">{{ pln.frekuensi }} Hz</p>
                </div>
                <div class="rounded-md bg-gray-50 p-2 dark:bg-gray-800">
                  <p class="text-gray-400">Cos φ</p>
                  <p class="font-semibold text-gray-800 dark:text-gray-100">{{ pln.cosPhi }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-3">
          <div
            v-for="item in powerSteam"
            :key="item.label"
            class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
          >
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ item.label }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ item.value }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ item.unit }}</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-3 xl:grid-cols-3">
        <div
          v-for="gen in generators"
          :key="gen.name"
          class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
        >
          <div class="mb-3 flex items-center gap-2">
            <span class="inline-flex rounded-lg bg-yellow-50 p-2 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400">
              <ZapIcon class="h-4 w-4" />
            </span>
            <p class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ gen.name }}</p>
          </div>
          <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="rounded-md bg-gray-50 p-2 dark:bg-gray-800">
              <p class="text-gray-400">Daya</p>
              <p class="font-semibold text-gray-800 dark:text-gray-100">{{ gen.daya }} kW</p>
            </div>
            <div class="rounded-md bg-gray-50 p-2 dark:bg-gray-800">
              <p class="text-gray-400">Ampere</p>
              <p class="font-semibold text-gray-800 dark:text-gray-100">{{ gen.ampere }} A</p>
            </div>
            <div class="rounded-md bg-gray-50 p-2 dark:bg-gray-800">
              <p class="text-gray-400">Tegangan</p>
              <p class="font-semibold text-gray-800 dark:text-gray-100">{{ gen.tegangan }} V</p>
            </div>
            <div class="rounded-md bg-gray-50 p-2 dark:bg-gray-800">
              <p class="text-gray-400">Frekuensi</p>
              <p class="font-semibold text-gray-800 dark:text-gray-100">{{ gen.frekuensi }} Hz</p>
            </div>
            <div class="col-span-2 rounded-md bg-gray-50 p-2 dark:bg-gray-800">
              <p class="text-gray-400">Cos φ</p>
              <p class="font-semibold text-gray-800 dark:text-gray-100">{{ gen.cosPhi }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- <div class="rounded-xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
      <div class="flex items-start gap-2 text-xs text-gray-500 dark:text-gray-400">
        <ActivityIcon class="mt-0.5 h-4 w-4 text-yellow-500" />
        Nilai pada halaman Monitoring Pabrik saat ini mengikuti data referensi yang Anda lampirkan (Boiler, Mill, dan Power House), dan siap dihubungkan ke endpoint SCADA ketika API tersedia.
      </div>
    </div> -->
  </div>
</template>
