<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        // ============================================================
        // PETA KEBUN
        // ============================================================
        Schema::connection('pgsql')->create('kebun_peta', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kebun', 20)->unique()->comment('Kode unik kebun: A1, B2, dst');
            $table->string('nama_kebun', 100);
            $table->string('wilayah', 100)->comment('Kecamatan/Desa');
            $table->string('kabupaten', 100)->nullable();
            $table->decimal('luas_ha', 10, 2)->comment('Luas dalam hektar');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('geojson')->nullable()->comment('Data GeoJSON untuk Leaflet');
            $table->string('jenis_lahan', 50)->nullable()->comment('Sawah, Tegalan, dll');
            $table->string('status', 20)->default('aktif')->comment('aktif, fallow, konversi');
            $table->string('pengelola', 100)->nullable()->comment('Nama pengelola/petani');
            $table->string('varietas', 50)->nullable()->comment('Varietas tebu yang ditanam');
            $table->date('tanggal_tanam')->nullable();
            $table->date('perkiraan_panen')->nullable();
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index('kode_kebun');
            $table->index('status');
            $table->index('wilayah');
        });

        // ============================================================
        // LAB QA — Data Kualitas Tebu
        // ============================================================
        Schema::connection('pgsql')->create('lab_qa', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_sampel', 50)->unique()->comment('Nomor identifikasi sampel');
            $table->string('kode_kebun', 20)->comment('Referensi ke kebun');
            $table->date('tanggal_sampling');
            $table->date('tanggal_analisis');
            $table->decimal('rendemen', 5, 2)->nullable()->comment('Rendemen (%)');
            $table->decimal('brix', 5, 2)->nullable()->comment('Brix (%)');
            $table->decimal('pol', 5, 2)->nullable()->comment('Pol (%)');
            $table->decimal('hk', 5, 2)->nullable()->comment('HK/Kemurnian (%)');
            $table->decimal('kadar_air', 5, 2)->nullable()->comment('Kadar air (%)');
            $table->decimal('kadar_kotoran', 5, 2)->nullable()->comment('Kadar kotoran (%)');
            $table->string('kategori_mutu', 20)->nullable()->comment('A, B, C, D');
            $table->string('status', 20)->default('pending')->comment('pending, dianalisis, disetujui, ditolak');
            $table->text('catatan_analis')->nullable();
            $table->text('catatan_approval')->nullable();
            $table->unsignedBigInteger('analis_id')->nullable()->comment('User yang menganalisis');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('User yang menyetujui');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('analis_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['kode_kebun', 'tanggal_sampling']);
            $table->index('status');
            $table->index('kategori_mutu');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('lab_qa');
        Schema::connection('pgsql')->dropIfExists('kebun_peta');
    }
};
