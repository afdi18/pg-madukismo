<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { ActivityIcon, FlameIcon, GaugeIcon, RefreshCwIcon, ZapIcon } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

type ScreenType = 'boiler' | 'mill' | 'power-house'

type WsStatus = 'disconnected' | 'connecting' | 'connected' | 'error'

type RealtimePayload = Record<string, unknown>

const activeScreen = ref<ScreenType>('boiler')
const updatedAt = ref(new Date().toLocaleString('id-ID'))
const wsStatus = ref<WsStatus>('disconnected')
const wsError = ref('')
const wsPayload = ref<RealtimePayload | null>(null)
const wsUrl = 'ws://36.67.9.117:3000'
let websocket: WebSocket | null = null

const authStore = useAuthStore()
const canAccessRealtimeMonitoring = computed(() => authStore.can('operasional.view'))

const boilerOverview = ref([
  { label: 'Steam Drum Pressure', value: '-', unit: 'Kg/cm²', accent: 'green' },
  { label: 'Temp. Steam', value: '-', unit: '°C', accent: 'red' },
  { label: 'Drum Level', value: '-', unit: '%', accent: 'purple' },
  { label: 'Water Flow', value: '0,00', unit: 'T/H', accent: 'teal' },
])

const totalSteamFlow = ref('-')
const tekananUapBaru = ref('-')
const tekananUapBekas = ref('-')

const boilerAirTemp = [
  { label: 'A/H Inlet Air', value: '-', unit: '°C' },
  { label: 'A/H Outlet Air', value: '-', unit: '°C' },
  { label: 'A/H Outlet Gas', value: '-', unit: '°C' },
  { label: 'B. Outlet Gas', value: '-', unit: '°C' },
]

const boilerUnits = ref([
  { boiler: 'Boiler EKM 1', steamPressure: '-', furnaceTemp: '-', waterFlow: '-', steamFlow: '-' },
  { boiler: 'Boiler EKM 2', steamPressure: '-', furnaceTemp: '-', waterFlow: '-', steamFlow: '-' },
  { boiler: 'Boiler EKM 3', steamPressure: '-', furnaceTemp: '-', waterFlow: '-', steamFlow: '-' },
  { boiler: 'Boiler EKM 4', steamPressure: '-', furnaceTemp: '-', waterFlow: '-', steamFlow: '-' },
  { boiler: 'Boiler EKM 5', steamPressure: '-', furnaceTemp: '-', waterFlow: '-', steamFlow: '-' },
])

const millStations = ref([
  { station: 'Gilingan 1', ampereMotor: '-', rpmMotor: '-', steamCharge: '-', rpmTurbin: '-' },
  { station: 'Gilingan 2', ampereMotor: '-', rpmMotor: '-', steamCharge: '-', rpmTurbin: '-' },
  { station: 'Gilingan 3', ampereMotor: '-', rpmMotor: '-', steamCharge: '-', rpmTurbin: '-' },
  { station: 'Gilingan 4', ampereMotor: '-', rpmMotor: '-', steamCharge: '-', rpmTurbin: '-' },
  { station: 'Gilingan 5', ampereMotor: '-', rpmMotor: '-', steamCharge: '-', rpmTurbin: '-' },
])

const powerPln = [
  { section: 'PLN Utara', arus: '-', tegangan: '-', frekuensi: '-', cosPhi: '-' },
  { section: 'PLN Selatan', arus: '-', tegangan: '-', frekuensi: '-', cosPhi: '-' },
]

const powerSteam = [
  { label: 'UBa Pressure', value: '-', unit: 'Bar' },
  { label: 'Turbin Gen. 1', value: '-', unit: 'T/H' },
  { label: 'Turbin Gen. 2', value: '-', unit: 'T/H' },
  { label: 'Turbin Gen. 3', value: '-', unit: 'T/H' },
]

const generators = ref([
  { name: 'Generator 1', daya: '-', ampere: '-', tegangan: '-', frekuensi: '-', cosPhi: '-' },
  { name: 'Generator 2', daya: '-', ampere: '-', tegangan: '-', frekuensi: '-', cosPhi: '-' },
  { name: 'Generator 3', daya: '-', ampere: '-', tegangan: '-', frekuensi: '-', cosPhi: '-' },
])

