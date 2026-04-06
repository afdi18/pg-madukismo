<?php

namespace App\Models\Pgsql;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserAttribute — Tabel atribut ABAC untuk setiap user.
 *
 * Contoh atribut:
 * - area_kebun: "A1", "B2", "C3" (akses dibatasi per area kebun)
 * - shift: "pagi", "siang", "malam"
 * - level_akses_data: "publik", "internal", "rahasia"
 */
class UserAttribute extends Model
{
    protected $connection = 'pgsql';
    protected $table      = 'user_attributes';

    protected $fillable = [
        'user_id',
        'attribute_key',
        'attribute_value',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ================================================================
    // COMMON ATTRIBUTE KEYS
    // ================================================================
    const KEY_AREA_KEBUN       = 'area_kebun';
    const KEY_DIVISI           = 'divisi';
    const KEY_SHIFT            = 'shift';
    const KEY_LEVEL_AKSES      = 'level_akses';
    const KEY_TANGGUNG_JAWAB   = 'tanggung_jawab';
}
