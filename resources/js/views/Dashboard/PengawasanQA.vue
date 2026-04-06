<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { RefreshCwIcon, PrinterIcon, CalendarIcon, AlertTriangleIcon } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

// ============================================================
// TYPES
// ============================================================
interface HourData {
  nilai: number | null
  alert: boolean
}

interface ParameterRow {
  id: number
  nama_parameter: string
  satuan?: string | null
  ssrn: string
  operator_kondisi: string
  batas_bawah: number | null
  batas_atas: number | null
  data: Record<string, HourData>
  pagi_avg: number | null
  siang_avg: number | null
  malam_avg: number | null
  jml_rt2: number | null
}

interface StasiunData {
  id: number
  nama_stasiun: string
  parameters: ParameterRow[]
}

interface DashboardResponse {
  tanggal: string
  stasiuns: StasiunData[]
}

// ============================================================
// STATE
// ============================================================
const tanggal    = ref(new Date().toISOString().slice(0, 10))
const isLoading  = ref(false)
const hasError   = ref(false)
const data       = ref<DashboardResponse | null>(null)
const authStore  = useAuthStore()

// ============================================================
// TIME SLOTS: Pagi 06–13, Siang 14–21, Malam 22–05
// ============================================================
const pagiSlots  = Array.from({ length: 8 }, (_, i) => `${String(i + 6).padStart(2, '0')}-${String(i + 7).padStart(2, '0')}`)
const siangSlots = Array.from({ length: 8 }, (_, i) => `${String(i + 14).padStart(2, '0')}-${String(i + 15).padStart(2, '0')}`)
const malamSlots = computed(() => {
  const slots: string[] = []
  for (let i = 0; i < 8; i++) {
    const h = (22 + i) % 24
    const n = (22 + i + 1) % 24
    slots.push(`${String(h).padStart(2, '0')}-${String(n).padStart(2, '0')}`)
  }
  return slots
})
// Total columns: 2 sticky + 8 pagi + 1 Pagi + 8 siang + 1 Siang + 8 malam + 1 Malam + 1 jml = 30
const totalCols = 30

// ============================================================
// FETCH
// ============================================================
async function loadData() {
  isLoading.value = true
  hasError.value  = false
  try {
    const res = await axios.get<DashboardResponse>('/api/lab-qa/dashboard-monitoring', {
      params: { tanggal: tanggal.value },
    })
    data.value = res.data
  } catch {
    hasError.value = true
  } finally {
    isLoading.value = false
  }
}

onMounted(loadData)

// ============================================================
// HELPERS
// ============================================================
function fmt(val: number | null): string {
  if (val === null || val === undefined) return '—'
  return val % 1 === 0 ? String(val) : val.toFixed(2).replace(/\.?0+$/, '')
}

function cellClass(d: HourData | undefined): string {
  if (!d || d.nilai === null) return 'cell-empty'
  return d.alert ? 'cell-alert' : 'cell-ok'
}

function avgCellClass(val: number | null, row: ParameterRow): string {
  if (val === null) return 'cell-empty'
  return checkAlert(val, row) ? 'cell-alert' : 'cell-ok'
}

function checkAlert(val: number | null, row: ParameterRow): boolean {
  if (val === null) return false
  const op = row.operator_kondisi
  const lo = row.batas_bawah
  const hi = row.batas_atas
  if (op === '>'  && lo !== null) return val <= lo
  if (op === '>=' && lo !== null) return val < lo
  if (op === '<'  && hi !== null) return val >= hi
  if (op === '<=' && hi !== null) return val > hi
  if (op === 'BETWEEN' && lo !== null && hi !== null) return val < lo || val > hi
  return false
}

/** Roman numeral station number from string like "Gilingan" → "01" via index */
function stasiunLabel(idx: number): string {
  return String(idx + 1).padStart(2, '0')
}

function esc(value: string): string {
  return value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
}

function fmtTanggalId(value: string): string {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(date)
}

