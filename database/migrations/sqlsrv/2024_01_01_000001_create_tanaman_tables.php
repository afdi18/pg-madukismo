<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'sqlsrv';

    /**
     * Deteksi versi SQL Server secara otomatis.
     * Digunakan untuk menentukan cara DROP TABLE yang tepat.
     *
     * SQL Server 2014 = versi 12.x
     * SQL Server 2017 = versi 14.x
     */
    // protected function getSqlServerMajorVersion(): int
    // {
    //     $version = DB::connection('sqlsrv')
    //         ->selectOne('SELECT SERVERPROPERTY(\'ProductMajorVersion\') AS ver');
    //     return (int) ($version->ver ?? 0);
    // }

    // /**
    //  * DROP TABLE yang kompatibel dengan SQL Server 2014 (tidak ada DROP IF EXISTS).
    //  * SQL Server 2016+ (versi 13+) sudah mendukung DROP TABLE IF EXISTS.
    //  */
    // protected function dropTableSafe(string $table): void
    // {
    //     $version = $this->getSqlServerMajorVersion();

    //     if ($version >= 13) {
    //         // SQL Server 2016, 2017, 2019, 2022 → pakai syntax modern
    //         DB::connection('sqlsrv')
    //           ->statement("DROP TABLE IF EXISTS [{$table}]");
    //     } else {
    //         // SQL Server 2014 dan lebih lama → pakai OBJECT_ID check
    //         DB::connection('sqlsrv')
    //           ->statement("IF OBJECT_ID(N'[dbo].[{$table}]', N'U') IS NOT NULL DROP TABLE [dbo].[{$table}]");
    //     }
    // }

    // public function up(): void
    // {
    //     // ============================================================
    //     // DATA RENDEMEN HARIAN
    //     // ============================================================
    //     Schema::connection('sqlsrv')->create('rendemen_harian', function (Blueprint $table) {
    //         $table->id();
    //         $table->date('tanggal');
    //         $table->integer('musim_tanam');
    //         $table->decimal('rendemen_kebun', 5, 2)->default(0)->comment('Rendemen kebun rata2 (%)');
    //         $table->decimal('rendemen_pabrik', 5, 2)->default(0)->comment('Rendemen pabrik (%)');
    //         $table->decimal('tonase_masuk', 12, 2)->default(0)->comment('Total tonase masuk hari ini (ton)');
    //         $table->decimal('tonase_gula', 10, 2)->default(0)->comment('Gula diproduksi (ton)');
    //         $table->decimal('kapasitas_giling', 10, 2)->default(0)->comment('Kapasitas giling aktual (TCD)');
    //         $table->decimal('efisiensi_pabrik', 5, 2)->default(0)->comment('Efisiensi pabrik (%)');
    //         $table->string('shift', 10)->nullable()->comment('Pagi, Siang, Malam');
    //         $table->text('keterangan')->nullable();
    //         $table->timestamps();

    //         $table->unique(['tanggal', 'shift']);
    //         $table->index(['tanggal', 'musim_tanam']);
    //     });

    //     // ============================================================
    //     // KPI OPERASIONAL PABRIK
    //     // ============================================================
    //     Schema::connection('sqlsrv')->create('kpi_operasional', function (Blueprint $table) {
    //         $table->id();
    //         $table->integer('musim_tanam');
    //         $table->string('periode', 20)->comment('Bulanan: 2024-01, 2024-02, dst');
    //         $table->string('kode_kpi', 50)->comment('kode unik KPI');
    //         $table->string('nama_kpi', 150);
    //         $table->string('satuan', 30)->comment('%, ton, TCD, dll');
    //         $table->decimal('target', 12, 2)->default(0);
    //         $table->decimal('realisasi', 12, 2)->default(0);
    //         // PERSISTED computed column — kompatibel SQL Server 2014, 2017, 2019, 2022
    //         // CAST ke FLOAT dulu agar integer division tidak terjadi di SQL Server 2014
    //         $table->decimal('persentase', 6, 2)->storedAs(
    //             'CASE WHEN [target] = 0 THEN CONVERT(DECIMAL(6,2), 0) ELSE CONVERT(DECIMAL(6,2), ROUND(CAST([realisasi] AS FLOAT) / CAST([target] AS FLOAT) * 100, 2)) END'
    //         );
    //         $table->string('kategori', 50)->nullable()->comment('Produksi, Kualitas, Efisiensi, SDM');
    //         $table->string('status_kpi', 20)->default('on_track')->comment('on_track, at_risk, off_track');
    //         $table->text('analisis')->nullable();
    //         $table->timestamps();

    //         $table->index(['musim_tanam', 'periode']);
    //         $table->index('kode_kpi');
    //         $table->index('kategori');
    //     });

    //     // ============================================================
    //     // RINGKASAN MUSIM TANAM (Akumulasi per musim)
    //     // ============================================================
    //     Schema::connection('sqlsrv')->create('ringkasan_musim', function (Blueprint $table) {
    //         $table->id();
    //         $table->integer('musim_tanam')->unique();
    //         $table->decimal('total_luas_ha', 10, 2)->default(0);
    //         $table->decimal('total_tonase_tebang', 14, 2)->default(0);
    //         $table->decimal('total_tonase_giling', 14, 2)->default(0);
    //         $table->decimal('total_hasil_gula', 14, 2)->default(0);
    //         $table->decimal('rendemen_rata', 5, 2)->default(0);
    //         $table->decimal('hablur_rata', 5, 2)->default(0);
    //         $table->decimal('efisiensi_rata', 5, 2)->default(0);
    //         $table->integer('jumlah_hari_giling')->default(0);
    //         $table->date('mulai_giling')->nullable();
    //         $table->date('selesai_giling')->nullable();
    //         $table->string('status_musim', 20)->default('berjalan')->comment('berjalan, selesai, evaluasi');
    //         $table->timestamps();
    //     });
    // }

    // public function down(): void
    // {
    //     // Kompatibel SQL Server 2014+ (auto-detect versi)
    //     $this->dropTableSafe('ringkasan_musim');
    //     $this->dropTableSafe('kpi_operasional');
    //     $this->dropTableSafe('rendemen_harian');
    // }
};
