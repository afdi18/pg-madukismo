<?php

namespace App\Models\Sqlsrv;

use Illuminate\Database\Eloquent\Model;

class RendemenHarian extends Model
{
    protected $connection = 'sqlsrv';
    protected $table      = 'rendemen_harian';

    protected $fillable = [
        'tanggal', 'musim_tanam', 'rendemen_kebun', 'rendemen_pabrik',
        'tonase_masuk', 'tonase_gula', 'kapasitas_giling',
        'efisiensi_pabrik', 'shift', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal'          => 'date',
            'rendemen_kebun'   => 'decimal:2',
            'rendemen_pabrik'  => 'decimal:2',
            'tonase_masuk'     => 'decimal:2',
            'tonase_gula'      => 'decimal:2',
            'kapasitas_giling' => 'decimal:2',
            'efisiensi_pabrik' => 'decimal:2',
        ];
    }

    public function scopeMusim($query, int $musim)
    {
        return $query->where('musim_tanam', $musim);
    }
}
