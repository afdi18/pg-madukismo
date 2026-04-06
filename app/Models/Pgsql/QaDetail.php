<?php

namespace App\Models\Pgsql;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaDetail extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table      = 'trx_qa_detail';

    protected $fillable = [
        'header_id',
        'parameter_id',
        'nilai_aktual',
        'status_alert',
    ];

    protected function casts(): array
    {
        return [
            'nilai_aktual' => 'decimal:4',
            'status_alert' => 'boolean',
        ];
    }

    // ================================================================
    // RELASI
    // ================================================================

    /**
     * Detail QA dimiliki oleh satu Header QA
     */
    public function header(): BelongsTo
    {
        return $this->belongsTo(QaHeader::class, 'header_id');
    }

    /**
     * Detail QA dimiliki oleh satu Parameter
     */
    public function parameter(): BelongsTo
    {
        return $this->belongsTo(Parameter::class, 'parameter_id');
    }

    /**
     * Akses Stasiun melalui Parameter
     */
    public function stasiun()
    {
        return $this->parameter?->stasiun;
    }

    // ================================================================
    // SCOPES
    // ================================================================

    public function scopeByHeader($query, int $header_id)
    {
        return $query->where('header_id', $header_id);
    }

    public function scopeWithAlert($query)
    {
        return $query->where('status_alert', true);
    }

    public function scopeWithoutAlert($query)
    {
        return $query->where('status_alert', false);
    }

    // ================================================================
    // LOGIKA PENGECEKAN ALERT
    // ================================================================

    /**
     * Cek apakah nilai_aktual melanggar batas dari parameter
     * Return: true jika ALERT (melanggar batas), false jika AMAN
     */
    public static function checkAlert(Parameter $parameter, ?float $nilaiAktual): bool
    {
        // Jika tidak ada nilai aktual atau operator = NONE, tidak ada alert
        if ($nilaiAktual === null || $parameter->operator_kondisi === 'NONE') {
            return false;
        }

        $operator = $parameter->operator_kondisi;

        return match ($operator) {
            '>' => $nilaiAktual <= $parameter->batas_bawah,
            '<' => $nilaiAktual >= $parameter->batas_atas,
            '>=' => $nilaiAktual < $parameter->batas_bawah,
            '<=' => $nilaiAktual > $parameter->batas_atas,
            'BETWEEN' => $nilaiAktual < $parameter->batas_bawah || $nilaiAktual > $parameter->batas_atas,
            default => false,
        };
    }
}
