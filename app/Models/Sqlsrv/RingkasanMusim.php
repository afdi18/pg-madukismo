<?php

namespace App\Models\Sqlsrv;

use Illuminate\Database\Eloquent\Model;

class RingkasanMusim extends Model
{
    protected $connection = 'sqlsrv';
    protected $table      = 'ringkasan_musim';

    protected $fillable = [
        'musim_tanam', 'total_luas_ha', 'total_tonase_tebang',
        'total_tonase_giling', 'total_hasil_gula', 'rendemen_rata',
        'hablur_rata', 'efisiensi_rata', 'jumlah_hari_giling',
        'mulai_giling', 'selesai_giling', 'status_musim',
    ];

    protected function casts(): array
    {
        return [
            'mulai_giling'   => 'date',
            'selesai_giling' => 'date',
            'rendemen_rata'  => 'decimal:2',
            'efisiensi_rata' => 'decimal:2',
        ];
    }

    public function getProgressHariAttribute(): float
    {
        if (!$this->mulai_giling || !$this->selesai_giling) return 0;
        $total = $this->mulai_giling->diffInDays($this->selesai_giling);
        $done  = $this->jumlah_hari_giling;
        return $total > 0 ? round($done / $total * 100, 1) : 0;
    }
}
