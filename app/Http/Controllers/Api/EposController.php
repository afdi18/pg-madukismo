<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EposController extends Controller
{
    // List registered devices (joins pos name)
    public function devices(Request $request)
    {
        try {
            $rows = DB::connection('sqlsrv')->select(
                "SELECT a.KODE_DEVICE,a.GMAIL,a.ID_DEVICE,a.ID_POS,b.NMPOS,a.METER_JARAK_MAKS,a.AKTIF,a.HARI_EXPIRED_SPA FROM [dbo].[TBL_DEVICE_EPOS] AS a LEFT JOIN [dbo].[TBL_POS_PANTAU] as b ON a.ID_POS=b.IDPOS"
            );

            // Normalize types so frontend treats AKTIF correctly (SQL Server may return strings)
            foreach ($rows as $r) {
                if (isset($r->AKTIF)) {
                    $r->AKTIF = (int) $r->AKTIF;
                }
                if (isset($r->HARI_EXPIRED_SPA)) {
                    $r->HARI_EXPIRED_SPA = is_numeric($r->HARI_EXPIRED_SPA) ? (int) $r->HARI_EXPIRED_SPA : $r->HARI_EXPIRED_SPA;
                }
            }

            return response()->json(['data' => $rows], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // List POS pantau
    public function posList()
    {
        try {
            $rows = DB::connection('sqlsrv')->select("SELECT IDPOS, NMPOS, LAT, LONG FROM [dbo].[TBL_POS_PANTAU]");
            return response()->json(['data' => $rows], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Create POS pantau
    public function storePos(Request $request)
    {
        $nmpos = trim((string) $request->input('NMPOS', ''));
        $lat = $request->input('LAT');
        $long = $request->input('LONG');

        if ($nmpos === '') {
            return response()->json(['error' => 'NMPOS wajib diisi'], 422);
        }
        if (!is_numeric($lat) || !is_numeric($long)) {
            return response()->json(['error' => 'LAT dan LONG harus berupa angka'], 422);
        }

        try {
            $row = DB::connection('sqlsrv')->transaction(function () use ($nmpos, $lat, $long) {
                $next = DB::connection('sqlsrv')->selectOne(
                    "SELECT ISNULL(MAX([IDPOS]), 0) + 1 AS next_id
                     FROM [dbo].[TBL_POS_PANTAU] WITH (UPDLOCK, HOLDLOCK)"
                );

                $nextId = (int) ($next->next_id ?? 1);

                DB::connection('sqlsrv')->insert(
                    "INSERT INTO [dbo].[TBL_POS_PANTAU] ([IDPOS], [NMPOS], [LAT], [LONG], [TGL_DIBUAT], [TGL_DIUBAH])
                     VALUES (?, ?, ?, ?, GETDATE(), GETDATE())",
                    [$nextId, $nmpos, (string) $lat, (string) $long]
                );

                return DB::connection('sqlsrv')->selectOne(
                    "SELECT [IDPOS], [NMPOS], [LAT], [LONG] FROM [dbo].[TBL_POS_PANTAU] WHERE [IDPOS] = ?",
                    [$nextId]
                );
            });

            return response()->json(['ok' => true, 'data' => $row], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Update POS pantau
    public function updatePos(Request $request, $idPos)
    {
        $nmpos = trim((string) $request->input('NMPOS', ''));
        $lat = $request->input('LAT');
        $long = $request->input('LONG');

        if ($nmpos === '') {
            return response()->json(['error' => 'NMPOS wajib diisi'], 422);
        }
        if (!is_numeric($lat) || !is_numeric($long)) {
            return response()->json(['error' => 'LAT dan LONG harus berupa angka'], 422);
        }

        try {
            $updated = DB::connection('sqlsrv')->update(
                "UPDATE [dbo].[TBL_POS_PANTAU]
                 SET [NMPOS] = ?, [LAT] = ?, [LONG] = ?, [TGL_DIUBAH] = GETDATE()
                 WHERE [IDPOS] = ?",
                [$nmpos, (string) $lat, (string) $long, $idPos]
            );

            if (!$updated) {
                return response()->json(['ok' => false, 'error' => 'POS tidak ditemukan'], 404);
            }

            $row = DB::connection('sqlsrv')->selectOne(
                "SELECT [IDPOS], [NMPOS], [LAT], [LONG] FROM [dbo].[TBL_POS_PANTAU] WHERE [IDPOS] = ?",
                [$idPos]
            );

            return response()->json(['ok' => true, 'data' => $row], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Activate / deactivate device by ID_DEVICE
    public function setActive(Request $request, $idDevice)
    {
        $aktif = (int) $request->input('aktif', 0);
        try {
            $updated = DB::connection('sqlsrv')->update(
                "UPDATE [dbo].[TBL_DEVICE_EPOS] SET AKTIF = ? WHERE ID_DEVICE = ?",
                [$aktif, $idDevice]
            );
            if ($updated) {
                $row = DB::connection('sqlsrv')->selectOne(
                    "SELECT KODE_DEVICE,GMAIL,ID_DEVICE,ID_POS,METER_JARAK_MAKS,AKTIF,HARI_EXPIRED_SPA FROM [dbo].[TBL_DEVICE_EPOS] WHERE ID_DEVICE = ?",
                    [$idDevice]
                );
                if ($row) {
                    $row->AKTIF = isset($row->AKTIF) ? (int) $row->AKTIF : $row->AKTIF;
                    $row->HARI_EXPIRED_SPA = isset($row->HARI_EXPIRED_SPA) && is_numeric($row->HARI_EXPIRED_SPA) ? (int) $row->HARI_EXPIRED_SPA : $row->HARI_EXPIRED_SPA;
                }
                return response()->json(['ok' => true, 'updated' => (bool) $updated, 'device' => $row], 200);
            }
            return response()->json(['ok' => false, 'updated' => false], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Update device settings: ID_POS, METER_JARAK_MAKS, and HARI_EXPIRED_SPA
    public function updateDevice(Request $request, $idDevice)
    {
        $idPos = $request->input('id_pos', null);
        $meter = $request->input('meter_jarak_maks', null);
        $hari = $request->input('hari_expired_spa', null);

        // basic validation
        if ($idPos !== null && $idPos !== '' && !is_numeric($idPos)) {
            return response()->json(['error' => 'id_pos must be numeric'], 422);
        }
        if ($meter !== null && !is_numeric($meter)) {
            return response()->json(['error' => 'meter_jarak_maks must be numeric'], 422);
        }
        if ($hari !== null && !is_numeric($hari)) {
            return response()->json(['error' => 'hari_expired_spa must be numeric'], 422);
        }

        if ($idPos === '') {
            $idPos = null;
        }

        try {
            $updated = DB::connection('sqlsrv')->update(
                "UPDATE [dbo].[TBL_DEVICE_EPOS] SET ID_POS = ?, METER_JARAK_MAKS = ?, HARI_EXPIRED_SPA = ? WHERE ID_DEVICE = ?",
                [$idPos, $meter, $hari, $idDevice]
            );

            if ($updated) {
                $row = DB::connection('sqlsrv')->selectOne(
                    "SELECT a.KODE_DEVICE,a.GMAIL,a.ID_DEVICE,a.ID_POS,b.NMPOS,a.METER_JARAK_MAKS,a.AKTIF,a.HARI_EXPIRED_SPA
                     FROM [dbo].[TBL_DEVICE_EPOS] AS a
                     LEFT JOIN [dbo].[TBL_POS_PANTAU] AS b ON a.ID_POS = b.IDPOS
                     WHERE a.ID_DEVICE = ?",
                    [$idDevice]
                );
                if ($row) {
                    $row->AKTIF = isset($row->AKTIF) ? (int) $row->AKTIF : $row->AKTIF;
                    $row->HARI_EXPIRED_SPA = isset($row->HARI_EXPIRED_SPA) && is_numeric($row->HARI_EXPIRED_SPA) ? (int) $row->HARI_EXPIRED_SPA : $row->HARI_EXPIRED_SPA;
                }
                return response()->json(['ok' => true, 'updated' => (bool) $updated, 'device' => $row], 200);
            }

            return response()->json(['ok' => false, 'updated' => false], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Save EPOS settings (stored to storage/app/epos_settings.json)
    public function saveSettings(Request $request)
    {
        $payload = $request->only(['area', 'hari_expired_spa']);

        // Basic validation
        if (isset($payload['hari_expired_spa']) && !is_numeric($payload['hari_expired_spa'])) {
            return response()->json(['error' => 'hari_expired_spa must be numeric'], 422);
        }

        // Ensure area is valid JSON or null
        if (isset($payload['area']) && $payload['area'] !== null) {
            if (is_string($payload['area'])) {
                $decoded = json_decode($payload['area'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json(['error' => 'area must be valid JSON'], 422);
                }
                $payload['area'] = $decoded;
            }
        }

        try {
            Storage::put('epos_settings.json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return response()->json(['ok' => true, 'data' => $payload], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Read settings
    public function settings()
    {
        if (!Storage::exists('epos_settings.json')) {
            return response()->json(['data' => null], 200);
        }
        $json = Storage::get('epos_settings.json');
        return response()->json(['data' => json_decode($json, true)], 200);
    }
}