const screenTitle = computed(() => {
  if (activeScreen.value === 'boiler') return 'Monitoring Pabrik — Boiler Screen'
  if (activeScreen.value === 'mill') return 'Monitoring Pabrik — Mill Screen'
  return 'Monitoring Pabrik — Power House'
})

function refreshData() {
  connectWebSocket(true)
  updatedAt.value = new Date().toLocaleString('id-ID')
}

function formatRealtimeNumber(value: unknown, decimals = 2, divideByHundred = true): string {
  if (value === null || value === undefined || value === '') return 'NA'
  const rawNumberValue = Number(value)
  if (!Number.isFinite(rawNumberValue) || rawNumberValue >= 60000) return 'NA'
  const numberValue = divideByHundred ? rawNumberValue / 100 : rawNumberValue
  const fixed = decimals === 0 ? numberValue.toFixed(0) : numberValue.toFixed(decimals)
  return fixed.replace('.', ',')
}

function formatFromRealtime(payload: RealtimePayload, key: string, decimals = 2, divideByHundred = true): string {
  return formatRealtimeNumber(payload[key], decimals, divideByHundred)
}

function formatFromRealtimeKeys(
  payload: RealtimePayload,
  keys: string[],
  decimals = 2,
  divideByHundred = true,
): string {
  for (const key of keys) {
    if (key in payload) {
      return formatRealtimeNumber(payload[key], decimals, divideByHundred)
    }
  }

  return '-'
}

function toNumberOrNull(value: unknown): number | null {
  const numberValue = Number(value)
  if (!Number.isFinite(numberValue) || numberValue >= 60000) return null
  return numberValue
}

function extractRealtimePayload(raw: unknown): RealtimePayload | null {
  if (!raw || typeof raw !== 'object') return null

  const source = raw as Record<string, unknown>
  const candidates = [
    source,
    source.Broadcasting,
    source.broadcasting,
    source.data,
  ]

  for (const candidate of candidates) {
    if (candidate && typeof candidate === 'object') {
      const payload = candidate as Record<string, unknown>
      if (Object.keys(payload).length > 0) {
        return payload
      }
    }
  }

  return null
}

function applyRealtimePayload(payload: RealtimePayload) {
  wsPayload.value = payload
  updatedAt.value = new Date().toLocaleString('id-ID')

  const uapBaru = payload.uapbaru
  const uapBekas = payload.uapbekas
  const rpm1 = payload.rpm_gil1
  const rpm2 = payload.rpm_gil2
  const rpm3 = payload.rpm_gil3
  const rpm4 = payload.rpm_gil4
  const rpm5 = payload.rpm_gil5

  // Mapping DM sesuai tabel tag:
  // DM0/10/20/30/40   -> WaterFlow_EKM1..5
  // DM2/12/22/32/42   -> SteamFlow_EKM1..5
  // DM80/82/84/86/88  -> TempFurnace_EKM1..5
  boilerUnits.value = boilerUnits.value.map((unit, index) => {
    const ekmIndex = index + 1
    const waterKey = `dm${(ekmIndex - 1) * 10}`
    const steamKey = `dm${(ekmIndex - 1) * 10 + 2}`
    const tempKey = `dm${80 + (ekmIndex - 1) * 2}`

    return {
      ...unit,
      waterFlow: formatFromRealtime(payload, waterKey, 2),
      steamFlow: formatFromRealtime(payload, steamKey, 2),
      furnaceTemp: formatFromRealtime(payload, tempKey, 0, false),
    }
  })

  const totalSteamFlowSum = [payload.dm2, payload.dm12, payload.dm22, payload.dm32, payload.dm42]
    .map(toNumberOrNull)
    .filter((value): value is number => value !== null)
    .reduce((sum, value) => sum + value, 0)

  tekananUapBaru.value = formatRealtimeNumber(uapBaru, 2)
  tekananUapBekas.value = formatRealtimeNumber(uapBekas, 2)
  totalSteamFlow.value = totalSteamFlowSum > 0 ? formatRealtimeNumber(totalSteamFlowSum, 2) : 'NA'

  boilerOverview.value = boilerOverview.value.map((item) => {
    if (item.label === 'Water Flow') {
      return { ...item, value: '0,00' }
    }

    return item
  })

  millStations.value = millStations.value.map((item) => {
    if (item.station === 'Gilingan 1') {
      return {
        ...item,
        rpmMotor: formatRealtimeNumber(rpm1, 0),
        ampereMotor: formatFromRealtimeKeys(payload, ['amp_gil1', 'amper_gil1', 'ampere_gil1'], 2),
      }
    }

    if (item.station === 'Gilingan 2') {
      return {
        ...item,
        rpmTurbin: formatRealtimeNumber(rpm2, 0),
        steamCharge: formatFromRealtimeKeys(payload, ['steam_charge_gil2', 'steamcharge_gil2', 'steam_gil2'], 2),
      }
    }

    if (item.station === 'Gilingan 3') {
      return {
        ...item,
        rpmTurbin: formatRealtimeNumber(rpm3, 0),
        steamCharge: formatFromRealtimeKeys(payload, ['steam_charge_gil3', 'steamcharge_gil3', 'steam_gil3'], 2),
      }
    }

    if (item.station === 'Gilingan 4') {
      return {
        ...item,
        rpmTurbin: formatRealtimeNumber(rpm4, 0),
        steamCharge: formatFromRealtimeKeys(payload, ['steam_charge_gil4', 'steamcharge_gil4', 'steam_gil4'], 2),
      }
    }

    if (item.station === 'Gilingan 5') {
      return {
        ...item,
        rpmTurbin: formatRealtimeNumber(rpm5, 0),
        steamCharge: formatFromRealtimeKeys(payload, ['steam_charge_gil5', 'steamcharge_gil5', 'steam_gil5'], 2),
      }
    }

    return item
  })

}

