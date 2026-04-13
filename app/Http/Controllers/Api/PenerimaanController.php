<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenerimaanController extends Controller
{
    /**
     * Return paginated list of SPA from SQL Server (TIMB_MK).
     * Query uses raw SQL on the `sqlsrv` connection.
     */
    public function index(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(200, max(1, (int) $request->query('per_page', 50)));
        $offset = ($page - 1) * $perPage;

        // Filters
        $induk = $request->query('induk');
        $nopol = $request->query('nopol');
        $tglmasuk = $request->query('tglmasuk'); // expecting YYYY-MM-DD or partial
        $q = $request->query('q'); // generic search across SPA, NOPOL, INDUK

        // Build where clauses and bindings
        $where = [];
        $bindings = [];

        if ($induk) {
            $where[] = "INDUK = ?";
            $bindings[] = $induk;
        }
        if ($nopol) {
            $where[] = "NOPOL LIKE ?";
            $bindings[] = "%{$nopol}%";
        }
        if ($tglmasuk) {
            // match date part of TGLMASUK (assuming datetime column)
            $where[] = "CONVERT(date, TGLMULAI) = ?";
            $bindings[] = $tglmasuk;
        }
        if ($q) {
            $where[] = "(SPA LIKE ? OR NOPOL LIKE ? OR INDUK LIKE ?)";
            $bindings[] = "%{$q}%";
            $bindings[] = "%{$q}%";
            $bindings[] = "%{$q}%";
        }

        $whereSql = '';
        if (count($where) > 0) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        // Determine available columns on the target table to avoid invalid column errors
        $cols = DB::connection('sqlsrv')->select(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = 'TBL_MASTSPA'"
        );
        $available = array_map(fn($c) => strtoupper($c->COLUMN_NAME), $cols);

        // Desired columns to expose
        $desired = ['SPA', 'INDUK', 'NOPOL', 'TGLMULAI', 'PREMI', 'TOKEN'];
        $selectCols = array_values(array_filter($desired, fn($c) => in_array($c, $available)));

        // Fallback: if none of desired columns exist, select all (careful)
        if (count($selectCols) === 0) {
            $selectSql = '*';
        } else {
            $selectSql = implode(', ', $selectCols);
        }

        // Count total with same filters
        $countSql = "SELECT COUNT(1) AS total FROM dbo.TBL_MASTSPA {$whereSql}";
        $totalObj = DB::connection('sqlsrv')->selectOne($countSql, $bindings);
        $total = $totalObj->total ?? 0;

        // Pagination using OFFSET/FETCH
        $dataSql = "SELECT {$selectSql} FROM dbo.TBL_MASTSPA {$whereSql} ORDER BY SPA OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
        // add offset/perPage to bindings for data query
        $dataBindings = array_merge($bindings, [$offset, $perPage]);
        $rows = DB::connection('sqlsrv')->select($dataSql, $dataBindings);

        // Return JSON while ignoring invalid UTF-8 sequences to prevent JsonResponse errors
        $payload = [
            'data' => $rows,
            'meta' => [
                'total' => (int) $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $total ? (int) ceil($total / $perPage) : 1,
            ],
        ];

        // Use JSON options to avoid failures on malformed UTF-8 from SQL Server blobs
        $jsonOptions = defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0;
        if (defined('JSON_INVALID_UTF8_IGNORE')) {
            $jsonOptions |= JSON_INVALID_UTF8_IGNORE;
        } elseif (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonOptions |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        return response()->json($payload, 200, [], $jsonOptions);
    }
}
