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

    /**
     * Return lori queue data from SQL Server using raw query.
     */
    public function antrianLori()
    {
        $sql = "
            SELECT
                spa,
                nopol,
                no_lori,
                kw_netto,
                a.induk,
                b.petani,
                b.kebun,
                CONVERT(varchar, tgl_msk, 103) as tgl_msk,
                CONVERT(varchar, CAST(tgl_msk as time), 108) as jam_msk,
                DATEDIFF(HOUR, tgl_msk, GETDATE()) as lama
            FROM TBL_TEBUMSK a
            INNER JOIN TBL_MSTKEBUN b ON a.induk = b.induk
            WHERE NOT NO_LORI IS NULL AND NOT NETTO IS NULL
            ORDER BY tgl_msk ASC
        ";

        $rows = DB::connection('sqlsrv')->select($sql);

        $payload = [
            'data' => $rows,
        ];

        $jsonOptions = defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0;
        if (defined('JSON_INVALID_UTF8_IGNORE')) {
            $jsonOptions |= JSON_INVALID_UTF8_IGNORE;
        } elseif (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonOptions |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        return response()->json($payload, 200, [], $jsonOptions);
    }

    /**
     * Return weighed truck queue data from SQL Server using raw query.
     */
    public function antrianTrukSudahTimbang()
    {
        $sql = "
            SELECT
                a.spa,
                a.nopol,
                a.induk,
                b.petani,
                b.kebun,
                CONVERT(varchar, tgl_msk, 103) as tgl_msk,
                CAST(tgl_msk as time) as jam_msk,
                DATEDIFF(HOUR, tgl_msk, GETDATE()) as lama
            FROM TBL_TEBUMSK a
            INNER JOIN TBL_MSTKEBUN b ON a.induk = b.induk
            WHERE NOT a.BRUTTO IS NULL AND TARA IS NULL AND NO_LORI IS NULL
            ORDER BY tgl_msk ASC
        ";

        $rows = DB::connection('sqlsrv')->select($sql);

        $payload = [
            'data' => $rows,
        ];

        $jsonOptions = defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0;
        if (defined('JSON_INVALID_UTF8_IGNORE')) {
            $jsonOptions |= JSON_INVALID_UTF8_IGNORE;
        } elseif (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonOptions |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        return response()->json($payload, 200, [], $jsonOptions);
    }

    /**
     * Return unweighed truck queue data from SQL Server using raw query.
     */
    public function antrianTrukBelumTimbang()
    {
        $sql = "
            SELECT
                a.spa,
                a.nopol,
                a.induk,
                b.petani,
                b.kebun,
                CONVERT(varchar, tgl_msk, 103) as tgl_msk,
                CAST(tgl_msk as time) as jam_msk,
                DATEDIFF(HOUR, tgl_msk, GETDATE()) as lama
            FROM TBL_TEBUMSK a
            INNER JOIN TBL_MSTKEBUN b ON a.induk = b.induk
            WHERE NOT a.TGL_MSK IS NULL AND a.BRUTTO IS NULL
            ORDER BY tgl_msk ASC
        ";

        $rows = DB::connection('sqlsrv')->select($sql);

        $payload = [
            'data' => $rows,
        ];

        $jsonOptions = defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0;
        if (defined('JSON_INVALID_UTF8_IGNORE')) {
            $jsonOptions |= JSON_INVALID_UTF8_IGNORE;
        } elseif (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonOptions |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        return response()->json($payload, 200, [], $jsonOptions);
    }

    /**
     * Return pemasukan per kebun (induk) from SQL Server SP Proc_Pmsk_Induk.
     * Parameter tanggal format: mm/dd/yyyy
     */
    public function pemasukanKebun(Request $request)
    {
        $tanggal = (string) $request->query('tanggal', '');
        if ($tanggal === '') {
            $tanggal = now()->format('m/d/Y');
        }

        if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $tanggal)) {
            return response()->json(['error' => 'Format tanggal harus mm/dd/yyyy'], 422);
        }

        $rows = DB::connection('sqlsrv')->select('EXEC Proc_Pmsk_Induk ?', [$tanggal]);

        $payload = [
            'data' => $rows,
            'meta' => ['tanggal' => $tanggal],
        ];

        $jsonOptions = defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0;
        if (defined('JSON_INVALID_UTF8_IGNORE')) {
            $jsonOptions |= JSON_INVALID_UTF8_IGNORE;
        } elseif (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonOptions |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        return response()->json($payload, 200, [], $jsonOptions);
    }

    /**
     * Return pemasukan per kategori from SQL Server SP Proc_Pem_Ktgr.
     * Parameter tanggal format: mm/dd/yyyy
     */
    public function pemasukanKategori(Request $request)
    {
        $tanggal = (string) $request->query('tanggal', '');
        if ($tanggal === '') {
            $tanggal = now()->format('m/d/Y');
        }

        if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $tanggal)) {
            return response()->json(['error' => 'Format tanggal harus mm/dd/yyyy'], 422);
        }

        $rows = DB::connection('sqlsrv')->select('EXEC Proc_Pem_Ktgr ?', [$tanggal]);

        $payload = [
            'data' => $rows,
            'meta' => ['tanggal' => $tanggal],
        ];

        $jsonOptions = defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0;
        if (defined('JSON_INVALID_UTF8_IGNORE')) {
            $jsonOptions |= JSON_INVALID_UTF8_IGNORE;
        } elseif (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonOptions |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        return response()->json($payload, 200, [], $jsonOptions);
    }
}
