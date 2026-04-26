<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sqlsrv\RendemenHarian;
use App\Models\Sqlsrv\KpiOperasional;
use App\Models\Sqlsrv\RingkasanMusim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Ringkasan utama dashboard — KPI cards di atas halaman.
     */
    public function summary(Request $request): JsonResponse
    {
        $musim = $request->musim_tanam ?? date('Y');

        $ringkasan = RingkasanMusim::on('sqlsrv')
            ->where('musim_tanam', $musim)
            ->first();

        // Data rendemen 7 hari terakhir
        $rendemenTerbaru = RendemenHarian::on('sqlsrv')
            ->where('musim_tanam', $musim)
            ->orderByDesc('tanggal')
            ->limit(7)
            ->get(['tanggal', 'rendemen_pabrik', 'tonase_masuk', 'efisiensi_pabrik']);

        // KPI hari ini (rata-rata dari shift)
        $hariIni = RendemenHarian::on('sqlsrv')
            ->whereDate('tanggal', today())
            ->first();

        return response()->json([
            'musim_tanam'     => $musim,
            'ringkasan'       => $ringkasan,
            'rendemen_7hari'  => $rendemenTerbaru,
            'hari_ini'        => $hariIni,
            'updated_at'      => now()->format('d M Y H:i'),
        ]);
    }

    /**
     * Data chart rendemen trend — untuk grafik line chart.
     */
    public function rendemenTrend(Request $request): JsonResponse
    {
        $musim  = $request->musim_tanam ?? date('Y');
        $periode= $request->periode ?? '30d'; // 7d, 30d, 3m, all

        $query = RendemenHarian::on('sqlsrv')
            ->where('musim_tanam', $musim)
            ->orderBy('tanggal');

        if ($periode === '7d') {
            $query->where('tanggal', '>=', now()->subDays(7));
        } elseif ($periode === '30d') {
            $query->where('tanggal', '>=', now()->subDays(30));
        } elseif ($periode === '3m') {
            $query->where('tanggal', '>=', now()->subMonths(3));
        }

        $data = $query->get([
            'tanggal',
            'rendemen_kebun',
            'rendemen_pabrik',
            'tonase_masuk',
            'tonase_gula',
            'efisiensi_pabrik',
            'kapasitas_giling',
        ]);

        return response()->json([
            'labels'         => $data->pluck('tanggal')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m')),
            'rendemen_kebun' => $data->pluck('rendemen_kebun'),
            'rendemen_pabrik'=> $data->pluck('rendemen_pabrik'),
            'tonase_masuk'   => $data->pluck('tonase_masuk'),
            'efisiensi'      => $data->pluck('efisiensi_pabrik'),
        ]);
    }

    /**
     * Data KPI per kategori untuk dashboard cards dan gauge.
     */
    public function kpiSummary(Request $request): JsonResponse
    {
        $musim  = $request->musim_tanam ?? date('Y');
        $periode= $request->periode ?? date('Y-m');

        $kpis = KpiOperasional::on('sqlsrv')
            ->where('musim_tanam', $musim)
            ->where('periode', $periode)
            ->orderBy('kategori')
            ->orderBy('nama_kpi')
            ->get();

        // Kelompokkan per kategori
        $grouped = $kpis->groupBy('kategori')->map(fn($items) =>
            $items->map(fn($k) => [
                'kode'        => $k->kode_kpi,
                'nama'        => $k->nama_kpi,
                'satuan'      => $k->satuan,
                'target'      => $k->target,
                'realisasi'   => $k->realisasi,
                'persentase'  => $k->persentase,
                'status'      => $k->status_kpi,
                'analisis'    => $k->analisis,
            ])
        );

        return response()->json([
            'musim_tanam' => $musim,
            'periode'     => $periode,
            'kpi'         => $grouped,
        ]);
    }

    /**
     * Perbandingan produksi antar musim tanam.
     */
    public function perbandinganMusim(): JsonResponse
    {
        $data = RingkasanMusim::on('sqlsrv')
            ->orderByDesc('musim_tanam')
            ->limit(5)
            ->get();

        return response()->json([
            'labels'          => $data->pluck('musim_tanam'),
            'tonase_tebang'   => $data->pluck('total_tonase_tebang'),
            'hasil_gula'      => $data->pluck('total_hasil_gula'),
            'rendemen_rata'   => $data->pluck('rendemen_rata'),
            'efisiensi_rata'  => $data->pluck('efisiensi_rata'),
        ]);
    }

    /**
     * Daftar musim tanam yang tersedia (untuk filter dropdown).
     */
    public function musimList(): JsonResponse
    {
        $musims = RingkasanMusim::on('sqlsrv')
            ->orderByDesc('musim_tanam')
            ->get(['musim_tanam', 'status_musim', 'mulai_giling', 'selesai_giling']);

        return response()->json(['data' => $musims]);
    }

    /**
     * Data tebu digiling per jam berdasarkan HR_GIL + pemasukan per jam berdasarkan HR_MSK.
     * - HR_GIL dan HR_MSK default diambil dari TBL_DEFAULT (SETT=1)
     * - Jika request hr_gil dikirim, gunakan value itu untuk query giling
     */
    public function dataDigilingPerJam(Request $request): JsonResponse
    {
        $defaultRow = DB::connection('sqlsrv')->selectOne(
            'SELECT TOP 1 HRGIL, HRPEM FROM TBL_DEFAULT WHERE SETT = 1'
        );

        $defaultHrGiling = isset($defaultRow->HRGIL)
            ? str_pad((string) $defaultRow->HRGIL, 3, '0', STR_PAD_LEFT)
            : '001';
        $defaultHrPem = isset($defaultRow->HRPEM)
            ? str_pad((string) $defaultRow->HRPEM, 3, '0', STR_PAD_LEFT)
            : '001';

        $requestedHrGiling = trim((string) $request->query('hr_gil', ''));
        $hrGiling = $requestedHrGiling !== ''
            ? str_pad($requestedHrGiling, 3, '0', STR_PAD_LEFT)
            : $defaultHrGiling;
        

        $rows = DB::connection('sqlsrv')->select(
            "SELECT
                DATEPART(HOUR, TGL_GIL) AS jam,
                COUNT(CASE WHEN NO_LORI IS NOT NULL THEN SPA END) AS rit_lori,
                COUNT(CASE WHEN NO_LORI IS NULL THEN SPA END) AS rit_truk,
                SUM(KW_NETTO) AS berat
             FROM TBL_TEBUMSK
             WHERE HR_GIL = ? AND KW_NETTO IS NOT NULL
             GROUP BY DATEPART(HOUR, TGL_GIL)",
            [$hrGiling]
        );

        $pemasukanRows = DB::connection('sqlsrv')->select(
            "SELECT
                DATEPART(HOUR, TGL_MSK) AS jam,
                COUNT(CASE WHEN BRUTTO IS NULL AND HR_MSK = ? THEN SPA END)
                +
                COUNT(CASE WHEN BRUTTO IS NOT NULL AND NETTO IS NOT NULL AND HR_MSK = ? THEN SPA END) AS rit_masuk_hi
             FROM TBL_TEBUMSK
             GROUP BY DATEPART(HOUR, TGL_MSK)",
            [$defaultHrPem, $defaultHrPem]
        );

        $pemasukanByHour = [];
        foreach ($pemasukanRows as $row) {
            $jam = (int) ($row->jam ?? 0);
            $pemasukanByHour[$jam] = (int) ($row->rit_masuk_hi ?? 0);
        }

        $gilingByHour = [];
        foreach ($rows as $row) {
            $jam = (int) ($row->jam ?? 0);
            $ritLori = (int) ($row->rit_lori ?? 0);
            $ritTruk = (int) ($row->rit_truk ?? 0);

            $gilingByHour[$jam] = [
                'rit_lori' => $ritLori,
                'rit_truk' => $ritTruk,
                'berat' => (float) ($row->berat ?? 0),
                'total' => $ritTruk + $ritLori,
            ];
        }

        $allHours = array_unique(array_merge(array_keys($gilingByHour), array_keys($pemasukanByHour)));
        sort($allHours);

        $normalized = array_map(static function ($jam) use ($gilingByHour, $pemasukanByHour) {
            $giling = $gilingByHour[$jam] ?? [
                'rit_lori' => 0,
                'rit_truk' => 0,
                'berat' => 0.0,
                'total' => 0,
            ];

            return [
                'jam' => (int) $jam,
                'rit_lori' => (int) $giling['rit_lori'],
                'rit_truk' => (int) $giling['rit_truk'],
                'total' => (int) $giling['total'],
                'berat' => (float) $giling['berat'],
                'pemasukan' => (int) ($pemasukanByHour[$jam] ?? 0),
            ];
        }, $allHours);

        return response()->json([
            'data' => $normalized,
            'meta' => [
                'hr_gil_default' => $defaultHrGiling,
                'hr_gil' => $hrGiling,
                'hr_pem_default' => $defaultHrPem,
                'hr_pem' => $defaultHrPem,
            ],
        ]);
    }

    /**
     * Total tebu digiling sampai dengan hari ini (cumulative).
     * Mengambil jumlah truk, lori, dan total berat dari TBL_TEBUMSK
     * dengan filter HR_GIL IS NOT NULL.
     */
    public function tebuDigilingSampaiBariIni(): JsonResponse
    {
        $digilingResult = DB::connection('sqlsrv')->selectOne(
            "SELECT
                COUNT(CASE WHEN NOT NO_LORI IS NULL THEN SPA END) AS rit_digiling_lori_sdhi,
                COUNT(CASE WHEN NO_LORI IS NULL THEN SPA END) AS rit_digiling_truk_sdhi,
                SUM(KW_NETTO) AS berat_digiling_sdhi
             FROM TBL_TEBUMSK
             WHERE NOT HR_GIL IS NULL"
        );

        $pemasukanResult = DB::connection('sqlsrv')->selectOne(
            "SELECT
                COUNT(CASE WHEN BRUTTO IS NULL THEN SPA END)
                + COUNT(CASE WHEN NOT BRUTTO IS NULL AND NETTO IS NULL THEN SPA END)
                + COUNT(CASE WHEN NOT BRUTTO IS NULL AND NOT NO_LORI IS NULL THEN SPA END) AS rit_pemasukan_sdhi
             FROM TBL_TEBUMSK"
        );

        return response()->json([
            'data' => [
                'rit_lori' => (int) ($digilingResult->rit_digiling_lori_sdhi ?? 0),
                'rit_truk' => (int) ($digilingResult->rit_digiling_truk_sdhi ?? 0),
                'berat' => (float) ($digilingResult->berat_digiling_sdhi ?? 0),
                'total_rit' => ((int) ($digilingResult->rit_digiling_lori_sdhi ?? 0)) + ((int) ($digilingResult->rit_digiling_truk_sdhi ?? 0)),
                'rit_pemasukan' => (int) ($pemasukanResult->rit_pemasukan_sdhi ?? 0),
            ],
        ]);
    }

    /**
     * Ringkasan antrian untuk dashboard informasi tebu.
     * Endpoint ini sengaja diletakkan di area dashboard.view agar
     * user tanpa akses menu monitoring antrian tetap melihat ringkasan.
     */
    public function antrianSummaryDashboard(): JsonResponse
    {
        $summary = DB::connection('sqlsrv')->selectOne(
            "SELECT
                COUNT(CASE WHEN NOT TGL_MSK IS NULL AND BRUTTO IS NULL THEN SPA END) AS antrian_truk,
                COUNT(CASE WHEN NOT NO_LORI IS NULL AND NOT NETTO IS NULL AND HR_GIL IS NULL THEN SPA END) AS antrian_lori,
                SUM(CASE WHEN NOT NO_LORI IS NULL AND NOT NETTO IS NULL AND HR_GIL IS NULL THEN ISNULL(KW_NETTO, 0) ELSE 0 END) AS berat_lori
             FROM TBL_TEBUMSK"
        );

        return response()->json([
            'data' => [
                'antrian_truk' => (int) ($summary->antrian_truk ?? 0),
                'antrian_lori' => (int) ($summary->antrian_lori ?? 0),
                'berat_lori' => (float) ($summary->berat_lori ?? 0),
            ],
        ]);
    }
}
