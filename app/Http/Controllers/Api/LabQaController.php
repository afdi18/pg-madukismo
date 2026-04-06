<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pgsql\QaHeader;
use App\Models\Pgsql\QaDetail;
use App\Models\Pgsql\Parameter;
use App\Models\Pgsql\Stasiun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabQaController extends Controller
{
    /**
     * Master stasiun untuk dropdown entri QA.
     */
    public function stasiunOptions(): JsonResponse
    {
        $data = Stasiun::query()
            ->select(['id', 'nama_stasiun'])
            ->orderBy('nama_stasiun')
            ->get();

        return response()->json(['data' => $data]);
    }

    /**
     * Master parameter aktif berdasarkan stasiun.
     */
    public function parameterOptions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stasiun_id' => ['required', 'integer', 'exists:pgsql.mst_stasiun,id'],
        ]);

        $data = Parameter::query()
            ->select(['id', 'stasiun_id', 'nama_parameter', 'satuan', 'operator_kondisi', 'batas_bawah', 'batas_atas', 'is_aktif'])
            ->where('stasiun_id', $validated['stasiun_id'])
            ->where('is_aktif', true)
            ->whereRaw('LOWER(nama_parameter) <> ?', ['tekanan roll'])
            ->orderBy('nama_parameter')
            ->get();

        return response()->json(['data' => $data]);
    }

    /**
     * Daftar data QA Header dengan filter
     */
    public function index(Request $request): JsonResponse
    {
        $query = QaHeader::query();

        // Filter berdasarkan tanggal
        if ($request->tanggal) {
            $query->byTanggal($request->tanggal);
        }

        // Filter berdasarkan shift
        if ($request->shift) {
            $query->byShift((int)$request->shift);
        }

        // Filter berdasarkan petugas
        if ($request->petugas) {
            $query->byPetugas($request->petugas);
        }

        // Filter berdasarkan stasiun
        if ($request->stasiun_id) {
            $query->byStasiun((int)$request->stasiun_id);
        }

        $qaHeaders = $query->with(['stasiun:id,nama_stasiun', 'details.parameter.stasiun'])
                           ->orderBy('tanggal', 'desc')
                           ->orderBy('jam', 'desc')
                           ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $qaHeaders->items(),
            'meta' => [
                'total'        => $qaHeaders->total(),
                'per_page'     => $qaHeaders->perPage(),
                'current_page' => $qaHeaders->currentPage(),
                'last_page'    => $qaHeaders->lastPage(),
            ],
        ]);
    }

    /**
     * Detail data QA Header lengkap dengan semua detail parameter
     */
    public function show(QaHeader $qaHeader): JsonResponse
    {
        $qaHeader->load(['stasiun:id,nama_stasiun', 'details.parameter.stasiun']);

        return response()->json([
            'data' => $qaHeader,
        ]);
    }

    /**
     * Simpan data QA baru dengan pengecekan alert otomatis
     *
     * Request body:
     * {
     *   "tanggal": "2026-03-20",
     *   "jam": "08:00:00",
     *   "shift": 1,
     *   "petugas": "Bambang Sutrisno",
     *   "stasiun_id": 1,
     *   "parameters": [
     *     { "parameter_id": 1, "nilai_aktual": 45.5 },
     *     { "parameter_id": 2, "nilai_aktual": 7.2 }
     *   ]
     * }
     */
    public function store(Request $request): JsonResponse
    {
        // ================================================================
        // VALIDASI INPUT
        // ================================================================
        $validated = $request->validate([
            'tanggal'    => ['required', 'date'],
            'jam'        => ['required', 'date_format:H:i:s'],
            'shift'      => ['required', 'integer', 'in:1,2,3'],
            'petugas'    => ['required', 'string', 'max:100'],
            'stasiun_id' => ['required', 'integer', 'exists:pgsql.mst_stasiun,id'],
            'parameters' => ['required', 'array', 'min:1'],
            'parameters.*.parameter_id'  => ['required', 'integer', 'exists:pgsql.mst_parameter,id'],
            'parameters.*.nilai_aktual'   => ['nullable', 'numeric'],
        ], [
            'tanggal.required'    => 'Tanggal QA wajib diisi.',
            'jam.required'        => 'Jam QA wajib diisi.',
            'shift.required'      => 'Shift wajib dipilih (1, 2, atau 3).',
            'petugas.required'    => 'Nama petugas wajib diisi.',
            'stasiun_id.required' => 'Stasiun wajib dipilih.',
            'stasiun_id.exists'   => 'Stasiun yang dipilih tidak ditemukan.',
            'parameters.required' => 'Minimal harus ada satu parameter data QA.',
            'parameters.min'      => 'Minimal harus ada satu parameter data QA.',
        ]);

        // ================================================================
        // TRANSAKSI DB: SIMPAN HEADER + DETAIL DENGAN PENGECEKAN ALERT
        // ================================================================
        try {
            $qaHeader = DB::connection('pgsql')->transaction(function () use ($validated) {
                // 1. SIMPAN HEADER QA
                $qaHeader = QaHeader::create([
                    'tanggal' => $validated['tanggal'],
                    'jam'     => $validated['jam'],
                    'shift'   => $validated['shift'],
                    'petugas' => $validated['petugas'],
                    'stasiun_id' => $validated['stasiun_id'],
                ]);

                // 2. SIMPAN DETAIL QA DENGAN PENGECEKAN ALERT OTOMATIS
                foreach ($validated['parameters'] as $paramData) {
                    $parameter = Parameter::query()
                        ->where('id', $paramData['parameter_id'])
                        ->where('stasiun_id', $validated['stasiun_id'])
                        ->first();

                    if (!$parameter) {
                        throw new \Exception("Parameter ID {$paramData['parameter_id']} tidak valid untuk stasiun yang dipilih.");
                    }

                    // PENGECEKAN ALERT: Apakah nilai melanggar batas?
                    $statusAlert = QaDetail::checkAlert($parameter, $paramData['nilai_aktual'] !== null ? (float)$paramData['nilai_aktual'] : null);

                    // SIMPAN DETAIL
                    QaDetail::create([
                        'header_id'    => $qaHeader->id,
                        'parameter_id' => $paramData['parameter_id'],
                        'nilai_aktual' => $paramData['nilai_aktual'],
                        'status_alert' => $statusAlert,
                    ]);
                }

                return $qaHeader;
            });

            // LOAD DETAIL & RELASI UNTUK RESPONSE
            $qaHeader->load(['stasiun:id,nama_stasiun', 'details.parameter.stasiun']);

            return response()->json([
                'message' => 'Data QA berhasil disimpan. Alert otomatis sudah dikonfirmasi.',
                'data'    => $qaHeader,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan data QA: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Edit data QA Header
     */
    public function update(Request $request, QaHeader $qaHeader): JsonResponse
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'jam'     => ['required', 'date_format:H:i:s'],
            'shift'   => ['required', 'integer', 'in:1,2,3'],
            'petugas' => ['required', 'string', 'max:100'],
        ]);

        $qaHeader->update($validated);

        return response()->json([
            'message' => 'Data QA berhasil diperbarui.',
            'data'    => $qaHeader->fresh(['stasiun:id,nama_stasiun', 'details.parameter.stasiun']),
        ]);
    }

    /**
     * Hapus data QA Header (cascade delete ke detail)
     */
    public function destroy(QaHeader $qaHeader): JsonResponse
    {
        $qaHeader->delete();

        return response()->json([
            'message' => 'Data QA berhasil dihapus.',
        ]);
    }

    /**
     * Update nilai_aktual untuk satu QA Detail dan re-check alert
     */
    public function updateDetail(Request $request, QaDetail $qaDetail): JsonResponse
    {
        $validated = $request->validate([
            'nilai_aktual' => ['nullable', 'numeric'],
        ]);

        // RE-CHECK ALERT setelah update nilai
        $statusAlert = QaDetail::checkAlert($qaDetail->parameter, $validated['nilai_aktual'] !== null ? (float)$validated['nilai_aktual'] : null);

        $qaDetail->update([
            'nilai_aktual' => $validated['nilai_aktual'],
            'status_alert' => $statusAlert,
        ]);

        return response()->json([
            'message' => 'Data detail QA berhasil diperbarui. Status alert sudah dikonfirmasi ulang.',
            'data'    => $qaDetail->fresh(['parameter.stasiun']),
        ]);
    }

    /**
     * Hapus satu QA Detail
     */
    public function destroyDetail(QaDetail $qaDetail): JsonResponse
    {
        $qaDetail->delete();

        return response()->json([
            'message' => 'Data detail QA berhasil dihapus.',
        ]);
    }

    /**
     * Dashboard monitoring: data per jam untuk hari giling tertentu.
     *
     * Satu "hari giling" = tanggal 06:00 s/d tanggal+1 05:59.
     * GET /api/lab-qa/dashboard-monitoring?tanggal=YYYY-MM-DD
     */
    public function dashboardMonitoring(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
        ]);

        $tanggal = $validated['tanggal'];
        $nextDay = date('Y-m-d', strtotime($tanggal . ' +1 day'));

        // Ambil semua header QA untuk hari giling ini:
        // - Tanggal ini, jam >= 06:00 (shift 1 & 2)
        // - Hari berikutnya, jam < 06:00 (sisa shift 3 malam)
        $headers = QaHeader::with(['details.parameter'])
            ->where(function ($q) use ($tanggal, $nextDay) {
                $q->where(function ($q2) use ($tanggal) {
                    $q2->whereDate('tanggal', $tanggal)->where('jam', '>=', '06:00:00');
                })->orWhere(function ($q2) use ($nextDay) {
                    $q2->whereDate('tanggal', $nextDay)->where('jam', '<', '06:00:00');
                });
            })
            ->get();

        // Bangun map: [stasiun_id][param_id][slot] = [[nilai, alert], ...]
        $dataMap = [];
        foreach ($headers as $header) {
            $hour     = (int) substr($header->jam, 0, 2);
            $nextHour = ($hour + 1) % 24;
            $slot     = sprintf('%02d-%02d', $hour, $nextHour);

            foreach ($header->details as $detail) {
                if (!$detail->parameter) continue;
                $sId  = $detail->parameter->stasiun_id;
                $pId  = $detail->parameter_id;
                if (!isset($dataMap[$sId][$pId][$slot])) {
                    $dataMap[$sId][$pId][$slot] = [];
                }
                if ($detail->nilai_aktual !== null) {
                    $dataMap[$sId][$pId][$slot][] = [
                        'nilai' => (float) $detail->nilai_aktual,
                        'alert' => (bool)  $detail->status_alert,
                    ];
                }
            }
        }

        // Urutan slot: pagi (06–13), siang (14–21), malam (22–05)
        $allSlots = [];
        for ($h = 6; $h < 30; $h++) {
            $a = $h % 24;
            $n = ($a + 1) % 24;
            $allSlots[] = sprintf('%02d-%02d', $a, $n);
        }
        $pagiSlots   = array_slice($allSlots, 0, 8);   // 06-07 … 13-14
        $siangSlots  = array_slice($allSlots, 8, 8);   // 14-15 … 21-22
        $malamSlots  = array_slice($allSlots, 16, 8);  // 22-23 … 05-06

        // Fetch semua stasiun dengan parameter aktif
        $stasiuns = Stasiun::with(['parametersAktif' => fn($q) => $q
            ->whereRaw('LOWER(nama_parameter) <> ?', ['tekanan roll'])
            ->orderBy('id')
        ])
                           ->orderBy('id')
                           ->get();

        $result = [];
        foreach ($stasiuns as $stasiun) {
            $params = [];
            foreach ($stasiun->parametersAktif as $param) {
                $hourData    = [];
                $pagiVals    = [];
                $siangVals   = [];
                $malamVals   = [];

                foreach ($allSlots as $slot) {
                    $entries = $dataMap[$stasiun->id][$param->id][$slot] ?? [];
                    if (empty($entries)) {
                        $hourData[$slot] = ['nilai' => null, 'alert' => false];
                    } else {
                        $avg      = array_sum(array_column($entries, 'nilai')) / count($entries);
                        $hasAlert = in_array(true, array_column($entries, 'alert'), true);
                        $hourData[$slot] = ['nilai' => round($avg, 2), 'alert' => $hasAlert];

                        if (in_array($slot, $pagiSlots))  $pagiVals[]  = $avg;
                        if (in_array($slot, $siangSlots)) $siangVals[] = $avg;
                        if (in_array($slot, $malamSlots)) $malamVals[] = $avg;
                    }
                }

                $allVals = array_merge($pagiVals, $siangVals, $malamVals);
                $isKuintal = $this->isKuintalParameter($param);
                $jmlRt2 = null;
                if (!empty($allVals)) {
                    $jmlRt2 = $isKuintal
                        ? round(array_sum($allVals), 2)
                        : round(array_sum($allVals) / count($allVals), 2);
                }

                $params[] = [
                    'id'               => $param->id,
                    'nama_parameter'   => $param->nama_parameter,
                    'satuan'           => $param->satuan,
                    'ssrn'             => $this->formatSsrn($param),
                    'operator_kondisi' => $param->operator_kondisi,
                    'batas_bawah'      => $param->batas_bawah,
                    'batas_atas'       => $param->batas_atas,
                    'data'             => $hourData,
                    'pagi_avg'         => !empty($pagiVals)  ? round(array_sum($pagiVals)  / count($pagiVals),  2) : null,
                    'siang_avg'        => !empty($siangVals) ? round(array_sum($siangVals) / count($siangVals), 2) : null,
                    'malam_avg'        => !empty($malamVals) ? round(array_sum($malamVals) / count($malamVals), 2) : null,
                    'jml_rt2'          => $jmlRt2,
                ];
            }

            $result[] = [
                'id'           => $stasiun->id,
                'nama_stasiun' => $stasiun->nama_stasiun,
                'parameters'   => $params,
            ];
        }

        return response()->json([
            'tanggal'  => $tanggal,
            'stasiuns' => $result,
        ]);
    }

    /**
     * Format label SSRN dari operator + batas parameter.
     */
    private function formatSsrn(Parameter $param): string
    {
        $op  = $param->operator_kondisi;
        $low = $param->batas_bawah;
        $hi  = $param->batas_atas;

        $fmt = function ($value): string {
            if($value < 20){
                return number_format((float) $value, 1, '.', '');
            }else{
                return number_format((float) $value, 0, '', '');
            }
        };

        return match ($op) {
            'BETWEEN' => ($low !== null && $hi !== null) ? ($fmt($low) . ' – ' . $fmt($hi)) : '-',
            '>'       => $low !== null ? ('>' . $fmt($low)) : '-',
            '>='      => $low !== null ? ('≥' . $fmt($low)) : '-',
            '<'       => $hi !== null ? ('<' . $fmt($hi)) : '-',
            '<='      => $hi !== null ? ('≤' . $fmt($hi)) : '-',
            default   => '-',
        };
    }

    /**
     * Parameter bersatuan kuintal (ku) ditotal pada kolom Jml/Rt2.
     */
    private function isKuintalParameter(Parameter $param): bool
    {
        $unit = strtolower(trim((string) ($param->satuan ?? '')));
        if (in_array($unit, ['ku', 'kuintal'], true)) {
            return true;
        }

        // Fallback untuk data lama yang satuannya belum terisi.
        $name = strtolower((string) $param->nama_parameter);
        return str_contains($name, 'kap. giling') || str_contains($name, 'kap. gilingan');
    }

    /**
     * Laporan QA: Summary alert per stasiun dalam rentang tanggal
     */
    public function reportAlert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tanggal_dari' => ['required', 'date'],
            'tanggal_sampai' => ['required', 'date', 'after_or_equal:tanggal_dari'],
        ]);

        $stasiuns = Stasiun::with(['parameters'])->get();

        $report = [];
        foreach ($stasiuns as $stasiun) {
            $alertCount = QaDetail::whereHas('parameter', fn($q) => $q->where('stasiun_id', $stasiun->id))
                                  ->whereHas('header', fn($q) => 
                                      $q->whereBetween('tanggal', [$validated['tanggal_dari'], $validated['tanggal_sampai']])
                                  )
                                  ->where('status_alert', true)
                                  ->count();

            $report[] = [
                'stasiun_id'      => $stasiun->id,
                'nama_stasiun'    => $stasiun->nama_stasiun,
                'total_alert'     => $alertCount,
            ];
        }

        return response()->json([
            'periode'        => "{$validated['tanggal_dari']} sampai {$validated['tanggal_sampai']}",
            'data'           => $report,
        ]);
    }
}