function buildPdfHtml(): string {
  const reportDate = data.value?.tanggal ? fmtTanggalId(data.value.tanggal) : '-'
  const generatedAt = new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  }).format(new Date())
  const printedFrom = `${window.location.origin}${window.location.pathname}`
  const printedBy = authStore.user?.name || authStore.user?.username || 'Unknown User'

  // Generate slot keys (same logic as dashboard)
  const pagiSlotKeys  = Array.from({ length: 8 }, (_, i) =>
    `${String(i + 6).padStart(2, '0')}-${String(i + 7).padStart(2, '0')}`)
  const siangSlotKeys = Array.from({ length: 8 }, (_, i) =>
    `${String(i + 14).padStart(2, '0')}-${String(i + 15).padStart(2, '0')}`)
  const malamSlotKeys = Array.from({ length: 8 }, (_, i) => {
    const h = (22 + i) % 24
    const n = (22 + i + 1) % 24
    return `${String(h).padStart(2, '0')}-${String(n).padStart(2, '0')}`
  })

  // Render a single hour-slot cell
  const slotCell = (slotKey: string, param: ParameterRow, shiftClass: string): string => {
    const entry = param.data?.[slotKey]
    if (!entry || entry.nilai === null) return `<td class="empty-cell ${shiftClass}">-</td>`
    return entry.alert
      ? `<td class="num alert-cell ${shiftClass}">${esc(fmt(entry.nilai))}</td>`
      : `<td class="num ${shiftClass}">${esc(fmt(entry.nilai))}</td>`
  }

  const slotLabel = (key: string) => key.split('-')[0] // "06-07" → "06"

  const TOTAL_COLS = 2 + 9 + 9 + 9 + 1 // 30
  const hourCols = '<col class="col-hour" />'.repeat(8)
  const shiftCols = `${hourCols}<col class="col-avg" />`
  const colgroupHtml = `<colgroup><col class="col-no" /><col class="col-param" />${shiftCols}${shiftCols}${shiftCols}<col class="col-total" /></colgroup>`

  const sectionHtml = (data.value?.stasiuns ?? [])
    .map((stasiun, idx) => {
      const rows = stasiun.parameters
        .map((param, pIdx) => {
          const satuan = param.satuan ? `<span class="satuan"> (${esc(param.satuan)})</span>` : ''
          const paramName = `<span class="param-name">${esc(param.nama_parameter)}${satuan}</span>`
          const ssrn   = param.ssrn   ? `<br><span class="ssrn-sub">SSRN: ${esc(param.ssrn)}</span>` : ''
          const rowClass = pIdx % 2 === 0 ? 'row-odd' : 'row-even'
          return `
            <tr class="${rowClass}">
              <td class="center">${pIdx + 1}</td>
              <td class="param-cell">${paramName}${ssrn}</td>
              ${pagiSlotKeys.map(k => slotCell(k, param, 'slot-pagi')).join('')}
              <td class="num avg-cell">${esc(fmt(param.pagi_avg))}</td>
              ${siangSlotKeys.map(k => slotCell(k, param, 'slot-siang')).join('')}
              <td class="num avg-cell">${esc(fmt(param.siang_avg))}</td>
              ${malamSlotKeys.map(k => slotCell(k, param, 'slot-malam')).join('')}
              <td class="num avg-cell">${esc(fmt(param.malam_avg))}</td>
              <td class="num total-cell">${esc(fmt(param.jml_rt2))}</td>
            </tr>`
        })
        .join('')

      return `
        <section class="station-block">
          <table>
            ${colgroupHtml}
            <thead>
              <tr>
                <th colspan="${TOTAL_COLS}" class="station-title-cell">${stasiunLabel(idx)}. ${esc(stasiun.nama_stasiun)}</th>
              </tr>
              <tr>
                <th rowspan="2" class="w-no">No</th>
                <th rowspan="2" class="w-param">Parameter</th>
                <th colspan="9" class="shift-hdr shift-pagi">&#9728; Shift Pagi</th>
                <th colspan="9" class="shift-hdr shift-siang">&#9925; Shift Siang</th>
                <th colspan="9" class="shift-hdr shift-malam">&#9790; Shift Malam</th>
                <th rowspan="2" class="w-total">Jml/<br>Rt2</th>
              </tr>
              <tr>
                ${pagiSlotKeys.map(k  => `<th class="w-hour hour-pagi">${slotLabel(k)}</th>`).join('')}
                <th class="w-avg avg-hdr">Avg</th>
                ${siangSlotKeys.map(k => `<th class="w-hour hour-siang">${slotLabel(k)}</th>`).join('')}
                <th class="w-avg avg-hdr">Avg</th>
                ${malamSlotKeys.map(k => `<th class="w-hour hour-malam">${slotLabel(k)}</th>`).join('')}
                <th class="w-avg avg-hdr">Avg</th>
              </tr>
            </thead>
            <tbody>
              ${rows || `<tr><td colspan="${TOTAL_COLS}" class="empty">Tidak ada data</td></tr>`}
            </tbody>
          </table>
        </section>`
    })
    .join('')

  return `<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Cetak PDF Dashboard Pengawasan QA</title>
  <style>
    @page { size: A4 landscape; margin: 12mm 10mm 10mm 10mm; }
    * {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      color-adjust: exact;
    }
    body {
      font-family: Arial, Helvetica, sans-serif;
      color: #111827;
      margin: 0;
      font-size: 7.5px;
    }
    .header {
      margin-bottom: 4px;
    }
    .title {
      font-size: 13px;
      font-weight: 700;
      color: #1e3a8a;
      margin: 0;
    }
    .meta {
      margin-top: 2px;
      color: #475569;
      font-size: 8px;
      white-space: normal;
      overflow-wrap: anywhere;
      word-break: break-word;
    }
    .report-divider {
      margin-top: 3px;
      height: 2px;
      background: #1d4ed8;
    }
    .station-block {
      margin-top: 6px;
      page-break-inside: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      page-break-inside: auto;
    }
    thead { display: table-header-group; }
    col.col-no    { width: 3%; }
    col.col-param { width: 25%; }
    col.col-hour  { width: 2.45%; }
    col.col-avg   { width: 2.8%; }
    col.col-total { width: 3%; }
    thead th {
      background: #e2e8f0;
      color: #334155;
      font-weight: 700;
      text-align: center;
      border: 1px solid #cbd5e1;
      padding: 3px 2px;
      line-height: 1.2;
    }
    thead th.w-no,
    thead th.w-param {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .station-title-cell {
      text-align: left;
      background: #ffffff;
      border: none;
      color: #1e40af;
      font-size: 9px;
      font-weight: 700;
      padding: 4px 0 4px 0;
    }
    .shift-hdr  { border-bottom: none; }
    .shift-pagi  { background: #fef3c7; color: #92400e; }
    .shift-siang { background: #dbeafe; color: #1e40af; }
    .shift-malam { background: #ede9fe; color: #5b21b6; }
    .hour-pagi   { background: #fffbeb; color: #92400e; }
    .hour-siang  { background: #eff6ff; color: #1e40af; }
    .hour-malam  { background: #f5f3ff; color: #5b21b6; }
    .avg-hdr     { background: #dcfce7; color: #166534; }
    tbody td {
      border: 1px solid #e2e8f0;
      padding: 1px 2px;
      vertical-align: middle;
      white-space: nowrap;
      overflow: hidden;
    }
    tbody td:first-child {
      padding-left: 1px;
      padding-right: 1px;
    }
    tbody tr {
      height: auto;
    }
    tbody tr.row-odd td  { background-color: #ffffff; }
    tbody tr.row-even td { background-color: #f8fafc; }

    .param-cell {
      color: #0f172a;
      white-space: normal;
      overflow: visible;
      word-break: break-word;
      line-height: 1.08;
      padding-top: 1px;
      padding-bottom: 1px;
      font-size: 6.7px;
    }
    .param-name {
      display: block;
      font-weight: 600;
      white-space: normal;
      overflow: visible;
      text-overflow: clip;
      overflow-wrap: anywhere;
      word-break: break-word;
      line-height: 1.12;
    }
    tbody tr.row-odd .param-cell  { background: #f1f5f9; }
    tbody tr.row-even .param-cell { background: #e8eef5; }

    tbody tr.row-odd .slot-pagi  { background: #fffdf2; }
    tbody tr.row-even .slot-pagi { background: #f8f4df; }
    tbody tr.row-odd .slot-siang  { background: #f7fbff; }
    tbody tr.row-even .slot-siang { background: #edf4fc; }
    tbody tr.row-odd .slot-malam  { background: #faf8ff; }
    tbody tr.row-even .slot-malam { background: #f1ecfa; }
    .center    { text-align: center; }
    .num       { text-align: right; font-variant-numeric: tabular-nums; }
    tbody tr.row-odd .avg-cell  { background: #ecfdf3; }
    tbody tr.row-even .avg-cell { background: #e0f7ea; }
    .avg-cell  { color: #166534; font-weight: 600; text-align: right; font-variant-numeric: tabular-nums; }

    tbody tr.row-odd .total-cell  { background: #e8f0ff; }
    tbody tr.row-even .total-cell { background: #dce8ff; }
    .total-cell{ color: #1e3a8a; font-weight: 700; text-align: right; font-variant-numeric: tabular-nums; }
    .alert-cell{ background: #fee2e2 !important; color: #991b1b; font-weight: 600; font-variant-numeric: tabular-nums; }
    .empty-cell{ color: #cbd5e1; text-align: center; }
    .ssrn-sub  {
      color: #6b7280;
      font-size: 5.8px;
      line-height: 1.02;
      display: block;
      margin-top: 1px;
      white-space: normal;
      overflow: visible;
      text-overflow: clip;
      overflow-wrap: anywhere;
      word-break: break-word;
    }
    .satuan    { color: #6b7280; }
    .w-no,
    .w-param,
    .w-hour,
    .w-avg,
    .w-total {
      width: auto;
      min-width: 0;
    }
    .empty   { text-align: center; color: #6b7280; font-style: italic; }
    @media print {
      html, body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color-adjust: exact;
      }
      col.col-no    { width: 3% !important; }
      col.col-param { width: 15% !important; }
      col.col-hour  { width: 3% !important; }
      col.col-avg   { width: 3% !important; }
      col.col-total { width: 3% !important; }
      .header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        margin: 0;
        z-index: 50;
        background: #ffffff;
        padding-bottom: 0.5mm;
      }
      .report-divider {
        margin-top: 2px;
      }
      .station-block:first-of-type {
        margin-top: 0;
      }
      thead th,
      .shift-pagi,
      .shift-siang,
      .shift-malam,
      .hour-pagi,
      .hour-siang,
      .hour-malam,
      .avg-hdr,
      .param-cell,
      .slot-pagi,
      .slot-siang,
      .slot-malam,
      .avg-cell,
      .total-cell,
      .alert-cell,
      .report-divider {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
    }
  </style>
</head>
<body>
  <div class="header">
    <p class="title">Dashboard Angka Pengawasan QA</p>
    <div class="meta">Tanggal data: ${esc(reportDate)} &middot; Dicetak dari: ${esc(printedFrom)} &middot; Oleh user: ${esc(printedBy)} &middot; Tanggal waktu cetak: ${esc(generatedAt)}</div>
    <div class="report-divider"></div>
  </div>
  ${sectionHtml || '<p>Tidak ada data untuk dicetak.</p>'}
</body>
</html>`
}

