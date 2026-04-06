<?php

namespace App\Models\Sqlsrv;

use Illuminate\Database\Eloquent\Model;

class KpiOperasional extends Model
{
    protected $connection = 'sqlsrv';
    protected $table      = 'kpi_operasional';

    protected $fillable = [
        'musim_tanam', 'periode', 'kode_kpi', 'nama_kpi',
        'satuan', 'target', 'realisasi', 'kategori',
        'status_kpi', 'analisis',
    ];

    protected function casts(): array
    {
        return [
            'target'     => 'decimal:2',
            'realisasi'  => 'decimal:2',
            'persentase' => 'decimal:2',
        ];
    }
}
