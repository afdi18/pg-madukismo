<?php

namespace App\Models\Pgsql;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KebunPeta extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';
    protected $table      = 'kebun_peta';

    protected $fillable = [
        'kode_kebun', 'nama_kebun', 'wilayah', 'kabupaten',
        'luas_ha', 'latitude', 'longitude', 'geojson',
        'jenis_lahan', 'status', 'pengelola', 'varietas',
        'tanggal_tanam', 'perkiraan_panen', 'keterangan',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'luas_ha'         => 'decimal:2',
            'latitude'        => 'decimal:7',
            'longitude'       => 'decimal:7',
            'tanggal_tanam'   => 'date',
            'perkiraan_panen' => 'date',
        ];
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeByWilayah($query, string $wilayah)
    {
        return $query->where('wilayah', $wilayah);
    }
}