function disconnectWebSocket() {
  if (!websocket) return
  websocket.onopen = null
  websocket.onmessage = null
  websocket.onerror = null
  websocket.onclose = null
  websocket.close()
  websocket = null
}

function connectWebSocket(forceReconnect = false) {
  if (!canAccessRealtimeMonitoring.value) return

  if (websocket && websocket.readyState === WebSocket.OPEN && !forceReconnect) return

  disconnectWebSocket()

  wsStatus.value = 'connecting'
  wsError.value = ''

  websocket = new WebSocket(wsUrl)

  websocket.onopen = () => {
    wsStatus.value = 'connected'
    wsError.value = ''
  }

  websocket.onmessage = (event) => {
    try {
      const parsed = JSON.parse(String(event.data))
      const payload = extractRealtimePayload(parsed)
      if (payload) {
        applyRealtimePayload(payload)
      }
    } catch {
      wsStatus.value = 'error'
      wsError.value = 'Format data WebSocket tidak valid.'
    }
  }

  websocket.onerror = () => {
    wsStatus.value = 'error'
    wsError.value = 'Gagal menghubungkan ke WebSocket server.'
  }

  websocket.onclose = () => {
    if (wsStatus.value !== 'error') {
      wsStatus.value = 'disconnected'
    }
  }
}

const wsStatusClass = computed(() => {
  if (wsStatus.value === 'connected') return 'text-emerald-600 dark:text-emerald-400'
  if (wsStatus.value === 'connecting') return 'text-amber-600 dark:text-amber-400'
  if (wsStatus.value === 'error') return 'text-red-600 dark:text-red-400'
  return 'text-gray-500 dark:text-gray-400'
})

const wsStatusLabel = computed(() => {
  if (wsStatus.value === 'connected') return 'WebSocket: Connected'
  if (wsStatus.value === 'connecting') return 'WebSocket: Connecting...'
  if (wsStatus.value === 'error') return 'WebSocket: Error'
  return 'WebSocket: Disconnected'
})

function isValidRawRealtimeValue(value: unknown): boolean {
  const numberValue = Number(value)
  return Number.isFinite(numberValue) && numberValue < 60000
}

const millRpmActiveCount = computed(() => {
  const payload = wsPayload.value
  if (!payload) return 0

  const rpmKeys = ['rpm_gil1', 'rpm_gil2', 'rpm_gil3', 'rpm_gil4', 'rpm_gil5'] as const

  return rpmKeys.reduce((count, key) => {
    const raw = payload[key]
    if (!isValidRawRealtimeValue(raw)) return count

    return Number(raw) > 0 ? count + 1 : count
  }, 0)
})

const isMillGilingLive = computed(() => millRpmActiveCount.value >= 4)

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

onMounted(() => {
  connectWebSocket()
})

