<?php

namespace App\Models\Sqlsrv;

use Illuminate\Database\Eloquent\Model;

class TanamanProduksi extends Model
{
    protected $connection = 'sqlsrv';
    protected $table      = 'tanaman_produksi';

    protected $fillable = [
        'kode_kebun', 'musim_tanam', 'tanggal_input',
        'tonase_tebang', 'tonase_giling', 'rendemen_kebun',
        'hasil_gula', 'hablur', 'varietas', 'jenis_tebangan',
        'status_input', 'input_by', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_input'  => 'date',
            'tonase_tebang'  => 'decimal:2',
            'tonase_giling'  => 'decimal:2',
            'rendemen_kebun' => 'decimal:2',
            'hasil_gula'     => 'decimal:2',
            'hablur'         => 'decimal:2',
        ];
    }

    public function scopeMusim($query, int $musim)
    {
        return $query->where('musim_tanam', $musim);
    }

    public function scopeKebun($query, string $kode)
    {
        return $query->where('kode_kebun', $kode);
    }

    public function scopeFinal($query)
    {
        return $query->where('status_input', 'final');
    }
}