function printPdf() {
  if (!data.value) return

  const html = buildPdfHtml()
  const blob = new Blob([html], { type: 'text/html; charset=utf-8' })
  const url = URL.createObjectURL(blob)

  const printWindow = window.open(url, '_blank')
  if (!printWindow) {
    URL.revokeObjectURL(url)
    alert('Popup diblokir oleh browser. Harap izinkan popup untuk halaman ini lalu coba lagi.')
    return
  }

  printWindow.onload = () => {
    printWindow.focus()
    printWindow.print()
    URL.revokeObjectURL(url)
  }
}
</script>

<template>
  <!-- <div class="min-h-screen bg-gray-50 dark:bg-gray-950 p-4 md:p-6"> -->
    <div class="space-y-6">
    <div class="mobile-landscape-guard">
      <div class="mobile-landscape-card">
        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Gunakan mode landscape</p>
        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Putar perangkat agar dashboard lebih nyaman dilihat.</p>
      </div>
    </div>

    <!-- ======================================================
         HEADER CARD
    ======================================================= -->
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 mb-5 overflow-hidden">
      <!-- Top gradient strip -->
      <div class="h-1 w-full bg-gradient-to-r from-blue-600 via-indigo-500 to-purple-600" />

      <div class="px-5 py-4 flex flex-wrap items-center gap-3">
        <!-- Title -->
        <div class="mr-auto">
          <h1 class="text-lg font-bold text-gray-900 dark:text-white leading-tight">
            Dashboard Angka Pengawasan QA
          </h1>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            Monitoring analisa kualitas per jam · semua stasiun
          </p>
        </div>

        <!-- Date picker -->
        <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2">
          <CalendarIcon class="w-4 h-4 text-gray-400 flex-shrink-0" />
          <label class="text-xs font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Tanggal</label>
          <input
            v-model="tanggal"
            type="date"
            class="bg-transparent text-sm font-semibold text-gray-800 dark:text-gray-100 outline-none cursor-pointer"
            @change="loadData"
          />
        </div>

        <!-- Refresh btn -->
        <button
          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-sm font-semibold rounded-xl shadow-sm transition-all duration-150"
          :disabled="isLoading"
          @click="loadData"
        >
          <RefreshCwIcon class="w-4 h-4" :class="{ 'animate-spin': isLoading }" />
          Refresh
        </button>

        <!-- Cetak btn -->
        <button
          class="flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-800 active:scale-95 text-white text-sm font-semibold rounded-xl shadow-sm transition-all duration-150 print:hidden"
          @click="printPdf"
        >
          <PrinterIcon class="w-4 h-4" />
          Cetak PDF
        </button>
      </div>
    </div>

    <!-- ======================================================
         LOADING SKELETON
    ======================================================= -->
    <div v-if="isLoading" class="flex flex-col items-center justify-center py-24 gap-4 text-gray-400">
      <RefreshCwIcon class="w-8 h-8 animate-spin text-indigo-400" />
      <span class="text-sm">Memuat data…</span>
    </div>

    <!-- ======================================================
         ERROR
    ======================================================= -->
    <div v-else-if="hasError" class="flex flex-col items-center justify-center py-20 gap-3 text-red-400">
      <AlertTriangleIcon class="w-8 h-8" />
      <p class="text-sm font-medium">Gagal memuat data. Periksa koneksi atau coba refresh.</p>
    </div>

    <!-- ======================================================
         TABLE
    ======================================================= -->
    <div
      v-else-if="data"
      class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden"
    >
      <!-- LEGEND -->
      <div class="flex flex-wrap items-center gap-4 px-5 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/30 print:hidden">
        <span class="text-xs text-gray-500 font-medium">Keterangan :</span>
        <span class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300">
          <span class="inline-block w-3 h-3 rounded bg-emerald-200 dark:bg-emerald-800" />
          Dalam batas
        </span>
        <span class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300">
          <span class="inline-block w-3 h-3 rounded bg-red-200 dark:bg-red-900" />
          Di luar batas (alert)
        </span>
        <span class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300">
          <span class="inline-block w-3 h-3 rounded bg-gray-100 dark:bg-gray-700 border border-dashed border-gray-300 dark:border-gray-600" />
          Belum ada data
        </span>
      </div>

      <!-- SCROLL WRAPPER -->
      <div class="qa-scroll overflow-auto h-screen">
        <table class="border-collapse text-xs w-max min-w-full">

          <!-- ================================================
               TABLE HEAD (sticky top while printing or scroll)
          ================================================= -->
          <thead>
            <!-- Row 1: group labels -->
            <tr>
              <!-- Fixed cols -->
              <th
                rowspan="2"
                class="sticky top-0 left-0 z-40 min-w-[180px] max-w-[180px] bg-gray-800 dark:bg-gray-950 text-white border border-gray-700 px-3 py-2.5 text-left font-semibold text-xs tracking-wide"
              >
                PARAMETER
              </th>
              <th
                rowspan="2"
                class="sticky top-0 left-[180px] z-40 min-w-[72px] max-w-[72px] bg-gray-800 dark:bg-gray-950 text-white border border-gray-700 px-2 py-2.5 text-center font-semibold text-xs tracking-wide"
              >
                SSRN
              </th>

              <!-- Pagi -->
              <th
                colspan="9"
                class="sticky top-0 z-30 bg-sky-600 text-white border border-sky-700 px-3 py-2 text-center font-bold text-xs tracking-widest uppercase"
              >
                ☀ Shift Pagi
              </th>

              <!-- Siang -->
              <th
                colspan="9"
                class="sticky top-0 z-30 bg-amber-500 text-white border border-amber-600 px-3 py-2 text-center font-bold text-xs tracking-widest uppercase"
              >
                ⛅ Shift Siang
              </th>

              <!-- Malam -->
              <th
                colspan="9"
                class="sticky top-0 z-30 bg-indigo-700 text-white border border-indigo-800 px-3 py-2 text-center font-bold text-xs tracking-widest uppercase"
              >
                🌙 Shift Malam
              </th>

              <!-- Total -->
              <th
                rowspan="2"
                class="sticky top-0 z-30 bg-gray-700 text-white border border-gray-600 px-2 py-2.5 text-center font-bold text-xs"
              >
                Jml/<br>Rt2
              </th>
            </tr>

            <!-- Row 2: hour slot labels + shift avg labels -->
            <tr>
              <!-- Pagi slots -->
              <th
                v-for="slot in pagiSlots"
                :key="'h-' + slot"
                class="sticky top-[36px] z-20 bg-sky-50 dark:bg-sky-950 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 px-1.5 py-1.5 text-center font-semibold min-w-[48px] whitespace-nowrap"
              >
                {{ slot }}
              </th>
              <th class="sticky top-[36px] z-20 bg-sky-600/90 text-white border border-sky-700 px-2 py-1.5 text-center font-bold min-w-[44px]">
                Pagi
              </th>

              <!-- Siang slots -->
              <th
                v-for="slot in siangSlots"
                :key="'h-' + slot"
                class="sticky top-[36px] z-20 bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 px-1.5 py-1.5 text-center font-semibold min-w-[48px] whitespace-nowrap"
              >
                {{ slot }}
              </th>
              <th class="sticky top-[36px] z-20 bg-amber-500/90 text-white border border-amber-600 px-2 py-1.5 text-center font-bold min-w-[44px]">
                Siang
              </th>

              <!-- Malam slots -->
              <th
                v-for="slot in malamSlots"
                :key="'h-' + slot"
                class="sticky top-[36px] z-20 bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 px-1.5 py-1.5 text-center font-semibold min-w-[48px] whitespace-nowrap"
              >
                {{ slot }}
              </th>
              <th class="sticky top-[36px] z-20 bg-indigo-700/90 text-white border border-indigo-800 px-2 py-1.5 text-center font-bold min-w-[44px]">
                Malam
              </th>
            </tr>
          </thead>

          <!-- ================================================
               TABLE BODY
          ================================================= -->
          <tbody>
            <template v-for="(stasiun, sIdx) in data.stasiuns" :key="stasiun.id">

              <!-- Station header row -->
              <tr class="hidden md:table-row">
                <td
                  :colspan="totalCols"
                  class="bg-gradient-to-r from-gray-800 to-gray-700 dark:from-gray-950 dark:to-gray-900 text-white font-bold text-xs tracking-widest px-4 py-2 border border-gray-600 text-center uppercase"
                >
                  {{ stasiunLabel(sIdx) }}. {{ stasiun.nama_stasiun.toUpperCase() }}
                </td>
              </tr>

              <tr class="md:hidden">
                <td
                  class="sticky left-0 z-20 min-w-[180px] max-w-[180px] bg-gradient-to-r from-gray-800 to-gray-700 dark:from-gray-950 dark:to-gray-900 text-white font-bold text-[11px] tracking-wide px-3 py-2 border border-gray-600 text-left uppercase"
                >
                  {{ stasiunLabel(sIdx) }}. {{ stasiun.nama_stasiun }}
                </td>
                <td
                  class="sticky left-[180px] z-20 min-w-[72px] max-w-[72px] bg-gradient-to-r from-gray-800 to-gray-700 dark:from-gray-950 dark:to-gray-900 border border-gray-600"
                />
                <td
                  :colspan="totalCols - 2"
                  class="bg-gradient-to-r from-gray-800 to-gray-700 dark:from-gray-950 dark:to-gray-900 border border-gray-600"
                />
              </tr>

              <!-- Parameter rows -->
              <tr
                v-for="(param, pIdx) in stasiun.parameters"
                :key="param.id"
                :class="pIdx % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50/80 dark:bg-gray-800/40'"
                class="hover:bg-blue-50/40 dark:hover:bg-blue-900/10 transition-colors"
              >
                <!-- PARAMETER name (sticky) -->
                <td
                  class="sticky left-0 z-10 min-w-[180px] max-w-[180px] px-3 py-1.5 border border-gray-200 dark:border-gray-700 font-medium text-gray-700 dark:text-gray-300 bg-inherit whitespace-nowrap overflow-hidden text-ellipsis"
                  :class="pIdx % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-800'"
                >
                  {{ param.nama_parameter }}
                </td>

                <!-- SSRN (sticky) -->
                <td
                  class="sticky left-[180px] z-10 min-w-[72px] max-w-[72px] px-2 py-1.5 border border-gray-200 dark:border-gray-700 text-center font-semibold text-gray-600 dark:text-gray-400 whitespace-nowrap"
                  :class="pIdx % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-800'"
                >
                  {{ param.ssrn }}
                </td>

                <!-- Pagi hour slots -->
                <td
                  v-for="slot in pagiSlots"
                  :key="'p-' + slot"
                  class="px-1.5 py-1.5 border border-gray-100 dark:border-gray-800 text-center min-w-[48px] tabular-nums transition-colors"
                  :class="cellClass(param.data[slot])"
                >
                  {{ fmt(param.data[slot]?.nilai ?? null) }}
                </td>

                <!-- Pagi avg -->
                <td
                  class="px-2 py-1.5 border border-sky-200 dark:border-sky-800 text-center font-bold min-w-[44px] tabular-nums"
                  :class="avgCellClass(param.pagi_avg, param)"
                >
                  {{ fmt(param.pagi_avg) }}
                </td>

                <!-- Siang hour slots -->
                <td
                  v-for="slot in siangSlots"
                  :key="'s-' + slot"
                  class="px-1.5 py-1.5 border border-gray-100 dark:border-gray-800 text-center min-w-[48px] tabular-nums transition-colors"
                  :class="cellClass(param.data[slot])"
                >
                  {{ fmt(param.data[slot]?.nilai ?? null) }}
                </td>

                <!-- Siang avg -->
                <td
                  class="px-2 py-1.5 border border-amber-200 dark:border-amber-800 text-center font-bold min-w-[44px] tabular-nums"
                  :class="avgCellClass(param.siang_avg, param)"
                >
                  {{ fmt(param.siang_avg) }}
                </td>

                <!-- Malam hour slots -->
                <td
                  v-for="slot in malamSlots"
                  :key="'m-' + slot"
                  class="px-1.5 py-1.5 border border-gray-100 dark:border-gray-800 text-center min-w-[48px] tabular-nums transition-colors"
                  :class="cellClass(param.data[slot])"
                >
                  {{ fmt(param.data[slot]?.nilai ?? null) }}
                </td>

                <!-- Malam avg -->
                <td
                  class="px-2 py-1.5 border border-indigo-200 dark:border-indigo-800 text-center font-bold min-w-[44px] tabular-nums"
                  :class="avgCellClass(param.malam_avg, param)"
                >
                  {{ fmt(param.malam_avg) }}
                </td>

                <!-- jml/rt2 -->
                <td
                  class="px-2 py-1.5 border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-center font-bold tabular-nums text-gray-700 dark:text-gray-200"
                >
                  {{ fmt(param.jml_rt2) }}
                </td>
              </tr>

            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ======================================================
         EMPTY STATE
    ======================================================= -->
    <div v-else class="flex flex-col items-center justify-center py-20 text-gray-400 gap-2">
      <CalendarIcon class="w-8 h-8" />
      <p class="text-sm">Pilih tanggal untuk memuat data.</p>
    </div>

  </div>
