<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosNppController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hari_giling' => ['nullable', 'integer', 'min:1'],
            'jam'         => ['nullable', 'integer', 'between:0,23'],
            'menit'       => ['nullable', 'integer', 'in:0,30'],
        ]);

        $hariGiling  = str_pad((string) ($validated['hari_giling'] ?? 1), 3, '0', STR_PAD_LEFT);
        $jam         = isset($validated['jam']) ? (int) $validated['jam'] : null;
        $startMinute = isset($validated['menit']) ? (int) $validated['menit'] : null;
        $endMinute   = $startMinute === null ? null : ($startMinute === 0 ? 29 : 59);

        $sql = "SELECT CAST(NO_MEJA AS VARCHAR) AS NO_MEJA,  NO_URUT,
                       FORMAT(DATEPART(HOUR,TGL_GIL),'00') + ':' + FORMAT(DATEPART(MINUTE,TGL_GIL),'00') AS MENIT,
                       HR_GIL, BRIX, POL, REND
                FROM TBL_TEBUMSK
                WHERE TGL_GIL IS NOT NULL
                  AND HR_GIL = ?";
        $bindings = [$hariGiling];

        if ($jam !== null) {
            $sql .= ' AND DATEPART(HOUR, TGL_GIL) = ?';
            $bindings[] = $jam;
        }

        if ($startMinute !== null) {
            $sql .= ' AND DATEPART(MINUTE, TGL_GIL) BETWEEN ? AND ?';
            $bindings[] = $startMinute;
            $bindings[] = $endMinute;
        }

        $sql .= ' ORDER BY TGL_GIL';

        $rows = DB::connection('sqlsrv')->select($sql, $bindings);

        $mappedRows = array_map(static function ($row) {
            return [
                'no_meja' => (string) ($row->NO_MEJA ?? ''),
                'no_urut' => (string) ($row->NO_URUT ?? ''),
                'menit' => (string) ($row->MENIT ?? ''),
                'hari_giling' => (string) ($row->HR_GIL ?? ''),
                'brix' => isset($row->BRIX) ? round((float) $row->BRIX, 2) : null,
                'pol' => isset($row->POL) ? round((float) $row->POL, 2) : null,
                'rend' => isset($row->REND) ? round((float) $row->REND, 2) : null,
            ];
        }, $rows);

        return response()->json([
            'data' => $mappedRows,
            'meta' => [
                'hari_giling' => $hariGiling,
                'total' => count($mappedRows),
            ],
        ]);
    }

    public function config(): JsonResponse
    {
        $row = DB::connection('sqlsrv')->selectOne(
            'SELECT TOP 1 HRGIL, FKR FROM TBL_DEFAULT WHERE SETT = 1'
        );

        $hariGiling = isset($row->HRGIL) ? (int) $row->HRGIL : 1;
        $faktorRendemen = isset($row->FKR) ? (float) $row->FKR : 0.0;

        return response()->json([
            'data' => [
                'hari_giling' => $hariGiling,
                'fkr' => $faktorRendemen,
            ],
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hari_giling' => ['required', 'integer', 'min:1'],
            'jam' => ['required', 'integer', 'between:0,23'],
            'menit' => ['required', 'integer', 'in:0,30'],
            'brix' => ['required', 'numeric', 'gt:0'],
            'pol' => ['required', 'numeric', 'gte:0'],
        ], [
            'hari_giling.required' => 'Hari giling wajib diisi.',
            'jam.required' => 'Jam ke wajib dipilih.',
            'menit.required' => 'Menit wajib dipilih.',
            'menit.in' => 'Menit hanya boleh 0 atau 30.',
            'brix.required' => 'Nilai Brix wajib diisi.',
            'pol.required' => 'Nilai Pol wajib diisi.',
        ]);

        $defaultRow = DB::connection('sqlsrv')->selectOne(
            'SELECT TOP 1 FKR FROM TBL_DEFAULT WHERE SETT = 1'
        );
        $faktorRendemen = isset($defaultRow->FKR) ? (float) $defaultRow->FKR : 0.0;

        $brix = (float) $validated['brix'];
        $pol = (float) $validated['pol'];

        $nilaiNira = $pol - (0.4 * ($brix - $pol));
        $hk = $brix == 0.0 ? 0.0 : (($pol / $brix) * 100);
        $rend = round($nilaiNira * $faktorRendemen, 2);

        $startMinute = (int) $validated['menit'];
        $endMinute = $startMinute === 0 ? 29 : 59;
        $jam = (int) $validated['jam'];
        $hariGiling = str_pad((string) $validated['hari_giling'], 3, '0', STR_PAD_LEFT);

        $columns = $this->resolveTebuMskColumns();
        if (!$columns['brix'] || !$columns['pol'] || !$columns['rend']) {
            return response()->json([
                'message' => 'Kolom BRIX/POL/REND pada TBL_TEBUMSK tidak ditemukan.',
                'data' => [
                    'resolved_columns' => $columns,
                ],
            ], 422);
        }

        $sql = sprintf(
            'UPDATE TBL_TEBUMSK
             SET [%s] = ?, [%s] = ?, [%s] = ?
             WHERE HR_GIL = ?
               AND TGL_GIL IS NOT NULL
               AND DATEPART(HOUR, TGL_GIL) = ?
               AND DATEPART(MINUTE, TGL_GIL) BETWEEN ? AND ?',
            $columns['brix'],
            $columns['pol'],
            $columns['rend']
        );

        $affectedRows = DB::connection('sqlsrv')->update($sql, [
            $brix,
            $pol,
            $rend,
            $hariGiling,
            $jam,
            $startMinute,
            $endMinute,
        ]);

        return response()->json([
            'message' => 'Data Pos NPP berhasil diproses.',
            'data' => [
                'hari_giling' => $hariGiling,
                'jam' => $jam,
                'menit' => $startMinute,
                'range_menit' => [$startMinute, $endMinute],
                'brix' => round($brix, 4),
                'pol' => round($pol, 4),
                'nilai_nira' => round($nilaiNira, 4),
                'hk' => round($hk, 2),
                'fkr' => $faktorRendemen,
                'rend' => $rend,
                'updated_rows' => $affectedRows,
                'columns' => $columns,
            ],
        ]);
    }

    protected function resolveTebuMskColumns(): array
    {
        $rawColumns = DB::connection('sqlsrv')->select(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = 'TBL_TEBUMSK'"
        );

        $columnMap = [];
        foreach ($rawColumns as $column) {
            $name = (string) ($column->COLUMN_NAME ?? '');
            if ($name !== '') {
                $columnMap[strtoupper($name)] = $name;
            }
        }

        return [
            'brix' => $this->pickFirstAvailableColumn($columnMap, ['BRIX', 'ANALISA_BRIX_K']),
            'pol' => $this->pickFirstAvailableColumn($columnMap, ['POL', 'ANALISA_POL_K']),
            'rend' => $this->pickFirstAvailableColumn($columnMap, ['REND', 'RENDEMEN']),
        ];
    }

    protected function pickFirstAvailableColumn(array $columnMap, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (isset($columnMap[strtoupper($candidate)])) {
                return $columnMap[strtoupper($candidate)];
            }
        }

        return null;
    }
}