<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import axios from 'axios'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const toast = useToast()

const loadingConfig = ref(false)
const submitting = ref(false)
const hariGiling = ref<number>(1)
const showHariGilingDropdown = ref(false)
const latestHariGiling = ref<number>(1)
const faktorRendemen = ref<number>(0)

const jam = ref<number>(0)
const menit = ref<number>(0)
const currentHour = ref<number>(new Date().getHours())
const brix = ref<number | null>(null)
const pol = ref<number | null>(null)
let currentTimeInterval: ReturnType<typeof setInterval> | null = null

const updatedRows = ref<number | null>(null)

type PosNppRow = {
    no_meja: string
    no_urut: string
    menit: string
    hari_giling: string
    brix: number | null
    pol: number | null
    rend: number | null
}

const tableRows = ref<PosNppRow[]>([])
const loadingTable = ref(false)
const hariGilingDropdownRef = ref<HTMLElement | null>(null)
const gilingHourOrder = [...Array.from({ length: 18 }, (_, index) => index + 6), ...Array.from({ length: 6 }, (_, index) => index)]


const hariGilingOptions = computed(() => {
    const maxDay = Math.max(1, Number(hariGiling.value || 1))
    return Array.from({ length: maxDay }, (_, index) => index + 1)
})
const jamOptions = computed(() => {
    const isTodayGiling = Number(hariGiling.value) === Number(latestHariGiling.value)
    if (!isTodayGiling) return gilingHourOrder

    const currentHourIndex = gilingHourOrder.indexOf(currentHour.value)
    if (currentHourIndex === -1) return gilingHourOrder

    return gilingHourOrder.slice(0, currentHourIndex + 1)
})
const menitOptions = [0, 30]
const selectedHariGilingLabel = computed(() => String(hariGiling.value || 1).padStart(3, '0'))


const nilaiNira = computed(() => {
    const brixValue = Number(brix.value ?? 0)
    const polValue = Number(pol.value ?? 0)
    if (!Number.isFinite(brixValue) || !Number.isFinite(polValue)) return 0
    return polValue - 0.4 * (brixValue - polValue)
})

const hk = computed(() => {
    const brixValue = Number(brix.value ?? 0)
    const polValue = Number(pol.value ?? 0)
    if (!Number.isFinite(brixValue) || brixValue === 0 || !Number.isFinite(polValue)) return 0
    return (polValue / brixValue) * 100
})

const rend = computed(() => nilaiNira.value * Number(faktorRendemen.value ?? 0))

const canSubmit = computed(() => authStore.can('lab_qa.pos_npp'))

function toggleHariGilingDropdown() {
    showHariGilingDropdown.value = !showHariGilingDropdown.value
}

function selectHariGiling(day: number) {
    hariGiling.value = day
    showHariGilingDropdown.value = false
}

function closeHariGilingDropdownOnOutsideClick(event: MouseEvent) {
    if (!showHariGilingDropdown.value) return
    const root = hariGilingDropdownRef.value
    if (root && event.target instanceof Node && !root.contains(event.target)) {
        showHariGilingDropdown.value = false
    }
}

function setCurrentTimeDefaults() {
    const now = new Date()
    const hourNow = now.getHours()
    const minuteNow = now.getMinutes()

    currentHour.value = hourNow
    jam.value = hourNow
    menit.value = minuteNow >= 30 ? 30 : 0
}

function refreshCurrentHourLimit() {
    currentHour.value = new Date().getHours()

    if (!jamOptions.value.includes(jam.value)) {
        jam.value = jamOptions.value[jamOptions.value.length - 1] ?? gilingHourOrder[0]
    }
}

async function fetchTable() {
    loadingTable.value = true
    try {
        const { data } = await axios.get('/api/lab-qa/pos-npp/list', {
            params: {
                hari_giling: hariGiling.value,
                jam: jam.value,
                menit: menit.value,
            },
        })

        const rows = Array.isArray(data?.data) ? data.data : []
        tableRows.value = rows.map((row: any) => ({
            no_meja: String(row?.no_meja ?? ''),
            no_urut: String(row?.no_urut ?? ''),
            menit: String(row?.menit ?? ''),
            hari_giling: String(row?.hari_giling ?? ''),
            brix: typeof row?.brix === 'number' ? row.brix : null,
            pol: typeof row?.pol === 'number' ? row.pol : null,
            rend: typeof row?.rend === 'number' ? row.rend : null,
        }))
    } catch (error: any) {
        tableRows.value = []
        toast.error(error?.response?.data?.message ?? 'Gagal memuat tabel Pos NPP.')
    } finally {
        loadingTable.value = false
    }
}


async function fetchConfig() {
    loadingConfig.value = true
    try {
        const { data } = await axios.get('/api/lab-qa/pos-npp/config')
        latestHariGiling.value = Number(data?.data?.hari_giling ?? 1)
        hariGiling.value = latestHariGiling.value
        faktorRendemen.value = Number(data?.data?.fkr ?? 0)
        await fetchTable()
    } catch (error: any) {
        toast.error(error?.response?.data?.message ?? 'Gagal memuat konfigurasi Pos NPP.')
    } finally {
        loadingConfig.value = false
    }
}

