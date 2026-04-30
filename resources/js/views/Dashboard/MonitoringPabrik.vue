<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { ActivityIcon, FlameIcon, RefreshCwIcon, ZapIcon } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

type WsStatus = 'disconnected' | 'connecting' | 'connected' | 'error'

type RealtimePayload = Record<string, unknown>

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
  <div class="flex min-h-screen flex-col gap-2 bg-gray-50 p-2 text-gray-900 dark:bg-gray-950 dark:text-white">

    <!-- ── Header bar ────────────────────────────────────────────── -->
    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 dark:border-gray-700 dark:bg-gray-900">
      <div class="flex items-center gap-3">
        <h1 class="text-sm font-bold tracking-wide text-gray-900 dark:text-white">MONITORING PABRIK REALTIME</h1>
        <span class="text-gray-300 dark:text-gray-600">|</span>
        <span class="text-xs text-gray-500">Mill · Boiler · Power House</span>
      </div>
      <div class="flex items-center gap-3">
        <div v-if="wsError" class="rounded border border-red-300 bg-red-50 px-2 py-0.5 text-xs text-red-600 dark:border-red-700 dark:bg-red-900/40 dark:text-red-300">
          {{ wsError }}
        </div>
        <span class="inline-flex items-center gap-1 text-xs font-semibold" :class="wsStatusClass">
          <ActivityIcon class="h-3 w-3" />
          {{ wsStatusLabel }}
        </span>
        <span class="text-xs text-gray-500">{{ updatedAt }}</span>
        <button
          @click="refreshData"
          class="inline-flex items-center gap-1 rounded border border-gray-200 bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 hover:bg-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
        >
          <RefreshCwIcon class="h-3 w-3" />
          Refresh
        </button>
      </div>
    </div>

    <!-- ── 3-column dashboard grid ───────────────────────────────── -->
    <div class="grid flex-1 grid-cols-1 gap-2 lg:grid-cols-3">

      <!-- ════════════════════════════════════════════════
           KOLOM 1 – MILL
      ════════════════════════════════════════════════ -->
      <div class="flex flex-col gap-2">

        <!-- Mill section header -->
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
          <div class="flex items-center justify-between border-b border-gray-100 px-3 py-1.5 dark:border-gray-800">
            <div class="flex items-center gap-2">
              <span
                :class="[
                  'relative inline-flex h-2.5 w-2.5 rounded-full',
                  isMillGilingLive ? 'bg-emerald-500' : 'bg-gray-400 dark:bg-gray-600',
                ]"
              >
                <span v-if="isMillGilingLive" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-70"></span>
              </span>
              <span class="text-[10px] font-bold uppercase tracking-widest text-gray-600 dark:text-gray-300">Mill</span>
              <span
                :class="[
                  'text-[9px] font-semibold uppercase',
                  isMillGilingLive ? 'animate-pulse text-emerald-500 dark:text-emerald-400' : 'text-red-500',
                ]"
              >{{ isMillGilingLive ? 'LIVE' : 'STOP' }}</span>
            </div>
            <span
              :class="[
                'rounded px-1.5 py-0.5 text-[9px] font-bold',
                isMillGilingLive
                  ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400'
                  : 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400',
              ]"
            >{{ millRpmActiveCount }}/5</span>
          </div>
        </div>

        <!-- Gilingan cards -->
        <div
          v-for="station in millStations"
          :key="station.station"
          class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"
        >
          <div class="border-b border-gray-100 px-3 py-1.5 dark:border-gray-800">
            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">{{ station.station }}</span>
          </div>

          <!-- Gilingan 1: RPM Motor + Ampere -->
          <div v-if="station.station === 'Gilingan 1'" class="grid grid-cols-2 divide-x divide-gray-100 dark:divide-gray-800">
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-amber-500 dark:text-amber-400">RPM Motor</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-amber-600 dark:text-amber-300">{{ station.rpmMotor }}</p>
                <p class="text-[9px] text-amber-500 dark:text-amber-700">RPM</p>
              </div>
            </div>
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Ampere</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-gray-700 dark:text-gray-200">{{ station.ampereMotor }}</p>
                <p class="text-[9px] text-gray-400 dark:text-gray-600">Amp</p>
              </div>
            </div>
          </div>

          <!-- Gilingan 2–5: RPM Turbin + Steam Charge -->
          <div v-else class="grid grid-cols-2 divide-x divide-gray-100 dark:divide-gray-800">
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-cyan-500 dark:text-cyan-400">RPM Turbin</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-cyan-600 dark:text-cyan-300">{{ station.rpmTurbin }}</p>
                <p class="text-[9px] text-cyan-500 dark:text-cyan-700">RPM</p>
              </div>
            </div>
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Steam Charge</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-gray-700 dark:text-gray-200">{{ station.steamCharge }}</p>
                <p class="text-[9px] text-gray-400 dark:text-gray-600">Kg/cm²</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════════════════════
           KOLOM 2 – BOILER
      ════════════════════════════════════════════════ -->
      <div class="flex flex-col gap-2">

        <!-- Boiler section header -->
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
          <div class="flex items-center gap-2 border-b border-gray-100 px-3 py-1.5 dark:border-gray-800">
            <FlameIcon class="h-3.5 w-3.5 text-orange-500" />
            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-600 dark:text-gray-300">Boiler</span>
          </div>
        </div>

        <!-- Uap Baru / Uap Bekas -->
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
          <div class="grid grid-cols-2 divide-x divide-gray-100 dark:divide-gray-800">
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-cyan-500 dark:text-cyan-400">Uap Baru</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-cyan-600 dark:text-cyan-300">{{ tekananUapBaru }}</p>
                <p class="text-[9px] text-cyan-500 dark:text-cyan-700">Kg/cm²</p>
              </div>
            </div>
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-amber-500 dark:text-amber-400">Uap Bekas</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-amber-600 dark:text-amber-300">{{ tekananUapBekas }}</p>
                <p class="text-[9px] text-amber-500 dark:text-amber-700">Kg/cm²</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Boiler units -->
        <div
          v-for="unit in boilerUnits"
          :key="unit.boiler"
          class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"
        >
          <div class="border-b border-gray-100 px-3 py-1.5 dark:border-gray-800">
            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">{{ unit.boiler }}</span>
          </div>
          <div class="grid grid-cols-2 divide-x divide-gray-100 dark:divide-gray-800">
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Steam Press.</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-gray-700 dark:text-gray-200">{{ unit.steamPressure }}</p>
                <p class="text-[9px] text-gray-400 dark:text-gray-600">Kg/cm²</p>
              </div>
            </div>
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-rose-500 dark:text-rose-400">Temp Furnace</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-rose-600 dark:text-rose-300">{{ unit.furnaceTemp }}</p>
                <p class="text-[9px] text-rose-500 dark:text-rose-700">°C</p>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-2 divide-x divide-gray-100 border-t border-gray-100 dark:divide-gray-800 dark:border-gray-800">
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-cyan-500 dark:text-cyan-400">Water Flow</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-cyan-600 dark:text-cyan-300">{{ unit.waterFlow }}</p>
                <p class="text-[9px] text-cyan-500 dark:text-cyan-700">M³/H</p>
              </div>
            </div>
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-emerald-500 dark:text-emerald-400">Steam Flow</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-emerald-600 dark:text-emerald-300">{{ unit.steamFlow }}</p>
                <p class="text-[9px] text-emerald-500 dark:text-emerald-700">TON</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Totalizer -->
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
          <div class="border-b border-gray-100 px-3 py-1.5 dark:border-gray-800">
            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">Totalizer</span>
          </div>
          <div class="grid grid-cols-2 divide-x divide-gray-100 dark:divide-gray-800">
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-emerald-500 dark:text-emerald-400">Steam Flow</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-emerald-600 dark:text-emerald-300">{{ totalSteamFlow }}</p>
                <p class="text-[9px] text-emerald-500 dark:text-emerald-700">TON</p>
              </div>
            </div>
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-cyan-500 dark:text-cyan-400">Water Flow</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-cyan-600 dark:text-cyan-300">0,00</p>
                <p class="text-[9px] text-cyan-500 dark:text-cyan-700">M³/H</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════════════════════
           KOLOM 3 – POWER HOUSE
      ════════════════════════════════════════════════ -->
      <div class="flex flex-col gap-2">

        <!-- Power House section header -->
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
          <div class="flex items-center gap-2 border-b border-gray-100 px-3 py-1.5 dark:border-gray-800">
            <ZapIcon class="h-3.5 w-3.5 text-yellow-500" />
            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-600 dark:text-gray-300">Power House</span>
          </div>
        </div>

        <!-- Steam parameters (Uap Baru PH / Uap Bekas PH) -->
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
          <div class="grid grid-cols-2 divide-x divide-gray-100 dark:divide-gray-800">
            <div
              v-for="item in powerSteam"
              :key="item.label"
              class="flex items-center justify-between px-3 py-2.5"
            >
              <p class="text-[9px] font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">{{ item.label }}</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-gray-700 dark:text-gray-200">{{ item.value }}</p>
                <p class="text-[9px] text-gray-400 dark:text-gray-600">{{ item.unit }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Generators -->
        <div
          v-for="gen in generators"
          :key="gen.name"
          class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"
        >
          <div class="flex items-center gap-2 border-b border-gray-100 px-3 py-1.5 dark:border-gray-800">
            <ZapIcon class="h-3 w-3 text-yellow-500" />
            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-600 dark:text-gray-300">{{ gen.name }}</span>
          </div>
          <div class="grid grid-cols-2 divide-x divide-gray-100 dark:divide-gray-800">
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-yellow-500 dark:text-yellow-400">Daya</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-yellow-600 dark:text-yellow-300">{{ gen.daya }}</p>
                <p class="text-[9px] text-yellow-500 dark:text-yellow-700">kW</p>
              </div>
            </div>
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Ampere</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-gray-700 dark:text-gray-200">{{ gen.ampere }}</p>
                <p class="text-[9px] text-gray-400 dark:text-gray-600">A</p>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-2 divide-x divide-gray-100 border-t border-gray-100 dark:divide-gray-800 dark:border-gray-800 lg:grid-cols-3">
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-blue-500 dark:text-blue-400">Tegangan</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-blue-600 dark:text-blue-300">{{ gen.tegangan }}</p>
                <p class="text-[9px] text-blue-500 dark:text-blue-700">V</p>
              </div>
            </div>
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-purple-500 dark:text-purple-400">Frekuensi</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-purple-600 dark:text-purple-300">{{ gen.frekuensi }}</p>
                <p class="text-[9px] text-purple-500 dark:text-purple-700">Hz</p>
              </div>
            </div>
            <div class="col-span-2 flex items-center justify-between px-3 py-2.5 lg:col-span-1">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Cos φ</p>
              <p class="seven-segment text-[2rem] font-bold leading-none text-gray-700 dark:text-gray-200">{{ gen.cosPhi }}</p>
            </div>
          </div>
        </div>

        <!-- PLN -->
        <div
          v-for="pln in powerPln"
          :key="pln.section"
          class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"
        >
          <div class="border-b border-gray-100 px-3 py-1.5 dark:border-gray-800">
            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">{{ pln.section }}</span>
          </div>
          <div class="grid grid-cols-2 divide-x divide-gray-100 dark:divide-gray-800">
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Arus</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-gray-700 dark:text-gray-200">{{ pln.arus }}</p>
                <p class="text-[9px] text-gray-400 dark:text-gray-600">A</p>
              </div>
            </div>
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-blue-500 dark:text-blue-400">Tegangan</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-blue-600 dark:text-blue-300">{{ pln.tegangan }}</p>
                <p class="text-[9px] text-blue-500 dark:text-blue-700">V</p>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-2 divide-x divide-gray-100 border-t border-gray-100 dark:divide-gray-800 dark:border-gray-800">
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-purple-500 dark:text-purple-400">Frekuensi</p>
              <div class="flex items-baseline gap-1">
                <p class="seven-segment text-[2rem] font-bold leading-none text-purple-600 dark:text-purple-300">{{ pln.frekuensi }}</p>
                <p class="text-[9px] text-purple-500 dark:text-purple-700">Hz</p>
              </div>
            </div>
            <div class="flex items-center justify-between px-3 py-2.5">
              <p class="text-[9px] font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Cos φ</p>
              <p class="seven-segment text-[2rem] font-bold leading-none text-gray-700 dark:text-gray-200">{{ pln.cosPhi }}</p>
            </div>
          </div>
        </div>

      </div><!-- /col Power House -->
    </div><!-- /3-column grid -->
  </div>
</template>

<style scoped>
@import url('https://fonts.cdnfonts.com/css/digital-7-mono');

.seven-segment {
  font-family: 'Digital-7 Mono', 'DS-Digital', 'Consolas', monospace;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.04em;
}
</style>
