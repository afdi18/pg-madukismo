<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sqlsrv\RendemenHarian;
use App\Models\Sqlsrv\KpiOperasional;
use App\Models\Sqlsrv\RingkasanMusim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