async function submitPosNpp() {
    if (!canSubmit.value) {
        toast.error('Anda tidak memiliki akses untuk proses Pos NPP.')
        return
    }

    if (brix.value === null || pol.value === null) {
        toast.error('Nilai Brix dan Pol wajib diisi.')
        return
    }

    submitting.value = true
    updatedRows.value = null

    try {
        const payload = {
            hari_giling: hariGiling.value,
            jam: jam.value,
            menit: menit.value,
            brix: brix.value,
            pol: pol.value,
        }

        const { data } = await axios.post('/api/lab-qa/pos-npp/submit', payload)
        updatedRows.value = Number(data?.data?.updated_rows ?? 0)
        toast.success(`Simpan berhasil. ${updatedRows.value} baris diperbarui.`)
        await fetchTable()
    } catch (error: any) {
        const message = error?.response?.data?.message ?? 'Gagal memproses update Pos NPP.'
        toast.error(message)
    } finally {
        submitting.value = false
    }
}

watch([hariGiling, jam, menit], () => {
    fetchTable()
})

onMounted(() => {
    document.addEventListener('click', closeHariGilingDropdownOnOutsideClick)
    setCurrentTimeDefaults()
    refreshCurrentHourLimit()
    currentTimeInterval = setInterval(() => {
        refreshCurrentHourLimit()
    }, 30000)
    fetchConfig()
})

onBeforeUnmount(() => {
    if (currentTimeInterval) {
        clearInterval(currentTimeInterval)
        currentTimeInterval = null
    }
})
</script>

<template>
    <div class="space-y-4">
        <div class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Pos NPP</h1>
            <p class="mt-1 text-sm text-gray-500">Analisa Rendemen NPP per 30 Menit</p>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <div class="space-y-3 lg:col-span-4">
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-200">Hari Giling</span>
                        <div ref="hariGilingDropdownRef" class="relative">
                            <button type="button" @click="toggleHariGilingDropdown"
                                class="flex h-10 w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                <span>{{ selectedHariGilingLabel }}</span>
                                <span class="text-xs text-gray-500">▼</span>
                            </button>

                            <div v-if="showHariGilingDropdown"
                                class="absolute z-20 mt-1 w-full rounded-lg border border-gray-300 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                <div class="max-h-72 overflow-y-auto py-1">
                                    <button v-for="day in hariGilingOptions" :key="`hari-${day}`" type="button"
                                        @click="selectHariGiling(day)"
                                        :class="day === hariGiling
                                            ? 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-100'
                                            : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700'"
                                        class="block w-full px-3 py-2 text-left text-sm">
                                        {{ String(day).padStart(3, '0') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </label>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="flex flex-col gap-1 text-sm custom-scrollbar">
                            <span class="font-medium text-gray-700 dark:text-gray-200">Jam ke</span>
                            <select v-model.number="jam"
                                class="h-10 rounded-lg border border-gray-300 bg-white px-3 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                <option v-for="hour in jamOptions" :key="`jam-${hour}`" :value="hour">{{ hour }}
                                </option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-1 text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-200">Menit</span>
                            <select v-model.number="menit"
                                class="h-10 rounded-lg border border-gray-300 bg-white px-3 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                <option v-for="minute in menitOptions" :key="`menit-${minute}`" :value="minute">{{
                                    minute }}</option>
                            </select>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="flex flex-col gap-1 text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-200">Brix</span>
                            <input v-model.number="brix" type="number" step="0.0001"
                                class="h-10 rounded-lg border border-gray-300 bg-white px-3 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" />
                        </label>

                        <label class="flex flex-col gap-1 text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-200">Pol</span>
                            <input v-model.number="pol" type="number" step="0.0001"
                                class="h-10 rounded-lg border border-gray-300 bg-white px-3 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" />
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="flex flex-col gap-1 text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-200">Nilai Nira</span>
                            <input :value="nilaiNira.toFixed(2)" readonly
                                class="h-10 rounded-lg border border-gray-300 bg-gray-50 px-3 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" />
                        </label>

                        <label class="flex flex-col gap-1 text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-200">HK (%)</span>
                            <input :value="hk.toFixed(2)" readonly
                                class="h-10 rounded-lg border border-gray-300 bg-gray-50 px-3 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" />
                        </label>
                    </div>

                    <div class="pt-1">
                        <button :disabled="submitting || loadingConfig || !canSubmit" @click="submitPosNpp"
                            class="w-full rounded-lg bg-yellow-500 px-5 py-2 text-sm font-semibold text-white transition hover:bg-yellow-600 disabled:cursor-not-allowed disabled:opacity-50">
                            {{ submitting ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                        <div class="mt-2 text-xs text-gray-500">
                            Rendemen: <strong class="text-gray-800 dark:text-gray-100">{{ rend.toFixed(2) }}</strong>
                        </div>
                        <div v-if="updatedRows !== null"
                            class="mt-2 rounded-md bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                            {{ updatedRows }} baris ter-update
                        </div>
                    </div>
                </div>

                <div class="space-y-4 lg:col-span-8">
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-100">No
                                    </th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-100">Meja
                                    </th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-100">No
                                        Analisa</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-100">Menit
                                    </th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-100">Hari
                                        Giling</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-100">Brix
                                    </th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-100">Pol
                                    </th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-100">Rend
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                                <tr v-if="loadingTable">
                                    <td colspan="8" class="px-3 py-6 text-center text-gray-500">Memuat data tabel...
                                    </td>
                                </tr>
                                <tr v-else-if="tableRows.length === 0">
                                    <td colspan="8" class="px-3 py-6 text-center text-gray-500">Tidak ada data.</td>
                                </tr>
                                <tr v-for="(row, index) in tableRows" :key="`${row.no_meja}-${row.no_urut}-${index}`">
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ index + 1 }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ row.no_meja }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ row.no_urut }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ row.menit }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ row.hari_giling }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ row.brix }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ row.pol }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ row.rend }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>
