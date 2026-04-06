<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pgsql\KebunPeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PetaKebunController extends Controller
{
    /**
     * Semua data kebun untuk Leaflet GeoJSON.
     */
    public function index(Request $request): JsonResponse
    {
        $query = KebunPeta::query()
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->wilayah, fn($q, $w) => $q->where('wilayah', 'ilike', "%{$w}%"))
            ->when($request->search, fn($q, $s) =>
                $q->where('nama_kebun', 'ilike', "%{$s}%")
                  ->orWhere('kode_kebun', 'ilike', "%{$s}%")
            );

        // Jika user bukan admin/manajer, filter berdasarkan area_kebun user
        $user = $request->user();
        if (!$user->isAdministrator() && !$user->hasRole('Manajer')) {
            if ($user->area_kebun) {
                $query->where('kode_kebun', 'like', $user->area_kebun . '%');
            }
        }

        $kebuns = $query->orderBy('kode_kebun')->get();

        return response()->json([
            'data'       => $kebuns,
            'geojson'    => $this->toGeoJson($kebuns),
            'statistik'  => [
                'total_kebun'  => $kebuns->count(),
                'total_luas'   => $kebuns->sum('luas_ha'),
                'aktif'        => $kebuns->where('status', 'aktif')->count(),
                'fallow'       => $kebuns->where('status', 'fallow')->count(),
            ],
        ]);
    }

    /**
     * Detail kebun tertentu.
     */
    public function show(string $kode): JsonResponse
    {
        $kebun = KebunPeta::where('kode_kebun', $kode)->firstOrFail();

        return response()->json(['data' => $kebun]);
    }

    /**
     * Tambah / Update data kebun.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_kebun'        => ['required', 'string', 'max:20', 'unique:pgsql.kebun_peta,kode_kebun'],
            'nama_kebun'        => ['required', 'string', 'max:100'],
            'wilayah'           => ['required', 'string', 'max:100'],
            'kabupaten'         => ['nullable', 'string', 'max:100'],
            'luas_ha'           => ['required', 'numeric', 'min:0'],
            'latitude'          => ['nullable', 'numeric'],
            'longitude'         => ['nullable', 'numeric'],
            'geojson'           => ['nullable', 'json'],
            'jenis_lahan'       => ['nullable', 'string', 'max:50'],
            'status'            => ['required', 'in:aktif,fallow,konversi'],
            'pengelola'         => ['nullable', 'string', 'max:100'],
            'varietas'          => ['nullable', 'string', 'max:50'],
            'tanggal_tanam'     => ['nullable', 'date'],
            'perkiraan_panen'   => ['nullable', 'date'],
            'keterangan'        => ['nullable', 'string'],
        ]);

        $validated['created_by'] = $request->user()->id;
        $kebun = KebunPeta::create($validated);

        return response()->json([
            'message' => 'Data kebun berhasil disimpan.',
            'data'    => $kebun,
        ], 201);
    }

    public function update(Request $request, KebunPeta $kebunPeta): JsonResponse
    {
        $validated = $request->validate([
            'nama_kebun'      => ['required', 'string', 'max:100'],
            'wilayah'         => ['required', 'string', 'max:100'],
            'kabupaten'       => ['nullable', 'string', 'max:100'],
            'luas_ha'         => ['required', 'numeric', 'min:0'],
            'latitude'        => ['nullable', 'numeric'],
            'longitude'       => ['nullable', 'numeric'],
            'geojson'         => ['nullable', 'json'],
            'jenis_lahan'     => ['nullable', 'string', 'max:50'],
            'status'          => ['required', 'in:aktif,fallow,konversi'],
            'pengelola'       => ['nullable', 'string', 'max:100'],
            'varietas'        => ['nullable', 'string', 'max:50'],
            'tanggal_tanam'   => ['nullable', 'date'],
            'perkiraan_panen' => ['nullable', 'date'],
            'keterangan'      => ['nullable', 'string'],
        ]);

        $validated['updated_by'] = $request->user()->id;
        $kebunPeta->update($validated);

        return response()->json([
            'message' => 'Data kebun berhasil diperbarui.',
            'data'    => $kebunPeta->fresh(),
        ]);
    }

    public function destroy(Request $request, KebunPeta $kebunPeta): JsonResponse
    {
        $kebunPeta->delete();
        return response()->json(['message' => 'Data kebun berhasil dihapus.']);
    }

    /**
     * Konversi collection kebun ke format GeoJSON untuk Leaflet.
     */
    private function toGeoJson($kebuns): array
    {
        $features = $kebuns->map(function ($kebun) {
            // Jika kebun punya polygon GeoJSON, gunakan itu
            if ($kebun->geojson) {
                $geo = json_decode($kebun->geojson, true);
                return [
                    'type'       => 'Feature',
                    'geometry'   => $geo,
                    'properties' => $this->kebunProperties($kebun),
                ];
            }

            // Fallback ke Point jika hanya ada lat/lng
            if ($kebun->latitude && $kebun->longitude) {
                return [
                    'type'     => 'Feature',
                    'geometry' => [
                        'type'        => 'Point',
                        'coordinates' => [(float)$kebun->longitude, (float)$kebun->latitude],
                    ],
                    'properties' => $this->kebunProperties($kebun),
                ];
            }

            return null;
        })->filter()->values();

        return [
            'type'     => 'FeatureCollection',
            'features' => $features,
        ];
    }

    private function kebunProperties($kebun): array
    {
        return [
            'kode_kebun'      => $kebun->kode_kebun,
            'nama_kebun'      => $kebun->nama_kebun,
            'wilayah'         => $kebun->wilayah,
            'luas_ha'         => $kebun->luas_ha,
            'status'          => $kebun->status,
            'pengelola'       => $kebun->pengelola,
            'varietas'        => $kebun->varietas,
            'tanggal_tanam'   => $kebun->tanggal_tanam?->format('d M Y'),
            'perkiraan_panen' => $kebun->perkiraan_panen?->format('d M Y'),
            'color'           => match($kebun->status) {
                'aktif'    => '#22c55e',
                'fallow'   => '#f59e0b',
                'konversi' => '#ef4444',
                default    => '#6b7280',
            },
        ];
    }
}
