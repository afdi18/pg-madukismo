<?php

namespace App\Models\Pgsql;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parameter extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table      = 'mst_parameter';

    protected $fillable = [
        'stasiun_id',
        'nama_parameter',
        'satuan',
        'operator_kondisi',
        'batas_bawah',
        'batas_atas',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'batas_bawah' => 'decimal:4',
            'batas_atas'  => 'decimal:4',
            'is_aktif'    => 'boolean',
        ];
    }

    // ================================================================
    // RELASI
    // ================================================================

    /**
     * Parameter dimiliki oleh satu Stasiun
     */
    public function stasiun(): BelongsTo
    {
        return $this->belongsTo(Stasiun::class, 'stasiun_id');
    }

    /**
     * Parameter memiliki banyak QA Detail
     */
    public function qaDetails(): HasMany
    {
        return $this->hasMany(QaDetail::class, 'parameter_id');
    }

    // ================================================================
    // SCOPES
    // ================================================================

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }

    public function scopeByStasiun($query, int $stasiun_id)
    {
        return $query->where('stasiun_id', $stasiun_id);
    }
}