onBeforeUnmount(() => {
  disconnectWebSocket()
})
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
        <span class="inline-flex items-center gap-1 text-xs font-semibold" :class="wsStatusClass">
          <ActivityIcon class="h-3.5 w-3.5" />
          {{ wsStatusLabel }}
        </span>
        <span class="text-xs text-gray-400 dark:text-gray-500">Update: {{ updatedAt }}</span>
      </div>
    </div>

    <div v-if="wsError" class="rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-xs text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300">
      {{ wsError }}
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
      <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <div class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
          <div class="mb-2 inline-flex rounded-lg bg-cyan-50 p-2 text-cyan-600 dark:bg-cyan-900/20 dark:text-cyan-400">
            <FlameIcon class="h-4 w-4" />
          </div>
          <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Tekanan Uap Baru</p>
          <p class="mt-1 text-xl font-bold text-gray-800 dark:text-white">{{ tekananUapBaru }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">Kg/cm²</p>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
          <div class="mb-2 inline-flex rounded-lg bg-amber-50 p-2 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400">
            <FlameIcon class="h-4 w-4" />
          </div>
          <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Tekanan Uap Bekas</p>
          <p class="mt-1 text-xl font-bold text-gray-800 dark:text-white">{{ tekananUapBekas }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">Kg/cm²</p>
        </div>
      </div>

      <div class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-3 flex items-center gap-2">
          <span class="inline-flex rounded-lg bg-orange-50 p-2 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400">
            <FlameIcon class="h-4 w-4" />
          </span>
          <p class="text-sm font-bold text-gray-700 dark:text-gray-200">Ketel Cheng-Chen</p>
        </div>

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

        <div class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-4">
          <div
            v-for="temp in boilerAirTemp"
            :key="temp.label"
            class="rounded-xl border border-gray-100 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900"
          >
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ temp.label }}</p>
            <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white">{{ temp.value }} {{ temp.unit }}</p>
          </div>
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
              <span class="font-semibold text-cyan-600 dark:text-cyan-400">-</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">Water Flow</span>
              <span class="font-semibold text-cyan-600 dark:text-cyan-400">-</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="activeScreen === 'mill'" class="space-y-4">
      <div
        :class="[
          'rounded-2xl border px-4 py-3 transition-all duration-300',
          isMillGilingLive
            ? 'border-emerald-300 bg-gradient-to-r from-emerald-50 via-lime-50 to-emerald-50 dark:border-emerald-700 dark:from-emerald-900/30 dark:via-lime-900/20 dark:to-emerald-900/30'
            : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/70',
        ]"
      >
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <span
              :class="[
                'relative inline-flex h-3 w-3 rounded-full',
                isMillGilingLive ? 'bg-emerald-500' : 'bg-gray-400 dark:bg-gray-500',
              ]"
            >
              <span
                v-if="isMillGilingLive"
                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-70"
              ></span>
            </span>
            <p
              :class="[
                'text-sm font-bold',
                isMillGilingLive
                  ? 'animate-pulse text-emerald-700 dark:text-emerald-300'
                  : 'text-red-600 dark:text-red-400',
              ]"
            >
              {{ isMillGilingLive ? 'GILING LIVE' : 'GILING BERHENTI' }}
            </p>
          </div>
          <span
            :class="[
              'rounded-full px-2.5 py-1 text-xs font-semibold',
              isMillGilingLive
                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
            ]"
          >
            RPM aktif: {{ millRpmActiveCount }}/5
          </span>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
        <div
          v-for="station in millStations"
          :key="station.station"
          class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
        >
          <p class="mb-3 text-sm font-bold text-gray-700 dark:text-gray-200">{{ station.station }}</p>
          <div v-if="station.station === 'Gilingan 1'" class="space-y-2 text-xs">
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">RPM Motor</span>
              <span class="font-semibold text-amber-600 dark:text-amber-400">{{ station.rpmMotor }} RPM</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">Ampere Motor</span>
              <span class="font-semibold text-gray-800 dark:text-gray-100">{{ station.ampereMotor }} Amp</span>
            </div>
          </div>

          <div v-else class="space-y-2 text-xs">
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">RPM Turbin</span>
              <span class="font-semibold text-cyan-600 dark:text-cyan-400">{{ station.rpmTurbin }} RPM</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-500 dark:text-gray-400">Steam Charge</span>
              <span class="font-semibold text-gray-800 dark:text-gray-100">{{ station.steamCharge }} Kg/cm²</span>
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