</template>

<style scoped>
/* ============================================================
   CELL COLOR STATES
============================================================ */
.cell-ok {
  @apply bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300;
}

.cell-alert {
  @apply bg-red-50 text-red-700 font-semibold dark:bg-red-950/60 dark:text-red-400;
}

.cell-empty {
  @apply bg-gray-50 text-gray-400 dark:bg-gray-800/30 dark:text-gray-600;
}

/* ============================================================
   SUBTLE SCROLLBAR
============================================================ */
.qa-scroll {
  scrollbar-width: thin;
  scrollbar-color: rgba(148, 163, 184, 0.45) transparent;
}

.qa-scroll::-webkit-scrollbar {
  width: 10px;
  height: 10px;
}

.qa-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.qa-scroll::-webkit-scrollbar-thumb {
  background-color: rgba(148, 163, 184, 0.38);
  border-radius: 9999px;
  border: 2px solid transparent;
  background-clip: padding-box;
}

.qa-scroll::-webkit-scrollbar-thumb:hover {
  background-color: rgba(148, 163, 184, 0.58);
}

/* ============================================================
   MOBILE ORIENTATION GUARD
============================================================ */
.mobile-landscape-guard {
  display: none;
}

@media (max-width: 1023px) and (orientation: portrait) {
  .mobile-landscape-guard {
    position: fixed;
    inset: 0;
    z-index: 70;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(3, 7, 18, 0.86);
    backdrop-filter: blur(2px);
    padding: 1rem;
  }

  .mobile-landscape-card {
    width: 100%;
    max-width: 320px;
    border-radius: 0.75rem;
    border: 1px solid rgba(148, 163, 184, 0.35);
    background: rgba(255, 255, 255, 0.92);
    padding: 1rem;
    text-align: center;
  }

  .dark .mobile-landscape-card {
    background: rgba(17, 24, 39, 0.95);
    border-color: rgba(71, 85, 105, 0.6);
  }
}

/* ============================================================
   PRINT STYLES
============================================================ */
@media print {
  thead th {
    background-color: #1f2937 !important;
    color: #ffffff !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .cell-ok {
    background-color: #d1fae5 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .cell-alert {
    background-color: #fee2e2 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  table {
    font-size: 7pt;
  }

  td, th {
    padding: 2px 4px !important;
  }
}
</style>
