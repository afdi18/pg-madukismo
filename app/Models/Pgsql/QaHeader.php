<?php

namespace App\Models\Pgsql;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QaHeader extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table      = 'trx_qa_header';

    protected $fillable = [
        'tanggal',
        'jam',
        'shift',
        'petugas',
        'stasiun_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jam'     => 'string',
            'shift'   => 'integer',
        ];
    }

    // ================================================================
    // RELASI
    // ================================================================

    /**
     * Header QA memiliki banyak Detail QA
     */
    public function details(): HasMany
    {
        return $this->hasMany(QaDetail::class, 'header_id');
    }

    /**
     * Header QA memiliki banyak Detail QA dengan relation ke Parameter dan Stasiun
     */
    public function detailsWithRelation(): HasMany
    {
        return $this->details()->with(['parameter.stasiun']);
    }

    /**
     * Header QA dimiliki satu Stasiun.
     */
    public function stasiun(): BelongsTo
    {
        return $this->belongsTo(Stasiun::class, 'stasiun_id');
    }

    // ================================================================
    // SCOPES
    // ================================================================

    public function scopeByTanggal($query, $tanggal)
    {
        return $query->whereDate('tanggal', $tanggal);
    }

    public function scopeByShift($query, int $shift)
    {
        return $query->where('shift', $shift);
    }

    public function scopeByPetugas($query, string $petugas)
    {
        return $query->where('petugas', 'ilike', "%{$petugas}%");
    }

    public function scopeByStasiun($query, int $stasiunId)
    {
        return $query->where('stasiun_id', $stasiunId);
    }
}
