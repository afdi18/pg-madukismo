<?php

namespace App\Models\Pgsql;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stasiun extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table      = 'mst_stasiun';

    protected $fillable = [
        'nama_stasiun',
    ];

    // ================================================================
    // RELASI
    // ================================================================

    /**
     * Stasiun memiliki banyak Parameter
     */
    public function parameters(): HasMany
    {
        return $this->hasMany(Parameter::class, 'stasiun_id');
    }

    /**
     * Stasiun memiliki banyak Parameter yang aktif
     */
    public function parametersAktif(): HasMany
    {
        return $this->parameters()->where('is_aktif', true);
    }
}
